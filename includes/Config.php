<?php
function isHttpsRequest(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return true;
    }
    if (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        return true;
    }
    return false;
}

function normalizeBaseUrl(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        $value = 'http://localhost/Attendance_System/';
    }
    return rtrim($value, '/') . '/';
}

// App environment
define('APP_ENV', getenv('APP_ENV') ?: 'local');
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: (APP_ENV === 'local' ? '1' : '0'), FILTER_VALIDATE_BOOLEAN));

// Database config (MySQL for XAMPP / RDS)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'attendance_system');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Base URL
define('BASE_URL', normalizeBaseUrl(getenv('BASE_URL') ?: 'http://localhost/Attendance_System/'));

// Security salts
define('SALT_DEVICE', getenv('SALT_DEVICE') ?: 'change-me-in-production');

// Email Config (AWS SES)
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'email-smtp.us-east-1.amazonaws.com');
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_PORT', (int) (getenv('SMTP_PORT') ?: 587));
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'noreply@example.com');

// Security Configuration (WiFi & Geolocation)
define('WIFI_FENCING_ENABLED', filter_var(getenv('WIFI_FENCING_ENABLED') ?: '1', FILTER_VALIDATE_BOOLEAN));
define('ALLOWED_SUBNETS', [
    '127.0.0.1',
    '::1',
    '192.168.1.',
    '10.0.0.'
]);

define('GEO_FENCING_ENABLED', filter_var(getenv('GEO_FENCING_ENABLED') ?: '1', FILTER_VALIDATE_BOOLEAN));
define('CAMPUS_LAT', (float) (getenv('CAMPUS_LAT') ?: 26.15843));
define('CAMPUS_LNG', (float) (getenv('CAMPUS_LNG') ?: 78.49089));
define('ALLOWED_RADIUS', (int) (getenv('ALLOWED_RADIUS') ?: 200));

// Timezone
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Kolkata');

// Error reporting
ini_set('log_errors', '1');
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');

// Secure session settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', isHttpsRequest() ? '1' : '0');
    ini_set('session.gc_maxlifetime', '86400');
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'secure' => isHttpsRequest(),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Database connection
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Internal server error. Please try again later.');
}

function app_url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

function app_redirect(string $path = 'login.php'): void
{
    header('Location: ' . app_url($path));
    exit();
}

// Device fingerprinting function
function generateDeviceFingerprint(): string
{
    $components = [
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
        $_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''
    ];
    return hash('sha256', SALT_DEVICE . implode('|', $components));
}
