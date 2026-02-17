<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../includes/Config.php';
require_once '../includes/Auth.php';

checkAuth();
if ($_SESSION['role'] != 'MASTER') {
    header('Location: ../dashboard.php');
    exit();
}

$user = getCurrentUser();

// Get unique batch years from database
$batch_years = $pdo->query("
    SELECT DISTINCT SUBSTRING(roll_no, 7, 2) as batch_yy
    FROM student_profiles 
    WHERE LENGTH(roll_no) >= 8 
    AND SUBSTRING(roll_no, 7, 2) REGEXP '^[0-9]+$'
    ORDER BY batch_yy DESC
")->fetchAll();

// Handle semester promotion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['run_promotion'])) {
    $batch_year = $_POST['batch_year'] ?? null;
    $current_semester = $_POST['current_semester'] ?? null;
    $target_semester = $_POST['target_semester'] ?? null;
    
    if (!$batch_year || !$current_semester || !$target_semester) {
        $error = "Please select batch year, current and target semesters.";
    } else {
        $batch_year = trim($batch_year);
        $current_semester = (int)$current_semester;
        $target_semester = (int)$target_semester;
        
        // Validate batch year format
        if (!preg_match('/^\d{2}$/', $batch_year)) {
            $error = "Invalid batch year format. Must be 2 digits (e.g., '23' for 2023 batch).";
        } else {
            // Validate promotion logic
            $is_graduation = ($current_semester === 8 && $target_semester === 9);
            $is_valid_promotion = ($target_semester === $current_semester + 1);
            
            if (!$is_graduation && !$is_valid_promotion) {
                $error = "Invalid promotion. Students can only be promoted to the next immediate semester.";
            } else {
                // Begin transaction
                $pdo->beginTransaction();
                
                try {
                    // Get all students in the current semester AND specific batch
                    $stmt = $pdo->prepare("
                        SELECT user_id, roll_no, current_semester 
                        FROM student_profiles 
                        WHERE current_semester = ?
                        AND LENGTH(roll_no) >= 8 
                        AND SUBSTRING(roll_no, 7, 2) = ?
                    ");
                    $stmt->execute([$current_semester, $batch_year]);
                    $students = $stmt->fetchAll();
                    error_log("PROMOTION DEBUG: Found " . count($students) . " students. Batch: $batch_year, From Sem: $current_semester, To Sem: $target_semester");
                    $promoted_count = 0;
                    $graduated_count = 0;
                    
                    foreach ($students as $student) {
                        if ($is_graduation) {
                            // GRADUATION: Archive as alumni
                            $stmt = $pdo->prepare("
                                UPDATE users 
                                SET is_active = 0, role = 'ALUMNI' 
                                WHERE user_id = ?
                            ");
                            $stmt->execute([$student['user_id']]);
                            $graduated_count++;
                        } else {
                            // REGULAR PROMOTION: Move to next semester
                            $new_semester = $target_semester;
                            $stmt = $pdo->prepare("
                                UPDATE student_profiles 
                                SET current_semester = ? 
                                WHERE user_id = ?
                            ");
                            $stmt->execute([$new_semester, $student['user_id']]);
                            $promoted_count++;
                        }
                    }
                    
                    // Log the action
                    $full_batch_year = 2000 + (int)$batch_year;
                    if ($is_graduation) {
                        $action = 'GRADUATION';
                        $details = "Graduated $graduated_count students from Batch $full_batch_year, Semester $current_semester (Archived as Alumni)";
                    } else {
                        $action = 'SEMESTER_PROMOTION';
                        $details = json_encode([
    'batch_year' => $full_batch_year,
    'from_semester' => $current_semester,
    'to_semester' => $target_semester,
    'promoted_count' => $promoted_count,
    'graduated_count' => $graduated_count
]);
                        }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO system_logs (user_id, action, details, ip_address, user_agent)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    
                    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                    $stmt->execute([
                        $user['user_id'], 
                        $action,
                        $details, 
                        $_SERVER['REMOTE_ADDR'],
                        $user_agent
                    ]);
                    
                    $pdo->commit();
                    
                    $full_batch_year = 2000 + (int)$batch_year;
                    if ($is_graduation) {
                        $success = "Successfully graduated $graduated_count students from Batch $full_batch_year, Semester $current_semester. Students have been archived as alumni.";
                    } else {
                        $success = "Successfully promoted $promoted_count students from Batch $full_batch_year, Semester $current_semester to Semester $target_semester.";
                    }
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Operation failed: " . $e->getMessage();
                    error_log("Promotion error: " . $e->getMessage());
                }
            }
        }
    }
}

// Get current semester distribution with batch info
$semester_stats = $pdo->query("
    SELECT 
        current_semester,
        SUBSTRING(roll_no, 7, 2) as batch_yy,
        COUNT(*) as student_count,
        GROUP_CONCAT(roll_no ORDER BY roll_no LIMIT 3) as sample_rolls
    FROM student_profiles 
    WHERE current_semester IS NOT NULL
    AND LENGTH(roll_no) >= 8 
    AND SUBSTRING(roll_no, 7, 2) REGEXP '^[0-9]+$'
    GROUP BY current_semester, batch_yy
    ORDER BY current_semester, batch_yy DESC
")->fetchAll();

// Get total students per semester
$total_per_semester = [];
foreach ($semester_stats as $stat) {
    if (!isset($total_per_semester[$stat['current_semester']])) {
        $total_per_semester[$stat['current_semester']] = 0;
    }
    $total_per_semester[$stat['current_semester']] += $stat['student_count'];
}

// Get alumni count
$alumni_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'ALUMNI'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semester Promotion - Master Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="dashboard.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
            <div class="flex items-center">
                <div class="h-14 w-14 rounded-full bg-gradient-to-r from-yellow-500 to-yellow-600 flex items-center justify-center mr-4">
                    <i class="fas fa-graduation-cap text-white text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Semester Promotion Wizard</h1>
                    <p class="text-gray-600">Promote students to next semester. Archive final year students as alumni.</p>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($success)): ?>
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                <span class="text-green-700"><?= htmlspecialchars($success) ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <span class="text-red-700"><?= htmlspecialchars($error) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left Column: Promotion Form -->
<div class="lg:col-span-2">
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
            <i class="fas fa-cogs text-yellow-600 mr-3"></i> Promotion Configuration
        </h2>
        
        <form method="POST">
            <div class="space-y-6">
                <!-- Batch Year Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Batch Year *
                    </label>
                    <div class="relative">
                        <select name="batch_year" required id="batch_year_select"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition appearance-none">
                            <option value="">Select Batch Year</option>
                            <?php foreach ($batch_years as $batch): 
                                $full_year = 2000 + (int)$batch['batch_yy'];
                                $student_count = $pdo->query("
                                    SELECT COUNT(*) 
                                    FROM student_profiles 
                                    WHERE LENGTH(roll_no) >= 8 
                                    AND SUBSTRING(roll_no, 7, 2) = '{$batch['batch_yy']}'
                                ")->fetchColumn();
                            ?>
                            <option value="<?= htmlspecialchars($batch['batch_yy']) ?>">
                                Batch <?= $full_year ?> (<?= $batch['batch_yy'] ?> batch)
                                <?php if ($student_count): ?>
                                (<?= $student_count ?> students)
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>

                <!-- Auto-Detected Year & Semester -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <h3 class="font-bold text-blue-800 mb-3 flex items-center">
                        <i class="fas fa-robot mr-2"></i>Academic Info
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <div class="text-sm text-gray-600 mb-1">Academic Year</div>
                            <div class="text-2xl font-bold text-blue-600" id="academicYearDisplay">--</div>
                            <input type="hidden" name="current_year" id="current_year">
                        </div>
                        <div class="text-center">
                            <div class="text-sm text-gray-600 mb-1">Current Semester</div>
                            <div class="text-2xl font-bold text-green-600" id="currentSemesterDisplay">--</div>
                            <input type="hidden" name="current_semester" id="current_semester">
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <div class="text-sm text-gray-600 mb-1">Target Semester</div>
                        <div class="text-2xl font-bold text-purple-600" id="targetSemesterDisplay">--</div>
                        <input type="hidden" name="target_semester" id="target_semester">
                    </div>
                    <p class="text-xs text-blue-700 mt-3">
                        <i class="fas fa-lightbulb"></i> detected based on batch selection and current date
                    </p>
                </div>

                <!-- Options -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                    <h3 class="font-bold text-yellow-800 mb-3 flex items-center">
                        <i class="fas fa-cog mr-2"></i> Additional Options
                    </h3>
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="reset_attendance" class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700">Reset attendance percentages to 0% for new semester</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="archive_logs" checked class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700">Archive previous semester logs</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="send_notification" class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700">Send promotion notification to students</span>
                        </label>
                    </div>
                </div>

                <!-- Warning Message -->
                <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-3 text-xl"></i>
                        <div>
                            <h3 class="font-bold text-red-800">Important Warning</h3>
                            <p class="text-sm text-red-700 mt-1">
                                This action cannot be undone. Promoted students will lose their current semester attendance records 
                                unless archived. Final year students will be marked as alumni and deactivated.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-4">
                    <button type="button" onclick="window.history.back()"
                            class="flex-1 border-2 border-gray-300 text-gray-700 py-3.5 rounded-xl font-bold hover:bg-gray-50 transition flex items-center justify-center">
                        <i class="fas fa-times mr-2"></i> Cancel
                    </button>
                    <button type="submit" name="run_promotion"
                            class="flex-1 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white py-3.5 rounded-xl font-bold hover:from-yellow-600 hover:to-yellow-700 transition flex items-center justify-center">
                        <i class="fas fa-graduation-cap mr-2"></i> RUN SEMESTER PROMOTION
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Right Column: Stats & Preview -->
<div class="lg:col-span-1">
    <!-- Current Distribution -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-chart-bar text-blue-600 mr-3"></i> Current Distribution
        </h2>
        
        <div class="space-y-4">
            <?php foreach ($semester_stats as $stat): 
                $full_batch_year = 2000 + (int)$stat['batch_yy'];
            ?>
            <div class="border border-gray-200 rounded-lg p-3">
                <div class="flex justify-between items-center mb-1">
                    <span class="font-bold text-gray-800">
                        Batch <?= $full_batch_year ?> - Sem <?= $stat['current_semester'] ?>
                    </span>
                    <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-1 rounded-full">
                        <?= $stat['student_count'] ?> students
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <?php 
                    $percentage = $stat['student_count'] > 0 ? 
                        min(($stat['student_count'] / array_sum($total_per_semester) * 100), 100) : 0;
                    ?>
                    <div class="bg-blue-500 h-2 rounded-full" style="width: <?= $percentage ?>%"></div>
                </div>
                <?php if ($stat['sample_rolls']): ?>
                <p class="text-xs text-gray-500 mt-2 truncate">
                    Sample: <?= htmlspecialchars($stat['sample_rolls']) ?>
                </p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            
            <?php if ($alumni_count > 0): ?>
            <div class="border border-purple-200 rounded-lg p-3 bg-purple-50">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-purple-800">Alumni</span>
                    <span class="bg-purple-100 text-purple-800 text-xs font-bold px-2 py-1 rounded-full">
                        <?= $alumni_count ?> archived
                    </span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-bolt text-green-600 mr-3"></i> Quick Actions
        </h2>
        
        <div class="space-y-3">
            <a href="dashboard.php" 
               class="flex items-center p-3 border border-gray-200 rounded-xl hover:bg-blue-50 hover:border-blue-300 transition">
                <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                    <i class="fas fa-tachometer-alt text-blue-600"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-800">Back to Dashboard</p>
                    <p class="text-xs text-gray-500">Return to main admin panel</p>
                </div>
            </a>
            
            <a href="grace_console.php" 
               class="flex items-center p-3 border border-gray-200 rounded-xl hover:bg-purple-50 hover:border-purple-300 transition">
                <div class="h-10 w-10 rounded-lg bg-purple-100 flex items-center justify-center mr-3">
                    <i class="fas fa-sliders-h text-purple-600"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-800">Grace Console</p>
                    <p class="text-xs text-gray-500">Adjust student attendance</p>
                </div>
            </a>
            
            <button onclick="location.reload()"
                    class="w-full flex items-center justify-center p-3 border border-gray-200 rounded-xl hover:bg-gray-50 transition text-gray-700">
                <i class="fas fa-sync-alt mr-2"></i>
                Refresh Data
            </button>
        </div>
    </div>
</div>
</div>

<script>
// Auto-calculate academic year based on batch selection
document.getElementById('batch_year_select').addEventListener('change', function() {
    const batchYear = parseInt(this.value); // e.g., 23 for 2023 batch
    const currentYear = 2026; // Current year
    const currentMonth = 2; // February
    
    if (batchYear >= 22 && batchYear <= 25) {
        const fullBatchYear = 2000 + batchYear; // e.g., 2023
        
        // Years completed since admission = Current year - Admission year
        const yearsCompleted = currentYear - fullBatchYear; // 2026 - 2023 = 3
        
        // Academic year: If before August (month < 8), academic year = yearsCompleted
        // If August or after, academic year = yearsCompleted + 1 (new academic year started)
        let academicYear = (currentMonth < 8) ? yearsCompleted : yearsCompleted + 1;
        
        // Ensure academic year is between 1 and 4
        academicYear = Math.max(1, Math.min(4, academicYear));
        
        // Calculate semesters based on academic year
        // Year 1: Sem 1-2, Year 2: Sem 3-4, Year 3: Sem 5-6, Year 4: Sem 7-8
        const baseSemester = (academicYear - 1) * 2 + 1;
        
        // Display theoretical values first
        document.getElementById('academicYearDisplay').textContent = `Year ${academicYear}`;
        document.getElementById('currentSemesterDisplay').textContent = `Sem ${baseSemester}`;
        document.getElementById('targetSemesterDisplay').textContent = `Sem ${baseSemester + 1}`;
        
        // Set theoretical hidden inputs
        document.getElementById('current_year').value = academicYear;
        document.getElementById('current_semester').value = baseSemester;
        document.getElementById('target_semester').value = baseSemester + 1;
        
        // Fetch actual semester from database via AJAX
        fetch('get_actual_semester.php?batch_year=' + batchYear)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.actual_semester) {
                    const actualSemester = data.actual_semester;
                    const actualYear = Math.ceil(actualSemester / 2);
                    
                    // Update with actual data from database
                    document.getElementById('academicYearDisplay').textContent = `Year ${actualYear}`;
                    document.getElementById('currentSemesterDisplay').textContent = `Sem ${actualSemester}`;
                    
                    // If semester is 8, show graduation option
                    if (actualSemester === 8) {
                        document.getElementById('targetSemesterDisplay').innerHTML = 
                            '<span class="text-red-600 font-bold">Graduate to Alumni</span>';
                        document.getElementById('target_semester').value = '9';
                    } else {
                        document.getElementById('targetSemesterDisplay').textContent = `Sem ${actualSemester + 1}`;
                        document.getElementById('target_semester').value = actualSemester + 1;
                    }
                    
                    // Update hidden inputs with actual values
                    document.getElementById('current_year').value = actualYear;
                    document.getElementById('current_semester').value = actualSemester;
                }
            })
            .catch(error => {
                console.error('Error fetching actual semester:', error);
                // Keep theoretical values if fetch fails
            });
    } else {
        document.getElementById('academicYearDisplay').textContent = '--';
        document.getElementById('currentSemesterDisplay').textContent = '--';
        document.getElementById('targetSemesterDisplay').textContent = '--';
        
        document.getElementById('current_year').value = '';
        document.getElementById('current_semester').value = '';
        document.getElementById('target_semester').value = '';
    }
});

// Auto-refresh after 30 seconds
setTimeout(() => {
    location.reload();
}, 30000);
</script>
</body>
</html>