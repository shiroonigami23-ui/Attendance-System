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

// Auto-end expired sessions EVERY time dashboard loads
$expired_count = SessionManager::checkAndEndExpiredSessions();
if ($expired_count > 0 && empty($_SESSION['auto_end_message'])) {
    $_SESSION['auto_end_message'] = "Auto-ended $expired_count expired session(s).";
}

// Get teacher stats
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT ts.subject_id) as total_subjects,
        COUNT(DISTINCT ls.session_id) as total_sessions,
        SUM(CASE WHEN ls.status = 'LIVE' THEN 1 ELSE 0 END) as live_sessions,
        (SELECT COUNT(*) FROM swap_requests WHERE receiver_id = ? AND status = 'PENDING') as pending_swaps
    FROM timetable_slots ts
    LEFT JOIN live_sessions ls ON ts.slot_id = ls.slot_id
    WHERE ts.default_teacher_id = ?
");
$stmt->execute([$user_id, $user_id]);
$stats = $stmt->fetch();

// Get today's schedule
$today = date('Y-m-d');
$day_of_week = date('l');
$stmt = $pdo->prepare("
    SELECT ts.*, s.code, s.name, ts.start_time, ts.end_time
    FROM timetable_slots ts
    INNER JOIN subjects s ON ts.subject_id = s.subject_id
    WHERE ts.default_teacher_id = ?
    AND ts.day_of_week = ?
    AND ? BETWEEN ts.valid_from AND ts.valid_until
    ORDER BY ts.start_time
");
$stmt->execute([$user_id, $day_of_week, $today]);
$todays_schedule = $stmt->fetchAll();

// Get upcoming live sessions - FIXED: join with timetable_slots for time info
$stmt = $pdo->prepare("
    SELECT ls.*, s.code, s.name, ts.target_group, ts.start_time, ts.end_time
    FROM live_sessions ls
    INNER JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
    INNER JOIN subjects s ON ts.subject_id = s.subject_id
    WHERE ls.actual_teacher_id = ?
    AND ls.status = 'LIVE'
    ORDER BY ls.session_date, ts.start_time
");
$stmt->execute([$user_id]);
$live_sessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Campus Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stat-card {
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .schedule-card {
            border-left: 4px solid transparent;
            transition: all 0.2s;
        }

        .schedule-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .live-pulse {
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
    </style>
</head>

<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-chalkboard-teacher text-yellow-600 text-2xl mr-3"></i>
                        <span class="text-xl font-bold text-gray-800">Teacher Dashboard</span>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="../dashboard.php" class="text-gray-600 hover:text-blue-600 transition">
                        <i class="fas fa-home mr-1"></i> Home
                    </a>

                    <a href="../profile.php" class="relative group">
                        <img src="<?= $avatar_url ?>"
                            alt="Profile"
                            class="h-10 w-10 rounded-full border-2 border-white shadow-sm hover:border-blue-400 transition">
                        <span class="absolute -bottom-1 -right-1 bg-blue-500 text-white text-xs px-1 py-0.5 rounded-full opacity-0 group-hover:opacity-100 transition">
                            <i class="fas fa-user"></i>
                        </span>
                    </a>
                    <div class="text-right">
                        <p class="font-medium text-gray-800"><?= htmlspecialchars($user['full_name'] ?? 'Teacher') ?></p>
                        <span class="text-xs text-gray-500"><?= htmlspecialchars($user['email'] ?? '') ?></span>
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
        <!-- Welcome & Stats -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Welcome, <?= htmlspecialchars($user['full_name'] ?? 'Teacher') ?>!</h1>
                    <p class="text-gray-600">Manage your classes, attendance, and collaborate with colleagues</p>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="h-3 w-3 rounded-full bg-green-500 live-pulse"></div>
                    <span class="text-sm text-gray-600">Teacher Portal Active</span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="stat-card bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-full bg-white/20 flex items-center justify-center mr-4">
                            <i class="fas fa-book text-white text-xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold"><?= $stats['total_subjects'] ?? 0 ?></p>
                            <p class="text-blue-100">Subjects</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-full bg-white/20 flex items-center justify-center mr-4">
                            <i class="fas fa-calendar-check text-white text-xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold"><?= $stats['total_sessions'] ?? 0 ?></p>
                            <p class="text-green-100">Total Sessions</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-full bg-white/20 flex items-center justify-center mr-4">
                            <i class="fas fa-exchange-alt text-white text-xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold"><?= $stats['pending_swaps'] ?? 0 ?></p>
                            <p class="text-purple-100">Pending Swaps</p>
                        </div>
                    </div>
                </div>
                <div class="stat-card bg-gradient-to-br from-red-500 to-red-600 text-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-full bg-white/20 flex items-center justify-center mr-4">
                            <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold">
                                <?php
                                // Get red zone count
                                $stmt = $pdo->prepare("
                                    SELECT COUNT(DISTINCT sp.user_id) as red_zone_count
                                    FROM student_profiles sp
                                    INNER JOIN timetable_slots ts ON sp.section = JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.section'))
                                    LEFT JOIN student_attendance_summary sas ON sp.user_id = sas.student_id
                                    WHERE ts.default_teacher_id = ?
                                    AND (COALESCE(sas.total_sessions, 0) = 0 OR 
                                         ((COALESCE(sas.present_count, 0) + (COALESCE(sas.late_count, 0) * 0.5)) / NULLIF(sas.total_sessions, 0) * 100) < 35)
                                ");
                                $stmt->execute([$user_id]);
                                $red_zone = $stmt->fetch();
                                echo $red_zone['red_zone_count'] ?? 0;
                                ?>
                            </p>
                            <p class="text-red-100">Red Zone</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Sessions Alert -->
        <?php if (!empty($live_sessions)): ?>
            <div class="mb-8">
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-2xl p-6">
                    <div class="flex items-center mb-4">
                        <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
                            <i class="fas fa-broadcast-tower text-green-600"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Active Live Sessions</h3>
                            <p class="text-sm text-gray-600">You have <?= count($live_sessions) ?> active class<?= count($live_sessions) > 1 ? 'es' : '' ?></p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <?php foreach ($live_sessions as $session):
                            $target = json_decode($session['target_group'], true);
                        ?>
                            <div class="bg-white rounded-xl p-4 border border-green-100">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h4 class="font-bold text-gray-800"><?= htmlspecialchars($session['code']) ?>: <?= htmlspecialchars($session['name']) ?></h4>
                                        <div class="flex items-center text-sm text-gray-600 mt-1 space-x-4">
                                            <span>
                                                <i class="far fa-calendar mr-1"></i>
                                                <?= date('M j, Y', strtotime($session['session_date'])) ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-users mr-1"></i>
                                                Section <?= $target['section'] ?? 'A' ?>
                                            </span>
                                            <span class="text-green-600 font-medium">
                                                <i class="fas fa-circle text-xs mr-1"></i>
                                                LIVE
                                            </span>
                                        </div>
                                    </div>
                                    <a href="close_session.php?session_id=<?= $session['session_id'] ?>"
                                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-medium transition">
                                        <i class="fas fa-stop-circle mr-2"></i> End Session
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Start Live Session -->
                <a href="live_session.php" class="group bg-white rounded-2xl shadow-lg p-6 border border-gray-200 hover:border-purple-300 transition">
                    <div class="h-16 w-16 rounded-xl bg-gradient-to-r from-purple-100 to-pink-100 flex items-center justify-center mb-4 group-hover:from-purple-200 group-hover:to-pink-200 transition">
                        <i class="fas fa-play-circle text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-lg text-gray-800 mb-2">Start Live Session</h3>
                    <p class="text-gray-600 text-sm">Generate QR code for current class</p>
                    <div class="mt-4 text-xs text-purple-600 font-medium">
                        <i class="fas fa-arrow-right mr-1"></i> Start Attendance
                    </div>
                </a>

                <!-- Red Zone Alert -->
                <a href="red_zone.php" class="group bg-white rounded-2xl shadow-lg p-6 border border-gray-200 hover:border-red-300 transition">
                    <div class="h-16 w-16 rounded-xl bg-gradient-to-r from-red-100 to-orange-100 flex items-center justify-center mb-4 group-hover:from-red-200 group-hover:to-orange-200 transition">
                        <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-lg text-gray-800 mb-2">Red Zone Alert</h3>
                    <p class="text-gray-600 text-sm">View students with <35% attendance</p>
                            <div class="mt-4 text-xs text-red-600 font-medium">
                                <i class="fas fa-arrow-right mr-1"></i> Check Detentions
                            </div>
                </a>

                <!-- Class Swap -->
                <a href="swap_dashboard.php" class="group bg-white rounded-2xl shadow-lg p-6 border border-gray-200 hover:border-green-300 transition">
                    <div class="h-16 w-16 rounded-xl bg-gradient-to-r from-green-100 to-emerald-100 flex items-center justify-center mb-4 group-hover:from-green-200 group-hover:to-emerald-200 transition">
                        <i class="fas fa-exchange-alt text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-lg text-gray-800 mb-2">Class Swap</h3>
                    <p class="text-gray-600 text-sm">Request swap with another teacher</p>
                    <div class="mt-4 text-xs text-green-600 font-medium">
                        <i class="fas fa-arrow-right mr-1"></i> Collaborate
                    </div>
                </a>

                <!-- Manual Override -->
                <a href="manual_override.php" class="group bg-white rounded-2xl shadow-lg p-6 border border-gray-200 hover:border-orange-300 transition">
                    <div class="h-16 w-16 rounded-xl bg-gradient-to-r from-orange-100 to-amber-100 flex items-center justify-center mb-4 group-hover:from-orange-200 group-hover:to-amber-200 transition">
                        <i class="fas fa-hand-paper text-orange-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-lg text-gray-800 mb-2">Manual Override</h3>
                    <p class="text-gray-600 text-sm">Mark students present (24h window)</p>
                    <div class="mt-4 text-xs text-orange-600 font-medium">
                        <i class="fas fa-arrow-right mr-1"></i> Fix Attendance
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

                    <?php if (empty($todays_schedule)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-coffee text-4xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900">No classes today!</h3>
                            <p class="text-gray-500">Enjoy your free time.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($todays_schedule as $slot):
                                $target = json_decode($slot['target_group'], true);
                                $current_time = time();
                                $slot_start = strtotime($slot['start_time']);
                                $slot_end = strtotime($slot['end_time']);
                                $is_active = ($current_time >= $slot_start && $current_time <= $slot_end);
                                $is_upcoming = ($current_time < $slot_start);
                            ?>
                                <div class="schedule-card border border-gray-200 rounded-xl p-5 <?= $is_active ? 'border-l-green-500 bg-green-50' : ($is_upcoming ? 'border-l-blue-500' : 'border-l-gray-300') ?>">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h4 class="font-bold text-gray-800"><?= htmlspecialchars($slot['code']) ?></h4>
                                            <p class="text-sm text-gray-600"><?= htmlspecialchars($slot['name']) ?></p>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-medium text-gray-800">
                                                <?= date('h:i A', $slot_start) ?> - <?= date('h:i A', $slot_end) ?>
                                            </span>
                                            <?php if ($is_active): ?>
                                                <div class="text-xs text-green-600 font-medium mt-1">
                                                    <i class="fas fa-circle text-xs mr-1"></i> IN PROGRESS
                                                </div>
                                            <?php elseif ($is_upcoming): ?>
                                                <div class="text-xs text-blue-600 font-medium mt-1">
                                                    <i class="far fa-clock mr-1"></i> UPCOMING
                                                </div>
                                            <?php else: ?>
                                                <div class="text-xs text-gray-500 font-medium mt-1">
                                                    <i class="far fa-check-circle mr-1"></i> COMPLETED
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex items-center text-sm text-gray-500 space-x-4">
                                        <span>
                                            <i class="fas fa-users mr-1"></i>
                                            Section <?= htmlspecialchars($target['section'] ?? 'A') ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-door-open mr-1"></i>
                                            Room: <?= htmlspecialchars($target['room'] ?? 'N/A') ?>
                                        </span>
                                    </div>
                                    <?php if ($is_active || $is_upcoming): ?>
                                        <div class="mt-4 pt-4 border-t border-gray-100">
                                            <a href="live_session.php?slot_id=<?= $slot['slot_id'] ?>"
                                                class="inline-block bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:from-blue-600 hover:to-blue-700 transition">
                                                <i class="fas fa-play-circle mr-2"></i>
                                                <?= $is_active ? 'Join Live Session' : 'Start Session' ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity & Quick Links -->
            <div>
                <!-- Quick Links -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-link text-purple-600 mr-3"></i> Quick Links
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
                        <a href="teacher_report.php" class="p-4 border border-gray-200 rounded-xl hover:bg-green-50 transition flex items-center">
                            <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center mr-3">
                                <i class="fas fa-chart-bar text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">Reports</p>
                                <p class="text-xs text-gray-500">Attendance analytics</p>
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
                    </div>
                </div>

                <!-- System Status -->
                <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-bold mb-4 flex items-center">
                        <i class="fas fa-shield-alt text-yellow-400 mr-3"></i> System Status
                    </h2>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="h-3 w-3 rounded-full bg-green-500 mr-3"></div>
                                <span>Attendance System</span>
                            </div>
                            <span class="text-sm font-medium">Operational</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="h-3 w-3 rounded-full bg-green-500 mr-3"></div>
                                <span>QR Generation</span>
                            </div>
                            <span class="text-sm font-medium">Active</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="h-3 w-3 rounded-full bg-green-500 mr-3"></div>
                                <span>Device Locking</span>
                            </div>
                            <span class="text-sm font-medium">Enabled</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="h-3 w-3 rounded-full bg-green-500 mr-3"></div>
                                <span>Swap System</span>
                            </div>
                            <span class="text-sm font-medium">Online</span>
                        </div>
                    </div>
                    <div class="mt-6 pt-6 border-t border-gray-700 text-xs text-gray-400">
                        <p><i class="fas fa-info-circle mr-1"></i> Last updated: <?= date('h:i A') ?></p>
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
                    <h3 class="font-bold mb-4">Teacher Portal</h3>
                    <p class="text-gray-400 text-sm">
                        Advanced class management and attendance tracking system.
                    </p>
                </div>
                <div>
                    <h3 class="font-bold mb-4">Quick Access</h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="live_session.php" class="hover:text-white transition">Start Live Session</a></li>
                        <li><a href="red_zone.php" class="hover:text-white transition">Red Zone Alerts</a></li>
                        <li><a href="swap_dashboard.php" class="hover:text-white transition">Class Swaps</a></li>
                        <li><a href="manual_override.php" class="hover:text-white transition">Manual Override</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="font-bold mb-4">Support</h3>
                    <p class="text-gray-400 text-sm">
                        <i class="fas fa-envelope mr-2"></i> support@campus.edu<br>
                        <i class="fas fa-phone mr-2"></i> +91-XXX-XXX-XXXX
                    </p>
                </div>
            </div>
            <div class="text-center mt-8 pt-8 border-t border-gray-700">
                <p class="text-gray-400 text-sm">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>
                    Teacher Dashboard | Advanced Campus Attendance System v3.0
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Auto-refresh page every 60 seconds to update live status
        setTimeout(() => {
            window.location.reload();
        }, 60000);

        // Add hover effects to cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card, .schedule-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-3px)';
                });
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>

</html>