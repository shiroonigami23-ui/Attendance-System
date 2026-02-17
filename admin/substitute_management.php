<?php
require_once '../includes/Config.php';
require_once '../includes/Auth.php';

checkAuth();
if ($_SESSION['role'] != 'MASTER') {
    header('Location: ../dashboard.php');
    exit();
}

$user = getCurrentUser();

// Handle substitute assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_substitute'])) {
    $slot_id = $_POST['slot_id'];
    $original_teacher_id = $_POST['original_teacher_id'];
    $substitute_teacher_id = $_POST['substitute_teacher_id'];
    $substitution_date = $_POST['substitution_date'];
    $reason = $_POST['reason'] ?? '';
    
    // Validate: Can't substitute same teacher
    if ($original_teacher_id == $substitute_teacher_id) {
        $error = "Substitute teacher cannot be the same as original teacher.";
    } else {
        // Create a live session with substitute teacher
        $stmt = $pdo->prepare("
            INSERT INTO live_sessions 
            (slot_id, actual_teacher_id, session_date, status, created_by)
            VALUES (?, ?, ?, 'SCHEDULED', ?)
        ");
        $stmt->execute([$slot_id, $substitute_teacher_id, $substitution_date, $user['user_id']]);
        
        // Log the action
        $details = "Assigned substitute teacher for slot $slot_id on $substitution_date";
        if (!empty($reason)) {
            $details .= ". Reason: $reason";
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (user_id, action, details, ip_address, user_agent)
            VALUES (?, 'SUBSTITUTE_ASSIGNMENT', ?, ?, ?)
        ");
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $stmt->execute([
            $user['user_id'], 
            'SUBSTITUTE_ASSIGNMENT',
            $details, 
            $_SERVER['REMOTE_ADDR'],
            $user_agent
        ]);
        
        $success = "Substitute teacher assigned successfully for " . date('F j, Y', strtotime($substitution_date));
    }
}

// Handle cancellation of substitute assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_substitute'])) {
    $session_id = $_POST['session_id'];
    
    $stmt = $pdo->prepare("
        UPDATE live_sessions 
        SET status = 'CANCELLED', cancelled_by = ?, cancelled_at = NOW()
        WHERE session_id = ?
    ");
    $stmt->execute([$user['user_id'], $session_id]);
    
    // Log the action
    $stmt = $pdo->prepare("
        INSERT INTO system_logs (user_id, action, details, ip_address, user_agent)
        VALUES (?, 'SUBSTITUTE_CANCELLATION', 'Cancelled substitute assignment for session $session_id', ?, ?)
    ");
    
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $stmt->execute([
        $user['user_id'], 
        'SUBSTITUTE_CANCELLATION',
        'Cancelled substitute assignment for session ' . $session_id, 
        $_SERVER['REMOTE_ADDR'],
        $user_agent
    ]);
    
    $success = "Substitute assignment cancelled successfully.";
}

// Fetch all teachers
$teachers = $pdo->query("
    SELECT user_id, email, full_name, approved 
    FROM users 
    WHERE role = 'SEMI_ADMIN' AND approved = 1
    ORDER BY full_name
")->fetchAll();

// Fetch upcoming timetable slots (next 7 days)
$upcoming_slots = $pdo->query("
    SELECT 
        ts.slot_id,
        ts.day_of_week,
        ts.start_time,
        ts.end_time,
        ts.target_group,
        s.code as subject_code,
        s.name as subject_name,
        u.full_name as teacher_name,
        u.user_id as teacher_id,
        COUNT(ls.session_id) as existing_sessions
    FROM timetable_slots ts
    JOIN subjects s ON ts.subject_id = s.subject_id
    JOIN users u ON ts.default_teacher_id = u.user_id
    LEFT JOIN live_sessions ls ON ts.slot_id = ls.slot_id 
        AND ls.session_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND ls.status IN ('SCHEDULED', 'LIVE')
    WHERE ts.valid_from <= CURDATE() 
        AND ts.valid_until >= CURDATE()
    GROUP BY ts.slot_id, ts.day_of_week, ts.start_time, ts.end_time, 
             s.code, s.name, u.full_name, u.user_id, ts.target_group
    ORDER BY 
        FIELD(ts.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
        ts.start_time
")->fetchAll();

// Fetch existing substitute assignments (next 7 days)
$substitute_assignments = $pdo->query("
    SELECT 
        ls.session_id,
        ls.slot_id,
        ls.session_date,
        ls.status,
        ts.start_time,
        ts.end_time,
        s.code as subject_code,
        s.name as subject_name,
        orig.full_name as original_teacher,
        sub.full_name as substitute_teacher,
        COALESCE(ls.started_at, CONCAT(ls.session_date, ' 00:00:00')) as assigned_at
    FROM live_sessions ls
    JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
    JOIN subjects s ON ts.subject_id = s.subject_id
    JOIN users orig ON ts.default_teacher_id = orig.user_id
    JOIN users sub ON ls.actual_teacher_id = sub.user_id
    WHERE ls.session_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND ls.actual_teacher_id != ts.default_teacher_id
        AND ls.status IN ('SCHEDULED', 'LIVE')
    ORDER BY ls.session_date, ts.start_time
")->fetchAll();
// Calculate dates for the next 7 days
$next_dates = [];
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    $day_name = date('l', strtotime($date));
    $next_dates[] = [
        'date' => $date,
        'day_name' => $day_name,
        'display' => date('D, M j', strtotime($date))
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Substitute Management - Master Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="dashboard.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
            <div class="flex items-center">
                <div class="h-14 w-14 rounded-full bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center mr-4">
                    <i class="fas fa-user-friends text-white text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Substitute Teacher Management</h1>
                    <p class="text-gray-600">Force-assign substitute teachers for unavailable staff. Bypasses peer-to-peer handshake.</p>
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
           <!-- Left Column: Assign Substitute -->
<div class="lg:col-span-2">
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
            <i class="fas fa-user-plus text-blue-600 mr-3"></i> Assign Substitute Teacher
        </h2>
        
        <form method="POST" id="assignSubstituteForm">
            <div class="space-y-6">
                <!-- Step 1: Filter by Teacher -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-bold text-blue-800 flex items-center">
                            <span class="h-6 w-6 rounded-full bg-blue-600 text-white flex items-center justify-center mr-2">1</span>
                            Select Original Teacher
                        </h3>
                        <button type="button" onclick="resetToStep(1)" class="text-xs text-blue-600 hover:text-blue-800">
                            <i class="fas fa-redo mr-1"></i> Change
                        </button>
                    </div>
                    <div class="relative">
                        <select name="filter_teacher" id="filterTeacher" 
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition appearance-none">
                            <option value="">-- Select teacher --</option>
                            <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= $teacher['user_id'] ?>">
                                <?= htmlspecialchars($teacher['full_name'] ?: $teacher['email']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle"></i> Teacher who needs a substitute
                    </p>
                </div>

                <!-- Step 2: Filter by Day -->
                <div id="dayFilterSection" class="hidden bg-green-50 border border-green-200 rounded-xl p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-bold text-green-800 flex items-center">
                            <span class="h-6 w-6 rounded-full bg-green-600 text-white flex items-center justify-center mr-2">2</span>
                            Select Day
                        </h3>
                        <button type="button" onclick="resetToStep(2)" class="text-xs text-green-600 hover:text-green-800">
                            <i class="fas fa-redo mr-1"></i> Change
                        </button>
                    </div>
                    <div class="grid grid-cols-3 gap-2" id="dayFilterButtons">
                        <!-- Days will be populated by JavaScript -->
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle"></i> Select which day of the week
                    </p>
                </div>

                <!-- Step 3: Select Specific Slot -->
                <div id="slotSelectionSection" class="hidden bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-bold text-yellow-800 flex items-center">
                            <span class="h-6 w-6 rounded-full bg-yellow-600 text-white flex items-center justify-center mr-2">3</span>
                            Select Class Slot
                        </h3>
                        <button type="button" onclick="resetToStep(3)" class="text-xs text-yellow-600 hover:text-yellow-800">
                            <i class="fas fa-redo mr-1"></i> Change
                        </button>
                    </div>
                    <div class="relative">
                        <select name="slot_id" required id="slotSelect"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-transparent transition appearance-none">
                            <option value="">-- Select class slot --</option>
                        </select>
                        <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <div class="mt-3 p-3 bg-white border border-gray-200 rounded-lg">
                        <h4 class="font-medium text-gray-800 mb-2">Selected Slot Details:</h4>
                        <div class="space-y-1 text-sm">
                            <div class="flex">
                                <span class="text-gray-600 w-24">Teacher:</span>
                                <span class="font-medium" id="selectedTeacherName">--</span>
                            </div>
                            <div class="flex">
                                <span class="text-gray-600 w-24">Subject:</span>
                                <span id="selectedSubject">--</span>
                            </div>
                            <div class="flex">
                                <span class="text-gray-600 w-24">Time:</span>
                                <span id="selectedTime">--</span>
                            </div>
                            <div class="flex">
                                <span class="text-gray-600 w-24">Details:</span>
                                <span id="selectedDetails">--</span>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="original_teacher_id" id="originalTeacherId">
                </div>

                <!-- Step 4: Select Date -->
                <div id="dateSelectionSection" class="hidden bg-purple-50 border border-purple-200 rounded-xl p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-bold text-purple-800 flex items-center">
                            <span class="h-6 w-6 rounded-full bg-purple-600 text-white flex items-center justify-center mr-2">4</span>
                            Select Date
                        </h3>
                        <button type="button" onclick="resetToStep(4)" class="text-xs text-purple-600 hover:text-purple-800">
                            <i class="fas fa-redo mr-1"></i> Change
                        </button>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2" id="dateSelectionButtons">
                        <!-- Dates will be populated by JavaScript -->
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle"></i> Select which specific date
                    </p>
                </div>

                <!-- Step 5: Select Substitute Teacher -->
                <div id="substituteSelectionSection" class="hidden bg-pink-50 border border-pink-200 rounded-xl p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-bold text-pink-800 flex items-center">
                            <span class="h-6 w-6 rounded-full bg-pink-600 text-white flex items-center justify-center mr-2">5</span>
                            Select Substitute Teacher
                        </h3>
                        <button type="button" onclick="resetToStep(5)" class="text-xs text-pink-600 hover:text-pink-800">
                            <i class="fas fa-redo mr-1"></i> Change
                        </button>
                    </div>
                    <div class="relative">
                        <select name="substitute_teacher_id" required id="substituteTeacherSelect"
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition appearance-none">
                            <option value="">-- Select substitute --</option>
                            <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= $teacher['user_id'] ?>">
                                <?= htmlspecialchars($teacher['full_name'] ?: $teacher['email']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                            <i class="fas fa-user-clock"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle"></i> Teacher who will take over
                    </p>
                </div>

                <!-- Step 6: Reason (Optional) -->
                <div id="reasonSection" class="hidden bg-gray-50 border border-gray-200 rounded-xl p-4">
                    <h3 class="font-bold text-gray-800 mb-3 flex items-center">
                        <span class="h-6 w-6 rounded-full bg-gray-600 text-white flex items-center justify-center mr-2">6</span>
                        Reason (Optional)
                    </h3>
                    <textarea name="reason" rows="2" 
                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                              placeholder="e.g., Teacher on leave, Medical emergency..."></textarea>
                    <p class="text-xs text-gray-500 mt-2">
                        <i class="fas fa-info-circle"></i> Will be recorded in audit log
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-4">
                    <button type="reset" onclick="resetAll()"
                            class="flex-1 border-2 border-gray-300 text-gray-700 py-3.5 rounded-xl font-bold hover:bg-gray-50 transition flex items-center justify-center">
                        <i class="fas fa-trash-alt mr-2"></i> Clear All
                    </button>
                    <button type="submit" name="assign_substitute" id="submitButton"
                            class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white py-3.5 rounded-xl font-bold hover:from-blue-600 hover:to-blue-700 transition flex items-center justify-center opacity-50 cursor-not-allowed"
                            disabled>
                        <i class="fas fa-user-check mr-2"></i> ASSIGN SUBSTITUTE
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
            <!-- Right Column: Current Assignments -->
            <div class="lg:col-span-1">
                <!-- Active Substitutes -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-list-check text-green-600 mr-3"></i> Active Substitutes
                        <span class="ml-2 bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded-full">
                            <?= count($substitute_assignments) ?>
                        </span>
                    </h2>
                    
                    <?php if (empty($substitute_assignments)): ?>
                    <div class="text-center py-6">
                        <i class="fas fa-user-friends text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900">No Active Substitutes</h3>
                        <p class="text-gray-500">No substitute assignments for the next 7 days.</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($substitute_assignments as $assignment): ?>
                        <div class="border border-gray-200 rounded-xl p-4 hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h4 class="font-bold text-gray-800"><?= htmlspecialchars($assignment['subject_code']) ?></h4>
                                    <p class="text-sm text-gray-600">
                                        <?= date('D, M j', strtotime($assignment['session_date'])) ?> 
                                        | <?= date('h:i A', strtotime($assignment['start_time'])) ?>
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?= $assignment['status'] == 'LIVE' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                    <?= $assignment['status'] ?>
                                </span>
                            </div>
                            
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center">
                                    <i class="fas fa-user text-gray-400 mr-2 w-4"></i>
                                    <span class="text-gray-600">Original: </span>
                                    <span class="ml-1 font-medium"><?= htmlspecialchars($assignment['original_teacher']) ?></span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-user-clock text-blue-400 mr-2 w-4"></i>
                                    <span class="text-gray-600">Substitute: </span>
                                    <span class="ml-1 font-medium text-blue-600"><?= htmlspecialchars($assignment['substitute_teacher']) ?></span>
                                </div>
                            </div>
                            
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="session_id" value="<?= $assignment['session_id'] ?>">
                                    <button type="submit" name="cancel_substitute" 
                                            class="text-red-600 hover:text-red-800 text-xs font-medium flex items-center"
                                            onclick="return confirm('Cancel this substitute assignment?\n\n<?= htmlspecialchars($assignment['subject_code']) ?> on <?= date('M j', strtotime($assignment['session_date'])) ?>\n\nSubstitute: <?= htmlspecialchars($assignment['substitute_teacher']) ?>')">
                                        <i class="fas fa-times mr-1"></i> Cancel Assignment
                                    </button>
                                </form>
                                <span class="text-xs text-gray-500 float-right">
                                    <?= date('M j, g:i A', strtotime($assignment['assigned_at'])) ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Stats -->
                <div class="bg-gradient-to-br from-blue-500 to-cyan-500 text-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center mb-4">
                        <div class="h-12 w-12 rounded-full bg-white/20 flex items-center justify-center mr-4">
                            <i class="fas fa-chart-bar text-white text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold">Substitute Stats</h2>
                            <p class="text-blue-100 text-sm">Last 30 Days</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <?php 
                        // Get stats for last 30 days
                        $stats = $pdo->query("
                            SELECT 
                                COUNT(*) as total_assignments,
                                COUNT(DISTINCT session_date) as days_with_subs,
                                COUNT(DISTINCT actual_teacher_id) as unique_substitutes
                            FROM live_sessions 
                            WHERE actual_teacher_id != (
                                SELECT default_teacher_id 
                                FROM timetable_slots 
                                WHERE slot_id = live_sessions.slot_id
                            )
                            AND session_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                            AND status != 'CANCELLED'
                        ")->fetch();
                        ?>
                        
                        <div class="bg-white/10 rounded-xl p-4">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm text-blue-200">Total Assignments</p>
                                    <p class="text-2xl font-bold"><?= $stats['total_assignments'] ?></p>
                                </div>
                                <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white/10 rounded-xl p-4">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm text-blue-200">Unique Substitutes</p>
                                    <p class="text-2xl font-bold"><?= $stats['unique_substitutes'] ?></p>
                                </div>
                                <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white/10 rounded-xl p-4">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm text-blue-200">Days with Subs</p>
                                    <p class="text-2xl font-bold"><?= $stats['days_with_subs'] ?></p>
                                </div>
                                <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const slotSelect = document.getElementById('slotSelect');
        const originalTeacherName = document.getElementById('originalTeacherName');
        const originalTeacherDetails = document.getElementById('originalTeacherDetails');
        const originalTeacherId = document.getElementById('originalTeacherId');
        
        // Update original teacher info when slot is selected
        slotSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.value) {
                const teacherName = selectedOption.getAttribute('data-teacher-name');
                const teacherId = selectedOption.getAttribute('data-teacher-id');
                const subject = selectedOption.getAttribute('data-subject');
                const time = selectedOption.getAttribute('data-time');
                const day = selectedOption.getAttribute('data-day');
                const details = selectedOption.getAttribute('data-details');
                
                originalTeacherName.textContent = teacherName;
                originalTeacherId.value = teacherId;
                originalTeacherDetails.textContent = `${subject} | ${day} | ${time} | ${details}`;
            } else {
                originalTeacherName.textContent = '-- Select slot above --';
                originalTeacherDetails.textContent = 'Subject and time will appear here';
                originalTeacherId.value = '';
            }
        });
        
        // Form validation
        document.getElementById('assignSubstituteForm').addEventListener('submit', function(e) {
            const slotId = slotSelect.value;
            const substituteTeacher = document.querySelector('select[name="substitute_teacher_id"]').value;
            const originalTeacher = originalTeacherId.value;
            const dateSelected = document.querySelector('input[name="substitution_date"]:checked');
            
            if (!slotId) {
                e.preventDefault();
                alert('Please select a timetable slot.');
                return false;
            }
            
            if (!substituteTeacher) {
                e.preventDefault();
                alert('Please select a substitute teacher.');
                return false;
            }
            
            if (!dateSelected) {
                e.preventDefault();
                alert('Please select a substitution date.');
                return false;
            }
            
            if (substituteTeacher === originalTeacher) {
                e.preventDefault();
                alert('Substitute teacher cannot be the same as original teacher.');
                return false;
            }
            
            // Custom confirmation
            const slotText = slotSelect.options[slotSelect.selectedIndex].text;
            const substituteName = document.querySelector('select[name="substitute_teacher_id"] option:checked').text;
            const dateText = dateSelected.nextElementSibling.querySelector('p:nth-child(2)').textContent;
            
            const message = `Assign Substitute Teacher\n\n` +
                           `Slot: ${slotText}\n` +
                           `Date: ${dateText}\n` +
                           `Substitute: ${substituteName}\n\n` +
                           `This will override the regular teacher for this session.\n\n` +
                           `Continue?`;
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
    const allSlots = <?= json_encode($upcoming_slots) ?>;
    const nextDates = <?= json_encode($next_dates) ?>;
    const teachers = <?= json_encode($teachers) ?>;
    
    // Global variables
    let teacherSlots = [];
    let selectedDay = '';
    let selectedSlot = null;
    let selectedDate = '';
    
    // Step 1: When teacher is selected
    filterTeacher.addEventListener('change', function() {
        const teacherId = this.value;
        
        if (!teacherId) {
            hideAllSections();
            return;
        }
        
        // Get this teacher's slots
        teacherSlots = allSlots.filter(slot => slot.teacher_id == teacherId);
        
        if (teacherSlots.length === 0) {
            alert('Selected teacher has no timetable slots in the next 7 days.');
            hideAllSections();
            return;
        }
        
        // Get unique days for this teacher
        const uniqueDays = [...new Set(teacherSlots.map(slot => slot.day_of_week))];
        
        // Show day filter section
        showSection(dayFilterSection);
        
        // Populate day buttons
        populateDayButtons(uniqueDays);
        
        // Hide later sections
        hideSectionsAfter(dayFilterSection);
        
        // Clear previous selections
        clearSelectionsAfterStep(2);
    });
    
    function populateDayButtons(days) {
        dayFilterButtons.innerHTML = '';
        days.forEach(day => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `p-3 border rounded-xl text-center transition ${selectedDay === day ? 'border-green-500 bg-green-100 ring-2 ring-green-200' : 'border-gray-300 hover:border-green-300 hover:bg-green-50'}`;
            button.textContent = day;
            button.onclick = function() {
                selectDay(day);
            };
            dayFilterButtons.appendChild(button);
        });
    }
    
    // Step 2: When day is selected
    function selectDay(day) {
        selectedDay = day;
        
        // Update UI - highlight selected day
        populateDayButtons([...new Set(teacherSlots.map(slot => slot.day_of_week))]);
        
        // Filter slots for this day
        const daySlots = teacherSlots.filter(slot => slot.day_of_week === day);
        
        // Show slot selection section
        showSection(slotSelectionSection);
        
        // Populate slot dropdown
        populateSlotDropdown(daySlots);
        
        // Hide later sections
        hideSectionsAfter(slotSelectionSection);
        
        // Clear previous selections
        clearSelectionsAfterStep(3);
    }
    
    function populateSlotDropdown(slots) {
        slotSelect.innerHTML = '<option value="">-- Select class slot --</option>';
        slots.forEach(slot => {
            const target = JSON.parse(slot.target_group);
            const semester = target.semester || '?';
            const branch = target.branch || '?';
            const section = target.section || 'All';
            const batch = target.batch || 'All';
            
            const option = document.createElement('option');
            option.value = slot.slot_id;
            option.textContent = `${slot.subject_code} (${slot.start_time.substring(0, 5)} - ${slot.end_time.substring(0, 5)})`;
            
            // Store data as attributes
            option.dataset.teacherId = slot.teacher_id;
            option.dataset.teacherName = slot.teacher_name || '';
            option.dataset.subject = slot.subject_code || '';
            option.dataset.subjectName = slot.subject_name || '';
            option.dataset.time = `${slot.start_time.substring(0, 5)} - ${slot.end_time.substring(0, 5)}`;
            option.dataset.day = slot.day_of_week;
            option.dataset.details = `Sem ${semester} | ${branch} | Sec ${section} | Batch ${batch}`;
            
            slotSelect.appendChild(option);
        });
    }
    
    // Step 3: When slot is selected
    slotSelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        
        if (!this.value) {
            hideSectionsAfter(slotSelectionSection);
            clearSelectionsAfterStep(4);
            return;
        }
        
        selectedSlot = {
            id: this.value,
            teacherId: option.dataset.teacherId,
            teacherName: option.dataset.teacherName,
            subject: option.dataset.subject,
            subjectName: option.dataset.subjectName,
            time: option.dataset.time,
            day: option.dataset.day,
            details: option.dataset.details
        };
        
        // Update display
        updateSlotDisplay();
        document.getElementById('originalTeacherId').value = selectedSlot.teacherId;
        
        // Show date selection section
        showSection(dateSelectionSection);
        
        // Populate dates for this day of week
        populateDateButtons();
        
        // Hide later sections
        hideSectionsAfter(dateSelectionSection);
        
        // Clear previous selections
        clearSelectionsAfterStep(4);
    });
    
    function updateSlotDisplay() {
        document.getElementById('selectedTeacherName').textContent = selectedSlot.teacherName;
        document.getElementById('selectedSubject').textContent = `${selectedSlot.subject} - ${selectedSlot.subjectName}`;
        document.getElementById('selectedTime').textContent = selectedSlot.time;
        document.getElementById('selectedDetails').textContent = selectedSlot.details;
    }
    
    function populateDateButtons() {
        dateSelectionButtons.innerHTML = '';
        nextDates.forEach(dateInfo => {
            if (dateInfo.day_name === selectedDay) {
                const label = document.createElement('label');
                label.className = 'relative';
                
                const input = document.createElement('input');
                input.type = 'radio';
                input.name = 'substitution_date';
                input.value = dateInfo.date;
                input.className = 'hidden peer';
                input.checked = (selectedDate === dateInfo.date);
                input.onchange = function() {
                    selectDate(dateInfo.date, dateInfo.display);
                };
                
                const div = document.createElement('div');
                div.className = `p-3 border rounded-xl text-center cursor-pointer transition ${selectedDate === dateInfo.date ? 'border-purple-500 bg-purple-50 ring-2 ring-purple-200' : 'border-gray-300 hover:border-purple-300 hover:bg-purple-50'}`;
                div.innerHTML = `
                    <p class="font-medium text-gray-800">${dateInfo.day_name}</p>
                    <p class="text-sm text-gray-600">${dateInfo.display}</p>
                `;
                
                label.appendChild(input);
                label.appendChild(div);
                dateSelectionButtons.appendChild(label);
            }
        });
    }
    
    // Step 4: When date is selected
    function selectDate(date, display) {
        selectedDate = date;
        
        // Update UI - highlight selected date
        populateDateButtons();
        
        // Show substitute selection section
        showSection(substituteSelectionSection);
        
        // Populate substitute dropdown (excluding original teacher)
        populateSubstituteDropdown();
        
        // Hide later sections
        hideSectionsAfter(substituteSelectionSection);
        
        // Clear previous selections
        clearSelectionsAfterStep(5);
    }
    
    function populateSubstituteDropdown() {
        const substituteSelect = document.getElementById('substituteTeacherSelect');
        const originalTeacherId = document.getElementById('originalTeacherId').value;
        
        substituteSelect.innerHTML = '<option value="">-- Select substitute --</option>';
        teachers.forEach(teacher => {
            if (teacher.user_id != originalTeacherId) {
                const option = document.createElement('option');
                option.value = teacher.user_id;
                option.textContent = teacher.full_name || teacher.email;
                substituteSelect.appendChild(option);
            }
        });
    }
    
    // Step 5: When substitute is selected
    substituteTeacherSelect.addEventListener('change', function() {
        if (this.value) {
            showSection(reasonSection);
            enableSubmitButton();
        } else {
            hideSection(reasonSection);
            disableSubmitButton();
        }
    });
    
    // Function to reset to specific step
    window.resetToStep = function(step) {
        switch(step) {
            case 1: // Change teacher
                filterTeacher.value = '';
                hideAllSections();
                clearAllSelections();
                break;
                
            case 2: // Change day
                selectedDay = '';
                populateDayButtons([...new Set(teacherSlots.map(slot => slot.day_of_week))]);
                hideSectionsAfter(dayFilterSection);
                clearSelectionsAfterStep(3);
                break;
                
            case 3: // Change slot
                slotSelect.value = '';
                hideSectionsAfter(slotSelectionSection);
                clearSelectionsAfterStep(4);
                break;
                
            case 4: // Change date
                selectedDate = '';
                populateDateButtons();
                hideSectionsAfter(dateSelectionSection);
                clearSelectionsAfterStep(5);
                break;
                
            case 5: // Change substitute
                substituteTeacherSelect.value = '';
                hideSection(reasonSection);
                disableSubmitButton();
                break;
        }
    };
    
    // Function to clear all selections
    window.resetAll = function() {
        filterTeacher.value = '';
        hideAllSections();
        clearAllSelections();
        disableSubmitButton();
    };
    
    function clearAllSelections() {
        selectedDay = '';
        selectedSlot = null;
        selectedDate = '';
        slotSelect.innerHTML = '<option value="">-- Select class slot --</option>';
        dateSelectionButtons.innerHTML = '';
        substituteTeacherSelect.value = '';
        document.getElementById('selectedTeacherName').textContent = '--';
        document.getElementById('selectedSubject').textContent = '--';
        document.getElementById('selectedTime').textContent = '--';
        document.getElementById('selectedDetails').textContent = '--';
        document.getElementById('originalTeacherId').value = '';
    }
    
    function clearSelectionsAfterStep(step) {
        if (step <= 3) {
            slotSelect.value = '';
            selectedSlot = null;
            document.getElementById('selectedTeacherName').textContent = '--';
            document.getElementById('selectedSubject').textContent = '--';
            document.getElementById('selectedTime').textContent = '--';
            document.getElementById('selectedDetails').textContent = '--';
            document.getElementById('originalTeacherId').value = '';
        }
        if (step <= 4) {
            selectedDate = '';
            dateSelectionButtons.innerHTML = '';
        }
        if (step <= 5) {
            substituteTeacherSelect.value = '';
            hideSection(reasonSection);
            disableSubmitButton();
        }
    }
    
    // Helper functions
    function enableSubmitButton() {
        submitButton.disabled = false;
        submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
        submitButton.classList.add('opacity-100', 'cursor-pointer');
    }
    
    function disableSubmitButton() {
        submitButton.disabled = true;
        submitButton.classList.add('opacity-50', 'cursor-not-allowed');
        submitButton.classList.remove('opacity-100', 'cursor-pointer');
    }
    
    function showSection(section) {
        section.classList.remove('hidden');
    }
    
    function hideSection(section) {
        section.classList.add('hidden');
    }
    
    function hideSectionsAfter(startSection) {
        const sections = [dayFilterSection, slotSelectionSection, dateSelectionSection, 
                         substituteSelectionSection, reasonSection];
        const startIndex = sections.indexOf(startSection);
        
        for (let i = startIndex + 1; i < sections.length; i++) {
            hideSection(sections[i]);
        }
    }
    
    function hideAllSections() {
        const sections = [dayFilterSection, slotSelectionSection, dateSelectionSection, 
                         substituteSelectionSection, reasonSection];
        sections.forEach(section => hideSection(section));
    }
    
    // Form validation and submission
    document.getElementById('assignSubstituteForm').addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            alert('Please complete all required steps before submitting.');
            return false;
        }
        
        const message = `Assign Substitute Teacher\n\n` +
                       `Original Teacher: ${selectedSlot.teacherName}\n` +
                       `Subject: ${selectedSlot.subject} - ${selectedSlot.subjectName}\n` +
                       `Day/Time: ${selectedDay}, ${selectedSlot.time}\n` +
                       `Date: ${selectedDate}\n` +
                       `Substitute: ${substituteTeacherSelect.options[substituteTeacherSelect.selectedIndex].text}\n\n` +
                       `Continue?`;
        
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
    
    function validateForm() {
        return filterTeacher.value && 
               selectedDay && 
               slotSelect.value && 
               selectedDate && 
               substituteTeacherSelect.value;
    }
});
    </script>
</body>
</html>