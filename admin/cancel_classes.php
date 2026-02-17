<?php
require_once '../includes/Config.php';
require_once '../includes/Auth.php';

checkAuth();
if ($_SESSION['role'] != 'MASTER') {
    header('Location: ../dashboard.php');
    exit();
}

$user = getCurrentUser();

// Get timetable statistics for UI
$timetable_stats = $pdo->query("
    SELECT 
        COUNT(*) as total_slots,
        COUNT(DISTINCT JSON_EXTRACT(target_group, '$.year')) as distinct_years,
        COUNT(DISTINCT JSON_EXTRACT(target_group, '$.branch')) as distinct_branches,
        GROUP_CONCAT(DISTINCT JSON_EXTRACT(target_group, '$.year') ORDER BY JSON_EXTRACT(target_group, '$.year')) as years_list,
        GROUP_CONCAT(DISTINCT JSON_EXTRACT(target_group, '$.branch') ORDER BY JSON_EXTRACT(target_group, '$.branch')) as branches_list
    FROM timetable_slots 
    WHERE valid_from <= CURDATE() AND valid_until >= CURDATE()
")->fetch();

// Parse available years and branches
$available_years = $timetable_stats['years_list'] ? json_decode('[' . $timetable_stats['years_list'] . ']', true) : [];
$available_branches = $timetable_stats['branches_list'] ? json_decode('[' . $timetable_stats['branches_list'] . ']', true) : [];

// Handle enhanced class cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_classes'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = trim($_POST['reason'] ?? '');
    $notify_users = isset($_POST['notify_users']);
    $time_of_day = $_POST['time_of_day'] ?? 'all';
    
    // Get selected filters
    $academic_years = $_POST['academic_years'] ?? [];
    $branches = $_POST['branches'] ?? [];
    
    // Validate
    if (strtotime($start_date) > strtotime($end_date)) {
        $error = "End date cannot be before start date.";
    } elseif (strtotime($start_date) < strtotime(date('Y-m-d'))) {
        $error = "Cannot cancel classes for past dates.";
    } elseif (empty($academic_years)) {
        $error = "Please select at least one academic year.";
    } elseif (empty($branches)) {
        $error = "Please select at least one branch.";
    }
    
    if (!isset($error)) {
        $pdo->beginTransaction();
        
        try {
            // Build SQL conditions based on filters
            $conditions = [];
            $params = [$start_date, $end_date, $start_date, $start_date, $end_date];
            
            // Academic year filter (convert year to semesters)
            $semester_conditions = [];
            foreach ($academic_years as $year) {
                $year = (int)$year;
                $sem_start = ($year * 2) - 1;
                $sem_end = $year * 2;
                $semester_conditions[] = "(JSON_EXTRACT(ts.target_group, '$.semester') BETWEEN ? AND ?)";
                $params[] = $sem_start;
                $params[] = $sem_end;
            }
            if (!empty($semester_conditions)) {
                $conditions[] = "(" . implode(" OR ", $semester_conditions) . ")";
            }
            
            // Branch filter
            $branch_conditions = [];
            foreach ($branches as $branch) {
                $branch_conditions[] = "JSON_EXTRACT(ts.target_group, '$.branch') = ?";
                $params[] = $branch;
            }
            if (!empty($branch_conditions)) {
                $conditions[] = "(" . implode(" OR ", $branch_conditions) . ")";
            }
            
            // Time of day filter
            if ($time_of_day === 'morning') {
                $conditions[] = "ts.start_time < '12:00:00'";
            } elseif ($time_of_day === 'afternoon') {
                $conditions[] = "ts.start_time >= '12:00:00'";
            }
            
            $where_clause = !empty($conditions) ? "AND " . implode(" AND ", $conditions) : "";
            
            // 1. Get all timetable slots matching filters
            $sql = "
                SELECT ts.slot_id, ts.day_of_week, ts.start_time, ts.end_time,
                       ts.default_teacher_id, ts.subject_id, ts.target_group
                FROM timetable_slots ts
                WHERE ts.valid_from <= ? 
                    AND ts.valid_until >= ?
                    AND ts.day_of_week IN (
                        SELECT DISTINCT DAYNAME(dates.date) 
                        FROM (
                            SELECT DATE_ADD(?, INTERVAL n.num DAY) as date
                            FROM (
                                SELECT 0 as num UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 
                                UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
                                UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
                                UNION SELECT 10 UNION SELECT 11 UNION SELECT 12
                                UNION SELECT 13 UNION SELECT 14
                            ) n
                            WHERE DATE_ADD(?, INTERVAL n.num DAY) <= ?
                        ) dates
                    )
                $where_clause
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $timetable_slots = $stmt->fetchAll();
            
            $cancelled_count = 0;
            $affected_users = [];
            
            // 2. Create cancelled live_sessions
            foreach ($timetable_slots as $slot) {
                $day_of_week = $slot['day_of_week'];
                $target_group = json_decode($slot['target_group'], true);
                $year = $target_group['year'] ?? '?';
                $branch = $target_group['branch'] ?? '?';
                
                // Calculate dates for this day of week in the range
                $date_stmt = $pdo->prepare("
                    SELECT DATE_ADD(?, INTERVAL n.num DAY) as session_date
                    FROM (
                        SELECT 0 as num UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 
                        UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
                        UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
                        UNION SELECT 10 UNION SELECT 11 UNION SELECT 12
                        UNION SELECT 13 UNION SELECT 14
                    ) n
                    WHERE DATE_ADD(?, INTERVAL n.num DAY) BETWEEN ? AND ?
                        AND DAYNAME(DATE_ADD(?, INTERVAL n.num DAY)) = ?
                ");
                $date_stmt->execute([$start_date, $start_date, $start_date, $end_date, $start_date, $day_of_week]);
                $dates = $date_stmt->fetchAll();
                
                foreach ($dates as $date_row) {
                    $session_date = $date_row['session_date'];
                    
                    // Check if session already exists
                    $check_stmt = $pdo->prepare("
                        SELECT session_id FROM live_sessions 
                        WHERE slot_id = ? AND session_date = ?
                    ");
                    $check_stmt->execute([$slot['slot_id'], $session_date]);
                    
                    if (!$check_stmt->fetch()) {
                        // Create cancelled session
                        $insert_stmt = $pdo->prepare("
                            INSERT INTO live_sessions 
                            (slot_id, actual_teacher_id, session_date, status, ended_at)
                            VALUES (?, ?, ?, 'CANCELLED', NOW())
                        ");
                        $insert_stmt->execute([
                            $slot['slot_id'],
                            $slot['default_teacher_id'],
                            $session_date
                        ]);
                        $cancelled_count++;
                    }
                }
            }
            
            // 3. Get affected teachers and students
            if ($cancelled_count > 0) {
                $affected_users = $pdo->query("
                    SELECT DISTINCT u.user_id, u.email, u.full_name, u.role
                    FROM live_sessions ls
                    JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
                    JOIN subjects s ON ts.subject_id = s.subject_id
                    JOIN users u ON (
                        u.user_id = ls.actual_teacher_id 
                        OR u.user_id IN (
                            SELECT sp.user_id 
                            FROM student_profiles sp 
                            WHERE sp.current_semester = JSON_EXTRACT(ts.target_group, '$.semester')
                              AND sp.branch_code = JSON_EXTRACT(ts.target_group, '$.branch')
                              AND (JSON_EXTRACT(ts.target_group, '$.section') IS NULL 
                                   OR sp.section = JSON_EXTRACT(ts.target_group, '$.section'))
                        )
                    )
                    WHERE ls.session_date BETWEEN '$start_date' AND '$end_date'
                      AND ls.status = 'CANCELLED'
                      AND ls.ended_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                ")->fetchAll();
                
                // 4. Create notifications if requested
                if ($notify_users && !empty($affected_users)) {
                    // Build notification message
                    $notification_title = "Classes Cancelled";
                    
                    $years_text = implode(", ", array_map(function($y) { 
                        return "Year $y"; 
                    }, $academic_years));
                    
                    $branches_text = implode(", ", $branches);
                    
                    $date_text = $start_date == $end_date 
                        ? "on " . date('l, F j', strtotime($start_date))
                        : "from " . date('M j', strtotime($start_date)) . " to " . date('M j', strtotime($end_date));
                    
                    $time_text = $time_of_day == 'all' ? '' : 
                                ($time_of_day == 'morning' ? ' (Morning only)' : ' (Afternoon only)');
                    
                    $notification_message = "$years_text $branches_text classes cancelled $date_text$time_text.";
                    
                    if (!empty($reason)) {
                        $notification_message .= "\nReason: " . $reason;
                    }
                    
                    $notification_stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, title, message, notification_type, related_type, expires_at)
                        VALUES (?, ?, ?, 'CANCELLATION', 'system_broadcast', DATE_ADD(NOW(), INTERVAL 7 DAY))
                    ");
                    
                    foreach ($affected_users as $affected_user) {
                        $notification_stmt->execute([
                            $affected_user['user_id'],
                            $notification_title,
                            $notification_message
                        ]);
                    }
                    
                    $notified_count = count($affected_users);
                }
            }
            
            // 5. Log the action (as JSON)
            $log_data = [
                'type' => 'class_cancellation',
                'cancelled_count' => $cancelled_count,
                'academic_years' => $academic_years,
                'branches' => $branches,
                'time_of_day' => $time_of_day,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'reason' => $reason,
                'notified_users' => $notify_users ? ($notified_count ?? 0) : 0,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $details_json = json_encode($log_data);
            
            $stmt = $pdo->prepare("
                INSERT INTO system_logs (user_id, action, details, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $stmt->execute([
                $user['user_id'], 
                'RED_BUTTON',
                $details_json,
                $_SERVER['REMOTE_ADDR'],
                $user_agent
            ]);
            
            $pdo->commit();
            
            // Build success message
            $years_desc = count($academic_years) == 1 
                ? "Year " . $academic_years[0] 
                : "Years " . implode(", ", $academic_years);
            
            $branches_desc = count($branches) == 1 
                ? $branches[0] . " branch" 
                : implode(", ", $branches) . " branches";
            
            $success = "Successfully cancelled $cancelled_count classes for $years_desc $branches_desc.";
            
            if ($notify_users && ($notified_count ?? 0) > 0) {
                $success .= " Notifications sent to $notified_count users.";
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Cancellation failed: " . $e->getMessage();
            error_log("Cancellation error: " . $e->getMessage());
        }
    }
}

// Handle restoration of cancelled classes
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['restore_classes'])) {
    $session_id = $_POST['session_id'];
    
    $pdo->beginTransaction();
    
    try {
        // Restore the session to SCHEDULED status
        $stmt = $pdo->prepare("
            UPDATE live_sessions 
            SET status = 'SCHEDULED',
                ended_at = NULL
            WHERE session_id = ?
        ");
        $stmt->execute([$session_id]);
        
        // Log the restoration (as JSON)
        $restore_data = [
            'type' => 'class_restoration',
            'session_id' => $session_id,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $restore_details = json_encode($restore_data);
        
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (user_id, action, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $stmt->execute([
            $user['user_id'], 
            'RED_BUTTON_RESTORE',
            $restore_details,
            $_SERVER['REMOTE_ADDR'],
            $user_agent
        ]);
        
        $pdo->commit();
        
        $success = "Class session restored successfully.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Restoration failed: " . $e->getMessage();
    }
}

// Get recent cancellations (last 24 hours only, grouped by date)
$upcoming_cancellations = $pdo->query("
    SELECT 
        ls.session_id,
        ls.session_date,
        ls.status,
        ls.ended_at as cancelled_at,
        ts.start_time,
        ts.end_time,
        s.code as subject_code,
        s.name as subject_name,
        t.full_name as teacher_name,
        t.user_id as teacher_id,
        COALESCE(JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.year')), '') as year,
        COALESCE(JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.branch')), '') as branch,
        COALESCE(JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.semester')), '') as semester,
        COALESCE(JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.section')), '') as section
    FROM live_sessions ls
    JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
    JOIN subjects s ON ts.subject_id = s.subject_id
    JOIN users t ON ls.actual_teacher_id = t.user_id
    WHERE ls.session_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND ls.status = 'CANCELLED'
        AND ls.ended_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY ls.session_date, ts.start_time
")->fetchAll();

// Get cancellation statistics
$cancellation_stats = $pdo->query("
    SELECT 
        DATE(ls.ended_at) as cancellation_date,
        COUNT(*) as cancelled_count,
        GROUP_CONCAT(DISTINCT s.code ORDER BY s.code LIMIT 3) as sample_subjects
    FROM live_sessions ls
    JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
    JOIN subjects s ON ts.subject_id = s.subject_id
    WHERE ls.status = 'CANCELLED'
        AND ls.ended_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(ls.ended_at)
    ORDER BY cancellation_date DESC
    LIMIT 10
")->fetchAll();

// Check if notifications table exists
$notifications_table_exists = false;
try {
    $pdo->query("SELECT 1 FROM notifications LIMIT 1");
    $notifications_table_exists = true;
} catch (Exception $e) {
    // Table doesn't exist
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Classes - Master Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .range-slider {
            -webkit-appearance: none;
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: #e5e7eb;
            outline: none;
        }
        .range-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #ef4444;
            cursor: pointer;
            border: 3px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .range-slider::-moz-range-thumb {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #ef4444;
            cursor: pointer;
            border: 3px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-red-50 min-h-screen">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <a href="dashboard.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4">
                <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
            </a>
            <div class="flex items-center">
                <div class="h-14 w-14 rounded-full bg-gradient-to-r from-red-500 to-red-600 flex items-center justify-center mr-4">
                    <i class="fas fa-ban text-white text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Cancel Classes</h1>
                    <p class="text-gray-600">Emergency cancellation of classes. Sends notifications to all affected users.</p>
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
           <!-- Left Column: Enhanced Cancellation Form -->
<div class="lg:col-span-2">
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
            <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i> Enhanced Class Cancellation
        </h2>
        
        <form method="POST" id="cancelForm">
            <!-- Date Range -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Select Dates</label>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Start Date</label>
                        <input type="date" name="start_date" id="startDate" 
                               min="<?= date('Y-m-d') ?>" 
                               value="<?= date('Y-m-d') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">End Date</label>
                        <input type="date" name="end_date" id="endDate" 
                               min="<?= date('Y-m-d') ?>"
                               value="<?= date('Y-m-d', strtotime('+3 days')) ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent transition">
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    <i class="fas fa-info-circle"></i> Cancel classes within this date range
                </p>
            </div>

            <!-- Academic Year Filter -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Academic Year</label>
                <div class="grid grid-cols-4 gap-2">
                    <?php for ($year = 1; $year <= 4; $year++): ?>
                    <label class="relative">
                        <input type="checkbox" name="academic_years[]" value="<?= $year ?>" 
                               class="hidden peer" <?= $year == 2 ? 'checked' : '' ?>>
                        <div class="p-3 border border-gray-300 rounded-xl text-center cursor-pointer hover:border-blue-300 hover:bg-blue-50 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:ring-2 peer-checked:ring-blue-200 transition">
                            <p class="font-bold text-gray-800">Year <?= $year ?></p>
                            <p class="text-xs text-gray-600">Sem <?= ($year*2)-1 ?> & <?= $year*2 ?></p>
                        </div>
                    </label>
                    <?php endfor; ?>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    <i class="fas fa-info-circle"></i> Year 1 = Sem 1-2, Year 2 = Sem 3-4, etc.
                </p>
            </div>

            <!-- Branch Filter -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Branch</label>
                <div class="flex flex-wrap gap-2">
                    <label class="relative">
                        <input type="checkbox" name="branches[]" value="CS" 
                               class="hidden peer" checked>
                        <div class="px-4 py-2 border border-gray-300 rounded-xl cursor-pointer hover:border-green-300 hover:bg-green-50 peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:ring-2 peer-checked:ring-green-200 transition">
                            <span class="font-medium text-gray-800">CS</span>
                        </div>
                    </label>
                    <label class="relative">
                        <input type="checkbox" name="branches[]" value="EC" 
                               class="hidden peer">
                        <div class="px-4 py-2 border border-gray-300 rounded-xl cursor-pointer hover:border-green-300 hover:bg-green-50 peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:ring-2 peer-checked:ring-green-200 transition">
                            <span class="font-medium text-gray-800">EC</span>
                        </div>
                    </label>
                    <label class="relative">
                        <input type="checkbox" name="branches[]" value="ME" 
                               class="hidden peer">
                        <div class="px-4 py-2 border border-gray-300 rounded-xl cursor-pointer hover:border-green-300 hover:bg-green-50 peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:ring-2 peer-checked:ring-green-200 transition">
                            <span class="font-medium text-gray-800">ME</span>
                        </div>
                    </label>
                    <label class="relative">
                        <input type="checkbox" name="branches[]" value="CE" 
                               class="hidden peer">
                        <div class="px-4 py-2 border border-gray-300 rounded-xl cursor-pointer hover:border-green-300 hover:bg-green-50 peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:ring-2 peer-checked:ring-green-200 transition">
                            <span class="font-medium text-gray-800">CE</span>
                        </div>
                    </label>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    <i class="fas fa-info-circle"></i> Select branches to cancel. Leave all checked for all branches.
                </p>
            </div>

            <!-- Time of Day Filter -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Time of Day</label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="relative">
                        <input type="radio" name="time_of_day" value="all" 
                               class="hidden peer" checked>
                        <div class="p-3 border border-gray-300 rounded-xl text-center cursor-pointer hover:border-yellow-300 hover:bg-yellow-50 peer-checked:border-yellow-500 peer-checked:bg-yellow-50 peer-checked:ring-2 peer-checked:ring-yellow-200 transition">
                            <i class="fas fa-sun text-yellow-500 text-lg mb-1"></i>
                            <p class="font-medium text-gray-800">All Day</p>
                            <p class="text-xs text-gray-600">Full day</p>
                        </div>
                    </label>
                    <label class="relative">
                        <input type="radio" name="time_of_day" value="morning" 
                               class="hidden peer">
                        <div class="p-3 border border-gray-300 rounded-xl text-center cursor-pointer hover:border-orange-300 hover:bg-orange-50 peer-checked:border-orange-500 peer-checked:bg-orange-50 peer-checked:ring-2 peer-checked:ring-orange-200 transition">
                            <i class="fas fa-sunrise text-orange-500 text-lg mb-1"></i>
                            <p class="font-medium text-gray-800">Morning</p>
                            <p class="text-xs text-gray-600">Before 12 PM</p>
                        </div>
                    </label>
                    <label class="relative">
                        <input type="radio" name="time_of_day" value="afternoon" 
                               class="hidden peer">
                        <div class="p-3 border border-gray-300 rounded-xl text-center cursor-pointer hover:border-purple-300 hover:bg-purple-50 peer-checked:border-purple-500 peer-checked:bg-purple-50 peer-checked:ring-2 peer-checked:ring-purple-200 transition">
                            <i class="fas fa-sunset text-purple-500 text-lg mb-1"></i>
                            <p class="font-medium text-gray-800">Afternoon</p>
                            <p class="text-xs text-gray-600">After 12 PM</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Reason -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Reason for Cancellation
                    <span class="text-gray-500 font-normal">(Optional)</span>
                </label>
                <textarea name="reason" rows="3" 
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent transition"
                          placeholder="e.g., Heavy rain alert, Power outage, College event, etc."></textarea>
                <p class="text-xs text-gray-500 mt-2">
                    <i class="fas fa-info-circle"></i> This reason will be included in notifications.
                </p>
            </div>

            <!-- Notification Options -->
            <div class="mb-6">
                <label class="flex items-center">
                    <input type="checkbox" name="notify_users" value="1" 
                           class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded" 
                           <?= $notifications_table_exists ? 'checked' : 'disabled' ?>>
                    <span class="ml-2 text-sm font-medium text-gray-700">
                        Send notifications to affected users
                        <?php if (!$notifications_table_exists): ?>
                        <span class="text-red-600 font-bold"> (Notifications table not setup)</span>
                        <?php endif; ?>
                    </span>
                </label>
            </div>

            <!-- Impact Preview -->
            <div class="mb-6 bg-gray-50 border border-gray-200 rounded-xl p-4">
                <h3 class="font-bold text-gray-800 mb-2">Cancellation Preview</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600">Selected Years:</p>
                        <p class="font-medium" id="previewYears">Year 2</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Selected Branches:</p>
                        <p class="font-medium" id="previewBranches">CS</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Time of Day:</p>
                        <p class="font-medium" id="previewTime">All Day</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Estimated Impact:</p>
                        <p class="font-medium text-red-600" id="previewImpact">Calculating...</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex space-x-4">
                <button type="button" onclick="window.history.back()"
                        class="flex-1 border-2 border-gray-300 text-gray-700 py-3.5 rounded-xl font-bold hover:bg-gray-50 transition flex items-center justify-center">
                    <i class="fas fa-times mr-2"></i> Cancel
                </button>
                <button type="submit" name="cancel_classes" 
                        class="flex-1 bg-gradient-to-r from-red-600 to-red-700 text-white py-3.5 rounded-xl font-bold hover:from-red-700 hover:to-red-800 transition flex items-center justify-center"
                        onclick="return confirmEnhancedCancellation()">
                    <i class="fas fa-ban mr-2"></i> CONFIRM CANCELLATION
                </button>
            </div>
        </form>
    </div>
</div>

            <!-- Right Column: Recent Cancellations & Stats -->
            <div class="lg:col-span-1">
                <!-- Recent Cancellations -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-history text-gray-600 mr-3"></i> Recent Cancellations
                        <span class="ml-2 bg-gray-100 text-gray-800 text-xs font-bold px-2 py-1 rounded-full">
                            <?= count($upcoming_cancellations) ?>
                        </span>
                        <span class="ml-auto text-sm text-gray-500">
                            <i class="fas fa-clock mr-1"></i> Last 24 hours
                        </span>
                    </h2>
                    
                    <?php if (empty($upcoming_cancellations)): ?>
                    <div class="text-center py-6">
                        <i class="fas fa-check-circle text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900">No Recent Cancellations</h3>
                        <p class="text-gray-500">No classes cancelled in the last 24 hours.</p>
                    </div>
                    <?php else: ?>
                    <?php 
                    $cancellations_by_date = [];
                    foreach ($upcoming_cancellations as $cancellation) {
                        $date = $cancellation['session_date'];
                        if (!isset($cancellations_by_date[$date])) {
                            $cancellations_by_date[$date] = [];
                        }
                        $cancellations_by_date[$date][] = $cancellation;
                    }
                    ?>
                    
                    <div class="space-y-4">
                        <?php foreach ($cancellations_by_date as $date => $date_cancellations): ?>
                        <div class="border border-gray-200 rounded-xl overflow-hidden">
                            <!-- Date Header -->
                            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-4 py-3 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar-day text-gray-600 mr-2"></i>
                                        <h3 class="font-bold text-gray-800">
                                            <?= date('l, F j', strtotime($date)) ?>
                                        </h3>
                                        <span class="ml-2 bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded-full">
                                            <?= count($date_cancellations) ?> class<?= count($date_cancellations) > 1 ? 'es' : '' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Classes List -->
                            <div class="p-3 space-y-2">
                                <?php foreach ($date_cancellations as $cancellation): ?>
                                <div class="border border-gray-100 rounded-lg p-2 hover:bg-gray-50 transition">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="flex items-center">
                                                <h4 class="font-bold text-gray-800 text-sm mr-2">
                                                    <?= htmlspecialchars($cancellation['subject_code'] ?? '') ?>
                                                </h4>
                                                <span class="text-xs text-red-600 font-medium">Cancelled</span>
                                            </div>
                                            <p class="text-xs text-gray-600">
                                                <?= date('h:i A', strtotime($cancellation['start_time'])) ?> | 
                                                Yr <?= $cancellation['year'] ?: '?' ?> | 
                                                <?= $cancellation['branch'] ?: '?' ?>
                                            </p>
                                        </div>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="session_id" value="<?= $cancellation['session_id'] ?>">
                                            <button type="submit" name="restore_classes" 
                                                    class="text-green-600 hover:text-green-800 text-xs font-medium flex items-center"
                                                    onclick="return confirm('Restore this class?\n\n<?= htmlspecialchars($cancellation['subject_code'] ?? 'Class') ?> on <?= date('M j', strtotime($cancellation['session_date'])) ?>')">
                                                <i class="fas fa-undo mr-1"></i> Restore
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Auto-restore notice -->
                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-info-circle text-yellow-500 mr-2"></i>
                            <p class="text-sm text-yellow-700">
                                Cancellations older than 24 hours are automatically removed from this list.
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Cancellation Statistics -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-chart-bar text-blue-600 mr-3"></i> Cancellation Stats
                    </h2>
                    
                    <div class="space-y-4">
                        <?php if (empty($cancellation_stats)): ?>
                        <div class="text-center py-4">
                            <p class="text-gray-500">No cancellation data available.</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($cancellation_stats as $stat): ?>
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-medium text-gray-800">
                                    <?= date('M j', strtotime($stat['cancellation_date'])) ?>
                                </span>
                                <span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded-full">
                                    <?= $stat['cancelled_count'] ?> classes
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-red-500 h-2 rounded-full" 
                                     style="width: <?= min(($stat['cancelled_count'] / 20 * 100), 100) ?>%"></div>
                            </div>
                            <?php if ($stat['sample_subjects']): ?>
                            <p class="text-xs text-gray-500 mt-2">
                                Subjects: <?= htmlspecialchars($stat['sample_subjects']) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- Quick Stats -->
                        <div class="grid grid-cols-2 gap-3 mt-4">
                            <?php
                            $total_cancelled = $pdo->query("SELECT COUNT(*) FROM live_sessions WHERE status = 'CANCELLED'")->fetchColumn();
                            $today_cancelled = $pdo->query("SELECT COUNT(*) FROM live_sessions WHERE status = 'CANCELLED' AND session_date = CURDATE()")->fetchColumn();
                            ?>
                            <div class="bg-blue-50 rounded-lg p-3 text-center">
                                <p class="text-2xl font-bold text-blue-600"><?= $total_cancelled ?></p>
                                <p class="text-xs text-blue-800">Total Cancelled</p>
                            </div>
                            <div class="bg-red-50 rounded-lg p-3 text-center">
                                <p class="text-2xl font-bold text-red-600"><?= $today_cancelled ?></p>
                                <p class="text-xs text-red-800">Today Cancelled</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

   <script>
document.addEventListener('DOMContentLoaded', function() {
    const cancellationTypeRadios = document.querySelectorAll('input[name="cancellation_type"]');
    const dateRangeSection = document.getElementById('dateRangeSection');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const dateRangePreview = document.getElementById('dateRangePreview');
    const impactCount = document.getElementById('impactCount');
    const impactUsers = document.getElementById('impactUsers');
    
    // Set default dates (today and +7 days)
    const today = new Date();
    const nextWeek = new Date();
    nextWeek.setDate(today.getDate() + 7);
    
    // Format dates for input fields (YYYY-MM-DD)
    function formatDateForInput(date) {
        return date.toISOString().split('T')[0];
    }
    
    if (startDate) {
        startDate.value = formatDateForInput(today);
        startDate.min = formatDateForInput(today);
    }
    
    if (endDate) {
        endDate.value = formatDateForInput(nextWeek);
        endDate.min = formatDateForInput(today);
    }
    
    // Toggle date range section
    if (cancellationTypeRadios.length > 0) {
        cancellationTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'date_range') {
                    dateRangeSection.classList.remove('hidden');
                    updateDateRangePreview();
                    updateImpactEstimate();
                } else {
                    dateRangeSection.classList.add('hidden');
                    updateImpactEstimate();
                }
            });
        });
    }
    
    // Update date range preview
    function updateDateRangePreview() {
        if (startDate && endDate && startDate.value && endDate.value) {
            const start = new Date(startDate.value);
            const end = new Date(endDate.value);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            dateRangePreview.innerHTML = `
                From <strong>${formatDateForDisplay(start)}</strong> to <strong>${formatDateForDisplay(end)}</strong><br>
                <span class="text-red-600 font-medium">${diffDays} day${diffDays > 1 ? 's' : ''} selected</span>
            `;
        } else {
            dateRangePreview.textContent = 'No date range selected';
        }
    }
    
    // Format date for display
    function formatDateForDisplay(date) {
        return date.toLocaleDateString('en-US', { 
            weekday: 'short', 
            month: 'short', 
            day: 'numeric' 
        });
    }
    
    // Estimate impact (simplified - in real app would make AJAX call)
    function updateImpactEstimate() {
        if (!impactCount || !impactUsers) return;
        
        const type = document.querySelector('input[name="cancellation_type"]:checked');
        if (!type) return;
        
        if (type.value === 'today_only') {
            impactCount.textContent = 'all scheduled';
            impactUsers.textContent = 'all affected';
        } else {
            if (startDate.value && endDate.value) {
                const start = new Date(startDate.value);
                const end = new Date(endDate.value);
                const diffDays = Math.ceil(Math.abs(end - start) / (1000 * 60 * 60 * 24)) + 1;
                impactCount.textContent = `${diffDays * 3}-${diffDays * 10} classes`;
                impactUsers.textContent = `${diffDays * 30}-${diffDays * 150} users`;
            } else {
                impactCount.textContent = '0';
                impactUsers.textContent = '0';
            }
        }
    }
    
    // Event listeners for date inputs
    if (startDate) {
        startDate.addEventListener('change', function() {
            if (endDate) {
                endDate.min = this.value;
                if (endDate.value < this.value) {
                    endDate.value = this.value;
                }
            }
            updateDateRangePreview();
            updateImpactEstimate();
        });
    }
    
    if (endDate) {
        endDate.addEventListener('change', function() {
            updateDateRangePreview();
            updateImpactEstimate();
        });
    }
    
    // Initial setup
    updateDateRangePreview();
    updateImpactEstimate();
    
    // Confirmation dialog
    window.confirmCancellation = function() {
        const type = document.querySelector('input[name="cancellation_type"]:checked');
        if (!type) return false;
        
        const reason = document.querySelector('textarea[name="reason"]')?.value.trim() || '';
        const notify = document.querySelector('input[name="notify_users"]')?.checked || false;
        
        let message = "⚠️ CONFIRM CLASS CANCELLATION\n\n";
        
        if (type.value === 'today_only') {
            message += "You are about to cancel ALL classes for TODAY.\n";
            message += `Date: ${new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}\n\n`;
        } else {
            if (!startDate.value || !endDate.value) {
                alert('Please select a valid date range.');
                return false;
            }
            message += `You are about to cancel classes from ${formatDateForDisplay(new Date(startDate.value))} to ${formatDateForDisplay(new Date(endDate.value))}.\n\n`;
        }
        
        message += "This will:\n";
        message += "• Mark all affected sessions as CANCELLED\n";
        message += "• Prevent attendance marking for these sessions\n";
        
        if (notify) {
            message += "• Send notifications to all affected teachers and students\n";
        }
        
        if (reason) {
            message += `• Include reason: "${reason}"\n`;
        }
        
        message += "\nThis action can be reversed but should be used cautiously.\n\n";
        message += "Click OK to proceed with cancellation.";
        
        return confirm(message);
    };
});
</script>
</body>
</html>