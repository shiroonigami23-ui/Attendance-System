<?php
// tests/setup_test_session.php
require_once __DIR__ . '/../includes/Config.php';

// 1. Get Latest Slot
$stmt = $pdo->query("SELECT slot_id, subject_id, default_teacher_id FROM timetable_slots ORDER BY slot_id DESC LIMIT 1");
$slot = $stmt->fetch();
if (!$slot) die("No slots found.\n");

$slot_id = $slot['slot_id'];
$teacher_id = $slot['default_teacher_id'];

// 2. Create Live Session
$token = '987654';
try {
    // Check existing
    $stmt = $pdo->prepare("SELECT session_id FROM live_sessions WHERE slot_id = ? AND status = 'LIVE'");
    $stmt->execute([$slot_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        $session_id = $existing['session_id'];
        echo "Using existing session: $session_id\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO live_sessions (slot_id, actual_teacher_id, active_totp_token, status, started_at) VALUES (?, ?, ?, 'LIVE', NOW())");
        $stmt->execute([$slot_id, $teacher_id, $token]);
        $session_id = $pdo->lastInsertId();
        echo "Created new session: $session_id\n";
    }

    // 3. Write Input JSON
    $data = ['session_id' => $session_id, 'token' => $token];
    file_put_contents(__DIR__ . '/input.json', json_encode($data));
    echo "Written to input.json: " . json_encode($data) . "\n";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
