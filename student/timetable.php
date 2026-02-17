<?php
require_once '../includes/Config.php';
require_once '../includes/Auth.php';

checkAuth();
if ($_SESSION['role'] != 'STUDENT') {
    header('Location: ../dashboard.php');
    exit();
}

$user = getCurrentUser();

// Get student's current semester subjects
$stmt = $pdo->prepare("
    SELECT 
        ts.slot_id,
        s.subject_id,
        s.code,
        s.name,
        s.is_lab,
        ts.day_of_week,
        TIME_FORMAT(ts.start_time, '%H:%i') as start_time,
        TIME_FORMAT(ts.end_time, '%H:%i') as end_time,
        JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.room')) as room,
        JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.batch')) as batch,
        JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.instructor')) as instructor,
        u.full_name as teacher_name,
        u.email as teacher_email
    FROM timetable_slots ts
    INNER JOIN subjects s ON ts.subject_id = s.subject_id
    LEFT JOIN users u ON ts.default_teacher_id = u.user_id
    WHERE JSON_EXTRACT(ts.target_group, '$.branch') = ?
    AND JSON_EXTRACT(ts.target_group, '$.semester') = ?
    AND JSON_EXTRACT(ts.target_group, '$.section') = ?
    AND (JSON_EXTRACT(ts.target_group, '$.batch') = 'all' 
         OR JSON_EXTRACT(ts.target_group, '$.batch') = ?)
    AND CURDATE() BETWEEN ts.valid_from AND ts.valid_until
    ORDER BY 
        FIELD(ts.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
        ts.start_time
");
$stmt->execute([
    $user['branch_code'],
    $user['current_semester'],
    $user['section'],
    $user['lab_batch'] ?: 'all'
]);
$timetable = $stmt->fetchAll();

// Group by day
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$timetable_by_day = [];
foreach ($days as $day) {
    $timetable_by_day[$day] = array_filter($timetable, function($slot) use ($day) {
        return $slot['day_of_week'] == $day;
    });
}

// Check for live sessions
$live_slots = [];
foreach ($timetable as $slot) {
    $stmt = $pdo->prepare("
        SELECT session_id, status 
        FROM live_sessions 
        WHERE slot_id = ? 
        AND session_date = CURDATE() 
        AND status = 'LIVE'
    ");
    $stmt->execute([$slot['slot_id']]);
    $live = $stmt->fetch();
    if ($live) {
        $live_slots[$slot['slot_id']] = $live;
    }
}

// Calculate attendance for each subject
$subject_attendance = [];
foreach ($timetable as $slot) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_sessions,
            SUM(CASE WHEN al.status = 'PRESENT' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN al.status = 'LATE' THEN 1 ELSE 0 END) as late
        FROM attendance_logs al
        INNER JOIN live_sessions ls ON al.session_id = ls.session_id
        WHERE al.student_id = ? 
        AND ls.slot_id = ?
    ");
    $stmt->execute([$user['user_id'], $slot['slot_id']]);
    $att = $stmt->fetch();
    
    $total = $att['total_sessions'];
    $present = $att['present'] + ($att['late'] * 0.5); // Late = 0.5 attendance
    $percentage = $total > 0 ? round(($present / $total) * 100, 1) : 0;
    
    $subject_attendance[$slot['subject_id']] = [
        'total' => $total,
        'present' => $present,
        'percentage' => $percentage,
        'color' => $percentage >= 75 ? 'green' : ($percentage >= 60 ? 'yellow' : 'red')
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Timetable - Student</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">My Timetable & Subjects</h1>
            <p class="text-gray-600">
                <span class="font-semibold"><?= htmlspecialchars($user['roll_no']) ?></span> | 
                Semester <?= $user['current_semester'] ?> <?= htmlspecialchars($user['branch_code']) ?> | 
                Section <?= htmlspecialchars($user['section']) ?> 
                <?= $user['lab_batch'] ? ' | Lab Batch ' . htmlspecialchars($user['lab_batch']) : '' ?>
            </p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                        <i class="fas fa-book text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold"><?= count(array_unique(array_column($timetable, 'subject_id'))) ?></p>
                        <p class="text-gray-500">Subjects This Sem</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center mr-4">
                        <i class="fas fa-chalkboard-teacher text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold"><?= count(array_unique(array_column($timetable, 'teacher_email'))) ?></p>
                        <p class="text-gray-500">Teachers</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center mr-4">
                        <i class="fas fa-flask text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold">
                            <?= count(array_filter($timetable, function($s) { return $s['is_lab']; })) ?>
                        </p>
                        <p class="text-gray-500">Lab Subjects</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center mr-4">
                        <i class="fas fa-broadcast-tower text-red-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold"><?= count($live_slots) ?></p>
                        <p class="text-gray-500">Live Classes</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left: Subjects List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-list-alt text-blue-600 mr-3"></i> Subjects This Semester
                    </h2>
                    
                    <?php if (empty($timetable)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-calendar-times text-gray-400 text-5xl mb-4"></i>
                        <p class="text-gray-600">No subjects scheduled for your semester/section yet.</p>
                    </div>
                    <?php else: 
                        $unique_subjects = [];
                        foreach ($timetable as $slot) {
                            $unique_subjects[$slot['subject_id']] = $slot;
                        }
                    ?>
                    <div class="space-y-4">
                        <?php foreach ($unique_subjects as $subject): 
                            $att = $subject_attendance[$subject['subject_id']] ?? ['total' => 0, 'present' => 0, 'percentage' => 0, 'color' => 'gray'];
                        ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition">
                            <div class="flex items-start">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <h3 class="font-bold text-lg text-gray-800">
                                            <?= htmlspecialchars($subject['code']) ?> - <?= htmlspecialchars($subject['name']) ?>
                                        </h3>
                                        <?php if ($subject['is_lab']): ?>
                                        <span class="ml-3 px-2 py-1 text-xs font-bold bg-purple-100 text-purple-800 rounded">
                                            LAB
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="flex flex-wrap items-center gap-4 mt-2 text-sm">
                                        <?php if ($subject['teacher_name']): ?>
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-chalkboard-teacher mr-2"></i>
                                            <?= htmlspecialchars($subject['teacher_name']) ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($subject['batch'] && $subject['batch'] != 'all'): ?>
                                        <div class="flex items-center text-gray-600">
                                            <i class="fas fa-users mr-2"></i>
                                            Batch <?= htmlspecialchars($subject['batch']) ?>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($subject['instructor']): ?>
                                        <div class="flex items-center text-purple-600">
                                            <i class="fas fa-user-tie mr-2"></i>
                                            Instructor: <?= htmlspecialchars($subject['instructor']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Attendance Progress -->
                                    <div class="mt-3">
                                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                                            <span>Attendance</span>
                                            <span class="font-semibold <?= 'text-' . $att['color'] . '-600' ?>">
                                                <?= $att['percentage'] ?>%
                                            </span>
                                        </div>
                                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full <?= 'bg-' . $att['color'] . '-500' ?>" 
                                                 style="width: <?= min($att['percentage'], 100) ?>%"></div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <?= $att['present'] ?> / <?= $att['total'] ?> sessions
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="ml-4 flex flex-col items-end">
                                    <?php 
                                    $slot_ids = array_column(array_filter($timetable, function($s) use ($subject) {
                                        return $s['subject_id'] == $subject['subject_id'];
                                    }), 'slot_id');
                                    $is_live = false;
                                    foreach ($slot_ids as $sid) {
                                        if (isset($live_slots[$sid])) {
                                            $is_live = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    <?php if ($is_live): ?>
                                    <a href="scanner.php" class="mb-2 inline-block bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-green-700 transition">
                                        <i class="fas fa-broadcast-tower mr-2"></i> LIVE
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($att['total'] > 0): ?>
                                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">
                                        <i class="fas fa-chart-bar mr-1"></i> View Details
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Weekly Timetable Grid -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-calendar-alt text-green-600 mr-3"></i> Weekly Schedule
                    </h2>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <?php foreach ($days as $day): ?>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <?= $day ?>
                                        <?php if ($day == date('l')): ?>
                                        <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">Today</span>
                                        <?php endif; ?>
                                    </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php for ($hour = 8; $hour <= 17; $hour++): 
                                    $time_start = sprintf('%02d:00', $hour);
                                    $time_end = sprintf('%02d:00', $hour + 1);
                                ?>
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 bg-gray-50">
                                        <?= $time_start ?> - <?= $time_end ?>
                                    </td>
                                    <?php foreach ($days as $day): 
                                        $slots = array_filter($timetable_by_day[$day], function($slot) use ($hour) {
                                            return substr($slot['start_time'], 0, 2) == sprintf('%02d', $hour);
                                        });
                                    ?>
                                    <td class="px-4 py-3">
                                        <?php foreach ($slots as $slot): 
                                            $is_live = isset($live_slots[$slot['slot_id']]);
                                            $is_current = $day == date('l') && 
                                                          $time_start <= date('H:i') && 
                                                          date('H:i') < $time_end;
                                        ?>
                                        <div class="mb-2 last:mb-0 p-3 rounded-lg border <?= $is_live ? 'border-green-500 bg-green-50' : 
                                                                                             ($is_current ? 'border-blue-500 bg-blue-50' : 
                                                                                             'border-gray-200 bg-gray-50') ?>">
                                            <div class="font-bold text-gray-800">
                                                <?= htmlspecialchars($slot['code']) ?>
                                                <?php if ($slot['is_lab']): ?>
                                                <span class="ml-1 text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">LAB</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-xs text-gray-600 mt-1">
                                                <?= $slot['start_time'] ?>-<?= $slot['end_time'] ?>
                                                <?php if ($slot['room']): ?>
                                                | <?= htmlspecialchars($slot['room']) ?>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($is_live): ?>
                                            <div class="mt-2">
                                                <a href="scanner.php" class="inline-block bg-green-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-green-700">
                                                    <i class="fas fa-qrcode mr-1"></i> Scan Now
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Today's Classes & Live Sessions -->
            <div>
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="far fa-clock text-yellow-600 mr-3"></i> Today's Classes
                    </h2>
                    
                    <?php 
                    $today = date('l');
                    $today_slots = $timetable_by_day[$today] ?? [];
                    $current_time = date('H:i:s');
                    ?>
                    
                    <?php if (empty($today_slots)): ?>
                    <div class="text-center py-4">
                        <p class="text-gray-500">No classes scheduled for today.</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($today_slots as $slot): 
                            $is_past = $slot['end_time'] < $current_time;
                            $is_current = $slot['start_time'] <= $current_time && $slot['end_time'] >= $current_time;
                            $is_upcoming = $slot['start_time'] > $current_time;
                        ?>
                        <div class="border border-gray-200 rounded-lg p-4 <?= $is_current ? 'border-blue-500 bg-blue-50' : '' ?>">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h3 class="font-bold text-gray-800"><?= htmlspecialchars($slot['code']) ?></h3>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($slot['name']) ?></p>
                                </div>
                                <span class="px-3 py-1 text-xs font-bold rounded-full 
                                    <?= $is_past ? 'bg-gray-100 text-gray-800' : 
                                       ($is_current ? 'bg-blue-100 text-blue-800' : 
                                       'bg-green-100 text-green-800') ?>">
                                    <?= $is_past ? 'Completed' : ($is_current ? 'Ongoing' : 'Upcoming') ?>
                                </span>
                            </div>
                            <div class="text-sm text-gray-700">
                                <i class="far fa-clock mr-2"></i>
                                <?= $slot['start_time'] ?> - <?= $slot['end_time'] ?>
                                <?php if ($slot['room']): ?>
                                | <i class="fas fa-door-closed mr-1"></i> <?= htmlspecialchars($slot['room']) ?>
                                <?php endif; ?>
                            </div>
                            <?php if (isset($live_slots[$slot['slot_id']])): ?>
                            <div class="mt-3">
                                <a href="scanner.php" class="block w-full bg-green-600 text-white text-center py-2 rounded-lg font-bold hover:bg-green-700 transition">
                                    <i class="fas fa-qrcode mr-2"></i> Mark Attendance
                                </a>
                            </div>
                            <?php elseif ($is_current && !$is_past): ?>
                            <div class="mt-3 text-sm text-yellow-700">
                                <i class="fas fa-info-circle mr-1"></i>
                                Session not started yet by teacher
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Live Sessions Panel -->
                <?php if ($live_slots): ?>
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-broadcast-tower text-red-600 mr-3"></i> Live Sessions Active
                    </h2>
                    <div class="space-y-4">
                        <?php foreach ($live_slots as $slot_id => $live): 
                            $slot = array_filter($timetable, function($s) use ($slot_id) { return $s['slot_id'] == $slot_id; });
                            $slot = reset($slot);
                        ?>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-center mb-2">
                                <div class="h-3 w-3 bg-red-500 rounded-full mr-3 animate-pulse"></div>
                                <h3 class="font-bold text-red-800"><?= htmlspecialchars($slot['code']) ?></h3>
                            </div>
                            <p class="text-sm text-red-700 mb-3"><?= htmlspecialchars($slot['name']) ?></p>
                            <a href="scanner.php" class="block w-full bg-red-600 text-white text-center py-2 rounded-lg font-bold hover:bg-red-700 transition">
                                <i class="fas fa-qrcode mr-2"></i> Scan QR Now
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh page every 60 seconds for live updates
        setTimeout(() => {
            location.reload();
        }, 60000);
    </script>
</body>
</html>