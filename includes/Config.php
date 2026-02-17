<?php
session_start();

// Database config (MySQL for XAMPP) - replace with your values
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'attendance_system');
define('DB_USER', getenv('DB_USER') ?: 'DB_USER_PLACEHOLDER');
define('DB_PASS', getenv('DB_PASS') ?: 'DB_PASS_PLACEHOLDER');

// Base URL - set in production
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost/Attendance_System/');

// Security salts - change in production
define('SALT_DEVICE', getenv('SALT_DEVICE') ?: 'SALT_DEVICE_PLACEHOLDER');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Device fingerprinting function
function generateDeviceFingerprint() {
    $salt = SALT_DEVICE;
    $components = [
        $_SERVER['HTTP_USER_AGENT'],
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
        $_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''
    ];
    return hash('sha256', $salt . implode('|', $components));
}
?>