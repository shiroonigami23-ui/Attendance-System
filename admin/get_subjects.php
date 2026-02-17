<?php
require_once '../includes/Config.php';
require_once '../includes/Auth.php';

checkAuth();
if ($_SESSION['role'] != 'MASTER') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Get parameters
$semester = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$branch = isset($_GET['branch']) ? trim($_GET['branch']) : '';

if ($semester <= 0 || empty($branch)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

try {
    global $pdo;
    
    // First, try to get subjects from subject_semesters table
    $query = "SELECT s.subject_id, s.code, s.name, s.is_lab 
              FROM subjects s
              INNER JOIN subject_semesters ss ON s.subject_id = ss.subject_id
              WHERE ss.semester = ? AND ss.branch_code = ?
              ORDER BY s.code";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$semester, $branch]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no subjects found, return all subjects as fallback
    if (empty($subjects)) {
        $stmt = $pdo->query("SELECT subject_id, code, name, is_lab FROM subjects ORDER BY code");
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'subjects' => $subjects,
        'count' => count($subjects)
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>