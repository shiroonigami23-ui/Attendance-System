<?php
require_once __DIR__ . '/Config.php';

function checkAuth() {
    global $pdo;
    
    if (!isset($_SESSION['user_id'], $_SESSION['device_fp'])) {
        app_redirect('login.php');
    }
    
    // Verify device fingerprint matches
    $stmt = $pdo->prepare("
        SELECT device_fingerprint 
        FROM device_locks 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $lock = $stmt->fetch();
    
    if (!$lock || $lock['device_fingerprint'] !== $_SESSION['device_fp']) {
        session_destroy();
        header('Location: ' . app_url('login.php?error=Device+mismatch.+Please+re-login.'));
        exit();
    }
    
    return true;
}

// Optional: Get current user details
function getCurrentUser() {
    global $pdo;
    
    if (!isset($_SESSION['user_id'])) return null;
    
    $stmt = $pdo->prepare("
        SELECT u.*, sp.roll_no, sp.branch_code, sp.current_semester, sp.section, sp.lab_batch
        FROM users u 
        LEFT JOIN student_profiles sp ON u.user_id = sp.user_id 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
?>
