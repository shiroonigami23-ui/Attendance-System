<?php
// tests/get_student.php
require_once __DIR__ . '/../includes/Config.php';

$stmt = $pdo->query("SELECT user_id, email, password_hash FROM users WHERE role = 'STUDENT' LIMIT 1");
$user = $stmt->fetch();
if ($user) {
    echo "Found Student: ID=" . $user['user_id'] . ", Email=" . $user['email'] . "\n";
} else {
    echo "No student found.\n";
}
?>
