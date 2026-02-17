<?php
require_once '../includes/Config.php';
require_once '../includes/Auth.php';
require_once '../includes/SessionManager.php';

checkAuth();
$user = getCurrentUser();

// Ensure only teachers can access
if ($_SESSION['role'] !== 'SEMI_ADMIN') {
    header('Location: ../dashboard.php');
    exit;
}

$user_id = $user['user_id'];

// Handle cancel request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_request'])) {
    $request_id = $_POST['request_id'];
    
    // Verify teacher owns this request and it's pending
    $stmt = $pdo->prepare("
        SELECT request_id, status 
        FROM teacher_leave_requests 
        WHERE request_id = ? 
        AND teacher_id = ?
    ");
    $stmt->execute([$request_id, $user_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        $error = "Request not found or unauthorized.";
    } elseif ($request['status'] != 'PENDING') {
        $error = "Only pending requests can be cancelled.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update request status
            $stmt = $pdo->prepare("
                UPDATE teacher_leave_requests 
                SET status = 'CANCELLED', 
                    notes = CONCAT(COALESCE(notes, ''), '\\nCancelled by teacher on ', NOW())
                WHERE request_id = ?
            ");
            $stmt->execute([$request_id]);
            
            // Log the cancellation
            $pdo->prepare("
                INSERT INTO system_logs (user_id, action, details, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([
                $user_id,
                'TEACHER_LEAVE_CANCELLED',
                json_encode(['request_id' => $request_id]),
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
            
            $pdo->commit();
            
            $_SESSION['leave_success'] = "Leave request #{$request_id} cancelled successfully.";
            header("Location: leave_apply.php");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to cancel request: " . $e->getMessage();
        }
    }
}

// Handle form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_leave'])) {
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $leave_type = $_POST['leave_type'] ?? 'CASUAL';
    
    // Validate dates
    if (empty($date_from) || empty($date_to)) {
        $errors[] = "Please select both start and end dates.";
    } else {
        $from = new DateTime($date_from);
        $to = new DateTime($date_to);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        // Check if dates are in future (teachers can apply for immediate leaves too)
        if ($from < $today) {
            $errors[] = "Cannot apply leave for past dates. Please select today or future dates.";
        }
        
        // Check date order
        if ($from > $to) {
            $errors[] = "End date must be after start date.";
        }
        
        // Calculate days
        $interval = $from->diff($to);
        $days = $interval->days + 1;
        
        if ($days > 30) {
            $errors[] = "Maximum leave period is 30 days. For longer leaves, contact admin directly.";
        }
    }
    
    // Validate reason
    if (empty($reason) || strlen($reason) < 10) {
        $errors[] = "Please provide a detailed reason (minimum 10 characters).";
    }
    
    // Handle document upload (optional for teachers)
    $document_path = null;
    if (isset($_FILES['document']) && $_FILES['document']['error'] == UPLOAD_ERR_OK) {
        $doc = $_FILES['document'];
        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 10 * 1024 * 1024; // 10MB
        
        if (!in_array($doc['type'], $allowed_types)) {
            $errors[] = "Invalid document type. Only PDF, JPG, PNG allowed.";
        } elseif ($doc['size'] > $max_size) {
            $errors[] = "Document too large. Maximum 10MB.";
        } else {
            $ext = pathinfo($doc['name'], PATHINFO_EXTENSION);
            $filename = 'teacher_leave_' . $user_id . '_' . time() . '.' . $ext;
            $upload_path = '../assets/uploads/teacher_leaves/' . $filename;
            
            // Create directory if not exists
            if (!file_exists('../assets/uploads/teacher_leaves/')) {
                mkdir('../assets/uploads/teacher_leaves/', 0777, true);
            }
            
            if (move_uploaded_file($doc['tmp_name'], $upload_path)) {
                $document_path = $upload_path;
            } else {
                $errors[] = "Failed to upload document.";
            }
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insert into teacher_leave_requests table
            $stmt = $pdo->prepare("
                INSERT INTO teacher_leave_requests (teacher_id, date_from, date_to, leave_type, reason, document_path, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'PENDING', NOW())
            ");
            $stmt->execute([$user_id, $date_from, $date_to, $leave_type, $reason, $document_path]);
            $request_id = $pdo->lastInsertId();
            
            // Log the request
            $pdo->prepare("
                INSERT INTO system_logs (user_id, action, details, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?)
            ")->execute([
                $user_id,
                'TEACHER_LEAVE_REQUEST',
                json_encode([
                    'request_id' => $request_id,
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                    'days' => $days ?? 0,
                    'leave_type' => $leave_type
                ]),
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
            
            $pdo->commit();
            
            $success = "Leave request submitted successfully! Admin will review and arrange substitute teachers.";
            
            // Clear form
            $_POST = [];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Check if teacher_leave_requests table exists, if not create it
try {
    $stmt = $pdo->query("SELECT 1 FROM teacher_leave_requests LIMIT 1");
} catch (PDOException $e) {
    // Table doesn't exist, create it
    $create_table = "
        CREATE TABLE teacher_leave_requests (
            request_id INT AUTO_INCREMENT PRIMARY KEY,
            teacher_id BIGINT NOT NULL,
            date_from DATE NOT NULL,
            date_to DATE NOT NULL,
            leave_type ENUM('CASUAL', 'MEDICAL', 'EMERGENCY', 'PERSONAL', 'OTHER') DEFAULT 'CASUAL',
            reason TEXT NOT NULL,
            document_path VARCHAR(255),
            status ENUM('PENDING', 'APPROVED', 'REJECTED', 'CANCELLED') DEFAULT 'PENDING',
            approved_by BIGINT,
            approved_at TIMESTAMP NULL,
            substitute_teacher_id BIGINT,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES users(user_id),
            FOREIGN KEY (approved_by) REFERENCES users(user_id),
            FOREIGN KEY (substitute_teacher_id) REFERENCES users(user_id),
            INDEX idx_teacher_id (teacher_id),
            INDEX idx_status (status),
            INDEX idx_dates (date_from, date_to)
        )
    ";
    $pdo->exec($create_table);
}

// Get teacher's pending leave requests
$stmt = $pdo->prepare("
    SELECT *
    FROM teacher_leave_requests
    WHERE teacher_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$leave_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Display success message from cancellation
if (isset($_SESSION['leave_success'])) {
    $success = $_SESSION['leave_success'];
    unset($_SESSION['leave_success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Leave - Teacher Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-calendar-times text-orange-600 text-2xl mr-3"></i>
                        <span class="text-xl font-bold text-gray-800">Teacher Leave Application</span>
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
            <h1 class="text-3xl font-bold text-gray-900">Apply for Leave</h1>
            <p class="text-gray-600 mt-2">Submit leave request for admin approval. Substitute teachers will be arranged.</p>
        </div>

        <!-- Messages -->
        <?php if (isset($error)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <div class="flex">
                <i class="fas fa-exclamation-circle text-red-400 mt-1"></i>
                <div class="ml-3">
                    <p class="text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <div class="flex">
                <i class="fas fa-exclamation-circle text-red-400 mt-1"></i>
                <div class="ml-3">
                    <p class="text-sm text-red-700 font-medium">Please fix the following errors:</p>
                    <ul class="mt-2 text-sm text-red-600 list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
            <div class="flex">
                <i class="fas fa-check-circle text-green-400 mt-1"></i>
                <div class="ml-3">
                    <p class="text-sm text-green-700"><?= htmlspecialchars($success) ?></p>
                    <p class="text-sm text-green-600 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Admin will review your request and arrange substitute teachers for your classes.
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Leave Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">New Leave Request</h2>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <!-- Date Range -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-gray-700 mb-2">
                                    <i class="fas fa-calendar-day mr-2 text-blue-500"></i> From Date
                                </label>
                                <input type="date" 
                                       name="date_from" 
                                       id="date_from"
                                       value="<?= htmlspecialchars($_POST['date_from'] ?? '') ?>" 
                                       min="<?= date('Y-m-d') ?>"
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                       required>
                                <p class="text-xs text-gray-500 mt-1">Earliest: Today</p>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">
                                    <i class="fas fa-calendar-day mr-2 text-blue-500"></i> To Date
                                </label>
                                <input type="date" 
                                       name="date_to" 
                                       id="date_to"
                                       value="<?= htmlspecialchars($_POST['date_to'] ?? '') ?>"
                                       min="<?= date('Y-m-d') ?>"
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                       required>
                                <p class="text-xs text-gray-500 mt-1">Leave end date</p>
                            </div>
                        </div>

                        <!-- Days Calculation -->
                        <div id="days_calculation" class="mb-6 p-4 bg-blue-50 rounded-lg hidden">
                            <p class="text-sm font-medium text-blue-800">
                                <i class="fas fa-calculator mr-2"></i>
                                <span id="days_text">0 days</span> requested
                            </p>
                            <p class="text-xs text-blue-600 mt-1" id="conflict_warning"></p>
                        </div>

                        <!-- Leave Type -->
                        <div class="mb-6">
                            <label class="block text-gray-700 mb-2">
                                <i class="fas fa-tag mr-2 text-blue-500"></i> Leave Type
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:border-blue-500">
                                    <input type="radio" name="leave_type" value="CASUAL" checked class="mr-2">
                                    <span>Casual</span>
                                </label>
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:border-blue-500">
                                    <input type="radio" name="leave_type" value="MEDICAL" class="mr-2">
                                    <span>Medical</span>
                                </label>
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:border-blue-500">
                                    <input type="radio" name="leave_type" value="EMERGENCY" class="mr-2">
                                    <span>Emergency</span>
                                </label>
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:border-blue-500">
                                    <input type="radio" name="leave_type" value="PERSONAL" class="mr-2">
                                    <span>Personal</span>
                                </label>
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:border-blue-500">
                                    <input type="radio" name="leave_type" value="OTHER" class="mr-2">
                                    <span>Other</span>
                                </label>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="mb-6">
                            <label class="block text-gray-700 mb-2">
                                <i class="fas fa-comment-medical mr-2 text-blue-500"></i> Reason for Leave
                            </label>
                            <textarea name="reason" 
                                      rows="4"
                                      class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                      placeholder="Provide detailed reason for leave (minimum 10 characters). Include any important details for substitute teachers."
                                      required><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
                        </div>

                        <!-- Document Upload (Optional) -->
                        <div class="mb-8">
                            <label class="block text-gray-700 mb-2">
                                <i class="fas fa-file-upload mr-2 text-blue-500"></i> Supporting Document (Optional)
                            </label>
                            <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-blue-400 transition">
                                <input type="file" 
                                       name="document" 
                                       id="document"
                                       accept=".pdf,.jpg,.jpeg,.png"
                                       class="hidden">
                                <div id="file_drop_area">
                                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
                                    <p class="text-gray-600 mb-2">
                                        <span class="text-blue-600 font-medium">Click to upload</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500">PDF, JPG, PNG (Max 10MB)</p>
                                    <p class="text-xs text-gray-500 mt-2">
                                        <i class="fas fa-info-circle"></i> Optional but recommended for medical/emergency leaves
                                    </p>
                                </div>
                                <div id="file_preview" class="hidden">
                                    <div class="flex items-center justify-between bg-gray-100 p-3 rounded-lg">
                                        <div class="flex items-center">
                                            <i class="fas fa-file-pdf text-red-500 text-xl mr-3"></i>
                                            <div>
                                                <p id="file_name" class="font-medium text-gray-800"></p>
                                                <p id="file_size" class="text-xs text-gray-500"></p>
                                            </div>
                                        </div>
                                        <button type="button" id="remove_file" class="text-red-500 hover:text-red-700">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Important Note -->
                        <div class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded-r-lg">
                            <div class="flex">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mt-1 mr-3"></i>
                                <div>
                                    <p class="font-medium text-yellow-800">Important Information</p>
                                    <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside">
                                        <li>Admin will arrange substitute teachers for your classes</li>
                                        <li>Check your timetable before applying for leave</li>
                                        <li>For emergency leaves, contact admin directly</li>
                                        <li>You can cancel pending requests before approval</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-4">
                            <button type="button" onclick="window.history.back()" 
                                    class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <button type="submit" name="submit_leave" 
                                    class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-8 py-3 rounded-lg font-medium hover:from-orange-700 hover:to-orange-800 transition shadow-lg">
                                <i class="fas fa-paper-plane mr-2"></i> Submit Leave Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column: Recent Requests & Info -->
            <div class="lg:col-span-1">
                <!-- Recent Requests -->
                <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">My Leave Requests</h2>
                    
                    <?php if (empty($leave_requests)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900">No leave requests</h3>
                        <p class="text-gray-500">Submit your first request</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($leave_requests as $request): 
                            $status_color = 'gray';
                            $status_icon = 'far fa-clock';
                            
                            switch($request['status']) {
                                case 'APPROVED':
                                    $status_color = 'green';
                                    $status_icon = 'fas fa-check-circle';
                                    break;
                                case 'REJECTED':
                                    $status_color = 'red';
                                    $status_icon = 'fas fa-times-circle';
                                    break;
                                case 'CANCELLED':
                                    $status_color = 'gray';
                                    $status_icon = 'fas fa-ban';
                                    break;
                                default:
                                    $status_color = 'yellow';
                                    $status_icon = 'far fa-clock';
                            }
                        ?>
                        <div class="border border-gray-200 rounded-xl p-4 hover:shadow-sm transition">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="font-medium text-gray-800">
                                        <?= date('M j', strtotime($request['date_from'])) ?> - 
                                        <?= date('M j', strtotime($request['date_to'])) ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?= $request['leave_type'] ?>
                                    </p>
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-<?= $status_color ?>-100 text-<?= $status_color ?>-800">
                                    <i class="<?= $status_icon ?> mr-1"></i>
                                    <?= $request['status'] ?>
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 truncate">
                                <?= htmlspecialchars(substr($request['reason'], 0, 50)) ?>...
                            </p>
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="far fa-clock mr-1"></i>
                                <?= date('M d, Y', strtotime($request['created_at'])) ?>
                            </p>
                            <?php if ($request['status'] == 'PENDING'): ?>
                            <div class="mt-2">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="request_id" value="<?= $request['request_id'] ?>">
                                    <input type="hidden" name="cancel_request" value="1">
                                    <button type="submit" 
                                            onclick="return confirm('Are you sure you want to cancel this leave request?')"
                                            class="text-xs text-red-600 hover:text-red-800">
                                        <i class="fas fa-times mr-1"></i> Cancel Request
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Leave Policy -->
                <div class="bg-blue-50 border-l-4 border-blue-500 rounded-r-lg p-6">
                    <h3 class="font-bold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i> Leave Policy
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span><strong>Casual Leave:</strong> Up to 12 days per year</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span><strong>Medical Leave:</strong> With medical certificate</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span><strong>Emergency Leave:</strong> Immediate approval possible</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-clock text-blue-500 mt-1 mr-2"></i>
                            <span><strong>Processing:</strong> 24-48 hours for non-emergency</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-user-tie text-purple-500 mt-1 mr-2"></i>
                            <span><strong>Substitute:</strong> Admin arranges replacement teachers</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Days calculation
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    const daysDiv = document.getElementById('days_calculation');
    const daysText = document.getElementById('days_text');
    const conflictWarning = document.getElementById('conflict_warning');
    
    function calculateDays() {
        if (dateFrom.value && dateTo.value) {
            const from = new Date(dateFrom.value);
            const to = new Date(dateTo.value);
            const diffTime = Math.abs(to - from);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            daysText.textContent = `${diffDays} day${diffDays !== 1 ? 's' : ''}`;
            daysDiv.classList.remove('hidden');
            
            // Check for conflicts (simplified - in real app, make AJAX call)
            if (diffDays > 7) {
                conflictWarning.textContent = "For leaves longer than 7 days, please contact admin directly.";
                conflictWarning.classList.remove('hidden');
            } else {
                conflictWarning.classList.add('hidden');
            }
        }
    }
    
    dateFrom.addEventListener('change', calculateDays);
    dateTo.addEventListener('change', calculateDays);
    
    // File upload handling
    const fileInput = document.getElementById('document');
    const fileDropArea = document.getElementById('file_drop_area');
    const filePreview = document.getElementById('file_preview');
    const fileName = document.getElementById('file_name');
    const fileSize = document.getElementById('file_size');
    const removeFileBtn = document.getElementById('remove_file');
    
    fileDropArea.addEventListener('click', () => fileInput.click());
    
    fileDropArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        fileDropArea.classList.add('border-blue-400', 'bg-blue-50');
    });
    
    fileDropArea.addEventListener('dragleave', () => {
        fileDropArea.classList.remove('border-blue-400', 'bg-blue-50');
    });
    
    fileDropArea.addEventListener('drop', (e) => {
        e.preventDefault();
        fileDropArea.classList.remove('border-blue-400', 'bg-blue-50');
        
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            updateFilePreview(e.dataTransfer.files[0]);
        }
    });
    
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length) {
            updateFilePreview(e.target.files[0]);
        }
    });
    
    removeFileBtn.addEventListener('click', () => {
        fileInput.value = '';
        filePreview.classList.add('hidden');
        fileDropArea.classList.remove('hidden');
    });
    
    function updateFilePreview(file) {
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        filePreview.classList.remove('hidden');
        fileDropArea.classList.add('hidden');
        
        // Update icon based on file type
        const icon = filePreview.querySelector('i');
        if (file.type === 'application/pdf') {
            icon.className = 'fas fa-file-pdf text-red-500 text-xl mr-3';
        } else if (file.type.startsWith('image/')) {
            icon.className = 'fas fa-file-image text-green-500 text-xl mr-3';
        } else {
            icon.className = 'fas fa-file text-gray-500 text-xl mr-3';
        }
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Initialize date min values
    const today = new Date().toISOString().split('T')[0];
    dateFrom.min = today;
    dateTo.min = today;
    </script>
</body>
</html>