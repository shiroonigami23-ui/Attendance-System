<?php
require_once '../includes/Config.php';
require_once '../includes/Auth.php';

checkAuth();
$user = getCurrentUser();

// Ensure only teachers can access
if ($_SESSION['role'] !== 'SEMI_ADMIN') {
    header('Location: ../dashboard.php');
    exit;
}

$user_id = $user['user_id'];

// Get teacher's assigned subjects
$stmt = $pdo->prepare("
    SELECT DISTINCT s.subject_id, s.code, s.name, s.is_lab
    FROM subjects s
    JOIN timetable_slots ts ON s.subject_id = ts.subject_id
    WHERE ts.default_teacher_id = ?
    GROUP BY s.subject_id, s.code, s.name, s.is_lab
    ORDER BY s.code
");
$stmt->execute([$user_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected subject details
$selected_subject = null;
$subject_stats = [];
$red_zone_students = [];

if (isset($_GET['subject_id'])) {
    $subject_id = $_GET['subject_id'];
    
    // Verify teacher teaches this subject
    $stmt = $pdo->prepare("
        SELECT s.* 
        FROM subjects s
        JOIN timetable_slots ts ON s.subject_id = ts.subject_id
        WHERE s.subject_id = ? AND ts.default_teacher_id = ?
        LIMIT 1
    ");
    $stmt->execute([$subject_id, $user_id]);
    $selected_subject = $stmt->fetch();
    
    if ($selected_subject) {
        // Get overall subject statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT ls.session_id) as total_sessions,
                COUNT(DISTINCT al.log_id) as total_attendance_records,
                COUNT(DISTINCT CASE WHEN al.status IN ('PRESENT', 'LATE', 'EXEMPT') THEN al.student_id END) as unique_students_attended,
                AVG(CASE WHEN al.status IN ('PRESENT', 'LATE', 'EXEMPT') THEN 1 ELSE 0 END) * 100 as avg_attendance_rate
            FROM live_sessions ls
            JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
            LEFT JOIN attendance_logs al ON ls.session_id = al.session_id
            WHERE ts.subject_id = ?
            AND ls.session_date <= CURDATE()
        ");
        $stmt->execute([$subject_id]);
        $subject_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get Red Zone students (<35% attendance)
        $stmt = $pdo->prepare("
            SELECT 
                sp.user_id,
                sp.roll_no,
                u.full_name,
                u.email,
                COUNT(CASE WHEN al.status IN ('PRESENT', 'LATE', 'EXEMPT') THEN 1 END) as attended,
                COUNT(*) as total,
                (COUNT(CASE WHEN al.status IN ('PRESENT', 'LATE', 'EXEMPT') THEN 1 END) / COUNT(*)) * 100 as percentage
            FROM student_profiles sp
            JOIN users u ON sp.user_id = u.user_id
            JOIN attendance_logs al ON sp.user_id = al.student_id
            JOIN live_sessions ls ON al.session_id = ls.session_id
            JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
            WHERE ts.subject_id = ?
            AND ls.session_date <= CURDATE()
            GROUP BY sp.user_id, sp.roll_no, u.full_name, u.email
            HAVING percentage < 35
            ORDER BY percentage ASC
            LIMIT 20
        ");
        $stmt->execute([$subject_id]);
        $red_zone_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get attendance by date (last 30 days)
        $stmt = $pdo->prepare("
            SELECT 
                DATE(ls.session_date) as date,
                COUNT(CASE WHEN al.status IN ('PRESENT', 'LATE', 'EXEMPT') THEN 1 END) as attended,
                COUNT(*) as total,
                (COUNT(CASE WHEN al.status IN ('PRESENT', 'LATE', 'EXEMPT') THEN 1 END) / COUNT(*)) * 100 as percentage
            FROM live_sessions ls
            LEFT JOIN attendance_logs al ON ls.session_id = al.session_id
            WHERE ls.session_id IN (
                SELECT ls2.session_id 
                FROM live_sessions ls2
                JOIN timetable_slots ts ON ls2.slot_id = ts.slot_id
                WHERE ts.subject_id = ?
            )
            AND ls.session_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(ls.session_date)
            ORDER BY ls.session_date DESC
        ");
        $stmt->execute([$subject_id]);
        $daily_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get batch/section wise attendance
        $stmt = $pdo->prepare("
            SELECT 
                sp.section,
                sp.lab_batch,
                COUNT(DISTINCT CASE WHEN al.status IN ('PRESENT', 'LATE', 'EXEMPT') THEN al.student_id END) as attended_count,
                COUNT(DISTINCT sp2.user_id) as total_students,
                (COUNT(DISTINCT CASE WHEN al.status IN ('PRESENT', 'LATE', 'EXEMPT') THEN al.student_id END) / COUNT(DISTINCT sp2.user_id)) * 100 as percentage
            FROM student_profiles sp
            JOIN users u ON sp.user_id = u.user_id
            JOIN attendance_logs al ON sp.user_id = al.student_id
            JOIN live_sessions ls ON al.session_id = ls.session_id
            JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
            JOIN student_profiles sp2 ON sp2.section = sp.section AND (sp2.lab_batch = sp.lab_batch OR sp.lab_batch IS NULL)
            WHERE ts.subject_id = ?
            AND ls.session_date <= CURDATE()
            GROUP BY sp.section, sp.lab_batch
            ORDER BY sp.section, sp.lab_batch
        ");
        $stmt->execute([$subject_id]);
        $batch_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Reports - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-chart-pie text-green-600 text-2xl mr-3"></i>
                        <span class="text-xl font-bold text-gray-800">Teacher Reports</span>
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
            <h1 class="text-3xl font-bold text-gray-900">Attendance Analytics</h1>
            <p class="text-gray-600 mt-2">View detailed reports for your subjects</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Left Column: Subject Selection -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">My Subjects</h2>
                    
                    <?php if (empty($subjects)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-book text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900">No subjects assigned</h3>
                        <p class="text-gray-500">Contact admin for subject assignment</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($subjects as $subject): 
                            $is_selected = isset($_GET['subject_id']) && $_GET['subject_id'] == $subject['subject_id'];
                        ?>
                        <a href="?subject_id=<?= $subject['subject_id'] ?>" 
                           class="block p-4 border rounded-xl hover:border-blue-300 transition <?= $is_selected ? 'border-blue-500 bg-blue-50 border-l-4 border-l-blue-500' : 'border-gray-200' ?>">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-lg <?= $subject['is_lab'] ? 'bg-blue-100' : 'bg-green-100' ?> flex items-center justify-center mr-3">
                                    <i class="<?= $subject['is_lab'] ? 'fas fa-flask text-blue-600' : 'fas fa-book text-green-600' ?>"></i>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-gray-800"><?= htmlspecialchars($subject['code']) ?></h4>
                                    <p class="text-sm text-gray-600 truncate"><?= htmlspecialchars($subject['name']) ?></p>
                                </div>
                                <?php if ($is_selected): ?>
                                <i class="fas fa-check text-green-500"></i>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Report Content -->
            <div class="lg:col-span-3">
                <?php if (!$selected_subject): ?>
                <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
                    <div class="h-20 w-20 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-chart-line text-green-600 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Select a Subject</h3>
                    <p class="text-gray-600 mb-6">Choose a subject from the left panel to view detailed attendance reports.</p>
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        View attendance statistics, red zone students, and batch-wise performance.
                    </div>
                </div>
                <?php else: ?>
               <!-- Subject Header -->
<div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
    <div class="flex justify-between items-start">
        <div>
            <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($selected_subject['code'] ?? '') ?>: <?= htmlspecialchars($selected_subject['name'] ?? '') ?></h2>
            <div class="flex items-center text-gray-600 mt-2 space-x-4">
                <span class="flex items-center">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>
                    <?= htmlspecialchars($user['full_name'] ?? $user['email'] ?? 'Teacher') ?>
                </span>
                <span class="flex items-center">
                    <i class="<?= ($selected_subject['is_lab'] ?? false) ? 'fas fa-flask' : 'fas fa-book' ?> mr-2"></i>
                    <?= ($selected_subject['is_lab'] ?? false) ? 'Laboratory' : 'Theory' ?>
                </span>
            </div>
        </div>
        <div class="text-right">
            <div class="text-sm text-gray-500">Overall Attendance</div>
            <div class="text-3xl font-bold text-green-600">
                <?= round($subject_stats['avg_attendance_rate'] ?? 0, 1) ?>%
            </div>
        </div>
    </div>
</div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center">
                                <i class="fas fa-calendar-day text-blue-600 text-xl"></i>
                            </div>
                            <span class="text-2xl font-bold text-gray-800"><?= $subject_stats['total_sessions'] ?? 0 ?></span>
                        </div>
                        <h3 class="text-gray-500 text-sm font-medium mb-1">Sessions Conducted</h3>
                        <p class="text-xs text-gray-400">Total classes held</p>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="h-12 w-12 rounded-lg bg-green-100 flex items-center justify-center">
                                <i class="fas fa-user-check text-green-600 text-xl"></i>
                            </div>
                            <span class="text-2xl font-bold text-gray-800"><?= $subject_stats['unique_students_attended'] ?? 0 ?></span>
                        </div>
                        <h3 class="text-gray-500 text-sm font-medium mb-1">Unique Students</h3>
                        <p class="text-xs text-gray-400">Attended at least once</p>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="h-12 w-12 rounded-lg bg-purple-100 flex items-center justify-center">
                                <i class="fas fa-chart-bar text-purple-600 text-xl"></i>
                            </div>
                            <span class="text-2xl font-bold text-gray-800"><?= $subject_stats['total_attendance_records'] ?? 0 ?></span>
                        </div>
                        <h3 class="text-gray-500 text-sm font-medium mb-1">Total Records</h3>
                        <p class="text-xs text-gray-400">Attendance entries</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Red Zone Students -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                            <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                            Red Zone Students (<35%)
                            <span class="ml-auto text-sm font-normal bg-red-100 text-red-800 px-3 py-1 rounded-full">
                                <?= count($red_zone_students) ?> students
                            </span>
                        </h3>
                        
                        <?php if (empty($red_zone_students)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-check-circle text-4xl text-green-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900">No Red Zone Students</h3>
                            <p class="text-gray-500">All students have >35% attendance</p>
                        </div>
                        <?php else: ?>
                        <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                            <?php foreach ($red_zone_students as $student): ?>
                            <div class="border border-red-200 rounded-xl p-4 bg-red-50">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h4 class="font-bold text-gray-800"><?= htmlspecialchars($student['roll_no']) ?></h4>
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($student['full_name']) ?></p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-2xl font-bold text-red-600"><?= round($student['percentage'], 1) ?>%</span>
                                        <div class="text-xs text-gray-500">
                                            <?= $student['attended'] ?>/<?= $student['total'] ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full bg-red-200 rounded-full h-2">
                                    <div class="bg-red-600 h-2 rounded-full" style="width: <?= min($student['percentage'], 100) ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Batch/Section Stats -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                            <i class="fas fa-users text-blue-600 mr-3"></i>
                            Batch-wise Performance
                        </h3>
                        
                        <?php if (empty($batch_stats)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-users-slash text-4xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900">No Batch Data</h3>
                            <p class="text-gray-500">No attendance records for batches</p>
                        </div>
                        <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($batch_stats as $batch): ?>
                            <div class="border border-gray-200 rounded-xl p-4">
                                <div class="flex justify-between items-center mb-3">
                                    <div>
                                        <h4 class="font-bold text-gray-800">
                                            Section <?= htmlspecialchars($batch['section']) ?>
                                            <?php if ($batch['lab_batch']): ?>
                                            <span class="ml-2 text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded">Batch <?= htmlspecialchars($batch['lab_batch']) ?></span>
                                            <?php endif; ?>
                                        </h4>
                                        <p class="text-sm text-gray-600">
                                            <?= $batch['attended_count'] ?>/<?= $batch['total_students'] ?> students
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-2xl font-bold 
                                            <?= $batch['percentage'] >= 75 ? 'text-green-600' : 
                                               ($batch['percentage'] >= 60 ? 'text-yellow-600' : 'text-red-600') ?>">
                                            <?= round($batch['percentage'], 1) ?>%
                                        </span>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full 
                                        <?= $batch['percentage'] >= 75 ? 'bg-green-600' : 
                                           ($batch['percentage'] >= 60 ? 'bg-yellow-600' : 'bg-red-600') ?>" 
                                        style="width: <?= min($batch['percentage'], 100) ?>%">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Daily Attendance Chart -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mt-8">
                    <h3 class="text-xl font-bold text-gray-800 mb-6">30-Day Attendance Trend</h3>
                    <canvas id="dailyChart" height="150"></canvas>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    <?php if (isset($daily_attendance) && !empty($daily_attendance)): ?>
    // Daily Attendance Chart
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    const dates = <?= json_encode(array_column($daily_attendance, 'date')) ?>;
    const percentages = <?= json_encode(array_column($daily_attendance, 'percentage')) ?>;
    
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: dates.map(date => new Date(date).toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric' 
            })),
            datasets: [{
                label: 'Attendance %',
                data: percentages,
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
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Attendance: ${context.parsed.y.toFixed(1)}%`;
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
    <?php endif; ?>
    </script>
</body>
</html>