<?php
// tests/create_live_session.php
require_once __DIR__ . '/../includes/Config.php';

// 1. Get a Slot
$stmt = $pdo->query("SELECT slot_id, subject_id FROM timetable_slots LIMIT 1");
$slot = $stmt->fetch();
if (!$slot) {
    die("No slots found.\n");
}
$slot_id = $slot['slot_id'];

// 2. Get a Teacher
$stmt = $pdo->query("SELECT user_id FROM users WHERE role = 'SEMI_ADMIN' LIMIT 1"); // Teacher role is SEMI_ADMIN based on readme
$teacher = $stmt->fetch();

if (!$teacher) {
    // Try MASTER admin if no teacher
    $stmt = $pdo->query("SELECT user_id FROM users WHERE role = 'MASTER' LIMIT 1");
    $teacher = $stmt->fetch();
}

if (!$teacher) {
    die("No teacher/admin found to host session.\n");
}
$teacher_id = $teacher['user_id'];

echo "Found Teacher ID: $teacher_id for Slot: $slot_id\n";

// 3. Create Session
try {
    $token = '123456'; // Fixed token for testing
    // Check if session already exists for this slot and is LIVE
    $stmt = $pdo->prepare("SELECT session_id FROM live_sessions WHERE slot_id = ? AND status = 'LIVE'");
    $stmt->execute([$slot_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        echo "Live session already exists: " . $existing['session_id'] . "\n";
    } else {
        // Prepare insert carefully
        // Check column names of live_sessions
        // Based on error: `actual_teacher_id` exists.
        
        $sql = "INSERT INTO live_sessions (slot_id, actual_teacher_id, active_totp_token, status, started_at) 
                VALUES (?, ?, ?, 'LIVE', NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$slot_id, $teacher_id, $token]);
        echo "Created Session ID: " . $pdo->lastInsertId() . " with Token: $token\n";
    }

} catch (PDOException $e) {
    die("Error creating session: " . $e->getMessage() . "\n");
}
?>
