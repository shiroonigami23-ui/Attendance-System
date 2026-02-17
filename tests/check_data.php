<?php
// tests/check_data.php
require_once __DIR__ . '/../includes/Config.php';

try {
    $subjectCount = $pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    echo "Subjects: $subjectCount\n";
    
    $slotCount = $pdo->query("SELECT COUNT(*) FROM timetable_slots")->fetchColumn();
    echo "Timetable Slots: $slotCount\n";
    
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Users: $userCount\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
