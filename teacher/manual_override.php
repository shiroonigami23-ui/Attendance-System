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

// Handle manual override submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_override'])) {
    $session_id = $_POST['session_id'];
    $student_id = $_POST['student_id'];
    $override_reason = $_POST['override_reason'];
    $custom_reason = $_POST['custom_reason'] ?? '';
    
    // Validate 24-hour window
    $stmt = $pdo->prepare("
        SELECT ls.session_date, ls.session_id 
        FROM live_sessions ls
        INNER JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
        WHERE ls.session_id = ? 
        AND ls.actual_teacher_id = ?
        AND ls.status = 'COMPLETED'
        AND TIMESTAMPDIFF(HOUR, CONCAT(ls.session_date, ' ', ts.end_time), NOW()) <= 24
    ");
    $stmt->execute([$session_id, $user_id]);
    $session = $stmt->fetch();
    
    if (!$session) {
        $error = "Cannot override: Session not found, not taught by you, or 24-hour window expired.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insert into manual_overrides
            $stmt = $pdo->prepare("
                INSERT INTO manual_overrides (session_id, student_id, teacher_id, override_reason, custom_reason)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$session_id, $student_id, $user_id, $override_reason, $custom_reason]);
            
            // Update attendance_logs
            $stmt = $pdo->prepare("
                INSERT INTO attendance_logs (session_id, student_id, status, verification_method, is_manual_override, scanned_at)
                VALUES (?, ?, 'PRESENT', 'MANUAL_OVERRIDE', TRUE, NOW())
                ON DUPLICATE KEY UPDATE 
                status = 'PRESENT', 
                verification_method = 'MANUAL_OVERRIDE',
                is_manual_override = TRUE,
                scanned_at = NOW()
            ");
            $stmt->execute([$session_id, $student_id]);
            
            $pdo->commit();
            $success = "Student marked as present via manual override.";
            
            // Refresh the page to show updated data
            header("Location: manual_override.php?session_id=" . $session_id);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error applying override: " . $e->getMessage();
        }
    }
}

// Get teacher's recent completed sessions (last 24 hours)
$stmt = $pdo->prepare("
    SELECT ls.session_id, ls.session_date, ts.start_time, ts.end_time,
           s.code as subject_code, s.name as subject_name,
           ts.target_group,
           COUNT(DISTINCT al.student_id) as marked_count,
           COUNT(DISTINCT sp.user_id) as total_students
    FROM live_sessions ls
    INNER JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
    INNER JOIN subjects s ON ts.subject_id = s.subject_id
    LEFT JOIN student_profiles sp ON JSON_UNQUOTE(JSON_EXTRACT(ts.target_group, '$.section')) = sp.section
    LEFT JOIN attendance_logs al ON ls.session_id = al.session_id AND al.student_id = sp.user_id
    WHERE ls.actual_teacher_id = ? 
    AND ls.status = 'COMPLETED'
    AND TIMESTAMPDIFF(HOUR, CONCAT(ls.session_date, ' ', ts.end_time), NOW()) <= 24
    GROUP BY ls.session_id
    ORDER BY ls.session_date DESC, ts.start_time DESC
");
$stmt->execute([$user_id]);
$recent_sessions = $stmt->fetchAll();

// Get selected session details
$selected_session = null;
$session_students = [];
if (isset($_GET['session_id'])) {
    $session_id = $_GET['session_id'];
    
    // Verify teacher owns this session
    $stmt = $pdo->prepare("
        SELECT ls.*, ts.start_time, ts.end_time, s.code, s.name, ts.target_group
        FROM live_sessions ls
        INNER JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
        INNER JOIN subjects s ON ts.subject_id = s.subject_id
        WHERE ls.session_id = ? 
        AND ls.actual_teacher_id = ?
        AND ls.status = 'COMPLETED'
        AND TIMESTAMPDIFF(HOUR, CONCAT(ls.session_date, ' ', ts.end_time), NOW()) <= 24
    ");
    $stmt->execute([$session_id, $user_id]);
    $selected_session = $stmt->fetch();
    
    if ($selected_session) {
        $target = json_decode($selected_session['target_group'], true);
        $section = $target['section'] ?? 'A';
        
        // Get all students in this section with their attendance status
        $stmt = $pdo->prepare("
            SELECT sp.user_id, sp.roll_no, u.full_name, u.email,
                   al.status as attendance_status,
                   al.scanned_at,
                   al.verification_method,
                   mo.override_reason,
                   mo.custom_reason,
                   mo.overridden_at
            FROM student_profiles sp
            INNER JOIN users u ON sp.user_id = u.user_id
            LEFT JOIN attendance_logs al ON sp.user_id = al.student_id AND al.session_id = ?
            LEFT JOIN manual_overrides mo ON sp.user_id = mo.student_id AND mo.session_id = ?
            WHERE sp.section = ?
            AND u.is_active = 1
            ORDER BY sp.roll_no
        ");
        $stmt->execute([$session_id, $session_id, $section]);
        $session_students = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Override - Teacher Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-badge {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 600;
        }
        .status-present { background-color: #d1fae5; color: #065f46; }
        .status-late { background-color: #fef3c7; color: #92400e; }
        .status-absent { background-color: #fee2e2; color: #991b1b; }
        .override-card {
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }
        .override-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .time-window {
            position: relative;
        }
        .time-window::after {
            content: '24h';
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
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
                        <i class="fas fa-hand-paper text-orange-600 text-2xl mr-3"></i>
                        <span class="text-xl font-bold text-gray-800">Manual Override</span>
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

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Manual Attendance Override</h1>
            <p class="text-gray-600 mt-2">Mark students as present for technical issues (24-hour window only)</p>
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
            <!-- Left Column: Recent Sessions -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-history text-blue-600 mr-3"></i> Recent Sessions (24h)
                    </h2>
                    
                    <?php if (empty($recent_sessions)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-calendar-times text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900">No recent sessions</h3>
                        <p class="text-gray-500">Completed sessions from last 24 hours will appear here</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_sessions as $session): 
                            $target = json_decode($session['target_group'], true);
                            $is_selected = isset($_GET['session_id']) && $_GET['session_id'] == $session['session_id'];
                        ?>
                        <a href="?session_id=<?= $session['session_id'] ?>" 
                           class="block override-card border border-gray-200 rounded-xl p-5 hover:border-blue-300 transition <?= $is_selected ? 'border-blue-500 bg-blue-50 border-l-4 border-l-blue-500' : '' ?>">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="font-bold text-gray-800"><?= htmlspecialchars($session['subject_code']) ?></h4>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($session['subject_name']) ?></p>
                                </div>
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full <?= $is_selected ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= date('h:i A', strtotime($session['start_time'])) ?>
                                </span>
                            </div>
                            <div class="flex items-center text-sm text-gray-500 space-x-4">
                                <span>
                                    <i class="far fa-calendar mr-1"></i>
                                    <?= date('M j', strtotime($session['session_date'])) ?>
                                </span>
                                <span>
                                    <i class="fas fa-users mr-1"></i>
                                    Sec: <?= $target['section'] ?? 'A' ?>
                                </span>
                            </div>
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-600">Attendance:</span>
                                    <span class="font-medium <?= $session['marked_count'] == $session['total_students'] ? 'text-green-600' : 'text-red-600' ?>">
                                        <?= $session['marked_count'] ?: 0 ?>/<?= $session['total_students'] ?: 0 ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Student List & Override Form -->
            <div class="lg:col-span-2">
                <?php if (!$selected_session): ?>
                <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
                    <div class="h-20 w-20 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-mouse-pointer text-blue-600 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Select a Session</h3>
                    <p class="text-gray-600 mb-6">Choose a completed session from the last 24 hours to override attendance.</p>
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Only sessions completed within 24 hours are eligible for overrides.
                    </div>
                </div>
                <?php else: 
                    $target = json_decode($selected_session['target_group'], true);
                    $hours_remaining = 24 - floor((time() - strtotime($selected_session['session_date'] . ' ' . $selected_session['end_time'])) / 3600);
                ?>
                <!-- Session Header -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($selected_session['code']) ?>: <?= htmlspecialchars($selected_session['name']) ?></h2>
                            <div class="flex items-center text-gray-600 mt-2 space-x-6">
                                <span class="flex items-center">
                                    <i class="far fa-calendar-alt mr-2"></i>
                                    <?= date('F j, Y', strtotime($selected_session['session_date'])) ?>
                                </span>
                                <span class="flex items-center">
                                    <i class="far fa-clock mr-2"></i>
                                    <?= date('h:i A', strtotime($selected_session['start_time'])) ?> - <?= date('h:i A', strtotime($selected_session['end_time'])) ?>
                                </span>
                                <span class="flex items-center">
                                    <i class="fas fa-users mr-2"></i>
                                    Section <?= $target['section'] ?? 'A' ?>
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500">Override Window</div>
                            <div class="text-2xl font-bold <?= $hours_remaining > 4 ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $hours_remaining ?>h remaining
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Student List -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-user-graduate text-purple-600 mr-3"></i>
                        Students in Section <?= $target['section'] ?? 'A' ?>
                        <span class="ml-auto text-sm font-normal text-gray-500">
                            <?= count($session_students) ?> students
                        </span>
                    </h3>

                    <?php if (empty($session_students)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-user-slash text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900">No students found</h3>
                        <p class="text-gray-500">No students registered in this section.</p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roll No</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verification</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($session_students as $student): 
                                    $status_class = 'status-absent';
                                    $status_text = 'ABSENT';
                                    $verification_icon = 'fas fa-times-circle text-red-500';
                                    $verification_text = 'Not Marked';
                                    
                                    if ($student['attendance_status'] == 'PRESENT') {
                                        $status_class = 'status-present';
                                        $status_text = 'PRESENT';
                                        $verification_icon = $student['verification_method'] == 'MANUAL_OVERRIDE' ? 'fas fa-hand-paper text-orange-500' : 'fas fa-qrcode text-green-500';
                                        $verification_text = $student['verification_method'] == 'MANUAL_OVERRIDE' ? 'Manual Override' : 'QR Scan';
                                    } elseif ($student['attendance_status'] == 'LATE') {
                                        $status_class = 'status-late';
                                        $status_text = 'LATE';
                                        $verification_icon = 'fas fa-clock text-yellow-500';
                                        $verification_text = 'QR Scan (Late)';
                                    }
                                ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($student['roll_no']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($student['full_name']) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($student['email']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="status-badge <?= $status_class ?>">
                                            <?= $status_text ?>
                                        </span>
                                        <?php if ($student['scanned_at']): ?>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <?= date('h:i A', strtotime($student['scanned_at'])) ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i class="<?= $verification_icon ?> mr-2"></i>
                                            <span class="text-sm"><?= $verification_text ?></span>
                                        </div>
                                        <?php if ($student['override_reason']): ?>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Reason: <?= $student['override_reason'] ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <?php if ($student['attendance_status'] != 'PRESENT'): ?>
                                        <button type="button" 
                                                onclick="showOverrideModal(<?= $student['user_id'] ?>, '<?= htmlspecialchars(addslashes($student['roll_no'])) ?>', '<?= htmlspecialchars(addslashes($student['full_name'])) ?>')"
                                                class="text-sm bg-gradient-to-r from-orange-500 to-orange-600 text-white px-4 py-2 rounded-lg font-medium hover:from-orange-600 hover:to-orange-700 transition flex items-center">
                                            <i class="fas fa-hand-paper mr-2"></i> Override
                                        </button>
                                        <?php elseif ($student['verification_method'] == 'MANUAL_OVERRIDE'): ?>
                                        <span class="text-sm text-gray-500 italic">Already overridden</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Override Modal -->
    <div id="overrideModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-md w-full max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-bold text-gray-800 mb-2">Manual Attendance Override</h3>
            <p class="text-gray-600 mb-6">Mark student as present with a valid reason.</p>
            
            <div class="mb-6 p-4 bg-blue-50 rounded-xl">
                <div class="flex items-center mb-2">
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                        <i class="fas fa-user-graduate text-blue-600"></i>
                    </div>
                    <div>
                        <h4 id="modalStudentName" class="font-bold text-gray-800"></h4>
                        <p id="modalStudentRoll" class="text-sm text-gray-600"></p>
                    </div>
                </div>
                <div class="mt-3 text-sm text-gray-700">
                    <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                    This override will be recorded in the audit trail.
                </div>
            </div>
            
            <form method="POST" id="overrideForm">
                <input type="hidden" name="session_id" value="<?= $selected_session['session_id'] ?? '' ?>">
                <input type="hidden" name="student_id" id="modalStudentId">
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-clipboard-list text-orange-600 mr-1"></i>
                        Select Reason
                    </label>
                    <select name="override_reason" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition">
                        <option value="">Choose a reason...</option>
                        <option value="NETWORK_ERROR">Network/Internet Issues</option>
                        <option value="DEVICE_ISSUE">Student Device Problem</option>
                        <option value="QR_NOT_VISIBLE">QR Code Not Visible</option>
                        <option value="STUDENT_PRESENT">Student Was Physically Present</option>
                        <option value="OTHER">Other (Specify Below)</option>
                    </select>
                </div>
                
                <div class="mb-6" id="customReasonDiv" style="display: none;">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-comment-dots text-orange-600 mr-1"></i>
                        Custom Reason
                    </label>
                    <textarea name="custom_reason" rows="2" 
                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition"
                              placeholder="Please specify the reason..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="hideOverrideModal()"
                            class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" name="submit_override"
                            class="px-6 py-2.5 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-xl font-medium hover:from-orange-600 hover:to-orange-700 transition flex items-center">
                        <i class="fas fa-check mr-2"></i> Apply Override
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show/hide custom reason field
        document.querySelector('select[name="override_reason"]')?.addEventListener('change', function() {
            const customReasonDiv = document.getElementById('customReasonDiv');
            customReasonDiv.style.display = this.value === 'OTHER' ? 'block' : 'none';
        });
        
        // Override modal functions
        function showOverrideModal(studentId, rollNo, studentName) {
            document.getElementById('modalStudentId').value = studentId;
            document.getElementById('modalStudentRoll').textContent = rollNo;
            document.getElementById('modalStudentName').textContent = studentName;
            document.getElementById('overrideModal').classList.remove('hidden');
            
            // Reset form
            document.querySelector('#overrideForm select[name="override_reason"]').value = '';
            document.getElementById('customReasonDiv').style.display = 'none';
            document.querySelector('#overrideForm textarea[name="custom_reason"]').value = '';
        }
        
        function hideOverrideModal() {
            document.getElementById('overrideModal').classList.add('hidden');
        }
        
        // Close modal on outside click
        document.getElementById('overrideModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideOverrideModal();
            }
        });
        
        // Form validation
        document.getElementById('overrideForm').addEventListener('submit', function(e) {
            const reason = document.querySelector('select[name="override_reason"]').value;
            const customReason = document.querySelector('textarea[name="custom_reason"]').value;
            
            if (!reason) {
                e.preventDefault();
                alert('Please select a reason for the override.');
                return false;
            }
            
            if (reason === 'OTHER' && !customReason.trim()) {
                e.preventDefault();
                alert('Please specify a custom reason.');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
            submitBtn.disabled = true;
            
            // Re-enable after 3 seconds if still on page
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });
        
        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const successMsg = document.querySelector('.bg-green-50');
            const errorMsg = document.querySelector('.bg-red-50');
            if (successMsg) successMsg.style.display = 'none';
            if (errorMsg) errorMsg.style.display = 'none';
        }, 5000);
    </script>
</body>
</html>