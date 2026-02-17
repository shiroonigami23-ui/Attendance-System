<?php
require_once __DIR__ . '/../includes/Config.php';

echo "=== Timetable Slots ===\n";
$stmt = $pdo->query("DESCRIBE timetable_slots");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== Subjects ===\n";
$stmt = $pdo->query("DESCRIBE subjects");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
