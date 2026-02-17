<?php
// scripts/import_timetable.php
require_once __DIR__ . '/../includes/Config.php';

// Teacher Mapping from CSV Footer
// Initial -> Full Name matches
$teacherMap = [
    'YRS' => 'Prof. Yograj Sharma',
    'MDK' => 'Prof. Madhukar Dubey',
    'VIG' => 'Prof. Vivek Gupta',
    'MKN' => 'Prof. Manoj Niranjan',
    'SKR' => 'Prof. Saurabh Raghuvanshi',
    'DJM' => 'Dr. Jagdish Makhijani',
    'SKG' => 'Prof. Saurabh Raghuvanshi', // Assuming typo for SKR based on subject CS602
    'VRS' => 'Prof. Vivek Gupta', // Assuming typo or variation? Wait, CS603 (MKN/VRS). 
                                 // Footer for CS603: Prof. Yograj Sharma/ Prof. Manoj Niranjan. 
                                 // Maybe VRS is not Vivek? 
                                 // Let's check: CS603 footer has "Prof. Yograj Sharma". YRS matches.
                                 // CS603 cell: (YRS)/(MKN).
                                 // Section B cell: (MKN/VRS).
                                 // Who is VRS? Maybe "Niranjan"? No, MKN.
                                 // Maybe "Yograj S..." -> YRS.
                                 // Let's look for VRS. None.
                                 // Maybe "Vivek"? VIG.
                                 // I'll leave VRS as "Unknown Teacher VRS" for now or map to default.
                                 // Actually, let's map to implied teacher from Subject Code if possible.
];

// Subject Code -> Name Mapping (from footer)
$subjectMap = [
    'CS601' => ['name' => 'Machine Learning', 'is_lab' => 0],
    'CS-601' => ['name' => 'Machine Learning', 'is_lab' => 0],
    'CS602' => ['name' => 'Computer Network', 'is_lab' => 0],
     'CS-602' => ['name' => 'Computer Network', 'is_lab' => 0],
    'CS603' => ['name' => 'Compiler/ Graphics', 'is_lab' => 0],
    'CS-603' => ['name' => 'Compiler/ Graphics', 'is_lab' => 0],
    'CS604' => ['name' => 'Project Management', 'is_lab' => 0],
    'CS-604' => ['name' => 'Project Management', 'is_lab' => 0],
    'CS605' => ['name' => 'Data Analytics Lab', 'is_lab' => 1],
    'CS-605' => ['name' => 'Data Analytics Lab', 'is_lab' => 1],
    'CS606' => ['name' => 'Skill Development Lab', 'is_lab' => 1],
    'CS-606' => ['name' => 'Skill Development Lab', 'is_lab' => 1],
];

function getOrCreateTeacher($initials) {
    global $pdo, $teacherMap;
    $name = $teacherMap[$initials] ?? "Teacher $initials";
    
    // Check if exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE full_name = ? AND role IN ('SEMI_ADMIN', 'MASTER')");
    $stmt->execute([$name]);
    $user = $stmt->fetch();
    
    if ($user) return $user['user_id'];
    
    // Create new
    $email = strtolower($initials) . "@example.com";
    $pass = password_hash('password123', PASSWORD_BCRYPT); // DEFAULT
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, is_active, approved) VALUES (?, ?, ?, 'SEMI_ADMIN', 1, 1)");
    try {
        $stmt->execute([$name, $email, $pass]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        // Handle duplicate email if coincidentally same
         return 0;
    }
}

function getOrCreateSubject($code, $name = '', $is_lab = 0) {
    global $pdo, $subjectMap;
    
    // Normalize code (remove hyphens usually? No, keep as provided or normalized?
    // CSV has CS-601 and CS601. Better verify standard.
    // Let's strip hyphen for standard code.
    $stdCode = str_replace('-', '', $code);
    
    $mapInfo = $subjectMap[$code] ?? $subjectMap[$stdCode] ?? ['name' => $name, 'is_lab' => $is_lab];
    
    $stmt = $pdo->prepare("SELECT subject_id FROM subjects WHERE code = ?");
    $stmt->execute([$stdCode]); // Use normalized code
    $subject = $stmt->fetch();
    
    if ($subject) return $subject['subject_id'];
    
    $stmt = $pdo->prepare("INSERT INTO subjects (code, name, is_lab) VALUES (?, ?, ?)");
    try {
        $stmt->execute([$stdCode, $mapInfo['name'], $mapInfo['is_lab']]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return 0;
    }
}

function parseCell($cell, $day, $startTime, $endTime, $section) {
    global $pdo;
    
    // Split by / for simultaneous batches
    // Example: "CS602 B1 (YRS) N/W LAB / CS601-B2 (MDK) CM LAB"
    // Also handle line breaks if any
    $parts = preg_split('#\s*/\s*#', $cell);
    
    foreach ($parts as $part) {
        $part = trim($part);
        if (empty($part) || stripos($part, 'Self Study') !== false || stripos($part, 'Library') !== false || stripos($part, 'LUNCH') !== false) continue;

        // Parse format: CODE (INITIALS) ROOM
        // Regex to extract
        // Patter: ([A-Z0-9-]+)\s*(?:B\d\+?B\d|B\d)?\s*\(([A-Z/]+)\)\s*(.*)
        // Groups: 1=Code, 2=Initials, 3=Room
        
        $code = '';
        $initials = '';
        $room = '';
        $batch = '';

        // Try to find Code
        if (preg_match('/(CS-?\d{3})/', $part, $m)) {
            $code = $m[1];
        }
        
        // Try to find Initials in parens
        if (preg_match('/\(([A-Z\/]+)\)/', $part, $m)) {
            $initials = $m[1];
        }
        
        // Try to find Batch (B1, B2, B1+B2)
        if (preg_match('/(B\d(?:\+B\d)?)/', $part, $m)) {
            $batch = $m[1];
        }
        
        // Room is whatever is left at end? "S-20", "CM LAB"
        // Let's just store the full text as location if vague?
        // Or specific extraction?
        // "S-20" or "Lab"
        
        if ($code && $initials) {
            // Handle Multi-Teacher (MKN/VRS)
            $teacherInitials = explode('/', $initials);
            $primaryTeacherInitial = $teacherInitials[0]; 
            // We only support one default_teacher_id in schema currently (unless we change schema).
            // We'll use first teacher.
            
            $tid = getOrCreateTeacher($primaryTeacherInitial);
            $sid = getOrCreateSubject($code);
            
            if ($tid && $sid) {
                // Target Group
                $tg = [
                    'degree' => 'B.Tech',
                    'branch' => 'CSE',
                    'semester' => 6,
                    'section' => $section
                ];
                if ($batch) {
                    $tg['batch'] = $batch;
                }
                
                // Insert Slot
                $sql = "INSERT INTO timetable_slots (subject_id, default_teacher_id, target_group, day_of_week, start_time, end_time, valid_from, valid_until) 
                        VALUES (?, ?, ?, ?, ?, ?, '2026-01-01', '2026-06-30')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $sid, 
                    $tid, 
                    json_encode($tg), 
                    $day, 
                    date('H:i:s', strtotime($startTime)), 
                    date('H:i:s', strtotime($endTime))
                ]);
                echo "Inserted: $day $startTime $code ($initials) Batch:$batch Sec:$section\n";
            }
        }
    }
}

function processCSV($file, $section) {
    if (!file_exists($file)) return;
    
    $lines = file($file);
    $headerTime = [];
    
    // Find header row with Start Times
    // Line 5 in CSV provided: DAY/TIME, 09:10-10:00, ...
    foreach ($lines as $i => $line) {
        $row = str_getcsv($line);
        if ($row[0] == 'DAY/TIME') {
            // Parse Times
            // Col 1 to End
            for ($k = 1; $k < count($row); $k++) {
                $timeStr = $row[$k]; // "09:10-10:00" or "BREAK (10:50-11:00)"
                if (strpos(strtoupper($timeStr), 'BREAK') !== false || strpos(strtoupper($timeStr), 'LUNCH') !== false) {
                     // Check if it has time range
                     if (preg_match('/(\d{2}:\d{2})-(\d{2}:\d{2})/', $timeStr, $m)) {
                         $headerTime[$k] = ['start' => $m[1], 'end' => $m[2], 'is_break' => true];
                     } else {
                         $headerTime[$k] = null;
                     }
                } elseif (preg_match('/(\d{2}:\d{2})-(\d{2}:\d{2})/', $timeStr, $m)) {
                    $headerTime[$k] = ['start' => $m[1], 'end' => $m[2], 'is_break' => false];
                } else {
                    $headerTime[$k] = null;
                }
            }
            
            // Limit to Days
            // Days start next lines
            for ($d = $i + 1; $d < count($lines); $d++) {
                $dayRow = str_getcsv($lines[$d]);
                if (empty($dayRow[0])) continue;
                if ($dayRow[0] == 'SUBJECT DETAILS') break; 
                
                $dayName = $dayRow[0]; // Mon, Tue...
                // Map to Full Day
                $fullDay = date('l', strtotime($dayName)); // Mon -> Monday
                
                for ($c = 1; $c < count($dayRow); $c++) {
                    if (isset($headerTime[$c]) && !$headerTime[$c]['is_break']) {
                        parseCell($dayRow[$c], $fullDay, $headerTime[$c]['start'], $headerTime[$c]['end'], $section);
                    }
                }
            }
            break;
        }
    }
}

// Run
echo "Processing Section A...\n";
processCSV('C:/Users/shiro/OneDrive/Desktop/Presentation/CSE_6th_Sem_Section_A_Timetable.csv', 'A');

echo "Processing Section B...\n";
processCSV('C:/Users/shiro/OneDrive/Desktop/Presentation/CSE_6th_Sem_Section_B_Timetable.csv', 'B');

echo "Done.\n";
?>
