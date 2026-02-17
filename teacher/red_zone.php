<?php
require_once '../includes/Config.php';
require_once '../includes/Auth.php';

checkAuth();
if ($_SESSION['role'] != 'SEMI_ADMIN') {
    header('Location: ../dashboard.php');
    exit();
}

$user = getCurrentUser();
$user_id = $user['user_id'];

// Get teacher's assigned subjects
$stmt = $pdo->prepare("
    SELECT DISTINCT s.subject_id, s.code, s.name
    FROM subjects s
    INNER JOIN timetable_slots ts ON s.subject_id = ts.subject_id
    WHERE ts.default_teacher_id = ?
    ORDER BY s.code
");
$stmt->execute([$user_id]);
$subjects = $stmt->fetchAll();

$selected_subject = $_GET['subject_id'] ?? ($subjects[0]['subject_id'] ?? 0);
$selected_section = $_GET['section'] ?? 'A';

// Fetch students in Red Zone for selected subject/section
$red_zone_students = [];
if ($selected_subject) {
    // We need to calculate attendance per subject since student_attendance_summary doesn't have subject_id
    // Get all completed sessions for this subject/section taught by this teacher
    $stmt = $pdo->prepare("
        SELECT ls.session_id
        FROM live_sessions ls
        INNER JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
        WHERE ts.subject_id = ?
        AND JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.section')) = ?
        AND (ls.actual_teacher_id = ? OR ts.default_teacher_id = ?)
        AND ls.status = 'COMPLETED'
    ");
    $stmt->execute([$selected_subject, $selected_section, $user_id, $user_id]);
    $completed_sessions = $stmt->fetchAll();
    
    if ($completed_sessions) {
        // Get session IDs as array
        $session_ids = array_column($completed_sessions, 'session_id');
        $session_ids_placeholder = implode(',', array_fill(0, count($session_ids), '?'));
        
        // Get all students in this section
        $stmt = $pdo->prepare("
            SELECT sp.user_id as student_id, sp.roll_no, u.full_name, u.email, 
                   sp.section, sp.current_semester
            FROM student_profiles sp
            INNER JOIN users u ON sp.user_id = u.user_id
            WHERE sp.section = ?
            AND u.is_active = 1
            ORDER BY sp.roll_no
        ");
        $stmt->execute([$selected_section]);
        $all_students = $stmt->fetchAll();
        
        foreach ($all_students as $student) {
            $student_id = $student['student_id'];
            
            // Get attendance for this student in these sessions
            $params = array_merge([$student_id], $session_ids);
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_sessions,
                    SUM(CASE WHEN status = 'PRESENT' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN status = 'LATE' THEN 1 ELSE 0 END) as late_count,
                    SUM(CASE WHEN status = 'ABSENT' THEN 1 ELSE 0 END) as absent_count
                FROM attendance_logs
                WHERE student_id = ?
                AND session_id IN ($session_ids_placeholder)
            ");
            $stmt->execute($params);
            $attendance_data = $stmt->fetch();
            
            $total_sessions = $attendance_data['total_sessions'] ?? 0;
            
            if ($total_sessions > 0) {
                $present_count = $attendance_data['present_count'] ?? 0;
                $late_count = $attendance_data['late_count'] ?? 0;
                
                // Calculate percentage (late counts as 0.5)
                $attendance_percentage = round((($present_count + ($late_count * 0.5)) / $total_sessions) * 100, 1);
                
                // Check if in red zone (< 35%)
                if ($attendance_percentage < 35) {
                    $red_zone_students[] = [
                        'roll_no' => $student['roll_no'],
                        'user_id' => $student_id,
                        'full_name' => $student['full_name'],
                        'email' => $student['email'],
                        'section' => $student['section'],
                        'current_semester' => $student['current_semester'],
                        'total_sessions' => $total_sessions,
                        'present_count' => $present_count,
                        'late_count' => $late_count,
                        'absent_count' => $attendance_data['absent_count'] ?? 0,
                        'attendance_percentage' => $attendance_percentage
                    ];
                }
            } else {
                // Student has no attendance records for this subject - consider as red zone
                $red_zone_students[] = [
                    'roll_no' => $student['roll_no'],
                    'user_id' => $student_id,
                    'full_name' => $student['full_name'],
                    'email' => $student['email'],
                    'section' => $student['section'],
                    'current_semester' => $student['current_semester'],
                    'total_sessions' => 0,
                    'present_count' => 0,
                    'late_count' => 0,
                    'absent_count' => 0,
                    'attendance_percentage' => 0
                ];
            }
        }
    }
}

// Sort by percentage ascending
usort($red_zone_students, function($a, $b) {
    return $a['attendance_percentage'] <=> $b['attendance_percentage'];
});

// Count total red zone students
$total_red = count($red_zone_students);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Red Zone Alert - Teacher Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i> Red Zone Alert
            </h1>
            <p class="text-gray-600">Students with attendance below 35% in your subjects</p>
        </div>

        <!-- Stats Card -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center mr-4">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold"><?= $total_red ?></p>
                        <p class="text-gray-500">Red Zone Students</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center mr-4">
                        <i class="fas fa-chalkboard-teacher text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold"><?= count($subjects) ?></p>
                        <p class="text-gray-500">Your Subjects</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                        <i class="fas fa-user-graduate text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold">
                            <?= $total_red > 0 ? round(($total_red / 50) * 100, 1) : 0 ?>%
                        </p>
                        <p class="text-gray-500">At Risk</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center mr-4">
                        <i class="fas fa-shield-alt text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold">35%</p>
                        <p class="text-gray-500">Threshold</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Filter Students</h2>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-gray-700 mb-2">Subject</label>
                    <select name="subject_id" class="w-full p-3 border border-gray-300 rounded-lg">
                        <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['subject_id'] ?>" <?= $selected_subject == $subject['subject_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($subject['code']) ?> - <?= htmlspecialchars($subject['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 mb-2">Section</label>
                    <select name="section" class="w-full p-3 border border-gray-300 rounded-lg">
                        <option value="A" <?= $selected_section == 'A' ? 'selected' : '' ?>>A</option>
                        <option value="B" <?= $selected_section == 'B' ? 'selected' : '' ?>>B</option>
                        <option value="C" <?= $selected_section == 'C' ? 'selected' : '' ?>>C</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white p-3 rounded-lg font-medium hover:bg-blue-700 transition">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Red Zone Students Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-red-50">
                <h2 class="text-xl font-bold text-red-800">
                    <i class="fas fa-users mr-3"></i> 
                    Red Zone Students (<?= $total_red ?>)
                    <?php if ($selected_subject && isset($subjects[0])): 
                        $subject_name = '';
                        foreach ($subjects as $s) {
                            if ($s['subject_id'] == $selected_subject) {
                                $subject_name = $s['code'];
                                break;
                            }
                        }
                    ?>
                    <span class="text-lg font-normal text-gray-700 ml-3">
                        for <?= htmlspecialchars($subject_name) ?> | Section <?= htmlspecialchars($selected_section) ?>
                    </span>
                    <?php endif; ?>
                </h2>
            </div>
            
            <?php if (empty($red_zone_students)): ?>
            <div class="p-12 text-center">
                <i class="fas fa-check-circle text-green-500 text-5xl mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-700 mb-2">No Students in Red Zone</h3>
                <p class="text-gray-600">All students have attendance above 35% for this subject.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roll No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attendance %</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($red_zone_students as $student): 
                            $percentage = $student['attendance_percentage'] ?? 0;
                            $color = $percentage >= 75 ? 'green' : ($percentage >= 60 ? 'yellow' : ($percentage >= 35 ? 'orange' : 'red'));
                        ?>
                        <tr class="<?= $percentage < 20 ? 'bg-red-50' : '' ?>">
                            <td class="px-6 py-4 whitespace-nowrap font-bold">
                                <?= htmlspecialchars($student['roll_no']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium"><?= htmlspecialchars($student['full_name']) ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($student['email']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?= htmlspecialchars($student['section']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-24 mr-3">
                                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full bg-<?= $color ?>-500" 
                                                 style="width: <?= min($percentage, 100) ?>%"></div>
                                        </div>
                                    </div>
                                    <span class="font-bold text-<?= $color ?>-700">
                                        <?= $percentage ?>%
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm">
                                    <span class="font-medium">Total: <?= $student['total_sessions'] ?></span><br>
                                    <span class="text-green-600">P: <?= $student['present_count'] ?></span> | 
                                    <span class="text-yellow-600">L: <?= $student['late_count'] ?></span> | 
                                    <span class="text-red-600">A: <?= $student['absent_count'] ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($percentage < 20): ?>
                                <span class="px-3 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800">
                                    <i class="fas fa-skull-crossbones mr-1"></i> CRITICAL
                                </span>
                                <?php elseif ($percentage < 35): ?>
                                <span class="px-3 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> RED ZONE
                                </span>
                                <?php else: ?>
                                <span class="px-3 py-1 text-xs font-bold rounded-full bg-yellow-100 text-yellow-800">
                                    WARNING
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="#" class="text-blue-600 hover:text-blue-900 mr-4">
                                    <i class="fas fa-envelope mr-1"></i> Email
                                </a>
                                <a href="#" class="text-purple-600 hover:text-purple-900">
                                    <i class="fas fa-flag mr-1"></i> Flag to Admin
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Export & Actions -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
                <div class="text-sm text-gray-600">
                    <i class="fas fa-info-circle mr-2"></i>
                    Students below 35% attendance are at risk of detention.
                </div>
                <div class="space-x-4">
                    <button class="bg-red-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-700 transition">
                        <i class="fas fa-file-export mr-2"></i> Export List
                    </button>
                    <a href="../admin/dashboard.php" class="bg-purple-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-purple-700 transition">
                        <i class="fas fa-user-shield mr-2"></i> Request Grace
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Explanation -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                <h3 class="text-lg font-bold text-blue-800 mb-3 flex items-center">
                    <i class="fas fa-info-circle mr-3"></i> About Red Zone
                </h3>
                <ul class="space-y-2 text-blue-700">
                    <li><strong>35% Threshold:</strong> University regulation for minimum attendance</li>
                    <li><strong>Auto-Lock:</strong> Accounts automatically locked below 20%</li>
                    <li><strong>Grace:</strong> Master Admin can manually boost attendance %</li>
                    <li><strong>Notification:</strong> Students receive warnings at 40%, 35%, 20%</li>
                </ul>
            </div>
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                <h3 class="text-lg font-bold text-yellow-800 mb-3 flex items-center">
                    <i class="fas fa-lightbulb mr-3"></i> Recommended Actions
                </h3>
                <ul class="space-y-2 text-yellow-700">
                    <li>✅ Email students individually</li>
                    <li>✅ Flag critical cases to Master Admin</li>
                    <li>✅ Schedule parent-teacher meetings</li>
                    <li>✅ Consider medical leave adjustments</li>
                    <li>❌ Do not manually mark attendance (requires admin grace)</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>