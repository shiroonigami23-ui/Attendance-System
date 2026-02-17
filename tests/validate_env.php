<?php
// tests/validate_env.php
require_once __DIR__ . '/../includes/Config.php';

echo "Running Environment Validation...\n";

// 1. Check Database Connection
echo "[*] Checking Database Connection... ";
try {
    // Attempt to connect using the PDO instance from Config.php
    // If Config.php fails, script execution might have stopped there, 
    // but assuming it's structured to allow inclusion.
    if ($pdo) {
        echo "OK\n";
    } else {
        echo "FAILED (PDO object not found)\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Check Required Tables
$requiredTables = [
    'users',
    'student_profiles',
    'device_locks',
    'system_logs',
    'attendance_logs',
    'live_sessions',
    'student_attendance_summary'
];

echo "[*] Checking Required Tables...\n";
$missingTables = [];
foreach ($requiredTables as $table) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "    - $table: OK\n";
        } else {
            echo "    - $table: MISSING\n";
            $missingTables[] = $table;
        }
    } catch (PDOException $e) {
        echo "    - $table: ERROR (" . $e->getMessage() . ")\n";
         $missingTables[] = $table;
    }
}

if (!empty($missingTables)) {
    echo "FAIL: Missing tables: " . implode(', ', $missingTables) . "\n";
    // Optional: Output SQL check suggestion
} else {
    echo "ALL REQUIRED TABLES FOUND.\n";
}

// 3. Check Write Permissions
$uploadDir = __DIR__ . '/../assets/uploads';
echo "[*] Checking Upload Directory Permissions ($uploadDir)... ";
if (is_dir($uploadDir) && is_writable($uploadDir)) {
    echo "OK\n";
} else {
    echo "FAILED (Not writable or does not exist)\n";
    if (!is_dir($uploadDir)) {
        echo "    - Directory does not exist.\n";
    }
}

echo "Environment Validation Completed.\n";
?>
