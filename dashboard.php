<?php
require_once 'includes/Config.php';
require_once 'includes/Auth.php';

checkAuth();

$user = getCurrentUser();
$role = $_SESSION['role'];

// Redirect based on role
switch ($role) {
    case 'STUDENT':
        header('Location: student/dashboard.php');
        exit();
    case 'SEMI_ADMIN':
        header('Location: teacher/dashboard.php');
        exit();
    case 'MASTER':
        header('Location: admin/dashboard.php');
        exit();
    default:
        // If unknown role, show basic info
        echo "Unknown role: {$role}. Please contact administrator.";
        exit();
}
?>