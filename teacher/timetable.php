<?php
require_once '../includes/Config.php';
require_once '../includes/Auth.php';
require_once '../includes/SessionManager.php';

checkAuth();
if ($_SESSION['role'] != 'SEMI_ADMIN') {
    header('Location: ../dashboard.php');
    exit();
}

$user = getCurrentUser();
$user_id = $user['user_id'];

// Auto-end expired sessions
SessionManager::checkAndEndExpiredSessions();

// Get teacher's timetable
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Get current week timetable
$timetable = [];
foreach ($days as $day) {
    $stmt = $pdo->prepare("
        SELECT 
            ts.slot_id,
            s.subject_id,
            s.code,
            s.name,
            s.is_lab,
            TIME_FORMAT(ts.start_time, '%H:%i') as start_time,
            TIME_FORMAT(ts.end_time, '%H:%i') as end_time,
            JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.section')) as section,
            JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.batch')) as batch,
            JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.room')) as room,
            ts.default_teacher_id,
            CASE 
                WHEN ts.default_teacher_id = ? THEN 'ASSIGNED'
                ELSE 'SWAPPED'
            END as assignment_type,
            sw.requester_id,
            sw.status as swap_status,
            u.full_name as swap_requester_name
        FROM timetable_slots ts
        INNER JOIN subjects s ON ts.subject_id = s.subject_id
        LEFT JOIN swap_requests sw ON ts.slot_id = sw.slot_id 
            AND sw.requested_date = CURDATE() 
            AND sw.status = 'APPROVED'
        LEFT JOIN users u ON sw.requester_id = u.user_id
        WHERE (
            ts.default_teacher_id = ?  -- Originally assigned
            OR EXISTS (
                SELECT 1 FROM swap_requests sw2 
                WHERE sw2.slot_id = ts.slot_id 
                AND sw2.status = 'APPROVED' 
                AND sw2.receiver_id = ?
                AND CURDATE() BETWEEN sw2.requested_date AND DATE_ADD(sw2.requested_date, INTERVAL 7 DAY)
            )
        )
        AND ts.day_of_week = ?
        AND CURDATE() BETWEEN ts.valid_from AND ts.valid_until
        ORDER BY ts.start_time
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $day]);
    $timetable[$day] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get today's classes for quick stats
$current_day = date('l');
$current_time = date('H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Timetable - Teacher Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-calendar-alt text-blue-600 text-2xl mr-3"></i>
                        <span class="text-xl font-bold text-gray-800">My Timetable</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                    </a>
                    <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        Teacher: <?= htmlspecialchars($user['full_name'] ?? 'Teacher') ?>
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">My Weekly Timetable</h1>
            <p class="text-gray-600 mt-2">View your assigned and swapped classes for the current schedule</p>
            
            <!-- Current Time & Day -->
            <div class="mt-4 flex items-center justify-between">
                <div class="bg-white rounded-xl shadow p-4">
                    <div class="flex items-center space-x-4">
                        <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-calendar-day text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Today is</p>
                            <p class="text-xl font-bold text-gray-800"><?= $current_day ?>, <?= date('F j, Y') ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500">Current Time</p>
                            <p class="text-2xl font-bold text-gray-800" id="currentTime"><?= date('H:i') ?></p>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-print mr-2"></i> Print
                    </button>
                    <button onclick="exportToPDF()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-file-pdf mr-2"></i> Export PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="mb-8 bg-white rounded-xl shadow-lg p-6">
            <h3 class="font-bold text-gray-800 mb-4">Legend</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex items-center space-x-3 p-3 bg-blue-50 rounded-lg">
                    <div class="h-8 w-8 rounded bg-blue-500"></div>
                    <div>
                        <p class="font-medium">Assigned Class</p>
                        <p class="text-sm text-gray-600">Your regular teaching slot</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3 p-3 bg-green-50 rounded-lg">
                    <div class="h-8 w-8 rounded bg-green-500"></div>
                    <div>
                        <p class="font-medium">Swapped Class</p>
                        <p class="text-sm text-gray-600">Covering for another teacher</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3 p-3 bg-purple-50 rounded-lg">
                    <div class="h-8 w-8 rounded bg-purple-500"></div>
                    <div>
                        <p class="font-medium">Lab Session</p>
                        <p class="text-sm text-gray-600">Laboratory practical class</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weekly Timetable -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider bg-blue-100">
                                Time / Day
                            </th>
                            <?php foreach ($days as $day): 
                                $is_today = $day == $current_day;
                            ?>
                            <th class="px-6 py-4 text-center text-sm font-bold text-gray-900 uppercase tracking-wider <?= $is_today ? 'bg-yellow-50 border-l border-r border-yellow-200' : '' ?>">
                                <div class="flex flex-col items-center">
                                    <span><?= $day ?></span>
                                    <?php if ($is_today): ?>
                                    <span class="text-xs text-yellow-600 font-normal mt-1">TODAY</span>
                                    <?php endif; ?>
                                </div>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <!-- Time slots from 8:00 AM to 5:00 PM -->
                        <?php for ($hour = 8; $hour <= 17; $hour++): 
                            $time_slot_start = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                            $time_slot_end = str_pad($hour + 1, 2, '0', STR_PAD_LEFT) . ':00';
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 bg-gray-50">
                                <div class="text-center">
                                    <div class="font-bold"><?= date('g:i A', strtotime($time_slot_start)) ?></div>
                                    <div class="text-xs text-gray-500">to</div>
                                    <div class="font-bold"><?= date('g:i A', strtotime($time_slot_end)) ?></div>
                                </div>
                            </td>
                            
                            <?php foreach ($days as $day): 
                                $has_class = false;
                                $class = null;
                                
                                foreach ($timetable[$day] as $slot) {
                                    $slot_start = $slot['start_time'];
                                    $slot_end = $slot['end_time'];
                                    
                                    // Check if slot overlaps with this time slot
                                    if ($slot_start < $time_slot_end && $slot_end > $time_slot_start) {
                                        $has_class = true;
                                        $class = $slot;
                                        break;
                                    }
                                }
                            ?>
                            <td class="px-4 py-3 whitespace-nowrap <?= $day == $current_day ? 'bg-yellow-50' : '' ?>">
                                <?php if ($has_class && $class): 
                                    $is_assigned = $class['assignment_type'] == 'ASSIGNED';
                                    $is_lab = $class['is_lab'];
                                    $rowspan = ceil((strtotime($class['end_time']) - strtotime($class['start_time'])) / 3600);
                                ?>
                                <div class="border-l-4 <?= $is_assigned ? 'border-blue-500' : 'border-green-500' ?> p-3 rounded-r-lg h-full
                                    <?= $is_assigned ? 'bg-blue-50' : 'bg-green-50' ?> 
                                    <?= $is_lab ? 'border-dashed' : '' ?>">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h4 class="font-bold text-gray-800 text-sm">
                                                <?= htmlspecialchars($class['code']) ?>
                                                <?php if ($is_lab): ?>
                                                <span class="ml-2 px-1.5 py-0.5 text-xs bg-purple-100 text-purple-800 rounded">LAB</span>
                                                <?php endif; ?>
                                            </h4>
                                            <p class="text-xs text-gray-600 truncate"><?= htmlspecialchars($class['name']) ?></p>
                                            <div class="mt-2 space-y-1 text-xs">
                                                <div class="flex items-center text-gray-700">
                                                    <i class="fas fa-users mr-1.5 w-4"></i>
                                                    Sec <?= htmlspecialchars($class['section']) ?>
                                                    <?php if ($class['batch'] && $class['batch'] != 'all'): ?>
                                                    | Batch <?= htmlspecialchars($class['batch']) ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex items-center text-gray-700">
                                                    <i class="fas fa-door-closed mr-1.5 w-4"></i>
                                                    <?= htmlspecialchars($class['room'] ?: 'N/A') ?>
                                                </div>
                                                <div class="flex items-center text-gray-700">
                                                    <i class="far fa-clock mr-1.5 w-4"></i>
                                                    <?= $class['start_time'] ?> - <?= $class['end_time'] ?>
                                                </div>
                                                <?php if (!$is_assigned && $class['swap_requester_name']): ?>
                                                <div class="flex items-center text-green-700">
                                                    <i class="fas fa-exchange-alt mr-1.5 w-4"></i>
                                                    Swapped with <?= htmlspecialchars($class['swap_requester_name']) ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($day == $current_day && strtotime($class['start_time']) <= strtotime($current_time) && strtotime($class['end_time']) >= strtotime($current_time)): ?>
                                        <span class="ml-2 px-2 py-1 text-xs font-bold bg-red-100 text-red-800 rounded-full">
                                            LIVE
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="h-16 flex items-center justify-center">
                                    <span class="text-gray-400 text-sm">—</span>
                                </div>
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Today's Classes Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Today's Classes -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-calendar-day text-blue-600 mr-3"></i>
                    Today's Classes (<?= $current_day ?>)
                </h3>
                
                <?php 
                $today_classes = $timetable[$current_day] ?? [];
                $current_class = null;
                $upcoming_classes = [];
                
                foreach ($today_classes as $class) {
                    $start_time = strtotime($class['start_time']);
                    $end_time = strtotime($class['end_time']);
                    $current_time_ts = strtotime($current_time);
                    
                    if ($start_time <= $current_time_ts && $end_time >= $current_time_ts) {
                        $current_class = $class;
                    } elseif ($start_time > $current_time_ts) {
                        $upcoming_classes[] = $class;
                    }
                }
                ?>
                
                <?php if ($current_class): ?>
                <div class="mb-8 p-5 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl text-white">
                    <div class="flex items-center mb-4">
                        <div class="h-12 w-12 rounded-full bg-white bg-opacity-20 flex items-center justify-center mr-4">
                            <i class="fas fa-broadcast-tower text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-xl font-bold">Currently Teaching</h4>
                            <p class="text-blue-100"><?= htmlspecialchars($current_class['code']) ?>: <?= htmlspecialchars($current_class['name']) ?></p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold"><?= $current_class['start_time'] ?> - <?= $current_class['end_time'] ?></div>
                            <div class="text-sm">Room <?= htmlspecialchars($current_class['room'] ?: 'N/A') ?></div>
                        </div>
                    </div>
                    <a href="live_session.php" class="inline-block mt-3 bg-white text-blue-600 px-6 py-2 rounded-lg font-bold hover:bg-blue-50 transition">
                        <i class="fas fa-play-circle mr-2"></i> Go to Live Session
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="space-y-4">
                    <?php if (empty($today_classes)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-coffee text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900">No classes today!</h3>
                        <p class="text-gray-500">Enjoy your free time.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($today_classes as $class): 
                            $is_current = ($class['slot_id'] == ($current_class['slot_id'] ?? null));
                            $is_upcoming = strtotime($class['start_time']) > strtotime($current_time);
                        ?>
                        <div class="border border-gray-200 rounded-xl p-5 hover:shadow-md transition <?= $is_current ? 'border-blue-500 bg-blue-50' : '' ?>">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="font-bold text-gray-800"><?= htmlspecialchars($class['code']) ?>: <?= htmlspecialchars($class['name']) ?></h4>
                                    <p class="text-sm text-gray-600">
                                        <?php if ($class['assignment_type'] == 'SWAPPED'): ?>
                                        <span class="text-green-600">
                                            <i class="fas fa-exchange-alt mr-1"></i> Swapped
                                        </span>
                                        <?php else: ?>
                                        <span class="text-blue-600">
                                            <i class="fas fa-user-tie mr-1"></i> Assigned
                                        </span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-medium text-gray-800">
                                        <?= $class['start_time'] ?> - <?= $class['end_time'] ?>
                                    </span>
                                    <div class="mt-1">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            <?= $is_current ? 'bg-blue-100 text-blue-800' : 
                                               ($is_upcoming ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') ?>">
                                            <?= $is_current ? 'IN PROGRESS' : ($is_upcoming ? 'UPCOMING' : 'COMPLETED') ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center text-sm text-gray-500 space-x-4">
                                <span>
                                    <i class="fas fa-door-open mr-1"></i>
                                    Room: <?= htmlspecialchars($class['room'] ?: 'N/A') ?>
                                </span>
                                <span>
                                    <i class="fas fa-users mr-1"></i>
                                    Sec <?= htmlspecialchars($class['section']) ?>
                                    <?php if ($class['batch'] && $class['batch'] != 'all'): ?>
                                    | Batch <?= htmlspecialchars($class['batch']) ?>
                                    <?php endif; ?>
                                </span>
                                <?php if ($is_current): ?>
                                <a href="live_session.php" class="ml-auto text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center">
                                    <i class="fas fa-play-circle mr-1"></i> Start Session
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Weekly Summary -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-chart-bar text-green-600 mr-3"></i>
                    Weekly Summary
                </h3>
                
                <div class="space-y-6">
                    <!-- Day-wise count -->
                    <div>
                        <h4 class="font-bold text-gray-700 mb-4">Classes per Day</h4>
                        <div class="space-y-3">
                            <?php foreach ($days as $day): 
                                $class_count = count($timetable[$day] ?? []);
                                $is_today = $day == $current_day;
                            ?>
                            <div class="flex items-center justify-between">
                                <span class="font-medium <?= $is_today ? 'text-blue-600' : 'text-gray-700' ?>">
                                    <?= $day ?>
                                    <?php if ($is_today): ?>
                                    <span class="text-xs text-blue-500 ml-1">(Today)</span>
                                    <?php endif; ?>
                                </span>
                                <div class="flex items-center">
                                    <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                                        <div class="bg-blue-600 h-2 rounded-full" 
                                             style="width: <?= min($class_count * 20, 100) ?>%"></div>
                                    </div>
                                    <span class="font-bold <?= $class_count > 0 ? 'text-gray-800' : 'text-gray-400' ?>">
                                        <?= $class_count ?> class<?= $class_count != 1 ? 'es' : '' ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t">
                        <div class="text-center p-4 bg-blue-50 rounded-xl">
                            <?php 
                            $total_classes = 0;
                            foreach ($days as $day) {
                                $total_classes += count($timetable[$day] ?? []);
                            }
                            ?>
                            <p class="text-3xl font-bold text-blue-600"><?= $total_classes ?></p>
                            <p class="text-sm text-gray-600">Total Weekly Classes</p>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-xl">
                            <?php 
                            $lab_count = 0;
                            foreach ($timetable as $day_slots) {
                                foreach ($day_slots as $slot) {
                                    if ($slot['is_lab']) $lab_count++;
                                }
                            }
                            ?>
                            <p class="text-3xl font-bold text-green-600"><?= $lab_count ?></p>
                            <p class="text-sm text-gray-600">Lab Sessions</p>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="pt-4 border-t">
                        <h4 class="font-bold text-gray-700 mb-3">Quick Actions</h4>
                        <div class="space-y-2">
                            <a href="swap_dashboard.php" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                <i class="fas fa-exchange-alt text-green-600 mr-3"></i>
                                <div>
                                    <p class="font-medium">Request Swap</p>
                                    <p class="text-xs text-gray-500">Swap classes with colleagues</p>
                                </div>
                            </a>
                            <a href="live_session.php" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                <i class="fas fa-play-circle text-blue-600 mr-3"></i>
                                <div>
                                    <p class="font-medium">Start Live Session</p>
                                    <p class="text-xs text-gray-500">Begin attendance for current class</p>
                                </div>
                            </a>
                            <a href="teacher_report.php" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                <i class="fas fa-chart-pie text-purple-600 mr-3"></i>
                                <div>
                                    <p class="font-medium">View Reports</p>
                                    <p class="text-xs text-gray-500">Attendance analytics</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit' });
            document.getElementById('currentTime').textContent = timeString;
        }
        setInterval(updateTime, 1000);
        
        // Export to PDF (placeholder)
        function exportToPDF() {
            alert('PDF export functionality will be implemented soon.');
        }
        
        // Auto-refresh page every 5 minutes to update timetable
        setTimeout(() => {
            location.reload();
        }, 5 * 60 * 1000); // 5 minutes
    </script>
</body>
</html>