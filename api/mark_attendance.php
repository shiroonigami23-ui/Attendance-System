<?php
require_once '../includes/Config.php';
require_once '../includes/Auth.php';

header('Content-Type: application/json');

// Only students can mark attendance
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'STUDENT') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$session_id = $input['session_id'] ?? 0;
$token = $input['token'] ?? '';

if (!$session_id || !$token) {
    echo json_encode(['success' => false, 'error' => 'Invalid QR data']);
    exit();
}

// Fetch session with slot info
$stmt = $pdo->prepare("
    SELECT ls.*, ts.target_group, s.code as subject_code
    FROM live_sessions ls
    LEFT JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
    LEFT JOIN subjects s ON ts.subject_id = s.subject_id
    WHERE ls.session_id = ? AND ls.status = 'LIVE'
");
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if (!$session) {
    echo json_encode(['success' => false, 'error' => 'Session not found or expired']);
    exit();
}

// Validate TOTP token (10-second window)
if ($session['active_totp_token'] !== $token) {
    echo json_encode(['success' => false, 'error' => 'Invalid or expired token']);
    exit();
}

// Check time window
$start_time = strtotime($session['started_at']);
$elapsed = time() - $start_time;

if ($elapsed > 420) { // 7 minutes
    echo json_encode(['success' => false, 'error' => 'QR locked (7+ minutes elapsed)']);
    exit();
}

// Determine status
$status = $elapsed <= 180 ? 'PRESENT' : 'LATE';

// Check if already marked
$stmt = $pdo->prepare("SELECT log_id FROM attendance_logs WHERE session_id = ? AND student_id = ?");
$stmt->execute([$session_id, $_SESSION['user_id']]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Attendance already marked']);
    exit();
}

// WiFi Subnet Fencing (Simulated - in production, check IP against campus subnet)
// $allowed_ips = ['192.168.1.', '10.0.0.']; // Example campus subnets
// $client_ip = $_SERVER['REMOTE_ADDR'];
// $allowed = false;
// foreach ($allowed_ips as $subnet) {
//     if (strpos($client_ip, $subnet) === 0) {
//         $allowed = true;
//         break;
//     }
// }
// if (!$allowed) {
//     echo json_encode(['success' => false, 'error' => 'Attendance only allowed from campus network']);
//     exit();
// }

// Insert attendance log
$stmt = $pdo->prepare("
    INSERT INTO attendance_logs (session_id, student_id, status, verification_method, scanned_at)
    VALUES (?, ?, ?, 'QR_SCAN', NOW())
");
$stmt->execute([$session_id, $_SESSION['user_id'], $status]);

// Update summary table
$pdo->prepare("
    INSERT INTO student_attendance_summary (student_id, total_sessions, present_count, late_count, absent_count)
    VALUES (?, 1, ?, ?, 0)
    ON DUPLICATE KEY UPDATE
        total_sessions = total_sessions + 1,
        present_count = present_count + ?,
        late_count = late_count + ?,
        last_updated = NOW()
")->execute([
    $_SESSION['user_id'],
    $status == 'PRESENT' ? 1 : 0,
    $status == 'LATE' ? 1 : 0,
    $status == 'PRESENT' ? 1 : 0,
    $status == 'LATE' ? 1 : 0
]);

// Log the scan
$pdo->prepare("
    INSERT INTO system_logs (user_id, action, details, ip_address, user_agent)
    VALUES (?, ?, ?, ?, ?)
")->execute([
    $_SESSION['user_id'],
    'ATTENDANCE_SCAN',
    json_encode([
        'session_id' => $session_id,
        'subject' => $session['subject_code'] ?? 'Unknown',
        'status' => $status,
        'elapsed_seconds' => $elapsed
    ]),
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
]);

echo json_encode([
    'success' => true,
    'status' => $status,
    'elapsed' => gmdate("i:s", $elapsed),
    'message' => $status == 'PRESENT' ? 'Marked present (scanned within 3 minutes)' : 'Marked late (scanned within 7 minutes)'
]);
?>