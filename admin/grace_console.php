<?php
require_once '../includes/Config.php';
require_once '../includes/Auth.php';

checkAuth();
if ($_SESSION['role'] != 'MASTER') {
    header('Location: ../dashboard.php');
    exit();
}

$user = getCurrentUser();

// Handle grace injection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['inject_grace'])) {
    $student_id = $_POST['student_id'];
    $grace_points = floatval($_POST['grace_points']);
    $reason = $_POST['reason'] ?? '';
    
    if ($student_id && $grace_points > 0) {
        // Get current attendance for the student
        $stmt = $pdo->prepare("
            SELECT sp.user_id, sp.roll_no, u.full_name, sp.current_semester, sp.section,
                   COALESCE(sas.attendance_percentage, 0) as current_percentage
            FROM student_profiles sp
            INNER JOIN users u ON sp.user_id = u.user_id
            LEFT JOIN student_attendance_summary sas ON sp.user_id = sas.student_id
            WHERE sp.user_id = ?
        ");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
        if ($student) {
            // Calculate new percentage
            $new_percentage = min(100, $student['current_percentage'] + $grace_points);
            
            // Insert grace record (we need a grace_records table)
            $stmt = $pdo->prepare("
                INSERT INTO grace_records (student_id, admin_id, grace_points, old_percentage, new_percentage, reason, injected_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $student_id, 
                $user['user_id'], 
                $grace_points, 
                $student['current_percentage'], 
                $new_percentage, 
                $reason
            ]);
            
            // Update student_attendance_summary (we'll need to create this or update logic)
            // For now, we'll just mark it in a separate table
            $success = "Grace of {$grace_points}% injected for {$student['roll_no']}. New total: {$new_percentage}%";
        }
    }
}

// Handle auto-unlock
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['unlock_account'])) {
    $student_id = $_POST['student_id'];
    $unlock_reason = $_POST['unlock_reason'] ?? '';
    
    // Update user account to active
    $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE user_id = ?");
    $stmt->execute([$student_id]);
    
    // Log the unlock action
    $stmt = $pdo->prepare("
        INSERT INTO system_logs (user_id, action, details, ip_address, created_at)
        VALUES (?, 'ACCOUNT_UNLOCK', ?, ?, NOW())
    ");
    $stmt->execute([
        $user['user_id'], 
        "Unlocked student ID: {$student_id}. Reason: {$unlock_reason}",
        $_SERVER['REMOTE_ADDR']
    ]);
    
    $success = "Student account unlocked successfully.";
}

// Search functionality
$search_results = [];
$search_query = '';
if (isset($_GET['search'])) {
    $search_query = trim($_GET['search']);
    if (!empty($search_query)) {
        // Search by roll number or name
        $stmt = $pdo->prepare("
            SELECT sp.user_id, sp.roll_no, u.full_name, u.email, sp.current_semester, sp.section, sp.branch_code,
                   COALESCE(sas.total_sessions, 0) as total_sessions,
                   COALESCE(sas.present_count, 0) as present_count,
                   COALESCE(sas.late_count, 0) as late_count,
                   COALESCE(sas.absent_count, 0) as absent_count,
                   CASE 
                       WHEN COALESCE(sas.total_sessions, 0) = 0 THEN 0
                       ELSE ROUND(((COALESCE(sas.present_count, 0) + (COALESCE(sas.late_count, 0) * 0.5)) / sas.total_sessions) * 100, 1)
                   END as attendance_percentage,
                   u.is_active,
                   dl.device_fingerprint
            FROM student_profiles sp
            INNER JOIN users u ON sp.user_id = u.user_id
            LEFT JOIN student_attendance_summary sas ON sp.user_id = sas.student_id
            LEFT JOIN device_locks dl ON sp.user_id = dl.user_id
            WHERE sp.roll_no LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?
            ORDER BY attendance_percentage ASC
            LIMIT 20
        ");
        $search_term = "%{$search_query}%";
        $stmt->execute([$search_term, $search_term, $search_term]);
        $search_results = $stmt->fetchAll();
    }
}

// Get red zone students (automatically)
$stmt = $pdo->prepare("
    SELECT sp.user_id, sp.roll_no, u.full_name, sp.current_semester, sp.section,
           COALESCE(sas.total_sessions, 0) as total_sessions,
           COALESCE(sas.present_count, 0) as present_count,
           COALESCE(sas.late_count, 0) as late_count,
           COALESCE(sas.absent_count, 0) as absent_count,
           CASE 
               WHEN COALESCE(sas.total_sessions, 0) = 0 THEN 0
               ELSE ROUND(((COALESCE(sas.present_count, 0) + (COALESCE(sas.late_count, 0) * 0.5)) / sas.total_sessions) * 100, 1)
           END as attendance_percentage,
           u.is_active
    FROM student_profiles sp
    INNER JOIN users u ON sp.user_id = u.user_id
    LEFT JOIN student_attendance_summary sas ON sp.user_id = sas.student_id
    HAVING attendance_percentage < 35 OR attendance_percentage IS NULL
    ORDER BY attendance_percentage ASC
    LIMIT 50
");
$stmt->execute();
$red_zone_students = $stmt->fetchAll();

// Count stats
$total_red_zone = count($red_zone_students);
$locked_accounts = array_filter($red_zone_students, function($s) {
    return isset($s['is_active']) && $s['is_active'] == 0;
});
$total_locked = count($locked_accounts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grace Console - Master Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .percentage-bar {
            height: 8px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .percentage-text {
            font-variant-numeric: tabular-nums;
        }
        .grace-slider {
            -webkit-appearance: none;
            width: 100%;
            height: 10px;
            border-radius: 5px;
            background: linear-gradient(to right, #ef4444, #f59e0b, #10b981);
            outline: none;
        }
        .grace-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #3b82f6;
            cursor: pointer;
            border: 3px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
        .grace-slider::-moz-range-thumb {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #3b82f6;
            cursor: pointer;
            border: 3px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
        .color-transition {
            transition: background-color 0.5s ease, color 0.5s ease;
        }
        .pulse-warning {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Grace Console</h1>
                    <p class="text-gray-600">Manage Red Zone students and inject grace percentage</p>
                </div>
                <a href="dashboard.php" class="flex items-center text-gray-700 hover:text-blue-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-red-500">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center mr-4">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold"><?= $total_red_zone ?></p>
                        <p class="text-gray-500">Red Zone Students</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-yellow-500">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center mr-4">
                        <i class="fas fa-user-lock text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold"><?= $total_locked ?></p>
                        <p class="text-gray-500">Auto-Locked Accounts</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center mr-4">
                        <i class="fas fa-sliders-h text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold">Grace Tool</p>
                        <p class="text-gray-500">Percentage Adjuster</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($success)): ?>
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                <span class="text-green-700"><?= htmlspecialchars($success) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Search & Select -->
            <div class="lg:col-span-2">
                <!-- Search Box -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-search text-blue-600 mr-3"></i> Search Student
                    </h2>
                    <form method="GET" class="flex gap-4">
                        <div class="flex-1 relative">
                            <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" 
                                   placeholder="Enter Roll Number or Name..." 
                                   class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            <div class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-medium hover:bg-blue-700 transition">
                            <i class="fas fa-search mr-2"></i> Search
                        </button>
                    </form>
                </div>

                <!-- Search Results / Red Zone List -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center">
                            <?php if (!empty($search_query)): ?>
                            <i class="fas fa-search-result text-blue-600 mr-3"></i> Search Results
                            <?php else: ?>
                            <i class="fas fa-fire text-red-600 mr-3"></i> Red Zone Students (<35%)
                            <?php endif; ?>
                        </h2>
                        <span class="text-sm text-gray-500">
                            <?= !empty($search_query) ? count($search_results) : $total_red_zone ?> students
                        </span>
                    </div>

                    <?php if (!empty($search_query) && empty($search_results)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-user-slash text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900">No students found</h3>
                        <p class="text-gray-500 mt-1">Try searching by roll number or name</p>
                    </div>
                    <?php elseif (empty($search_query) && empty($red_zone_students)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-check-circle text-4xl text-green-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900">No Red Zone students!</h3>
                        <p class="text-gray-500 mt-1">All students have >35% attendance</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2">
                        <?php 
                        $students_list = !empty($search_query) ? $search_results : $red_zone_students;
                        foreach ($students_list as $student): 
                            $percentage = $student['attendance_percentage'] ?? 0;
                            $status_color = $percentage >= 75 ? 'bg-green-100 text-green-800' :
                                          ($percentage >= 60 ? 'bg-yellow-100 text-yellow-800' :
                                          ($percentage >= 35 ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800'));
                            $bar_color = $percentage >= 75 ? 'bg-green-500' :
                                       ($percentage >= 60 ? 'bg-yellow-500' :
                                       ($percentage >= 35 ? 'bg-orange-500' : 'bg-red-500'));
                            $is_locked = isset($student['is_active']) && $student['is_active'] == 0;
                        ?>
                        <div class="border border-gray-200 rounded-xl p-5 hover:shadow-md transition cursor-pointer student-card"
                             data-student-id="<?= $student['user_id'] ?>"
                             data-roll-no="<?= htmlspecialchars($student['roll_no']) ?>"
                             data-name="<?= htmlspecialchars($student['full_name']) ?>"
                             data-percentage="<?= $percentage ?>"
                             onclick="selectStudent(this)">
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center mb-2">
                                        <h4 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($student['roll_no']) ?></h4>
                                        <?php if ($is_locked): ?>
                                        <span class="ml-3 bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded-full flex items-center pulse-warning">
                                            <i class="fas fa-lock mr-1"></i> LOCKED
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-gray-700 mb-1"><?= htmlspecialchars($student['full_name']) ?></p>
                                    <div class="flex items-center text-sm text-gray-500 space-x-4">
                                        <span>Sem <?= $student['current_semester'] ?? 'N/A' ?></span>
                                        <span>Sec <?= $student['section'] ?? 'N/A' ?></span>
                                        <span>Total: <?= $student['total_sessions'] ?? 0 ?> sessions</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold <?= $percentage < 35 ? 'text-red-600' : 'text-gray-800' ?>">
                                        <?= number_format($percentage, 1) ?>%
                                    </div>
                                    <span class="text-xs <?= $percentage < 35 ? 'text-red-600 font-bold' : 'text-gray-500' ?>">
                                        Attendance
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div class="mt-3">
                                <div class="flex justify-between text-xs text-gray-500 mb-1">
                                    <span>0%</span>
                                    <span class="<?= $percentage < 35 ? 'text-red-600 font-bold' : '' ?>">
                                        Current: <?= number_format($percentage, 1) ?>%
                                    </span>
                                    <span>100%</span>
                                </div>
                                <div class="percentage-bar bg-gray-200">
                                    <div class="percentage-bar <?= $bar_color ?>" style="width: <?= min($percentage, 100) ?>%"></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>Red Zone</span>
                                    <span>35% Threshold</span>
                                    <span>Safe Zone</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Grace Injector -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-sliders-h text-purple-600 mr-3"></i> Grace Injector
                    </h2>

                    <!-- Selected Student Info -->
                    <div id="selectedStudentInfo" class="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-xl p-5 mb-6 hidden">
                        <div class="text-center mb-4">
                            <div class="h-16 w-16 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-user-graduate text-white text-2xl"></i>
                            </div>
                            <h3 id="selectedStudentName" class="font-bold text-gray-800 text-lg"></h3>
                            <p id="selectedStudentRoll" class="text-gray-600"></p>
                        </div>
                        <div class="text-center">
                            <div class="text-4xl font-bold text-gray-800 mb-2">
                                <span id="currentPercentage">0</span>%
                            </div>
                            <div class="text-sm text-gray-500">Current Attendance</div>
                        </div>
                    </div>

                    <!-- Grace Slider -->
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <label class="block text-sm font-medium text-gray-700">
                                <i class="fas fa-plus-circle text-green-600 mr-1"></i>
                                Inject Grace Percentage
                            </label>
                            <span id="graceValueDisplay" class="text-2xl font-bold text-green-600">0%</span>
                        </div>
                        
                        <input type="range" min="0" max="65" step="0.5" value="0" 
                               class="grace-slider" id="graceSlider">
                        
                        <div class="flex justify-between text-xs text-gray-500 mt-2">
                            <span>0%</span>
                            <span>+5%</span>
                            <span>+10%</span>
                            <span>+20%</span>
                            <span>+30%</span>
                            <span>+65% Max</span>
                        </div>

                        <!-- Result Preview -->
                        <div class="mt-6 p-4 bg-gray-50 rounded-xl">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-700">Current:</span>
                                <span id="previewCurrent" class="font-bold text-gray-800">0%</span>
                            </div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-700">Grace to add:</span>
                                <span id="previewGrace" class="font-bold text-green-600">+0%</span>
                            </div>
                            <div class="flex justify-between items-center border-t border-gray-300 pt-2">
                                <span class="text-gray-700 font-medium">New Total:</span>
                                <span id="previewNew" class="text-2xl font-bold text-blue-600">0%</span>
                            </div>
                            <div class="mt-3">
                                <div id="statusIndicator" class="text-center py-2 rounded-lg color-transition bg-gray-200 text-gray-700">
                                    Select a student first
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reason & Action -->
                    <form method="POST" id="graceForm">
                        <input type="hidden" name="student_id" id="studentIdInput">
                        <input type="hidden" name="grace_points" id="gracePointsInput">
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-sticky-note text-yellow-600 mr-1"></i>
                                Reason for Grace
                            </label>
                            <textarea name="reason" id="reasonInput" rows="3" 
                                      class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                      placeholder="Medical appeal, sports participation, exceptional circumstances..." required></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-4">
                            <button type="submit" name="inject_grace" id="injectButton"
                                    class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-4 rounded-xl font-bold text-lg hover:from-green-600 hover:to-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled>
                                <i class="fas fa-syringe mr-2"></i> INJECT GRACE
                            </button>
                            
                            <button type="button" id="unlockButton" 
                                    class="w-full bg-gradient-to-r from-red-500 to-red-600 text-white py-3 rounded-xl font-medium hover:from-red-600 hover:to-red-700 transition hidden"
                                    onclick="showUnlockModal()">
                                <i class="fas fa-unlock mr-2"></i> UNLOCK ACCOUNT
                            </button>
                            
                            <button type="button" onclick="resetForm()"
                                    class="w-full border border-gray-300 text-gray-700 py-3 rounded-xl font-medium hover:bg-gray-50 transition">
                                <i class="fas fa-redo mr-2"></i> RESET
                            </button>
                        </div>
                    </form>

                    <!-- Unlock Account Modal (hidden by default) -->
                    <div id="unlockModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                        <div class="bg-white rounded-2xl p-6 max-w-md w-full">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">Unlock Student Account</h3>
                            <p class="text-gray-600 mb-6">This student's account is auto-locked due to low attendance. Provide a reason for unlocking:</p>
                            
                            <form method="POST" id="unlockForm">
                                <input type="hidden" name="student_id" id="unlockStudentId">
                                <div class="mb-6">
                                    <textarea name="unlock_reason" rows="3" 
                                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                              placeholder="Reason for unlocking account..." required></textarea>
                                </div>
                                <div class="flex justify-end space-x-4">
                                    <button type="button" onclick="hideUnlockModal()"
                                            class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                                        Cancel
                                    </button>
                                    <button type="submit" name="unlock_account"
                                            class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                                        <i class="fas fa-unlock mr-2"></i> Unlock Account
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedStudent = null;
        let currentPercentage = 0;
        let isLocked = false;

        function selectStudent(element) {
            // Remove previous selection
            document.querySelectorAll('.student-card').forEach(card => {
                card.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-50');
            });
            
            // Add selection to clicked card
            element.classList.add('ring-2', 'ring-blue-500', 'bg-blue-50');
            
            // Get student data
            selectedStudent = {
                id: element.dataset.studentId,
                rollNo: element.dataset.rollNo,
                name: element.dataset.name,
                percentage: parseFloat(element.dataset.percentage)
            };
            
            currentPercentage = selectedStudent.percentage;
            isLocked = element.querySelector('.fa-lock') !== null;
            
            // Update UI
            updateSelectedStudentUI();
            updateGracePreview();
            
            // Enable form
            document.getElementById('injectButton').disabled = false;
            document.getElementById('studentIdInput').value = selectedStudent.id;
            
            // Show/hide unlock button
            if (isLocked) {
                document.getElementById('unlockButton').classList.remove('hidden');
                document.getElementById('unlockStudentId').value = selectedStudent.id;
            } else {
                document.getElementById('unlockButton').classList.add('hidden');
            }
        }

        function updateSelectedStudentUI() {
            const infoDiv = document.getElementById('selectedStudentInfo');
            infoDiv.classList.remove('hidden');
            
            document.getElementById('selectedStudentName').textContent = selectedStudent.name;
            document.getElementById('selectedStudentRoll').textContent = selectedStudent.rollNo;
            document.getElementById('currentPercentage').textContent = currentPercentage.toFixed(1);
            document.getElementById('previewCurrent').textContent = currentPercentage.toFixed(1) + '%';
        }

        function updateGracePreview() {
            const graceSlider = document.getElementById('graceSlider');
            const graceValue = parseFloat(graceSlider.value);
            const newTotal = Math.min(100, currentPercentage + graceValue);
            
            // Update displays
            document.getElementById('graceValueDisplay').textContent = `+${graceValue.toFixed(1)}%`;
            document.getElementById('previewGrace').textContent = `+${graceValue.toFixed(1)}%`;
            document.getElementById('previewNew').textContent = newTotal.toFixed(1) + '%';
            document.getElementById('gracePointsInput').value = graceValue;
            
            // Update status indicator
            const statusIndicator = document.getElementById('statusIndicator');
            if (newTotal < 35) {
                statusIndicator.className = 'text-center py-2 rounded-lg color-transition bg-red-100 text-red-700';
                statusIndicator.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> Still in Red Zone';
            } else if (newTotal < 60) {
                statusIndicator.className = 'text-center py-2 rounded-lg color-transition bg-yellow-100 text-yellow-700';
                statusIndicator.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i> Warning Zone';
            } else {
                statusIndicator.className = 'text-center py-2 rounded-lg color-transition bg-green-100 text-green-700';
                statusIndicator.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Safe Zone';
            }
        }

        function resetForm() {
            // Reset selection
            document.querySelectorAll('.student-card').forEach(card => {
                card.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-50');
            });
            
            // Hide selected student info
            document.getElementById('selectedStudentInfo').classList.add('hidden');
            
            // Reset form
            document.getElementById('graceSlider').value = 0;
            document.getElementById('reasonInput').value = '';
            document.getElementById('studentIdInput').value = '';
            document.getElementById('gracePointsInput').value = '';
            document.getElementById('injectButton').disabled = true;
            document.getElementById('unlockButton').classList.add('hidden');
            
            // Reset preview
            document.getElementById('graceValueDisplay').textContent = '0%';
            document.getElementById('previewCurrent').textContent = '0%';
            document.getElementById('previewGrace').textContent = '+0%';
            document.getElementById('previewNew').textContent = '0%';
            
            // Reset status
            document.getElementById('statusIndicator').className = 'text-center py-2 rounded-lg color-transition bg-gray-200 text-gray-700';
            document.getElementById('statusIndicator').innerHTML = 'Select a student first';
            
            selectedStudent = null;
            currentPercentage = 0;
        }

        function showUnlockModal() {
            document.getElementById('unlockModal').classList.remove('hidden');
        }

        function hideUnlockModal() {
            document.getElementById('unlockModal').classList.add('hidden');
        }

        // Event Listeners
        document.getElementById('graceSlider').addEventListener('input', updateGracePreview);
        
        document.getElementById('graceForm').addEventListener('submit', function(e) {
            if (!selectedStudent) {
                e.preventDefault();
                alert('Please select a student first.');
                return false;
            }
            
            const graceValue = parseFloat(document.getElementById('graceSlider').value);
            if (graceValue <= 0) {
                e.preventDefault();
                alert('Please set a grace percentage greater than 0.');
                return false;
            }
            
            if (!document.getElementById('reasonInput').value.trim()) {
                e.preventDefault();
                alert('Please provide a reason for the grace injection.');
                return false;
            }
            
            // Show confirmation
            const newTotal = Math.min(100, currentPercentage + graceValue);
            const confirmed = confirm(
                `Inject ${graceValue.toFixed(1)}% grace to ${selectedStudent.rollNo}?\n\n` +
                `Current: ${currentPercentage.toFixed(1)}%\n` +
                `New Total: ${newTotal.toFixed(1)}%\n\n` +
                `Reason: ${document.getElementById('reasonInput').value}`
            );
            
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
            
            // Show loading
            const button = document.getElementById('injectButton');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
            button.disabled = true;
            
            // Re-enable after 3 seconds if still on page
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 3000);
        });

        // Auto-select first red zone student if exists
        document.addEventListener('DOMContentLoaded', function() {
            const firstRedZoneStudent = document.querySelector('.student-card');
            if (firstRedZoneStudent && !document.querySelector('input[name="search"]').value) {
                setTimeout(() => {
                    selectStudent(firstRedZoneStudent);
                }, 500);
            }
            
            // Close modal on outside click
            document.getElementById('unlockModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    hideUnlockModal();
                }
            });
        });
    </script>
</body>
</html>