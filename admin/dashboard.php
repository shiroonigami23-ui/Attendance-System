<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once '../includes/Config.php';
require_once '../includes/Auth.php';

checkAuth();
if ($_SESSION['role'] != 'MASTER') {
    header('Location: ../dashboard.php');
    exit();
}

$user = getCurrentUser();

// Handle teacher approval
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_teacher'])) {
    $teacher_id = $_POST['teacher_id'];
    $pdo->prepare("UPDATE users SET approved = 1 WHERE user_id = ?")->execute([$teacher_id]);
    $_SESSION['success_message'] = "Teacher approved successfully.";
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Handle device reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_device'])) {
    $student_id = $_POST['student_id'];
    $pdo->prepare("DELETE FROM device_locks WHERE user_id = ?")->execute([$student_id]);
    $_SESSION['success_message'] = "Device lock reset. Student can log in from new device.";
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Handle subject assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_subject'])) {
    $teacher_id = $_POST['teacher_id'];
    $subject_id = $_POST['subject_id'];
    $semester = $_POST['semester'];
    $branch_code = $_POST['branch_code'];
    $section = $_POST['section'];
    $lab_batch = $_POST['lab_batch'];
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $valid_from = $_POST['valid_from'];
    $valid_until = $_POST['valid_until'];
    
    // Prepare target_group JSON
    $target_group = [
        'semester' => $semester,
        'branch' => $branch_code,
        'section' => $section == 'all' ? null : $section,
        'batch' => $lab_batch == 'all' ? null : $lab_batch
    ];
    
    try {
        // Insert into timetable_slots
        $stmt = $pdo->prepare("
            INSERT INTO timetable_slots 
            (subject_id, default_teacher_id, target_group, day_of_week, start_time, end_time, valid_from, valid_until)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $subject_id, 
            $teacher_id, 
            json_encode($target_group), 
            $day_of_week, 
            $start_time, 
            $end_time, 
            $valid_from, 
            $valid_until
        ]);
        
        // Try to log the action (optional - won't fail if logging fails)
        try {
            $subject_code = $pdo->query("SELECT code FROM subjects WHERE subject_id = $subject_id")->fetchColumn();
            $teacher_name = $pdo->query("SELECT full_name FROM users WHERE user_id = $teacher_id")->fetchColumn();
            
            // Truncate details to fit constraint (max 255 chars)
            $details = "Assigned {$subject_code} (Sem {$semester}, {$branch_code}) to " . substr($teacher_name, 0, 50);
            if (strlen($details) > 255) {
                $details = substr($details, 0, 252) . '...';
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO system_logs (user_id, action, details, ip_address)
                VALUES (?, 'SUBJECT_ASSIGNMENT', ?, ?)
            ");
            $stmt->execute([$user['user_id'], $details, $_SERVER['REMOTE_ADDR']]);
        } catch (Exception $log_error) {
            // Logging failed but that's okay - main action succeeded
            error_log("System log insert failed: " . $log_error->getMessage());
        }
        
        $_SESSION['success_message'] = "Subject assigned successfully! Timetable slot created.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error assigning subject: " . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get success message if any
$success = null;
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Get error message if any
$error = null;
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Fetch pending teachers
$pending_teachers = $pdo->query("
    SELECT user_id, email, full_name, phone, created_at 
    FROM users 
    WHERE role = 'SEMI_ADMIN' AND approved = 0
")->fetchAll();

// Fetch all teachers
$teachers = $pdo->query("
    SELECT user_id, email, full_name, approved 
    FROM users 
    WHERE role = 'SEMI_ADMIN'
    ORDER BY approved DESC, created_at DESC
")->fetchAll();

// Fetch all students
$students = $pdo->query("
    SELECT u.user_id, u.email, u.full_name, u.phone, sp.roll_no, sp.current_semester, sp.section,
           dl.device_fingerprint, dl.locked_at
    FROM users u
    LEFT JOIN student_profiles sp ON u.user_id = sp.user_id
    LEFT JOIN device_locks dl ON u.user_id = dl.user_id
    WHERE u.role = 'STUDENT'
    ORDER BY sp.roll_no
")->fetchAll();

// Fetch all subjects
$subjects = $pdo->query("SELECT subject_id, code, name, is_lab FROM subjects")->fetchAll();

// Fetch unique branch codes
$branch_stmt = $pdo->query("SELECT DISTINCT branch_code FROM student_profiles WHERE branch_code IS NOT NULL AND branch_code != '' ORDER BY branch_code");
$branch_codes = $branch_stmt->fetchAll(PDO::FETCH_COLUMN);

// If no branches found, try from timetable_slots
if (empty($branch_codes)) {
    $branch_stmt = $pdo->query("SELECT DISTINCT JSON_EXTRACT(target_group, '$.branch') as branch FROM timetable_slots WHERE JSON_EXTRACT(target_group, '$.branch') IS NOT NULL");
    $branch_codes_raw = $branch_stmt->fetchAll(PDO::FETCH_COLUMN);
    $branch_codes = array_map(function($code) {
        return trim($code, '"');
    }, $branch_codes_raw);
}

// Create branches array
$branches = [];
foreach ($branch_codes as $code) {
    $branches[] = [
        'branch_code' => $code,
        'branch_name' => $code
    ];
}

// If still no branches found, add default CS
if (empty($branches)) {
    $branches = [
        ['branch_code' => 'CS', 'branch_name' => 'CS']
    ];
}

// Stats
$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'STUDENT'")->fetchColumn();
$total_teachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'SEMI_ADMIN'")->fetchColumn();
$pending_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'SEMI_ADMIN' AND approved = 0")->fetchColumn();
$active_sessions = $pdo->query("SELECT COUNT(*) FROM live_sessions WHERE status = 'LIVE'")->fetchColumn();
$red_zone_count = $pdo->query("
    SELECT COUNT(DISTINCT sp.user_id) 
    FROM student_profiles sp
    LEFT JOIN student_attendance_summary sas ON sp.user_id = sas.student_id
    WHERE (COALESCE(sas.total_sessions, 0) = 0 OR 
           ((COALESCE(sas.present_count, 0) + (COALESCE(sas.late_count, 0) * 0.5)) / NULLIF(sas.total_sessions, 0) * 100) < 35)
")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stat-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        .pulse-alert {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .glassmorphism {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .step-card {
            transition: all 0.3s ease;
        }
        .rotate-180 {
            transform: rotate(180deg);
        }
        /* FORCE VISIBILITY */
        #step1, #step2, #step3 {
            position: relative !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Master Admin Panel</h1>
                    <p class="text-gray-600 mt-1">Global authority over system configuration, users, and academic structure</p>
                </div>
                <div class="flex items-center space-x-3 glassmorphism rounded-2xl p-3">
                    <div class="h-12 w-12 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center">
                        <i class="fas fa-crown text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="font-medium text-gray-800"><?= htmlspecialchars($user['full_name'] ?? 'Master Admin') ?></p>
                        <span class="text-xs text-gray-500">Super Administrator</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-white/20 flex items-center justify-center mr-4">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold"><?= $total_students ?></p>
                        <p class="text-blue-100">Total Students</p>
                    </div>
                </div>
            </div>
            <div class="stat-card bg-gradient-to-br from-yellow-500 to-yellow-600 text-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-white/20 flex items-center justify-center mr-4">
                        <i class="fas fa-chalkboard-teacher text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold"><?= $total_teachers ?></p>
                        <p class="text-yellow-100">Teachers</p>
                    </div>
                </div>
            </div>
            <div class="stat-card bg-gradient-to-br from-red-500 to-red-600 text-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-white/20 flex items-center justify-center mr-4">
                        <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold"><?= $red_zone_count ?></p>
                        <p class="text-red-100">Red Zone</p>
                    </div>
                </div>
            </div>
            <div class="stat-card bg-gradient-to-br from-green-500 to-green-600 text-white rounded-2xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-white/20 flex items-center justify-center mr-4">
                        <i class="fas fa-broadcast-tower text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold"><?= $active_sessions ?></p>
                        <p class="text-green-100">Live Sessions</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($success)): ?>
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg flex items-center" id="successMessage">
            <i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i>
            <span class="text-green-700 font-medium"><?= htmlspecialchars($success) ?></span>
        </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if (isset($error)): ?>
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg flex items-center" id="errorMessage">
            <i class="fas fa-exclamation-circle text-red-500 mr-3 text-lg"></i>
            <span class="text-red-700 font-medium"><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Pending Approvals & Subject Assignment -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Pending Approvals -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-user-clock text-yellow-600 mr-3"></i> Pending Teacher Approvals
                            <?php if ($pending_count > 0): ?>
                            <span class="ml-2 bg-red-500 text-white text-xs rounded-full h-6 w-6 inline-flex items-center justify-center pulse-alert">
                                <?= $pending_count ?>
                            </span>
                            <?php endif; ?>
                        </h2>
                        <span class="text-sm text-gray-500"><?= count($pending_teachers) ?> pending</span>
                    </div>
                    
                    <?php if (empty($pending_teachers)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-check-circle text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900">All Clear!</h3>
                        <p class="text-gray-500">No pending teacher approvals.</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                        <?php foreach ($pending_teachers as $teacher): ?>
                        <div class="border border-gray-200 rounded-xl p-5 hover:shadow-md transition">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div class="flex items-center">
                                    <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center mr-4">
                                        <i class="fas fa-user-clock text-yellow-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-800"><?= htmlspecialchars($teacher['full_name'] ?? $teacher['email']) ?></p>
                                        <p class="text-sm text-gray-600"><?= htmlspecialchars($teacher['email']) ?></p>
                                        <?php if ($teacher['phone']): ?>
                                        <p class="text-xs text-gray-500">Phone: <?= htmlspecialchars($teacher['phone']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <form method="POST">
                                        <input type="hidden" name="teacher_id" value="<?= $teacher['user_id'] ?>">
                                        <button type="submit" name="approve_teacher" 
                                                class="bg-gradient-to-r from-green-500 to-green-600 text-white px-5 py-2.5 rounded-lg font-medium hover:from-green-600 hover:to-green-700 transition flex items-center">
                                            <i class="fas fa-check mr-2"></i> Approve
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-500">
                                <i class="far fa-clock mr-1"></i>
                                Applied on <?= date('F j, Y', strtotime($teacher['created_at'])) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Subject Assignment -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-book-open text-blue-600 mr-3"></i> Assign Subject to Teacher
                    </h2>
                    
                    <!-- Step Progress Indicator -->
                    <div class="flex items-center justify-center mb-8">
                        <div class="flex items-center">
                            <div class="step-indicator flex flex-col items-center mx-4" data-step="1">
                                <div class="h-10 w-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold mb-2 step-circle">
                                    1
                                </div>
                                <span class="text-sm font-medium text-blue-600">Basic Info</span>
                            </div>
                            <div class="h-1 w-16 bg-gray-300"></div>
                            <div class="step-indicator flex flex-col items-center mx-4" data-step="2">
                                <div class="h-10 w-10 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold mb-2 step-circle">
                                    2
                                </div>
                                <span class="text-sm font-medium text-gray-500">Subject</span>
                            </div>
                            <div class="h-1 w-16 bg-gray-300"></div>
                            <div class="step-indicator flex flex-col items-center mx-4" data-step="3">
                                <div class="h-10 w-10 rounded-full bg-gray-300 text-gray-600 flex items-center justify-center font-bold mb-2 step-circle">
                                    3
                                </div>
                                <span class="text-sm font-medium text-gray-500">Schedule</span>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" id="assignSubjectForm" class="space-y-6" onsubmit="return handleFormSubmit(event)">
                        <!-- Step 1: Basic Selection -->
                        <div class="step-card p-6 border-2 border-blue-200 rounded-2xl bg-blue-50" id="step1">
                            <h3 class="text-lg font-bold text-blue-800 mb-4 flex items-center">
                                <i class="fas fa-info-circle mr-2"></i> Basic Information
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-graduation-cap mr-1 text-blue-500"></i> Semester *
                                    </label>
                                    <select name="semester" required id="semesterSelect" onchange="checkStep1()"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                        <option value="">Select Semester</option>
                                        <?php for ($i = 1; $i <= 8; $i++): ?>
                                        <option value="<?= $i ?>">Semester <?= $i ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-code-branch mr-1 text-blue-500"></i> Branch *
                                    </label>
                                    <select name="branch_code" required id="branchSelect" onchange="checkStep1()"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                        <option value="">Select Branch</option>
                                        <?php foreach ($branches as $branch): ?>
                                        <option value="<?= htmlspecialchars($branch['branch_code']) ?>">
                                            <?= htmlspecialchars($branch['branch_code']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-chalkboard-teacher mr-1 text-blue-500"></i> Teacher *
                                    </label>
                                    <select name="teacher_id" required id="teacherSelect" onchange="checkStep1()"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                        <option value="">Select Teacher</option>
                                        <?php foreach ($teachers as $t): ?>
                                        <?php if ($t['approved']): ?>
                                        <option value="<?= $t['user_id'] ?>">
                                            <?= htmlspecialchars($t['full_name'] ?: $t['email']) ?>
                                        </option>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-6 text-center">
                                <button type="button" onclick="return loadSubjects()" id="step1Btn"
                                        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition font-medium inline-flex items-center disabled:opacity-50 disabled:cursor-not-allowed"
                                        disabled>
                                    <i class="fas fa-search mr-2"></i> Find Available Subjects
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Subject Selection -->
                        <div class="step-card p-6 border-2 border-green-200 rounded-2xl bg-green-50" id="step2" style="display:none;">
                            <h3 class="text-lg font-bold text-green-800 mb-4 flex items-center">
                                <i class="fas fa-book mr-2"></i> Select Subject
                            </h3>
                            
                            <div id="subjectLoader" style="display:none;">
                                <div class="text-center py-8">
                                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto mb-4"></div>
                                    <p class="text-green-700 font-medium">Loading subjects...</p>
                                </div>
                            </div>
                            
                            <div id="subjectContent">
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Subject *</label>
                                    <select name="subject_id" required id="subjectSelect" onchange="document.getElementById('step2Btn').disabled = !this.value"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-transparent transition">
                                        <option value="">Select Subject</option>
                                    </select>
                                </div>

                                <div class="flex justify-between items-center">
                                    <button type="button" onclick="return backToStep1()" 
                                            class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition inline-flex items-center">
                                        <i class="fas fa-arrow-left mr-2"></i> Back
                                    </button>
                                    <button type="button" onclick="return showScheduleOptions()" id="step2Btn"
                                            class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl hover:from-green-700 hover:to-green-800 transition font-medium inline-flex items-center disabled:opacity-50 disabled:cursor-not-allowed"
                                            disabled>
                                        Next: Schedule <i class="fas fa-arrow-right ml-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Schedule Options -->
                        <div class="step-card p-6 border-2 border-purple-200 rounded-2xl bg-purple-50" id="step3" style="display:none;">
                            <h3 class="text-lg font-bold text-purple-800 mb-6 flex items-center">
                                <i class="fas fa-calendar-alt mr-2"></i> Schedule Configuration
                            </h3>
                            
                            <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Section</label>
                                        <select name="section" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                                            <option value="A">Section A</option>
                                            <option value="B">Section B</option>
                                            <option value="C">Section C</option>
                                            <option value="all">All Sections</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Day of Week</label>
                                        <select name="day_of_week" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                                            <option value="Monday">Monday</option>
                                            <option value="Tuesday">Tuesday</option>
                                            <option value="Wednesday">Wednesday</option>
                                            <option value="Thursday">Thursday</option>
                                            <option value="Friday">Friday</option>
                                            <option value="Saturday">Saturday</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Time Slot</label>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Start Time</label>
                                            <input type="time" name="start_time" value="09:00" 
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">End Time</label>
                                            <input type="time" name="end_time" value="10:00" 
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Validity Period</label>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">From Date</label>
                                            <input type="date" name="valid_from" value="<?= date('Y-m-d') ?>" 
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Until Date</label>
                                            <input type="date" name="valid_until" value="<?= date('Y-m-d', strtotime('+6 months')) ?>" 
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Lab Batch (Optional)</label>
                                    <select name="lab_batch" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                                        <option value="">No Lab Batch</option>
                                        <option value="A1">Batch A1</option>
                                        <option value="A2">Batch A2</option>
                                        <option value="B1">Batch B1</option>
                                        <option value="B2">Batch B2</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-8 flex justify-between items-center">
                                <button type="button" onclick="return backToStep2()" 
                                        class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition inline-flex items-center">
                                    <i class="fas fa-arrow-left mr-2"></i> Back
                                </button>
                                <button type="submit" name="assign_subject"
                                        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-xl hover:from-purple-700 hover:to-purple-800 transition font-medium inline-flex items-center">
                                    <i class="fas fa-link mr-2"></i> Create Assignment
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-8">
                <!-- Grace Console -->
                <div class="bg-gradient-to-br from-purple-500 to-pink-500 text-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center mb-4">
                        <div class="h-12 w-12 rounded-full bg-white/20 flex items-center justify-center mr-4">
                            <i class="fas fa-sliders-h text-white text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold">Grace Console</h2>
                            <p class="text-purple-100 text-sm">Adjust attendance thresholds</p>
                        </div>
                    </div>
                    <p class="text-purple-100 mb-6">Modify attendance percentages for detained students in Red Zone.</p>
                    <a href="grace_console.php" 
                       class="inline-block w-full bg-white text-purple-600 py-3.5 rounded-xl font-bold hover:bg-gray-100 transition text-center">
                        <i class="fas fa-external-link-alt mr-2"></i> Open Grace Console
                    </a>
                </div>

                <!-- Device Management -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-mobile-alt text-purple-600 mr-3"></i> Device Management
                    </h2>
                    <div class="space-y-3 max-h-80 overflow-y-auto pr-2">
                        <?php foreach (array_slice($students, 0, 6) as $s): ?>
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full <?= $s['device_fingerprint'] ? 'bg-red-100' : 'bg-green-100' ?> flex items-center justify-center mr-3">
                                    <i class="fas <?= $s['device_fingerprint'] ? 'fa-lock text-red-600' : 'fa-unlock text-green-600' ?>"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800"><?= htmlspecialchars($s['roll_no'] ?? 'N/A') ?></p>
                                    <p class="text-xs text-gray-500 truncate max-w-[120px]">
                                        <?= htmlspecialchars(explode('@', $s['email'])[0]) ?>
                                    </p>
                                </div>
                            </div>
                            <div>
                                <?php if ($s['device_fingerprint']): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="student_id" value="<?= $s['user_id'] ?>">
                                    <button type="submit" name="reset_device" 
                                            class="text-red-600 hover:text-red-800 text-sm font-medium flex items-center"
                                            onclick="return confirm('Reset device lock for <?= htmlspecialchars(addslashes($s['roll_no'] ?? 'Student')) ?>?')">
                                        <i class="fas fa-unlock mr-1"></i> Reset
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-xs text-gray-500">Unlocked</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($students) > 6): ?>
                    <div class="mt-4 text-center">
                        <a href="device_management.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View all <?= count($students) ?> students
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Emergency Controls -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i> Emergency Controls
                    </h2>
                    <div class="space-y-4">
                        <!-- Red Button -->
                        <div class="p-5 bg-gradient-to-r from-red-50 to-orange-50 border border-red-200 rounded-xl hover:shadow-md transition">
                            <div class="flex items-center mb-3">
                                <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-ban text-red-600"></i>
                                </div>
                                <h3 class="font-bold text-red-800">Cancel Classes</h3>
                            </div>
                            <p class="text-sm text-red-700 mb-4">Cancel all classes for today or selected date range.</p>
                            <a href="cancel_classes.php" 
                               class="block w-full bg-gradient-to-r from-red-600 to-red-700 text-white py-3 rounded-xl font-bold hover:from-red-700 hover:to-red-800 transition flex items-center justify-center">
                                <i class="fas fa-ban mr-2"></i> MANAGE CANCELLATIONS
                            </a>
                        </div>

                        <!-- Semester Promotion -->
                        <div class="p-5 bg-gradient-to-r from-yellow-50 to-amber-50 border border-yellow-200 rounded-xl hover:shadow-md transition">
                            <div class="flex items-center mb-3">
                                <div class="h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-graduation-cap text-yellow-600"></i>
                                </div>
                                <h3 class="font-bold text-yellow-800">Semester Promotion</h3>
                            </div>
                            <p class="text-sm text-yellow-700 mb-4">Promote students to next semester. Archive final year students.</p>
                            <a href="semester_promotion.php" 
                               class="block w-full bg-gradient-to-r from-yellow-500 to-yellow-600 text-white py-3 rounded-xl font-bold hover:from-yellow-600 hover:to-yellow-700 transition flex items-center justify-center">
                                <i class="fas fa-graduation-cap mr-2"></i> RUN PROMOTION
                            </a>
                        </div>

                        <!-- Substitute Override -->
                        <div class="p-5 bg-gradient-to-r from-blue-50 to-cyan-50 border border-blue-200 rounded-xl hover:shadow-md transition">
                            <div class="flex items-center mb-3">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-user-friends text-blue-600"></i>
                                </div>
                                <h3 class="font-bold text-blue-800">Substitute Management</h3>
                            </div>
                            <p class="text-sm text-blue-700 mb-4">Force-assign substitute teachers for unavailable staff.</p>
                            <a href="substitute_management.php" 
                               class="block w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-3 rounded-xl font-bold hover:from-blue-600 hover:to-blue-700 transition flex items-center justify-center">
                                <i class="fas fa-user-friends mr-2"></i> MANAGE SUBSTITUTES
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // CRITICAL: Preserve step elements
        let step1El, step2El, step3El, loaderEl, contentEl, selectEl;
        
        function initElements() {
            step1El = document.getElementById('step1');
            step2El = document.getElementById('step2');
            step3El = document.getElementById('step3');
            loaderEl = document.getElementById('subjectLoader');
            contentEl = document.getElementById('subjectContent');
            selectEl = document.getElementById('subjectSelect');
            
            console.log('Elements initialized:', {
                step1: !!step1El,
                step2: !!step2El,
                step3: !!step3El,
                loader: !!loaderEl,
                content: !!contentEl,
                select: !!selectEl
            });
        }
        
        function handleFormSubmit(e) {
            const submitter = e.submitter;
            if (!submitter || submitter.getAttribute('name') !== 'assign_subject') {
                e.preventDefault();
                console.log('Form submission blocked - not final submit');
                return false;
            }
            return true;
        }
        
        function checkStep1() {
            const sem = document.getElementById('semesterSelect').value;
            const branch = document.getElementById('branchSelect').value;
            const teacher = document.getElementById('teacherSelect').value;
            document.getElementById('step1Btn').disabled = !(sem && branch && teacher);
        }
        
        function updateStepIndicator(step) {
            document.querySelectorAll('.step-indicator').forEach(ind => {
                const circle = ind.querySelector('.step-circle');
                const text = ind.querySelector('span');
                const num = parseInt(ind.dataset.step);
                
                circle.className = 'h-10 w-10 rounded-full flex items-center justify-center font-bold mb-2 step-circle';
                text.className = 'text-sm font-medium';
                
                if (num < step) {
                    circle.classList.add('bg-green-500', 'text-white');
                    text.classList.add('text-green-600');
                } else if (num === step) {
                    circle.classList.add('bg-blue-600', 'text-white');
                    text.classList.add('text-blue-600');
                } else {
                    circle.classList.add('bg-gray-300', 'text-gray-600');
                    text.classList.add('text-gray-500');
                }
            });
        }
        
        function loadSubjects() {
            const sem = document.getElementById('semesterSelect').value;
            const branch = document.getElementById('branchSelect').value;
            
            if (!sem || !branch) {
                alert('Please select semester and branch');
                return false;
            }
            
            console.log('Loading subjects for:', sem, branch);
            
            // Show step 2
            step1El.style.display = 'none';
            step2El.style.display = 'block';
            updateStepIndicator(2);
            
            // Show loader
            loaderEl.style.display = 'block';
            contentEl.style.display = 'none';
            selectEl.innerHTML = '<option value="">Loading...</option>';
            document.getElementById('step2Btn').disabled = true;
            
            // Fetch subjects
            fetch('get_subjects.php?semester=' + sem + '&branch=' + branch)
                .then(r => r.json())
                .then(data => {
                    console.log('Subjects loaded:', data);
                    loaderEl.style.display = 'none';
                    contentEl.style.display = 'block';
                    
                    selectEl.innerHTML = '<option value="">Select Subject</option>';
                    
                    if (data.success && data.subjects && data.subjects.length > 0) {
                        data.subjects.forEach(sub => {
                            const opt = document.createElement('option');
                            opt.value = sub.subject_id;
                            opt.textContent = sub.code + ' - ' + sub.name;
                            selectEl.appendChild(opt);
                        });
                    } else {
                        selectEl.innerHTML = '<option value="">No subjects found</option>';
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    loaderEl.innerHTML = '<div class="text-red-600 p-4">Error loading subjects</div>';
                    loaderEl.style.display = 'block';
                });
            
            return false;
        }
        
        function backToStep1() {
            step2El.style.display = 'none';
            step1El.style.display = 'block';
            updateStepIndicator(1);
            return false;
        }
        
        function showScheduleOptions() {
            if (!selectEl.value) {
                alert('Please select a subject');
                return false;
            }
            step2El.style.display = 'none';
            step3El.style.display = 'block';
            updateStepIndicator(3);
            return false;
        }
        
        function backToStep2() {
            step3El.style.display = 'none';
            step2El.style.display = 'block';
            updateStepIndicator(2);
            return false;
        }
        
        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard loaded');
            initElements();
            checkStep1();
            
            // Auto-hide success message
            const msg = document.getElementById('successMessage');
            if (msg) {
                setTimeout(() => {
                    msg.style.opacity = '0';
                    setTimeout(() => msg.remove(), 300);
                }, 5000);
            }
            
            // Auto-hide error message
            const errMsg = document.getElementById('errorMessage');
            if (errMsg) {
                setTimeout(() => {
                    errMsg.style.opacity = '0';
                    setTimeout(() => errMsg.remove(), 300);
                }, 7000);
            }
        });
    </script>
</body>
</html>

<?php
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function get_action_icon($action) {
    $icons = [
        'RED_BUTTON' => 'fas fa-ban',
        'ACCOUNT_UNLOCK' => 'fas fa-unlock',
        'TEACHER_APPROVAL' => 'fas fa-user-check',
        'DEVICE_RESET' => 'fas fa-mobile-alt',
        'GRACE_INJECTION' => 'fas fa-sliders-h',
        'SUBJECT_ASSIGNMENT' => 'fas fa-book',
        'LOGIN' => 'fas fa-sign-in-alt',
        'LOGOUT' => 'fas fa-sign-out-alt'
    ];
    return $icons[$action] ?? 'fas fa-info-circle';
}

function get_action_color($action) {
    $colors = [
        'RED_BUTTON' => 'bg-red-500',
        'ACCOUNT_UNLOCK' => 'bg-green-500',
        'TEACHER_APPROVAL' => 'bg-blue-500',
        'DEVICE_RESET' => 'bg-purple-500',
        'GRACE_INJECTION' => 'bg-pink-500',
        'SUBJECT_ASSIGNMENT' => 'bg-indigo-500'
    ];
    return $colors[$action] ?? 'bg-gray-500';
}
?>