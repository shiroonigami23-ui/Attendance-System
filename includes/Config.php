<?php
// Secure Session Settings (Best for AWS/Production)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Only secure if HTTPS

    // 24 hour session lifetime
    ini_set('session.gc_maxlifetime', 86400);
    session_set_cookie_params(86400);

    session_start();
}

// Database config (MySQL for XAMPP)
// Database config (MySQL for XAMPP)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'attendance_system');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Base URL
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost/Attendance_System/');

// Security salts
define('SALT_DEVICE', 'your_secret_salt_change_in_production');

// Email Config (AWS SES)
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'email-smtp.us-east-1.amazonaws.com');
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'noreply@example.com');

// Security Configuration (WiFi & Geolocation)
// TODO: Update these with real Campus IPs and Coordinates for production
define('WIFI_FENCING_ENABLED', true);
define('ALLOWED_SUBNETS', [
    '127.0.0.1',    // Localhost
    '::1',          // Localhost IPv6
    '192.168.1.',   // Example Local Subnet
    '10.0.0.'       // Example Campus Subnet
]);

define('GEO_FENCING_ENABLED', true);
define('CAMPUS_LAT', 26.15843); // Example: RJIT Gwalior Latitude
define('CAMPUS_LNG', 78.49089); // Example: RJIT Gwalior Longitude
define('ALLOWED_RADIUS', 200);   // Meters

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error Reporting (Turn off display_errors for production)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

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
function generateDeviceFingerprint()
{
    $salt = SALT_DEVICE;
    $components = [
        $_SERVER['HTTP_USER_AGENT'],
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
        $_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''
    ];
    return hash('sha256', $salt . implode('|', $components));
}
