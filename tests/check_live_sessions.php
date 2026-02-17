<?php
// tests/check_live_sessions.php
require_once __DIR__ . '/../includes/Config.php';

$stmt = $pdo->query("SELECT * FROM live_sessions WHERE status = 'LIVE'");
$sessions = $stmt->fetchAll();

if (count($sessions) > 0) {
    echo "Found " . count($sessions) . " live sessions.\n";
    foreach ($sessions as $s) {
        echo "ID: " . $s['session_id'] . ", Token: " . $s['active_totp_token'] . ", Started: " . $s['started_at'] . "\n";
    }
} else {
    echo "No LIVE sessions found.\n";
    
    // Create a dummy live session for testing?
    // We need a valid slot_id.
    $stmt = $pdo->query("SELECT slot_id FROM timetable_slots LIMIT 1");
    $slot = $stmt->fetch();
    if ($slot) {
        $slot_id = $slot['slot_id'];
        echo "Creating dummy live session for slot $slot_id...\n";
        
        $token = '123456';
        $stmt = $pdo->prepare("INSERT INTO live_sessions (slot_id, active_totp_token, status, started_at) VALUES (?, ?, 'LIVE', NOW())");
        $stmt->execute([$slot_id, $token]);
        $id = $pdo->lastInsertId();
        echo "Created Session ID: $id with Token: $token\n";
    } else {
        echo "Cannot create session: No timetable slots found.\n";
    }
}
?>
