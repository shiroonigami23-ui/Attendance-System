<?php
require_once '../includes/Config.php';
require_once '../includes/Auth.php';
require_once '../includes/SessionManager.php';

checkAuth();
if ($_SESSION['role'] != 'STUDENT') {
    header('Location: ../dashboard.php');
    exit();
}

$user = getCurrentUser();
$user_id = $user['user_id'];

// Auto-end expired sessions
$expired_count = SessionManager::checkAndEndExpiredSessions();
if ($expired_count > 0) {
    // Optional: add a message
    $_SESSION['auto_end_message'] = "Auto-ended $expired_count expired sessions.";
}

// Rest of your existing student dashboard code continues here...
// Get student stats
$stmt = $pdo->prepare("
    SELECT 
        sp.roll_no,
        sp.current_semester,
        sp.section,
        sp.branch_code,
        COALESCE(sas.total_sessions, 0) as total_sessions,
        COALESCE(sas.present_count, 0) as present_count,
        COALESCE(sas.late_count, 0) as late_count,
        COALESCE(sas.absent_count, 0) as absent_count,
        CASE 
            WHEN COALESCE(sas.total_sessions, 0) = 0 THEN 0
            ELSE ROUND(((COALESCE(sas.present_count, 0) + (COALESCE(sas.late_count, 0) * 0.5)) / sas.total_sessions) * 100, 1)
        END as attendance_percentage
    FROM student_profiles sp
    LEFT JOIN student_attendance_summary sas ON sp.user_id = sas.student_id
    WHERE sp.user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Get today's classes
$today = date('Y-m-d');
$day_of_week = date('l');
$stmt = $pdo->prepare("
    SELECT ts.*, s.code, s.name, s.subject_id,
           u.full_name as teacher_name,
           ls.status as session_status,
           ls.session_id,
           al.status as attendance_status
    FROM timetable_slots ts
    INNER JOIN subjects s ON ts.subject_id = s.subject_id
    INNER JOIN users u ON ts.default_teacher_id = u.user_id
    LEFT JOIN live_sessions ls ON ts.slot_id = ls.slot_id AND ls.session_date = ?
    LEFT JOIN attendance_logs al ON ls.session_id = al.session_id AND al.student_id = ?
    WHERE JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.section')) = ?
    AND ts.day_of_week = ?
    AND ? BETWEEN ts.valid_from AND ts.valid_until
    ORDER BY ts.start_time
");
$stmt->execute([
    $today,
    $user_id,
    $user['section'] ?? 'A',
    $day_of_week,
    $today
]);
$todays_classes = $stmt->fetchAll();

// Check if any live session is active right now
$current_time = date('H:i:s');
$active_session = null;
foreach ($todays_classes as $class) {
    if ($class['start_time'] <= $current_time && $class['end_time'] >= $current_time && $class['session_status'] == 'LIVE') {
        $active_session = $class;
        break;
    }
}

// Get pending leave applications submitted by this student
$stmt = $pdo->prepare("
    SELECT lr.*, 
           (SELECT COUNT(*) FROM leave_requests WHERE student_id = ? AND approval_status = 'PENDING') as pending_count
    FROM leave_requests lr
    WHERE lr.student_id = ?
    AND lr.approval_status = 'PENDING'
    ORDER BY lr.date_from
    LIMIT 3
");
$stmt->execute([$user_id, $user_id]);
$pending_leaves = $stmt->fetchAll();
$pending_leave_count = $pending_leaves[0]['pending_count'] ?? 0;

// Get attendance summary by subject
$stmt = $pdo->prepare("
    SELECT s.code, s.name, 
           COUNT(DISTINCT ls.session_id) as total_sessions,
           SUM(CASE WHEN al.status = 'PRESENT' THEN 1 ELSE 0 END) as present_count,
           SUM(CASE WHEN al.status = 'LATE' THEN 1 ELSE 0 END) as late_count,
           SUM(CASE WHEN al.status = 'ABSENT' THEN 1 ELSE 0 END) as absent_count,
           CASE 
               WHEN COUNT(DISTINCT ls.session_id) = 0 THEN 0
               ELSE ROUND((SUM(CASE WHEN al.status = 'PRESENT' THEN 1 ELSE 0 END) + 
                          SUM(CASE WHEN al.status = 'LATE' THEN 1 ELSE 0 END) * 0.5) / 
                          COUNT(DISTINCT ls.session_id) * 100, 1)
           END as attendance_percentage
    FROM timetable_slots ts
    INNER JOIN subjects s ON ts.subject_id = s.subject_id
    LEFT JOIN live_sessions ls ON ts.slot_id = ls.slot_id AND ls.status = 'COMPLETED'
    LEFT JOIN attendance_logs al ON ls.session_id = al.session_id AND al.student_id = ?
    WHERE JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.section')) = ?
    GROUP BY s.subject_id
    ORDER BY s.code
");
$stmt->execute([$user_id, $user['section'] ?? 'A']);
$subject_attendance = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Campus Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .attendance-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
            transition: all 0.3s;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: 600;
        }

        .status-present {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-late {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-absent {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-upcoming {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .pulse-live {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .progress-bar {
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            transition: width 0.5s ease;
        }
    </style>
</head>
<!-- Navbar -->
<nav class="glass-card shadow-lg sticky top-0 z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0 flex items-center">
                    <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-blue-600 flex items-center justify-center mr-3">
                        <i class="fas fa-user-graduate text-white text-xl"></i>
                    </div>
                    <div>
                        <span class="text-xl font-bold text-gray-800">Student Portal</span>
                        <p class="text-xs text-gray-500">Attendance System</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-6">
                <a href="../dashboard.php" class="text-gray-700 hover:text-blue-600 transition">
                    <i class="fas fa-home mr-1"></i> Home
                </a>
                <?php
                // Get profile photo or default
                $profile_photo = $user['profile_photo'] ?? null;
                $avatar_url = $profile_photo ? htmlspecialchars($profile_photo) :
                    'https://ui-avatars.com/api/?name=' . urlencode($user['full_name'] ?? $user['email']) . '&background=random&color=ffffff';
                ?>
                <div class="flex items-center space-x-3">
                    <a href="../profile.php" class="relative group">
                        <img src="<?= $avatar_url ?>"
                            alt="Profile"
                            class="h-10 w-10 rounded-full border-2 border-white shadow-sm hover:border-blue-400 transition">
                        <span class="absolute -bottom-1 -right-1 bg-blue-500 text-white text-xs px-1 py-0.5 rounded-full opacity-0 group-hover:opacity-100 transition">
                            <i class="fas fa-user"></i>
                        </span>
                    </a>
                    <div class="text-right">
                        <p class="font-medium text-gray-800"><?= htmlspecialchars($user['full_name'] ?? 'Student') ?></p>
                        <span class="text-xs text-gray-500"><?= htmlspecialchars($user['roll_no'] ?? 'Roll No') ?></span>
                    </div>
                </div>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Welcome & Attendance Summary -->
    <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Welcome, <?= htmlspecialchars($user['full_name'] ?? 'Student') ?>!</h1>
                <div class="flex items-center text-gray-600 mt-2 space-x-6">
                    <span class="flex items-center">
                        <i class="fas fa-id-card mr-2"></i>
                        <?= htmlspecialchars($stats['roll_no'] ?? 'N/A') ?>
                    </span>
                    <span class="flex items-center">
                        <i class="fas fa-graduation-cap mr-2"></i>
                        Semester <?= $stats['current_semester'] ?? 'N/A' ?>
                    </span>
                    <span class="flex items-center">
                        <i class="fas fa-users mr-2"></i>
                        Section <?= $stats['section'] ?? 'N/A' ?>
                    </span>
                    <span class="flex items-center">
                        <i class="fas fa-code-branch mr-2"></i>
                        <?= htmlspecialchars($stats['branch_code'] ?? 'N/A') ?>
                    </span>
                </div>
            </div>
            <!-- Live Session Alert -->
            <?php if ($active_session): ?>
                <div class="bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl p-4 flex items-center pulse-live">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center mr-3">
                        <i class="fas fa-broadcast-tower text-white"></i>
                    </div>
                    <div>
                        <p class="font-bold">Live Session Active!</p>
                        <p class="text-sm text-green-100"><?= htmlspecialchars($active_session['code']) ?> - Scan QR Now</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Overall Attendance -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="text-center">
                    <?php
                    $percentage = $stats['attendance_percentage'] ?? 0;
                    $circle_color = $percentage >= 75 ? 'from-green-400 to-green-500' : ($percentage >= 60 ? 'from-yellow-400 to-yellow-500' :
                            'from-red-400 to-red-500');
                    ?>
                    <div class="attendance-circle bg-gradient-to-r <?= $circle_color ?> text-white mx-auto mb-4">
                        <?= number_format($percentage, 1) ?>%
                    </div>
                    <h3 class="font-bold text-gray-800 mb-1">Overall Attendance</h3>
                    <p class="text-sm text-gray-600">
                        <?= $stats['present_count'] ?? 0 ?> Present,
                        <?= $stats['late_count'] ?? 0 ?> Late,
                        <?= $stats['absent_count'] ?? 0 ?> Absent
                    </p>
                </div>
            </div>

            <!-- Today's Classes -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                        <i class="fas fa-calendar-day text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800">Today's Classes</h3>
                        <p class="text-sm text-gray-600"><?= date('l, F j') ?></p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-800 mb-2"><?= count($todays_classes) ?></div>
                    <p class="text-sm text-gray-600">Classes scheduled</p>
                    <?php if (count($todays_classes) > 0): ?>
                        <div class="mt-3 text-xs text-gray-500">
                            <?php
                            $attended_today = 0;
                            foreach ($todays_classes as $class) {
                                if ($class['attendance_status'] == 'PRESENT' || $class['attendance_status'] == 'LATE') {
                                    $attended_today++;
                                }
                            }
                            ?>
                            <span class="text-green-600 font-medium"><?= $attended_today ?> attended</span> •
                            <span class="text-red-600"><?= count($todays_classes) - $attended_today ?> pending</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pending Leaves -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center mr-3">
                        <i class="fas fa-file-medical text-yellow-600"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800">Leave Applications</h3>
                        <p class="text-sm text-gray-600">Pending approval</p> <!-- Updated text -->
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold <?= $pending_leave_count > 0 ? 'text-yellow-600' : 'text-gray-800' ?> mb-2">
                        <?= $pending_leave_count ?>
                    </div>
                    <p class="text-sm text-gray-600">Applications pending</p> <!-- Updated text -->
                    <?php if ($pending_leave_count > 0): ?>
                        <div class="mt-3">
                            <a href="#" class="text-yellow-600 hover:text-yellow-700 text-sm font-medium">
                                <i class="fas fa-eye mr-1"></i> View Applications
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Device Status -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center mb-4">
                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
                        <i class="fas fa-mobile-alt text-green-600"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800">Device Status</h3>
                        <p class="text-sm text-gray-600">Hardware locked</p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600 mb-2">
                        <i class="fas fa-lock"></i>
                    </div>
                    <p class="text-sm text-gray-600 mb-3">This device is bound</p>
                    <div class="text-xs text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Contact admin for device change
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- QR Scanner -->
            <a href="scanner.php" class="group bg-white rounded-2xl shadow-lg p-6 border border-gray-200 hover:border-blue-300 transition">
                <div class="h-16 w-16 rounded-xl bg-gradient-to-r from-blue-100 to-cyan-100 flex items-center justify-center mb-4 group-hover:from-blue-200 group-hover:to-cyan-200 transition">
                    <i class="fas fa-qrcode text-blue-600 text-2xl"></i>
                </div>
                <h3 class="font-bold text-lg text-gray-800 mb-2">Scan QR Code</h3>
                <p class="text-gray-600 text-sm">Mark attendance for current class</p>
                <?php if ($active_session): ?>
                    <div class="mt-4 text-xs text-green-600 font-medium">
                        <i class="fas fa-circle text-xs mr-1"></i> Session Active - Scan Now!
                    </div>
                <?php else: ?>
                    <div class="mt-4 text-xs text-gray-500">
                        <i class="far fa-clock mr-1"></i> Wait for live session
                    </div>
                <?php endif; ?>
            </a>

            <!-- Timetable -->
            <a href="timetable.php" class="group bg-white rounded-2xl shadow-lg p-6 border border-gray-200 hover:border-green-300 transition">
                <div class="h-16 w-16 rounded-xl bg-gradient-to-r from-green-100 to-emerald-100 flex items-center justify-center mb-4 group-hover:from-green-200 group-hover:to-emerald-200 transition">
                    <i class="fas fa-calendar-alt text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-bold text-lg text-gray-800 mb-2">View Timetable</h3>
                <p class="text-gray-600 text-sm">Check your weekly schedule</p>
                <div class="mt-4 text-xs text-green-600 font-medium">
                    <i class="fas fa-arrow-right mr-1"></i> View Schedule
                </div>
            </a>

            <!-- Apply Leave -->
            <a href="leave_apply.php" class="group bg-white rounded-2xl shadow-lg p-6 border border-gray-200 hover:border-yellow-300 transition">
                <div class="h-16 w-16 rounded-xl bg-gradient-to-r from-yellow-100 to-amber-100 flex items-center justify-center mb-4 group-hover:from-yellow-200 group-hover:to-amber-200 transition">
                    <i class="fas fa-file-medical text-yellow-600 text-2xl"></i>
                </div>
                <h3 class="font-bold text-lg text-gray-800 mb-2">Apply for Leave</h3>
                <p class="text-gray-600 text-sm">Submit leave request with document</p>
                <div class="mt-4 text-xs text-yellow-600 font-medium">
                    <i class="fas fa-arrow-right mr-1"></i> Request Leave
                </div>
            </a>

            <!-- Attendance Report -->
            <a href="report.php" class="group bg-white rounded-2xl shadow-lg p-6 border border-gray-200 hover:border-purple-300 transition">
                <div class="h-16 w-16 rounded-xl bg-gradient-to-r from-purple-100 to-pink-100 flex items-center justify-center mb-4 group-hover:from-purple-200 group-hover:to-pink-200 transition">
                    <i class="fas fa-chart-bar text-purple-600 text-2xl"></i>
                </div>
                <h3 class="font-bold text-lg text-gray-800 mb-2">Attendance Report</h3>
                <p class="text-gray-600 text-sm">View detailed attendance analytics</p>
                <div class="mt-4 text-xs text-purple-600 font-medium">
                    <i class="fas fa-arrow-right mr-1"></i> View Reports
                </div>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Today's Schedule -->
        <div>
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="far fa-calendar-alt text-blue-600 mr-3"></i> Today's Schedule
                    <span class="ml-auto text-sm font-normal text-gray-500">
                        <?= date('l, F j, Y') ?>
                    </span>
                </h2>

                <?php if (empty($todays_classes)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-coffee text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900">No classes today!</h3>
                        <p class="text-gray-500">Enjoy your free time.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($todays_classes as $class):
                            $current_time = time();
                            $class_start = strtotime($class['start_time']);
                            $class_end = strtotime($class['end_time']);
                            $is_active = ($current_time >= $class_start && $current_time <= $class_end);
                            $is_upcoming = ($current_time < $class_start);
                            $is_completed = ($current_time > $class_end);

                            $status_class = 'status-upcoming';
                            $status_text = 'UPCOMING';
                            $status_icon = 'far fa-clock';

                            if ($is_active) {
                                $status_class = $class['session_status'] == 'LIVE' ? 'status-present' : 'status-upcoming';
                                $status_text = $class['session_status'] == 'LIVE' ? 'LIVE NOW' : 'IN PROGRESS';
                                $status_icon = $class['session_status'] == 'LIVE' ? 'fas fa-broadcast-tower' : 'fas fa-play-circle';
                            } elseif ($is_completed) {
                                if ($class['attendance_status'] == 'PRESENT') {
                                    $status_class = 'status-present';
                                    $status_text = 'PRESENT';
                                    $status_icon = 'fas fa-check-circle';
                                } elseif ($class['attendance_status'] == 'LATE') {
                                    $status_class = 'status-late';
                                    $status_text = 'LATE';
                                    $status_icon = 'fas fa-clock';
                                } elseif ($class['attendance_status'] == 'ABSENT') {
                                    $status_class = 'status-absent';
                                    $status_text = 'ABSENT';
                                    $status_icon = 'fas fa-times-circle';
                                } else {
                                    $status_class = 'status-absent';
                                    $status_text = 'NOT MARKED';
                                    $status_icon = 'fas fa-question-circle';
                                }
                            }
                        ?>
                            <div class="border border-gray-200 rounded-xl p-5 hover:shadow-md transition">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-bold text-gray-800"><?= htmlspecialchars($class['code']) ?>: <?= htmlspecialchars($class['name']) ?></h4>
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($class['teacher_name']) ?></p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm font-medium text-gray-800">
                                            <?= date('h:i A', $class_start) ?> - <?= date('h:i A', $class_end) ?>
                                        </span>
                                        <div class="mt-1">
                                            <span class="status-badge <?= $status_class ?>">
                                                <i class="<?= $status_icon ?> mr-1"></i> <?= $status_text ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                $target = json_decode($class['target_group'] ?? '{}', true);
                                $room = $target['room'] ?? 'N/A';
                                ?>
                                <div class="flex items-center text-sm text-gray-500 space-x-4">
                                    <span>
                                        <i class="fas fa-door-open mr-1"></i>
                                        Room: <?= htmlspecialchars($room) ?>
                                    </span>
                                    <?php if ($is_active && $class['session_status'] == 'LIVE'): ?>
                                        <a href="scanner.php" class="ml-auto text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center">
                                            <i class="fas fa-qrcode mr-1"></i> Scan QR
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Subject-wise Attendance -->
        <div>
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-chart-pie text-purple-600 mr-3"></i> Subject-wise Attendance
                    <span class="ml-auto text-sm font-normal text-gray-500">
                        Current Semester
                    </span>
                </h2>

                <?php if (empty($subject_attendance)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-book-open text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900">No attendance data</h3>
                        <p class="text-gray-500">Attendance records will appear after classes.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto pb-4 -mx-2 px-2">
                        <div class="flex space-x-4 min-w-max">
                            <?php foreach ($subject_attendance as $subject):
                                $percentage = $subject['attendance_percentage'] ?? 0;
                                $bar_color = $percentage >= 75 ? 'bg-green-500' : ($percentage >= 60 ? 'bg-yellow-500' :
                                        'bg-red-500');
                                $text_color = $percentage >= 75 ? 'text-green-600' : ($percentage >= 60 ? 'text-yellow-600' :
                                        'text-red-600');
                            ?>
                                <div class="w-64 flex-shrink-0 bg-white border border-gray-200 rounded-xl p-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <div>
                                            <h4 class="font-bold text-gray-800"><?= htmlspecialchars($subject['code']) ?></h4>
                                            <p class="text-sm text-gray-600"><?= htmlspecialchars($subject['name']) ?></p>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-bold <?= $text_color ?>">
                                                <?= number_format($percentage, 1) ?>%
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?= $subject['present_count'] ?>P, <?= $subject['late_count'] ?>L, <?= $subject['absent_count'] ?>A
                                            </div>
                                        </div>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                                        <div class="h-2.5 rounded-full <?= $bar_color ?>" style="width: <?= min($percentage, 100) ?>%"></div>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-500">
                                        <span>0%</span>
                                        <span>35%</span>
                                        <span>75%</span>
                                        <span>100%</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <div class="text-2xl font-bold text-green-600">
                                    <?= array_reduce($subject_attendance, function ($carry, $item) {
                                        return $carry + ($item['attendance_percentage'] >= 75 ? 1 : 0);
                                    }, 0) ?>
                                </div>
                                <div class="text-xs text-gray-600">Above 75%</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-yellow-600">
                                    <?= array_reduce($subject_attendance, function ($carry, $item) {
                                        return $carry + ($item['attendance_percentage'] >= 60 && $item['attendance_percentage'] < 75 ? 1 : 0);
                                    }, 0) ?>
                                </div>
                                <div class="text-xs text-gray-600">60-75%</div>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-red-600">
                                    <?= array_reduce($subject_attendance, function ($carry, $item) {
                                        return $carry + ($item['attendance_percentage'] < 60 ? 1 : 0);
                                    }, 0) ?>
                                </div>
                                <div class="text-xs text-gray-600">Below 60%</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Quick Links -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mt-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-link text-gray-600 mr-3"></i> Quick Links
            </h2>
            <div class="grid grid-cols-2 gap-4">
                <a href="../dashboard.php" class="p-4 border border-gray-200 rounded-xl hover:bg-gray-50 transition flex items-center">
                    <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center mr-3">
                        <i class="fas fa-home text-gray-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">Main Dashboard</p>
                        <p class="text-xs text-gray-500">System Home</p>
                    </div>
                </a>
                <a href="../profile.php" class="p-4 border border-gray-200 rounded-xl hover:bg-gray-50 transition flex items-center">
                    <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                        <i class="fas fa-user text-blue-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">My Profile</p>
                        <p class="text-xs text-gray-500">Edit details</p>
                    </div>
                </a>
                <a href="../logout.php" class="p-4 border border-gray-200 rounded-xl hover:bg-red-50 transition flex items-center">
                    <div class="h-10 w-10 rounded-lg bg-red-100 flex items-center justify-center mr-3">
                        <i class="fas fa-sign-out-alt text-red-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">Logout</p>
                        <p class="text-xs text-gray-500">Sign out</p>
                    </div>
                </a>
                <a href="#" class="p-4 border border-gray-200 rounded-xl hover:bg-gray-50 transition flex items-center">
                    <div class="h-10 w-10 rounded-lg bg-purple-100 flex items-center justify-center mr-3">
                        <i class="fas fa-question-circle text-purple-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">Help</p>
                        <p class="text-xs text-gray-500">Support & FAQ</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Footer -->
<footer class="bg-gray-800 text-white py-8 mt-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h3 class="font-bold mb-4">Student Portal</h3>
                <p class="text-gray-400 text-sm">
                    Advanced attendance tracking and class management system for students.
                </p>
            </div>
            <div>
                <h3 class="font-bold mb-4">Important Links</h3>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="scanner.php" class="hover:text-white transition">QR Scanner</a></li>
                    <li><a href="timetable.php" class="hover:text-white transition">Timetable</a></li>
                    <li><a href="logout.php" class="hover:text-white transition">Leave Application</a></li>
                    <li><a href="attendance_report.php" class="hover:text-white transition">Attendance Report</a></li>
                </ul>
            </div>
            <div>
                <h3 class="font-bold mb-4">Contact Support</h3>
                <p class="text-gray-400 text-sm">
                    <i class="fas fa-envelope mr-2"></i> student-support@campus.edu<br>
                    <i class="fas fa-phone mr-2"></i> +91-XXX-XXX-XXXX<br>
                    <i class="fas fa-clock mr-2"></i> Mon-Fri: 9AM-5PM
                </p>
            </div>
        </div>
        <div class="text-center mt-8 pt-8 border-t border-gray-700">
            <p class="text-gray-400 text-sm">
                <i class="fas fa-user-graduate mr-2"></i>
                Student Dashboard | Advanced Campus Attendance System v3.0
            </p>
            <p class="text-gray-500 text-xs mt-2">
                Device: <?= substr($_SESSION['device_fp'] ?? 'Unknown', 0, 12) ?>... |
                IP: <?= htmlspecialchars($_SERVER['REMOTE_ADDR']) ?> |
                Session: <?= date('h:i A') ?>
            </p>
        </div>
    </div>
</footer>

<script>
    // Auto-refresh page every 30 seconds to update live status
    setTimeout(() => {
        window.location.reload();
    }, 30000);

    // Highlight active session card
    document.addEventListener('DOMContentLoaded', function() {
        const scannerCard = document.querySelector('a[href="scanner.php"]');
        const activeSession = <?= $active_session ? 'true' : 'false' ?>;

        if (activeSession && scannerCard) {
            scannerCard.classList.add('ring-2', 'ring-green-500');
            scannerCard.classList.remove('hover:border-blue-300');
            scannerCard.classList.add('hover:border-green-300');

            // Add pulsing effect
            const iconDiv = scannerCard.querySelector('.h-16');
            if (iconDiv) {
                iconDiv.classList.add('pulse-live');
                iconDiv.classList.remove('from-blue-100', 'to-cyan-100');
                iconDiv.classList.add('from-green-100', 'to-emerald-100');
            }
        }
    });
</script>
</body>

</html>