<?php
require_once '../includes/Config.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    redirectBasedOnRole();
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$device_fp = $_POST['device_fingerprint'] ?? '';

// Validation
if (empty($email) || empty($password) || empty($device_fp)) {
    header('Location: login.php?error=Missing+credentials');
    exit();
}

// Fetch user with profile data if student
$stmt = $pdo->prepare("
    SELECT u.user_id, u.email, u.password_hash, u.role, u.is_active, u.approved,
           u.full_name,
           sp.roll_no, sp.current_semester, sp.section
    FROM users u
    LEFT JOIN student_profiles sp ON u.user_id = sp.user_id
    WHERE u.email = ? AND u.email_verified = 1
");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: login.php?error=Invalid+email+or+account+not+verified');
    exit();
}

if (!$user['is_active']) {
    header('Location: login.php?error=Account+deactivated');
    exit();
}

if ($user['role'] == 'SEMI_ADMIN' && !$user['approved']) {
    header('Location: login.php?error=Teacher+account+pending+admin+approval');
    exit();
}

// Verify password
if (!password_verify($password, $user['password_hash'])) {
    header('Location: login.php?error=Invalid+password');
    exit();
}

// DEVICE FINGERPRINT CHECK (1:1 Binding)
$stmt = $pdo->prepare("SELECT user_id, device_fingerprint FROM device_locks WHERE user_id = ?");
$stmt->execute([$user['user_id']]);
$device_lock = $stmt->fetch();

if ($device_lock) {
    // Existing device lock → must match
    if ($device_lock['device_fingerprint'] !== $device_fp) {
        // Special handling for teachers/admins (allow multi-device for now)
        if ($user['role'] == 'SEMI_ADMIN' || $user['role'] == 'MASTER') {
            // Update device fingerprint for teachers/admins (allow device change)
            $stmt = $pdo->prepare("
                UPDATE device_locks 
                SET device_fingerprint = ?, trust_score = trust_score - 20 
                WHERE user_id = ?
            ");
            $stmt->execute([$device_fp, $user['user_id']]);
            
            // Log device change
            $pdo->prepare("
                INSERT INTO system_logs (user_id, action, details, ip_address) 
                VALUES (?, ?, ?, ?)
            ")->execute([
                $user['user_id'],
                'DEVICE_CHANGED',
                'Device fingerprint updated for ' . $user['role'],
                $_SERVER['REMOTE_ADDR']
            ]);
        } else {
            // Students - strict 1:1 binding
            header('Location: login.php?error=Unauthorized+device.+Please+contact+admin.');
            exit();
        }
    } else {
        // Device matches → update trust score
        $pdo->prepare("UPDATE device_locks SET trust_score = trust_score + 10 WHERE user_id = ?")
            ->execute([$user['user_id']]);
    }
} else {
    // First login → create device lock
    // Check if this fingerprint is already used by another STUDENT
    if ($user['role'] == 'STUDENT') {
        $stmt = $pdo->prepare("
            SELECT u.user_id, u.role 
            FROM device_locks dl
            JOIN users u ON dl.user_id = u.user_id
            WHERE dl.device_fingerprint = ? AND u.role = 'STUDENT'
        ");
        $stmt->execute([$device_fp]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            header('Location: login.php?error=Device+already+bound+to+another+student');
            exit();
        }
    }
    
    // Create device lock
    $stmt = $pdo->prepare("
        INSERT INTO device_locks (user_id, device_fingerprint, trust_score) 
        VALUES (?, ?, 100)
    ");
    $stmt->execute([$user['user_id'], $device_fp]);
    
    // Log this first binding
    $pdo->prepare("
        INSERT INTO system_logs (user_id, action, details, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ")->execute([
        $user['user_id'],
        'DEVICE_LOCK_CREATED',
        json_encode([
            'fingerprint' => substr($device_fp, 0, 20) . '...',
            'role' => $user['role']
        ]),
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
}

// Set session with user data
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];
$_SESSION['full_name'] = $user['full_name'] ?? '';
$_SESSION['device_fp'] = $device_fp;

// Add student-specific data to session if student
if ($user['role'] == 'STUDENT') {
    $_SESSION['roll_no'] = $user['roll_no'] ?? '';
    $_SESSION['current_semester'] = $user['current_semester'] ?? '';
    $_SESSION['section'] = $user['section'] ?? '';
}

// Log login
$pdo->prepare("
    INSERT INTO system_logs (user_id, action, details, ip_address, user_agent) 
    VALUES (?, ?, ?, ?, ?)
")->execute([
    $user['user_id'],
    'LOGIN',
    json_encode(['role' => $user['role'], 'device' => substr($device_fp, 0, 12) . '...']),
    $_SERVER['REMOTE_ADDR'],
    $_SERVER['HTTP_USER_AGENT']
]);

// Redirect based on role
redirectBasedOnRole();
exit();

// Function to redirect based on user role
function redirectBasedOnRole() {
    if (!isset($_SESSION['role'])) {
        header('Location: login.php');
        exit();
    }
    
    switch ($_SESSION['role']) {
        case 'STUDENT':
            header('Location: student/dashboard.php');
            break;
        case 'SEMI_ADMIN':
            header('Location: teacher/dashboard.php');
            break;
        case 'MASTER':
            header('Location: admin/dashboard.php');
            break;
        default:
            header('Location: dashboard.php');
    }
    exit();
}
?>