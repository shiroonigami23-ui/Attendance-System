<?php
require_once '../includes/Config.php';
require_once '../includes/Auth.php';

checkAuth();
if ($_SESSION['role'] != 'SEMI_ADMIN') {
    header('Location: ../dashboard.php');
    exit();
}

$session_id = $_GET['session'] ?? 0;
$user_id = $_SESSION['user_id'];

if (!$session_id) {
    header('Location: live_session.php?error=Invalid+session');
    exit();
}

// Verify teacher owns this session
$stmt = $pdo->prepare("
    SELECT ls.*, ts.target_group, s.code, s.name, s.is_lab
    FROM live_sessions ls
    INNER JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
    INNER JOIN subjects s ON ts.subject_id = s.subject_id
    WHERE ls.session_id = ? AND ls.actual_teacher_id = ?
");
$stmt->execute([$session_id, $user_id]);
$session = $stmt->fetch();

if (!$session) {
    header('Location: live_session.php?error=Unauthorized');
    exit();
}

if ($session['status'] == 'COMPLETED') {
    header('Location: live_session.php?msg=Session+already+ended');
    exit();
}

// Start transaction
$pdo->beginTransaction();

try {
    // 1. Mark session as completed
    $stmt = $pdo->prepare("
        UPDATE live_sessions 
        SET status = 'COMPLETED', ended_at = NOW() 
        WHERE session_id = ?
    ");
    $stmt->execute([$session_id]);
    
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
            
            // Update summary table
            $pdo->prepare("
                INSERT INTO student_attendance_summary (student_id, total_sessions, absent_count)
                VALUES (?, 1, 1)
                ON DUPLICATE KEY UPDATE
                    total_sessions = total_sessions + 1,
                    absent_count = absent_count + 1,
                    last_updated = NOW()
            ")->execute([$student_id]);
        }
    }
    
    // 6. Log the session closure
    $pdo->prepare("
        INSERT INTO system_logs (user_id, action, details, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([
        $user_id,
        'SESSION_CLOSED',
        json_encode([
            'session_id' => $session_id,
            'subject' => $session['code'],
            'total_students' => count($all_students),
            'scanned' => count($scanned_students),
            'absent' => $absent_count
        ]),
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    // Clear session variables
    unset($_SESSION['current_session'], $_SESSION['session_token'], $_SESSION['session_start']);
    
    // Redirect with success message
    $msg = "Session ended. " . count($scanned_students) . " students marked present, " . $absent_count . " marked absent.";
    header('Location: live_session.php?msg=' . urlencode($msg));
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    header('Location: live_session.php?error=' . urlencode('Failed to end session: ' . $e->getMessage()));
    exit();
}
?>