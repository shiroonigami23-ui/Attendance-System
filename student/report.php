<?php
require_once '../includes/Config.php';
require_once '../includes/Auth.php';

checkAuth();
$user = getCurrentUser();

// Ensure only students can access
if ($_SESSION['role'] !== 'STUDENT') {
    header('Location: ../dashboard.php');
    exit;
}

// Get current semester subjects
$stmt = $pdo->prepare("
    SELECT DISTINCT s.subject_id, s.code, s.name, s.is_lab,
           COUNT(DISTINCT ts.slot_id) as total_slots
    FROM subjects s
    JOIN timetable_slots ts ON s.subject_id = ts.subject_id
    JOIN student_profiles sp ON sp.user_id = ?
    WHERE (JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.section')) = sp.section 
           OR JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.section')) = 'ALL')
      AND (JSON_EXTRACT(ts.target_group, '$.batch') IS NULL 
           OR JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.batch')) = sp.lab_batch 
           OR sp.lab_batch IS NULL)
      AND CURDATE() BETWEEN ts.valid_from AND ts.valid_until
    GROUP BY s.subject_id, s.code, s.name, s.is_lab
    ORDER BY s.code
");
$stmt->execute([$user['user_id']]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get attendance summary for each subject
foreach ($subjects as &$subject) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN al.status = 'PRESENT' THEN 1 END) as present_count,
            COUNT(CASE WHEN al.status = 'LATE' THEN 1 END) as late_count,
            COUNT(CASE WHEN al.status = 'ABSENT' THEN 1 END) as absent_count,
            COUNT(CASE WHEN al.status = 'EXEMPT' THEN 1 END) as exempt_count,
            COUNT(*) as total_conducted
        FROM attendance_logs al
        JOIN live_sessions ls ON al.session_id = ls.session_id
        JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
        WHERE al.student_id = ? 
          AND ts.subject_id = ?
          AND ls.session_date <= CURDATE()
    ");
    $stmt->execute([$user['user_id'], $subject['subject_id']]);
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $subject['attendance'] = $attendance;
    
    // Calculate percentage
    $total_attended = ($attendance['present_count'] + $attendance['late_count'] + $attendance['exempt_count']);
    $total_conducted = $attendance['total_conducted'];
    
    if ($total_conducted > 0) {
        $subject['percentage'] = round(($total_attended / $total_conducted) * 100, 1);
    } else {
        $subject['percentage'] = 0;
    }
    
    // Determine status color
    if ($subject['percentage'] >= 75) {
        $subject['status_color'] = 'green';
        $subject['status_text'] = 'Safe';
    } elseif ($subject['percentage'] >= 60) {
        $subject['status_color'] = 'yellow';
        $subject['status_text'] = 'Warning';
    } else {
        $subject['status_color'] = 'red';
        $subject['status_text'] = 'Danger';
    }
}

// Get overall statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN al.status = 'PRESENT' THEN 1 END) as total_present,
        COUNT(CASE WHEN al.status = 'LATE' THEN 1 END) as total_late,
        COUNT(CASE WHEN al.status = 'ABSENT' THEN 1 END) as total_absent,
        COUNT(CASE WHEN al.status = 'EXEMPT' THEN 1 END) as total_exempt,
        COUNT(*) as total_classes
    FROM attendance_logs al
    JOIN live_sessions ls ON al.session_id = ls.session_id
    JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
    JOIN subjects s ON ts.subject_id = s.subject_id
    WHERE al.student_id = ?
      AND ls.session_date <= CURDATE()
");
$stmt->execute([$user['user_id']]);
$overall_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate overall percentage
$total_attended = ($overall_stats['total_present'] + $overall_stats['total_late'] + $overall_stats['total_exempt']);
$total_classes = $overall_stats['total_classes'];
$overall_percentage = $total_classes > 0 ? round(($total_attended / $total_classes) * 100, 1) : 0;

// Get attendance trend (last 30 days)
$stmt = $pdo->prepare("
    SELECT 
        DATE(ls.session_date) as date,
        COUNT(CASE WHEN al.status IN ('PRESENT', 'LATE', 'EXEMPT') THEN 1 END) as attended,
        COUNT(*) as total
    FROM attendance_logs al
    JOIN live_sessions ls ON al.session_id = ls.session_id
    WHERE al.student_id = ?
      AND ls.session_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(ls.session_date)
    ORDER BY ls.session_date
");
$stmt->execute([$user['user_id']]);
$daily_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get leave statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN approval_status = 'APPROVED_FULL' THEN 1 END) as approved_full,
        COUNT(CASE WHEN approval_status = 'APPROVED_PARTIAL' THEN 1 END) as approved_partial,
        COUNT(CASE WHEN approval_status = 'REJECTED' THEN 1 END) as rejected,
        COUNT(CASE WHEN approval_status = 'PENDING' THEN 1 END) as pending,
        COALESCE(SUM(days_granted), 0) as total_days_granted
    FROM leave_requests
    WHERE student_id = ?
");
$stmt->execute([$user['user_id']]);
$leave_stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report - Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen">
    <!-- Navbar -->
    <nav class="glass-card shadow-lg sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-blue-600 flex items-center justify-center mr-3">
                            <i class="fas fa-chart-bar text-white text-xl"></i>
                        </div>
                        <div>
                            <span class="text-xl font-bold text-gray-800">Attendance Report</span>
                            <p class="text-xs text-gray-500">Student Portal</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 transition">
                        <i class="fas fa-home mr-1"></i> Dashboard
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

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Attendance Analytics</h1>
            <p class="text-gray-600">Detailed attendance report and statistics</p>
        </div>

        <!-- Overall Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="h-12 w-12 rounded-lg bg-gradient-to-r from-green-100 to-emerald-100 flex items-center justify-center">
                        <i class="fas fa-calendar-check text-green-600 text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-gray-800"><?= $overall_percentage ?>%</span>
                </div>
                <h3 class="text-gray-500 text-sm font-medium mb-1">Overall Attendance</h3>
                <p class="text-xs text-gray-400">
                    <?= $total_attended ?> of <?= $total_classes ?> classes
                </p>
                <div class="mt-4 w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full" style="width: <?= min($overall_percentage, 100) ?>%"></div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="h-12 w-12 rounded-lg bg-gradient-to-r from-blue-100 to-cyan-100 flex items-center justify-center">
                        <i class="fas fa-user-check text-blue-600 text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-gray-800"><?= $overall_stats['total_present'] ?></span>
                </div>
                <h3 class="text-gray-500 text-sm font-medium mb-1">Present</h3>
                <p class="text-xs text-gray-400">
                    On time attendance
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="h-12 w-12 rounded-lg bg-gradient-to-r from-yellow-100 to-amber-100 flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-gray-800"><?= $overall_stats['total_late'] ?></span>
                </div>
                <h3 class="text-gray-500 text-sm font-medium mb-1">Late</h3>
                <p class="text-xs text-gray-400">
                    3-7 minutes delay
                </p>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="h-12 w-12 rounded-lg bg-gradient-to-r from-red-100 to-pink-100 flex items-center justify-center">
                        <i class="fas fa-user-times text-red-600 text-xl"></i>
                    </div>
                    <span class="text-2xl font-bold text-gray-800"><?= $overall_stats['total_absent'] ?></span>
                </div>
                <h3 class="text-gray-500 text-sm font-medium mb-1">Absent</h3>
                <p class="text-xs text-gray-400">
                    Missed classes
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Subject-wise Attendance -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Subject-wise Attendance</h2>
                    
                    <?php if (empty($subjects)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-book-open text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900">No subjects found</h3>
                        <p class="text-gray-500">No timetable data available for current semester</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($subjects as $subject): 
                            $att = $subject['attendance'];
                            $total = $att['total_conducted'];
                            $attended = $att['present_count'] + $att['late_count'] + $att['exempt_count'];
                        ?>
                        <div class="border border-gray-200 rounded-xl p-5 hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-lg">
                                        <?= htmlspecialchars($subject['code']) ?>: <?= htmlspecialchars($subject['name']) ?>
                                        <?php if ($subject['is_lab']): ?>
                                        <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Lab</span>
                                        <?php endif; ?>
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        <?= $attended ?> of <?= $total ?> classes attended
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="text-2xl font-bold text-gray-800"><?= $subject['percentage'] ?>%</span>
                                    <div class="mt-1">
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-<?= $subject['status_color'] ?>-100 text-<?= $subject['status_color'] ?>-800">
                                            <?= $subject['status_text'] ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div class="mb-3">
                                <div class="flex justify-between text-xs text-gray-500 mb-1">
                                    <span>Attendance Progress</span>
                                    <span><?= $subject['percentage'] ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-<?= $subject['status_color'] ?>-600 h-2.5 rounded-full" style="width: <?= min($subject['percentage'], 100) ?>%"></div>
                                </div>
                            </div>
                            
                            <!-- Detailed Stats -->
                            <div class="grid grid-cols-4 gap-4 text-center">
                                <div>
                                    <p class="text-lg font-bold text-green-600"><?= $att['present_count'] ?></p>
                                    <p class="text-xs text-gray-500">Present</p>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-yellow-600"><?= $att['late_count'] ?></p>
                                    <p class="text-xs text-gray-500">Late</p>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-red-600"><?= $att['absent_count'] ?></p>
                                    <p class="text-xs text-gray-500">Absent</p>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-blue-600"><?= $att['exempt_count'] ?></p>
                                    <p class="text-xs text-gray-500">Exempt</p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Attendance Trend Chart -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">30-Day Attendance Trend</h2>
                    <canvas id="attendanceTrendChart" height="200"></canvas>
                </div>
            </div>

            <!-- Right Column: Leave Statistics & Details -->
            <div class="lg:col-span-1">
                <!-- Leave Statistics -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Leave Statistics</h2>
                    
                    <div class="space-y-6">
                        <div class="text-center">
                            <div class="h-20 w-20 rounded-full bg-gradient-to-r from-blue-100 to-cyan-100 flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-calendar-minus text-blue-600 text-2xl"></i>
                            </div>
                            <h3 class="text-3xl font-bold text-gray-800"><?= $leave_stats['total_days_granted'] ?></h3>
                            <p class="text-gray-500">Days Granted</p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-4 bg-green-50 rounded-xl">
                                <p class="text-2xl font-bold text-green-600"><?= $leave_stats['approved_full'] ?></p>
                                <p class="text-sm text-gray-600">Approved</p>
                            </div>
                            <div class="text-center p-4 bg-yellow-50 rounded-xl">
                                <p class="text-2xl font-bold text-yellow-600"><?= $leave_stats['pending'] ?></p>
                                <p class="text-sm text-gray-600">Pending</p>
                            </div>
                            <div class="text-center p-4 bg-red-50 rounded-xl">
                                <p class="text-2xl font-bold text-red-600"><?= $leave_stats['rejected'] ?></p>
                                <p class="text-sm text-gray-600">Rejected</p>
                            </div>
                            <div class="text-center p-4 bg-blue-50 rounded-xl">
                                <p class="text-2xl font-bold text-blue-600"><?= $leave_stats['approved_partial'] ?></p>
                                <p class="text-sm text-gray-600">Partial</p>
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t">
                            <h4 class="font-medium text-gray-700 mb-3">Leave Rules</h4>
                            <ul class="space-y-2 text-sm text-gray-600">
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                    <span>1-3 days: Teacher approval with document</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                                    <span>>3 days: Master Admin approval required</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-times text-red-500 mt-1 mr-2"></i>
                                    <span>No leave applications for past dates</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-exclamation-triangle text-yellow-500 mt-1 mr-2"></i>
                                    <span>Max 30 days per request</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Red Zone Warning -->
                <?php if ($overall_percentage < 75): ?>
                <div class="bg-gradient-to-r from-red-50 to-pink-50 border-l-4 border-red-500 rounded-r-lg p-6 mb-8">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-red-500 text-xl mt-1 mr-3"></i>
                        <div>
                            <h3 class="font-bold text-gray-800 mb-2">Attendance Warning</h3>
                            <p class="text-sm text-gray-700 mb-3">
                                Your overall attendance is <strong><?= $overall_percentage ?>%</strong>. 
                                Maintain at least <strong>75%</strong> to avoid detention.
                            </p>
                            <div class="flex space-x-3">
                                <a href="timetable.php" class="text-sm bg-red-100 text-red-700 hover:bg-red-200 px-3 py-2 rounded-lg transition">
                                    <i class="fas fa-calendar-alt mr-1"></i> Check Schedule
                                </a>
                                <a href="leave_apply.php" class="text-sm bg-blue-100 text-blue-700 hover:bg-blue-200 px-3 py-2 rounded-lg transition">
                                    <i class="fas fa-file-medical mr-1"></i> Apply Leave
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 rounded-r-lg p-6 mb-8">
                    <div class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 text-xl mt-1 mr-3"></i>
                        <div>
                            <h3 class="font-bold text-gray-800 mb-2">Good Standing</h3>
                            <p class="text-sm text-gray-700">
                                Your overall attendance is <strong><?= $overall_percentage ?>%</strong>. 
                                You are above the required 75% threshold.
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Export Options -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Export Report</h2>
                    <p class="text-gray-600 text-sm mb-4">Download your attendance data in various formats</p>
                    <div class="space-y-3">
                        <button class="w-full bg-blue-50 hover:bg-blue-100 text-blue-700 px-4 py-3 rounded-lg text-left transition group">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-file-pdf text-red-500 text-xl mr-3"></i>
                                    <div>
                                        <p class="font-medium">PDF Report</p>
                                        <p class="text-xs text-gray-500">Detailed attendance summary</p>
                                    </div>
                                </div>
                                <i class="fas fa-download text-gray-400 group-hover:text-blue-600"></i>
                            </div>
                        </button>
                        <button class="w-full bg-green-50 hover:bg-green-100 text-green-700 px-4 py-3 rounded-lg text-left transition group">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-file-excel text-green-500 text-xl mr-3"></i>
                                    <div>
                                        <p class="font-medium">Excel Sheet</p>
                                        <p class="text-xs text-gray-500">Raw data for analysis</p>
                                    </div>
                                </div>
                                <i class="fas fa-download text-gray-400 group-hover:text-green-600"></i>
                            </div>
                        </button>
                        <button class="w-full bg-purple-50 hover:bg-purple-100 text-purple-700 px-4 py-3 rounded-lg text-left transition group">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <i class="fas fa-chart-pie text-purple-500 text-xl mr-3"></i>
                                    <div>
                                        <p class="font-medium">Visual Summary</p>
                                        <p class="text-xs text-gray-500">Charts and graphs</p>
                                    </div>
                                </div>
                                <i class="fas fa-download text-gray-400 group-hover:text-purple-600"></i>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Attendance Trend Chart
    const trendCtx = document.getElementById('attendanceTrendChart').getContext('2d');
    
    // Prepare data for chart
    const dates = <?= json_encode(array_column($daily_trend, 'date')) ?>;
    const attendanceRates = <?= json_encode(array_map(function($day) {
        return $day['total'] > 0 ? round(($day['attended'] / $day['total']) * 100, 0) : 0;
    }, $daily_trend)) ?>;
    
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: dates.map(date => new Date(date).toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric' 
            })),
            datasets: [{
                label: 'Daily Attendance %',
                data: attendanceRates,
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Attendance: ${context.parsed.y}%`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });

    // Export buttons (placeholder functionality)
    document.querySelectorAll('button').forEach(button => {
        if (button.textContent.includes('PDF') || button.textContent.includes('Excel') || button.textContent.includes('Visual')) {
            button.addEventListener('click', function() {
                const format = this.querySelector('.font-medium').textContent.split(' ')[0];
                alert(`Export functionality for ${format} format will be implemented soon.`);
            });
        }
    });
    </script>
</body>
</html>