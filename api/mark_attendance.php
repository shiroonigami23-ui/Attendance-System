<?php
require_once __DIR__ . '/../includes/Config.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

// Only students can mark attendance
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'STUDENT') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$session_id = isset($input['session_id']) ? (int) $input['session_id'] : 0;
$token = trim((string) ($input['token'] ?? ''));

// RATE LIMITING: Allow 1 request per 5 seconds per user
if (isset($_SESSION['last_api_request'])) {
    $time_since_last = time() - $_SESSION['last_api_request'];
    if ($time_since_last < 5) {
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Too many requests. Please wait.']);
        exit();
    }
}
$_SESSION['last_api_request'] = time();

if ($session_id <= 0 || $token === '') {
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

// 1. WiFi Subnet Fencing
$verification_method = 'QR_SCAN';
$lat = null;
$lng = null;
$distance = null;
if (defined('WIFI_FENCING_ENABLED') && WIFI_FENCING_ENABLED) {
    if (defined('ALLOWED_SUBNETS')) {
        $client_ip = $_SERVER['REMOTE_ADDR'];
        $allowed = false;
        foreach (ALLOWED_SUBNETS as $subnet) {
            if (strpos($client_ip, $subnet) === 0) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            echo json_encode(['success' => false, 'error' => 'Attendance only allowed from campus network']);
            exit();
        }
    }
}

// 2. GPS Geolocation Fencing
if (defined('GEO_FENCING_ENABLED') && GEO_FENCING_ENABLED) {
    if (defined('CAMPUS_LAT') && defined('CAMPUS_LNG')) {
        $lat = isset($input['lat']) ? (float) $input['lat'] : null;
        $lng = isset($input['lng']) ? (float) $input['lng'] : null;

        if ($lat === null || $lng === null) {
            echo json_encode(['success' => false, 'error' => 'Location data required. Please enable GPS.']);
            exit();
        }

        // Haversine Formula
        $earth_radius = 6371000; // Meters
        $dLat = deg2rad($lat - CAMPUS_LAT);
        $dLng = deg2rad($lng - CAMPUS_LNG);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad(CAMPUS_LAT)) * cos(deg2rad($lat)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earth_radius * $c;

        if ($distance > ALLOWED_RADIUS) {
            echo json_encode(['success' => false, 'error' => "You are " . round($distance) . "m away from class! Max " . ALLOWED_RADIUS . "m allowed."]);
            exit();
        }
    }
}

// Insert attendance log (unique key on session_id + student_id handles double-submit)
try {
    $stmt = $pdo->prepare("
        INSERT INTO attendance_logs (session_id, student_id, status, verification_method, geo_lat, geo_long, scanned_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $session_id,
        $_SESSION['user_id'],
        $status,
        $verification_method,
        $lat,
        $lng
    ]);
} catch (PDOException $e) {
    // 1062 = duplicate key: already marked for this session
    if ((int) $e->errorInfo[1] === 1062) {
        echo json_encode(['success' => false, 'error' => 'Attendance already marked']);
        exit();
    }
    error_log('mark_attendance insert failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error while marking attendance']);
    exit();
}

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
        'distance_m' => $distance !== null ? round($distance, 2) : null,
        'elapsed_seconds' => $elapsed
    ]),
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
]);

echo json_encode([
    'success' => true,
    'status' => $status,
    'elapsed' => gmdate("i:s", $elapsed),
    'message' => $status == 'PRESENT' ? 'Marked present (scanned within 3 minutes)' : 'Marked late (scanned within 7 minutes)'
]);
