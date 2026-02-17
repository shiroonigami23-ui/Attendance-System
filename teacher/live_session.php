<?php
require_once '../includes/Config.php';
require_once '../includes/Auth.php';

checkAuth();
if ($_SESSION['role'] != 'SEMI_ADMIN') {
    header('Location: ../dashboard.php');
    exit();
}

$user = getCurrentUser();
$user_id = $user['user_id'];

// Get current day and time
$current_day = date('l');
$current_time = date('H:i:s');
$window_start = date('H:i:s', strtotime('-30 minutes'));
$window_end = date('H:i:s', strtotime('+30 minutes'));

// Fetch slots where this teacher is the ACTUAL teacher (assigned or swapped) for CURRENT time
$stmt = $pdo->prepare("
    SELECT 
        ts.slot_id,
        ts.subject_id,
        s.code,
        s.name,
        s.is_lab,
        TIME_FORMAT(ts.start_time, '%H:%i') as start_time,
        TIME_FORMAT(ts.end_time, '%H:%i') as end_time,
        JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.room')) as room,
        JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.batch')) as batch,
        JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.section')) as section,
        CASE 
            WHEN ls.actual_teacher_id = ? THEN 'SWAPPED'
            ELSE 'ASSIGNED'
        END as assignment_type
    FROM timetable_slots ts
    INNER JOIN subjects s ON ts.subject_id = s.subject_id
    LEFT JOIN live_sessions ls ON ts.slot_id = ls.slot_id 
        AND ls.session_date = CURDATE() 
        AND ls.status = 'LIVE'
    WHERE (
        ts.default_teacher_id = ?  -- Originally assigned
        OR ls.actual_teacher_id = ?  -- Swapped to this teacher
    )
    AND ts.day_of_week = ?
    AND CURDATE() BETWEEN ts.valid_from AND ts.valid_until
    AND ts.start_time BETWEEN ? AND ?
    ORDER BY ts.start_time
");
$stmt->execute([
    $user_id,
    $user_id,
    $user_id,
    $current_day,
    $window_start,
    $window_end
]);
$current_slots = $stmt->fetchAll();

// Fetch today's upcoming slots for reference
$stmt = $pdo->prepare("
    SELECT 
        ts.slot_id,
        s.code,
        s.name,
        TIME_FORMAT(ts.start_time, '%H:%i') as start_time,
        TIME_FORMAT(ts.end_time, '%H:%i') as end_time,
        JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.room')) as room,
        JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.batch')) as batch,
        u.full_name as assigned_teacher,
        CASE 
            WHEN ts.default_teacher_id = ? THEN 'YOU'
            ELSE u.full_name
        END as display_name
    FROM timetable_slots ts
    INNER JOIN subjects s ON ts.subject_id = s.subject_id
    LEFT JOIN users u ON ts.default_teacher_id = u.user_id
    WHERE ts.day_of_week = ?
    AND CURDATE() BETWEEN ts.valid_from AND ts.valid_until
    AND ts.start_time >= ?
    AND (
        ts.default_teacher_id = ? 
        OR EXISTS (
            SELECT 1 FROM live_sessions ls 
            WHERE ls.slot_id = ts.slot_id 
            AND ls.session_date = CURDATE() 
            AND ls.actual_teacher_id = ?
            AND ls.status = 'LIVE'
        )
    )
    ORDER BY ts.start_time
    LIMIT 5
");
$stmt->execute([
    $user_id,
    $current_day,
    $current_time,
    $user_id,
    $user_id
]);
$upcoming_slots = $stmt->fetchAll();

// Handle session start
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start_session'])) {
    $slot_id = $_POST['slot_id'];
    
    // Verify this teacher is allowed to start this slot
    $stmt = $pdo->prepare("
        SELECT ts.*, s.code, s.name 
        FROM timetable_slots ts
        INNER JOIN subjects s ON ts.subject_id = s.subject_id
        WHERE ts.slot_id = ? 
        AND (
            ts.default_teacher_id = ?
            OR EXISTS (
                SELECT 1 FROM live_sessions ls 
                WHERE ls.slot_id = ts.slot_id 
                AND ls.session_date = CURDATE() 
                AND ls.actual_teacher_id = ?
                AND ls.status = 'LIVE'
            )
        )
    ");
    $stmt->execute([$slot_id, $user_id, $user_id]);
    $slot = $stmt->fetch();
    
    if (!$slot) {
        die("Unauthorized: You are not assigned to this class.");
    }
    
    // Check if session already exists for this slot today
    $stmt = $pdo->prepare("
        SELECT session_id FROM live_sessions 
        WHERE slot_id = ? AND session_date = CURDATE() AND status = 'LIVE'
    ");
    $stmt->execute([$slot_id]);
    if ($stmt->fetch()) {
        die("A session for this class is already active.");
    }
    
    // Generate TOTP token (10-second window)
    $secret = uniqid('ses_', true);
    $token = substr(hash('sha256', $secret . time()), 0, 16);
    
    // Insert live session
    $stmt = $pdo->prepare("
        INSERT INTO live_sessions (slot_id, actual_teacher_id, session_date, status, active_totp_token, started_at)
        VALUES (?, ?, CURDATE(), 'LIVE', ?, NOW())
    ");
    $stmt->execute([$slot_id, $user_id, $token]);
    $session_id = $pdo->lastInsertId();
    
    $_SESSION['current_session'] = $session_id;
    $_SESSION['session_token'] = $token;
    $_SESSION['session_start'] = time();
    
    header('Location: live_session.php?session=' . $session_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Session - Teacher Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Live Session Controller</h1>
        <p class="text-gray-600 mb-8">Start attendance session for your current class</p>
        
        <!-- Session Active View -->
        <?php if (isset($_SESSION['current_session'])): 
            $session_id = $_SESSION['current_session'];
            $token = $_SESSION['session_token'];
            $start_time = $_SESSION['session_start'];
            $elapsed = time() - $start_time;
            
            // Fetch session details
            $stmt = $pdo->prepare("
                SELECT ls.*, s.code, s.name, ts.target_group
                FROM live_sessions ls
                INNER JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
                INNER JOIN subjects s ON ts.subject_id = s.subject_id
                WHERE ls.session_id = ?
            ");
            $stmt->execute([$session_id]);
            $session = $stmt->fetch();
        ?>
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-green-700">
                        <i class="fas fa-broadcast-tower mr-3"></i> Session Active
                    </h2>
                    <p class="text-gray-600">
                        <strong><?= htmlspecialchars($session['code']) ?>:</strong> 
                        <?= htmlspecialchars($session['name']) ?> | 
                        Session ID: <strong>#<?= $session_id ?></strong>
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-4xl font-bold <?= $elapsed < 180 ? 'text-green-600' : ($elapsed < 420 ? 'text-yellow-600' : 'text-red-600') ?>">
                        <?= gmdate("i:s", $elapsed) ?>
                    </div>
                    <p class="text-sm text-gray-500">
                        <?php if ($elapsed < 180): ?>
                        <span class="text-green-600"><i class="fas fa-circle"></i> Green Zone (0-3 min)</span>
                        <?php elseif ($elapsed < 420): ?>
                        <span class="text-yellow-600"><i class="fas fa-circle"></i> Late Zone (3-7 min)</span>
                        <?php else: ?>
                        <span class="text-red-600"><i class="fas fa-circle"></i> Locked (7+ min)</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <!-- QR Code Display -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-lg font-bold mb-4">Attendance QR Code</h3>
                    <div class="bg-gray-100 p-6 rounded-xl flex justify-center">
                        <div id="qrcode" class="p-4 bg-white rounded-lg"></div>
                    </div>
                    <p class="text-sm text-gray-600 mt-4 text-center">
                        <i class="fas fa-sync-alt mr-1"></i> Refreshes every 10 seconds
                    </p>
                </div>
                
                <div>
                    <h3 class="text-lg font-bold mb-4">Session Details</h3>
                    <div class="space-y-4">
                        <div class="p-4 bg-blue-50 rounded-lg">
                            <div class="font-medium text-blue-800">Token (TOTP)</div>
                            <div class="font-mono text-sm break-all mt-1"><?= $token ?></div>
                        </div>
                        <div class="p-4 bg-yellow-50 rounded-lg">
                            <div class="font-medium text-yellow-800">Instructions</div>
                            <ul class="text-sm text-yellow-700 mt-2 list-disc pl-5">
                                <li>Display QR on projector/board</li>
                                <li>Students scan within 7 minutes</li>
                                <li>After 7 minutes, QR auto-locks</li>
                                <li>Close session after class ends</li>
                            </ul>
                        </div>
                        <div class="flex space-x-4">
                            <button onclick="refreshQR()" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 transition">
                                <i class="fas fa-sync mr-2"></i> Refresh QR
                            </button>
                            <a href="close_session.php?session=<?= $session_id ?>" class="flex-1 bg-red-600 text-white py-3 rounded-lg font-medium hover:bg-red-700 transition text-center">
                                <i class="fas fa-stop-circle mr-2"></i> End Session
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            // Generate QR Code
            function generateQR() {
                const token = "<?= $token ?>";
                const sessionId = "<?= $session_id ?>";
                const data = JSON.stringify({
                    session_id: sessionId,
                    token: token,
                    timestamp: Math.floor(Date.now() / 10000) // 10-second window
                });
                
                QRCode.toCanvas(document.getElementById('qrcode'), data, {
                    width: 256,
                    margin: 2,
                    color: {
                        dark: '#1e40af',
                        light: '#ffffff'
                    }
                }, function(error) {
                    if (error) console.error(error);
                });
            }
            
            function refreshQR() {
                // In production, request new token from server
                location.reload();
            }
            
            // Initial generation
            generateQR();
            // Refresh every 10 seconds
            setInterval(generateQR, 10000);
        </script>
        
        <!-- Session Start Form (hidden when active) -->
        <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left: Start Current Session -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="far fa-clock mr-3"></i> Current Time Slot
                        <span class="text-lg font-normal text-gray-600 ml-3">
                            (<?= $current_day ?>, <?= date('H:i') ?>)
                        </span>
                    </h2>
                    
                    <?php if (empty($current_slots)): ?>
                    <div class="p-8 bg-yellow-50 border-2 border-yellow-300 rounded-xl text-center">
                        <i class="fas fa-calendar-times text-yellow-500 text-5xl mb-4"></i>
                        <h3 class="text-2xl font-bold text-yellow-800 mb-3">No Class Scheduled Right Now</h3>
                        <p class="text-yellow-700 mb-6">
                            You don't have any assigned classes within 30 minutes of current time.
                        </p>
                        
                        <!-- Check upcoming classes -->
                        <?php if (!empty($upcoming_slots)): ?>
                        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg inline-block">
                            <p class="text-blue-800">
                                <i class="fas fa-arrow-right mr-2"></i>
                                Your next class is at <strong><?= $upcoming_slots[0]['start_time'] ?></strong>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <form method="POST">
                        <div class="space-y-6">
                            <!-- Current Slot Selection -->
                            <div>
                                <label class="block text-gray-700 mb-4 font-medium">Select Your Current Class</label>
                                <div class="space-y-4">
                                    <?php foreach ($current_slots as $slot): 
                                        $target = json_decode($slot['target_group'], true);
                                    ?>
                                    <label class="block border-2 border-blue-500 bg-blue-50 rounded-xl p-6 cursor-pointer hover:border-blue-700 transition">
                                        <input type="radio" name="slot_id" value="<?= $slot['slot_id'] ?>" required class="mr-3">
                                        <div class="flex items-start">
                                            <div class="flex-1">
                                                <div class="flex items-center mb-2">
                                                    <h3 class="text-2xl font-bold text-blue-800">
                                                        <?= htmlspecialchars($slot['code']) ?> - <?= htmlspecialchars($slot['name']) ?>
                                                    </h3>
                                                    <?php if ($slot['is_lab']): ?>
                                                    <span class="ml-3 px-3 py-1 text-sm font-bold bg-purple-100 text-purple-800 rounded">
                                                        LAB
                                                    </span>
                                                    <?php endif; ?>
                                                    <?php if ($slot['assignment_type'] == 'SWAPPED'): ?>
                                                    <span class="ml-3 px-3 py-1 text-sm font-bold bg-green-100 text-green-800 rounded">
                                                        <i class="fas fa-exchange-alt mr-1"></i> SWAPPED
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                                                    <div class="p-3 bg-white rounded-lg border">
                                                        <div class="text-sm text-gray-500">Time</div>
                                                        <div class="font-bold text-gray-800">
                                                            <?= $slot['start_time'] ?> - <?= $slot['end_time'] ?>
                                                        </div>
                                                    </div>
                                                    <div class="p-3 bg-white rounded-lg border">
                                                        <div class="text-sm text-gray-500">Room</div>
                                                        <div class="font-bold text-gray-800">
                                                            <?= htmlspecialchars($slot['room'] ?: 'N/A') ?>
                                                        </div>
                                                    </div>
                                                    <div class="p-3 bg-white rounded-lg border">
                                                        <div class="text-sm text-gray-500">Section</div>
                                                        <div class="font-bold text-gray-800">
                                                            <?= htmlspecialchars($slot['section'] ?: 'N/A') ?>
                                                        </div>
                                                    </div>
                                                    <div class="p-3 bg-white rounded-lg border">
                                                        <div class="text-sm text-gray-500">Batch</div>
                                                        <div class="font-bold text-gray-800">
                                                            <?= htmlspecialchars($slot['batch'] ?: 'All') ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-5xl text-blue-600">
                                                    <i class="fas fa-chalkboard-teacher"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Batch Override (Only for labs) -->
                            <?php 
                            $has_lab = false;
                            foreach ($current_slots as $slot) {
                                if ($slot['is_lab']) {
                                    $has_lab = true;
                                    break;
                                }
                            }
                            ?>
                            <?php if ($has_lab): ?>
                            <div>
                                <label class="block text-gray-700 mb-2 font-medium">Lab Batch Override</label>
                                <p class="text-sm text-gray-600 mb-3">
                                    For lab sessions, you can override the batch if needed.
                                </p>
                                <div class="grid grid-cols-3 gap-4">
                                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:border-blue-500">
                                        <input type="radio" name="batch_override" value="A1" class="mr-2">
                                        <span>Batch A1</span>
                                    </label>
                                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:border-blue-500">
                                        <input type="radio" name="batch_override" value="A2" class="mr-2">
                                        <span>Batch A2</span>
                                    </label>
                                    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:border-blue-500">
                                        <input type="radio" name="batch_override" value="keep" checked class="mr-2">
                                        <span>Keep from Timetable</span>
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-8 p-4 bg-blue-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-shield-alt text-blue-500 text-2xl mr-3"></i>
                                <div>
                                    <p class="font-bold text-blue-800">Security Protocols Active</p>
                                    <p class="text-sm text-blue-700">
                                        QR changes every 10 seconds. Attendance only accepted from campus WiFi.
                                        Device-locked to prevent proxy marking.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="start_session" 
                                class="w-full mt-8 bg-gradient-to-r from-green-600 to-green-700 text-white font-bold py-4 rounded-lg hover:from-green-700 hover:to-green-800 transition duration-300 shadow-lg text-xl">
                            <i class="fas fa-play-circle mr-3"></i> Start Live Session
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Today's Upcoming Classes -->
            <div>
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="far fa-calendar-alt text-blue-600 mr-3"></i> Today's Schedule
                    </h2>
                    
                    <?php if (empty($upcoming_slots)): ?>
                    <div class="text-center py-4">
                        <p class="text-gray-500">No more classes scheduled for today.</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($upcoming_slots as $slot): 
                            $is_now = ($slot['start_time'] <= date('H:i') && date('H:i') <= $slot['end_time']);
                        ?>
                        <div class="border border-gray-200 rounded-lg p-4 <?= $is_now ? 'border-blue-500 bg-blue-50' : '' ?>">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h3 class="font-bold text-gray-800"><?= htmlspecialchars($slot['code']) ?></h3>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($slot['name']) ?></p>
                                </div>
                                <span class="px-2 py-1 text-xs font-bold rounded-full 
                                    <?= $is_now ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= $is_now ? 'NOW' : $slot['start_time'] ?>
                                </span>
                            </div>
                            <div class="text-sm text-gray-700">
                                <i class="far fa-clock mr-2"></i>
                                <?= $slot['start_time'] ?> - <?= $slot['end_time'] ?>
                                <?php if ($slot['room']): ?>
                                | <i class="fas fa-door-closed mr-1"></i> <?= htmlspecialchars($slot['room']) ?>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                Batch <?= htmlspecialchars($slot['batch']) ?> | 
                                Teacher: <?= htmlspecialchars($slot['display_name']) ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Current Time -->
                    <div class="mt-6 p-4 bg-gray-100 rounded-lg">
                        <div class="text-sm text-gray-600">Current Time</div>
                        <div class="text-2xl font-bold text-gray-800" id="currentTime">
                            <?= date('H:i') ?>
                        </div>
                        <div class="text-xs text-gray-500"><?= date('l, F j, Y') ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <!-- Red Zone Alert Widget -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i> Red Zone Alert
    </h2>
    
    <?php
    // Count red zone students for this teacher's subjects
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT sp.user_id) as red_count
        FROM student_profiles sp
        INNER JOIN users u ON sp.user_id = u.user_id
        LEFT JOIN student_attendance_summary sas ON sp.user_id = sas.student_id
        WHERE (
            sas.total_sessions > 0 
            AND ((sas.present_count + (sas.late_count * 0.5)) / sas.total_sessions) < 0.35
        )
        OR sas.total_sessions IS NULL
        LIMIT 1
    ");
    $stmt->execute();
    $red_count = $stmt->fetchColumn();
    ?>
    
    <div class="<?= $red_count > 0 ? 'bg-red-50 border border-red-200' : 'bg-green-50 border border-green-200' ?> rounded-lg p-6">
        <div class="flex items-center">
            <div class="h-16 w-16 rounded-full <?= $red_count > 0 ? 'bg-red-100' : 'bg-green-100' ?> flex items-center justify-center mr-6">
                <i class="fas <?= $red_count > 0 ? 'fa-exclamation-triangle text-red-600' : 'fa-check-circle text-green-600' ?> text-3xl"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-2xl font-bold <?= $red_count > 0 ? 'text-red-800' : 'text-green-800' ?>">
                    <?= $red_count > 0 ? $red_count . ' Students in Red Zone' : 'All Students Safe' ?>
                </h3>
                <p class="<?= $red_count > 0 ? 'text-red-700' : 'text-green-700' ?>">
                    <?= $red_count > 0 
                        ? 'Immediate attention required for students below 35% attendance.' 
                        : 'No students below the 35% attendance threshold.' ?>
                </p>
            </div>
            <?php if ($red_count > 0): ?>
            <a href="red_zone.php" class="bg-red-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-red-700 transition">
                <i class="fas fa-list mr-2"></i> View Red Zone
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
        <!-- Recent Sessions -->
        <div class="bg-white rounded-xl shadow-lg p-6 mt-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Sessions</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scans</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        $stmt = $pdo->prepare("
                            SELECT 
                                ls.session_id,
                                ls.session_date,
                                ls.started_at,
                                ls.ended_at,
                                ls.status,
                                s.code,
                                s.name
                            FROM live_sessions ls
                            INNER JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
                            INNER JOIN subjects s ON ts.subject_id = s.subject_id
                            WHERE ls.actual_teacher_id = ? 
                            ORDER BY ls.session_id DESC 
                            LIMIT 5
                        ");
                        $stmt->execute([$user_id]);
                        $sessions = $stmt->fetchAll();
                        
                        foreach ($sessions as $s):
                            $duration = $s['ended_at'] 
                                ? strtotime($s['ended_at']) - strtotime($s['started_at'])
                                : (time() - strtotime($s['started_at']));
                                
                            // Count scans
                            $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE session_id = ?");
                            $stmt2->execute([$s['session_id']]);
                            $scan_count = $stmt2->fetchColumn();
                        ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?= date('d/m/Y', strtotime($s['session_date'])) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium"><?= htmlspecialchars($s['code']) ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($s['name']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= gmdate("H:i:s", $duration) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $scan_count ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full 
                                    <?= $s['status'] == 'LIVE' ? 'bg-green-100 text-green-800' : 
                                       ($s['status'] == 'COMPLETED' ? 'bg-blue-100 text-blue-800' : 
                                       'bg-gray-100 text-gray-800') ?>">
                                    <?= $s['status'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
    </script>
</body>
</html>