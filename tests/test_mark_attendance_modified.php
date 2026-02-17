<?php
// tests/test_mark_attendance_modified.php
session_start();
$_SESSION['user_id'] = 3;
$_SESSION['role'] = 'STUDENT';

// Read original API code
$code = file_get_contents(__DIR__ . '/../api/mark_attendance.php');

// Replace input reading
$code = str_replace(
    "file_get_contents('php://input')",
    "file_get_contents(__DIR__ . '/input.json')",
    $code
);

// Remove open tag
$code = str_replace('<?php', '', $code);

// Eval or Save? Save to avoid eval issues with namespace/require
file_put_contents(__DIR__ . '/temp_api.php', "<?php\n" . $code);

echo "Running modified API...\n";
require_once __DIR__ . '/temp_api.php';
