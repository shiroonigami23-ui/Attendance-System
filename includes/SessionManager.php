<?php
require_once 'Config.php';

class SessionManager {
    public static function checkAndEndExpiredSessions() {
        global $pdo;
        
        // Find sessions that are LIVE and started more than 7 minutes ago
        $stmt = $pdo->prepare("
            SELECT ls.session_id, ls.slot_id, ls.actual_teacher_id, ls.session_date,
                   ts.start_time, ts.end_time
            FROM live_sessions ls
            JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
            WHERE ls.status = 'LIVE'
            AND TIMESTAMPDIFF(MINUTE, CONCAT(ls.session_date, ' ', ts.start_time), NOW()) > 7
        ");
        $stmt->execute();
        $expired_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($expired_sessions as $session) {
            self::endSession($session['session_id'], $session['actual_teacher_id'], true);
        }
        
        return count($expired_sessions);
    }
    
    public static function endSession($session_id, $teacher_id, $auto_end = false) {
        global $pdo;
        
        try {
            $pdo->beginTransaction();
            
            // Get session details (same as your close_session.php)
            $stmt = $pdo->prepare("
                SELECT ls.*, ts.target_group, s.code, s.name, s.is_lab
                FROM live_sessions ls
                INNER JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
                INNER JOIN subjects s ON ts.subject_id = s.subject_id
                WHERE ls.session_id = ? AND ls.actual_teacher_id = ?
            ");
            $stmt->execute([$session_id, $teacher_id]);
            $session = $stmt->fetch();
            
            if (!$session) {
                throw new Exception("Session not found or unauthorized");
            }
            
            if ($session['status'] == 'COMPLETED') {
                throw new Exception("Session already ended");
            }
            
            // 1. Mark session as completed
            $stmt = $pdo->prepare("
                UPDATE live_sessions 
                SET status = 'COMPLETED', 
                    ended_at = NOW(),
                    auto_ended = ?
                WHERE session_id = ?
            ");
            $stmt->execute([$auto_end ? 1 : 0, $session_id]);
            
            // 2. Parse target group to get section and batch
            $target = json_decode($session['target_group'], true);
            $section = $target['section'] ?? 'A';
            $batch = $target['batch'] ?? 'all';
            
            // 3. Get all students in this section/batch
            $student_query = "
                SELECT u.user_id 
                FROM users u
                INNER JOIN student_profiles sp ON u.user_id = sp.user_id
                WHERE u.role = 'STUDENT' 
                AND sp.section = ?
            ";
            
            $params = [$section];
            
            if ($batch != 'all') {
                $student_query .= " AND sp.lab_batch = ?";
                $params[] = $batch;
            }
            
            $stmt = $pdo->prepare($student_query);
            $stmt->execute($params);
            $all_students = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // 4. Get students who already scanned
            $stmt = $pdo->prepare("
                SELECT student_id 
                FROM attendance_logs 
                WHERE session_id = ?
            ");
            $stmt->execute([$session_id]);
            $scanned_students = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // 5. Mark absent for students who didn't scan
            $absent_count = 0;
            foreach ($all_students as $student_id) {
                if (!in_array($student_id, $scanned_students)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO attendance_logs (session_id, student_id, status, verification_method, scanned_at)
                        VALUES (?, ?, 'ABSENT', 'AUTO_ABSENT', NOW())
                    ");
                    $stmt->execute([$session_id, $student_id]);
                    $absent_count++;
                }
            }
            
            // 6. Log the session closure
            $pdo->prepare("
                INSERT INTO system_logs (user_id, action, details, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([
                $teacher_id,
                $auto_end ? 'SESSION_AUTO_CLOSED' : 'SESSION_CLOSED',
                json_encode([
                    'session_id' => $session_id,
                    'subject' => $session['code'],
                    'total_students' => count($all_students),
                    'scanned' => count($scanned_students),
                    'absent' => $absent_count,
                    'auto_ended' => $auto_end
                ]),
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
            
            $pdo->commit();
            return [
                'success' => true,
                'scanned' => count($scanned_students),
                'absent' => $absent_count,
                'total' => count($all_students)
            ];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Failed to end session {$session_id}: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public static function isSessionActive($session_id) {
        global $pdo;
        
        $stmt = $pdo->prepare("
            SELECT ls.session_id, ls.status, ls.session_date,
                   ts.start_time, ts.end_time,
                   TIMESTAMPDIFF(MINUTE, CONCAT(ls.session_date, ' ', ts.start_time), NOW()) as minutes_passed
            FROM live_sessions ls
            JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
            WHERE ls.session_id = ?
        ");
        $stmt->execute([$session_id]);
        $session = $stmt->fetch();
        
        if (!$session || $session['status'] != 'LIVE') {
            return false;
        }
        
        // Session is only active for 7 minutes
        return $session['minutes_passed'] <= 7;
    }
}