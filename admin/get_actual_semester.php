<?php
require_once '../includes/Config.php';
require_once '../includes/Auth.php';

checkAuth();
if ($_SESSION['role'] != 'MASTER') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Access denied']));
}

header('Content-Type: application/json');

$batch_year = $_GET['batch_year'] ?? '';

if (empty($batch_year) || !preg_match('/^\d{2}$/', $batch_year)) {
    echo json_encode(['success' => false, 'error' => 'Invalid batch year']);
    exit;
}

try {
    // Get most common semester for this batch
    $stmt = $pdo->prepare("
        SELECT current_semester, COUNT(*) as student_count
        FROM student_profiles 
        WHERE LENGTH(roll_no) >= 8 
        AND SUBSTRING(roll_no, 7, 2) = ?
        GROUP BY current_semester
        ORDER BY student_count DESC
        LIMIT 1
    ");
    $stmt->execute([$batch_year]);
    $result = $stmt->fetch();
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'actual_semester' => (int)$result['current_semester'],
            'student_count' => (int)$result['student_count']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No students found for this batch']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>