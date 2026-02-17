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

// Handle form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    
    // Validate dates
    if (empty($date_from) || empty($date_to)) {
        $errors[] = "Please select both start and end dates.";
    } else {
        $from = new DateTime($date_from);
        $to = new DateTime($date_to);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        // Check if dates are in future
        if ($from < $today) {
            $errors[] = "Cannot apply leave for past dates. Please select future dates only.";
        }
        
        // Check date order
        if ($from > $to) {
            $errors[] = "End date must be after start date.";
        }
        
        // Calculate days
        $interval = $from->diff($to);
        $days = $interval->days + 1;
        
        if ($days > 30) {
            $errors[] = "Maximum leave period is 30 days. For longer leaves, contact admin offline.";
        }
    }
    
    // Validate reason
    if (empty($reason) || strlen($reason) < 10) {
        $errors[] = "Please provide a detailed reason (minimum 10 characters).";
    }
    
    // Handle document upload
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
            $filename = 'leave_' . $user['user_id'] . '_' . time() . '.' . $ext;
            $upload_path = '../assets/uploads/leaves/' . $filename;
            
            // Create directory if not exists
            if (!file_exists('../assets/uploads/leaves/')) {
                mkdir('../assets/uploads/leaves/', 0777, true);
            }
            
            if (move_uploaded_file($doc['tmp_name'], $upload_path)) {
                $document_path = $upload_path;
            } else {
                $errors[] = "Failed to upload document.";
            }
        }
    } elseif ($_FILES['document']['error'] == UPLOAD_ERR_NO_FILE && $days > 3) {
        $errors[] = "Document is required for leaves longer than 3 days.";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO leave_requests (student_id, date_from, date_to, reason, document_path, approval_status, created_at) VALUES (?, ?, ?, ?, ?, 'PENDING', NOW())");
            $stmt->execute([$user['user_id'], $date_from, $date_to, $reason, $document_path]);
            
            $success = "Leave request submitted successfully!";
            
            // Clear form
            $_POST = [];
            
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Get user's pending leave requests
$stmt = $pdo->prepare("
    SELECT lr.*
    FROM leave_requests lr
    WHERE lr.student_id = ?
    ORDER BY lr.created_at DESC
    LIMIT 10
");
$stmt->execute([$user['user_id']]);
$leave_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Leave - Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen">
    <!-- Navbar -->
    <nav class="glass-card shadow-lg sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-blue-600 flex items-center justify-center mr-3">
                            <i class="fas fa-file-medical text-white text-xl"></i>
                        </div>
                        <div>
                            <span class="text-xl font-bold text-gray-800">Leave Application</span>
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
            <h1 class="text-3xl font-bold text-gray-900">Apply for Leave</h1>
            <p class="text-gray-600">Submit leave request with supporting documents</p>
        </div>

        <!-- Messages -->
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
                                <p class="text-xs text-gray-500 mt-1">Cannot select past dates</p>
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
                                <span id="days_warning" class="ml-4 text-yellow-600 hidden">
                                    <i class="fas fa-exclamation-triangle"></i> Document required
                                </span>
                            </p>
                        </div>

                        <!-- Reason -->
                        <div class="mb-6">
                            <label class="block text-gray-700 mb-2">
                                <i class="fas fa-comment-medical mr-2 text-blue-500"></i> Reason for Leave
                            </label>
                            <textarea name="reason" 
                                      rows="4"
                                      class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                      placeholder="Provide detailed reason for leave (minimum 10 characters)"
                                      required><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
                        </div>

                        <!-- Document Upload -->
                        <div class="mb-8">
                            <label class="block text-gray-700 mb-2">
                                <i class="fas fa-file-upload mr-2 text-blue-500"></i> Supporting Document
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
                                    <p class="text-xs text-red-500 mt-2">
                                        <i class="fas fa-exclamation-circle"></i> Required for leaves longer than 3 days
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

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-3 rounded-lg font-medium hover:from-blue-700 hover:to-blue-800 transition shadow-lg">
                                <i class="fas fa-paper-plane mr-2"></i> Submit Leave Request
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Leave Policies -->
                <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-r-lg p-6">
                    <h3 class="font-bold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-info-circle text-yellow-600 mr-2"></i> Leave Application Rules
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span><strong>Short Leave (1-3 days):</strong> Requires document upload. Approved by Teacher.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-green-500 mt-1 mr-2"></i>
                            <span><strong>Long Leave (>3 days):</strong> Requires document upload. Approved by Master Admin.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-times text-red-500 mt-1 mr-2"></i>
                            <span><strong>No Past Dates:</strong> Cannot apply for leave on past dates.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-clock text-blue-500 mt-1 mr-2"></i>
                            <span><strong>Processing Time:</strong> Typically 24-48 hours. Check status below.</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Right Column: Recent Requests -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Recent Requests</h2>
                    
                    <?php if (empty($leave_requests)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900">No leave requests</h3>
                        <p class="text-gray-500">Submit your first request above</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($leave_requests as $request): 
                            $status_color = 'gray';
                            $status_icon = 'far fa-clock';
                            
                            switch($request['approval_status']) {
                                case 'APPROVED_FULL':
                                    $status_color = 'green';
                                    $status_icon = 'fas fa-check-circle';
                                    break;
                                case 'APPROVED_PARTIAL':
                                    $status_color = 'blue';
                                    $status_icon = 'fas fa-check-double';
                                    break;
                                case 'REJECTED':
                                    $status_color = 'red';
                                    $status_icon = 'fas fa-times-circle';
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
    <?= $request['days_requested'] ?? '?' ?> days
    <?php if ($request['subject_code']): ?>
    | <?= htmlspecialchars($request['subject_code']) ?>
    <?php endif; ?>
</p>
                                </div>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-<?= $status_color ?>-100 text-<?= $status_color ?>-800">
                                    <i class="<?= $status_icon ?> mr-1"></i>
                                    <?= $request['approval_status'] ?>
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 truncate">
                                <?= htmlspecialchars(substr($request['reason'], 0, 60)) ?>...
                            </p>
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="far fa-clock mr-1"></i>
                                <?= date('M d, Y', strtotime($request['created_at'])) ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($leave_requests) >= 10): ?>
                    <div class="mt-6 text-center">
                        <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            <i class="fas fa-history mr-2"></i> View All Requests
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php endif; ?>
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
    const daysWarning = document.getElementById('days_warning');
    
    function calculateDays() {
        if (dateFrom.value && dateTo.value) {
            const from = new Date(dateFrom.value);
            const to = new Date(dateTo.value);
            const diffTime = Math.abs(to - from);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            daysText.textContent = `${diffDays} day${diffDays !== 1 ? 's' : ''}`;
            daysDiv.classList.remove('hidden');
            
            if (diffDays > 3) {
                daysWarning.classList.remove('hidden');
            } else {
                daysWarning.classList.add('hidden');
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