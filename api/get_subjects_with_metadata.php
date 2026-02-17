<?php
require_once '../includes/Config.php';

header('Content-Type: application/json');

try {
    // Get subjects with metadata from timetable_slots
    $query = "
        SELECT DISTINCT 
            s.subject_id, 
            s.code, 
            s.name,
            GROUP_CONCAT(DISTINCT ts.target_group) as target_groups
        FROM subjects s
        LEFT JOIN timetable_slots ts ON s.subject_id = ts.subject_id
        GROUP BY s.subject_id, s.code, s.name
        ORDER BY s.code
    ";
    
    $stmt = $pdo->query($query);
    $subjects = $stmt->fetchAll();
    
    // Parse metadata
    $result = [];
    foreach ($subjects as $subject) {
        $metadata = [
            'subject_id' => $subject['subject_id'],
            'code' => $subject['code'],
            'name' => $subject['name'],
            'semester' => '',
            'branch_code' => ''
        ];
        
        // Extract semester from subject code (e.g., CS501 = Semester 5)
        if (preg_match('/(\d{3})$/', $subject['code'], $matches)) {
            $semester = substr($matches[1], 0, 1); // First digit of 3-digit number
            if ($semester >= 1 && $semester <= 8) {
                $metadata['semester'] = $semester;
            }
        }
        
        // Extract branch from subject code (e.g., CS501 = CS branch)
        if (preg_match('/^([A-Z]{2})/', $subject['code'], $matches)) {
            $metadata['branch_code'] = $matches[1];
        }
        
        $result[] = $metadata;
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>