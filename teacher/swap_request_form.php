<?php
if (!isset($pdo)) {
    require_once '../includes/Config.php';
}
if (!function_exists('checkAuth')) {
    require_once '../includes/Auth.php';
}
checkAuth();

if (($_SESSION['role'] ?? '') !== 'SEMI_ADMIN') {
    header('Location: ../dashboard.php');
    exit();
}

$user_id = $user_id ?? (int) ($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    header('Location: ../login.php?error=Unauthorized');
    exit();
}

// Get teacher's upcoming slots (next 7 days)
$today = date('Y-m-d');
$seven_days_later = date('Y-m-d', strtotime('+7 days'));

$stmt = $pdo->prepare("
    SELECT ts.slot_id, ts.day_of_week, ts.start_time, ts.end_time,
           s.code, s.name, s.subject_id,
           ts.target_group,
           DATE_ADD(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) - 1) * -1 DAY) as week_start
    FROM timetable_slots ts
    INNER JOIN subjects s ON ts.subject_id = s.subject_id
    WHERE ts.default_teacher_id = ?
    AND ts.valid_from <= ? AND ts.valid_until >= ?
    ORDER BY ts.day_of_week, ts.start_time
");
$stmt->execute([$user_id, $seven_days_later, $today]);
$upcoming_slots = $stmt->fetchAll();

// Get other teachers (excluding current user)
$stmt = $pdo->prepare("
    SELECT u.user_id, u.full_name, u.email, 
           GROUP_CONCAT(DISTINCT s.code SEPARATOR ', ') as subjects
    FROM users u
    LEFT JOIN timetable_slots ts ON u.user_id = ts.default_teacher_id
    LEFT JOIN subjects s ON ts.subject_id = s.subject_id
    WHERE u.role = 'SEMI_ADMIN' 
    AND u.user_id != ?
    AND u.is_active = 1
    GROUP BY u.user_id
    ORDER BY u.full_name
");
$stmt->execute([$user_id]);
$other_teachers = $stmt->fetchAll();
?>

<h2 class="text-2xl font-bold text-gray-800 mb-6">Request Class Swap</h2>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Left Column: Select Slot -->
    <div>
        <h3 class="text-lg font-semibold text-gray-700 mb-4">1. Select Your Slot to Swap</h3>
        
        <?php if (empty($upcoming_slots)): ?>
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-xl p-6 text-center">
                <div class="h-16 w-16 rounded-full bg-yellow-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-calendar-times text-yellow-500 text-2xl"></i>
                </div>
                <h4 class="font-bold text-gray-800 mb-2">No Upcoming Classes</h4>
                <p class="text-gray-600 text-sm">You don't have any classes scheduled for the next 7 days.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                <?php foreach ($upcoming_slots as $slot): 
                    $target = json_decode($slot['target_group'], true);
                ?>
                <div class="swap-card bg-white border border-gray-200 rounded-xl p-5 hover:border-blue-400 cursor-pointer transition-all duration-200"
                     onclick="selectSlot(<?= $slot['slot_id'] ?>, '<?= htmlspecialchars($slot['code']) ?>', '<?= $slot['day_of_week'] ?>', '<?= date('h:i A', strtotime($slot['start_time'])) ?>')">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center mb-2">
                                <div class="h-8 w-8 rounded-lg bg-blue-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-book text-blue-600 text-sm"></i>
                                </div>
                                <h4 class="font-bold text-gray-800"><?= htmlspecialchars($slot['code']) ?></h4>
                            </div>
                            <p class="text-sm text-gray-600 mb-1"><?= htmlspecialchars($slot['name']) ?></p>
                            <div class="flex items-center text-xs text-gray-500 space-x-4">
                                <span class="flex items-center">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    <?= $slot['day_of_week'] ?>
                                </span>
                                <span class="flex items-center">
                                    <i class="far fa-clock mr-1"></i>
                                    <?= date('h:i A', strtotime($slot['start_time'])) ?>
                                </span>
                                <span class="flex items-center">
                                    <i class="fas fa-users mr-1"></i>
                                    Sec: <?= $target['section'] ?? 'N/A' ?>
                                </span>
                            </div>
                        </div>
                        <span class="bg-gradient-to-r from-blue-500 to-blue-600 text-white text-xs font-semibold px-3 py-1 rounded-full">
                            Slot #<?= $slot['slot_id'] ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Column: Select Teacher & Details -->
    <div>
        <h3 class="text-lg font-semibold text-gray-700 mb-4">2. Select Substitute Teacher</h3>
        
        <?php if (empty($other_teachers)): ?>
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-xl p-6 text-center">
                <div class="h-16 w-16 rounded-full bg-yellow-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-slash text-yellow-500 text-2xl"></i>
                </div>
                <h4 class="font-bold text-gray-800 mb-2">No Teachers Available</h4>
                <p class="text-gray-600 text-sm">No other active teachers found in the system.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                <?php foreach ($other_teachers as $teacher): ?>
                <div class="swap-card bg-white border border-gray-200 rounded-xl p-5 hover:border-green-400 cursor-pointer transition-all duration-200"
                     onclick="selectTeacher(<?= $teacher['user_id'] ?>, '<?= htmlspecialchars(addslashes($teacher['full_name'])) ?>')">
                    <div class="flex items-center">
                        <div class="h-12 w-12 rounded-full bg-gradient-to-r from-green-100 to-blue-100 flex items-center justify-center mr-4">
                            <i class="fas fa-chalkboard-teacher text-green-600"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-800"><?= htmlspecialchars($teacher['full_name']) ?></h4>
                            <p class="text-sm text-gray-600 truncate"><?= htmlspecialchars($teacher['email']) ?></p>
                            <?php if ($teacher['subjects']): ?>
                            <div class="mt-2">
                                <span class="inline-block bg-gray-100 text-gray-700 text-xs font-medium px-2.5 py-0.5 rounded">
                                    <i class="fas fa-book mr-1"></i>
                                    <?= htmlspecialchars($teacher['subjects']) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Swap Details Form -->
        <div class="mt-8">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">3. Swap Details</h3>
            
            <form id="swapForm" action="swap_requests.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create_swap">
                
                <!-- Hidden inputs for selected data -->
                <input type="hidden" name="slot_id" id="selected_slot_id">
                <input type="hidden" name="receiver_id" id="selected_receiver_id">
                
                <div class="space-y-6">
                    <!-- Display Selected Info -->
                    <div class="bg-gradient-to-r from-blue-50 to-gray-50 border border-blue-100 rounded-xl p-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-1">Selected Class:</p>
                                <p id="selected_class_display" class="font-bold text-gray-800 text-lg">None selected</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 mb-1">Substitute Teacher:</p>
                                <p id="selected_teacher_display" class="font-bold text-gray-800 text-lg">None selected</p>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-blue-100">
                            <p class="text-xs text-gray-500">
                                <i class="fas fa-info-circle mr-1"></i>
                                Select a class slot and teacher to proceed
                            </p>
                        </div>
                    </div>

                    <!-- Date Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                            <div class="h-8 w-8 rounded-lg bg-blue-100 flex items-center justify-center mr-2">
                                <i class="fas fa-calendar-day text-blue-600 text-sm"></i>
                            </div>
                            Date for Swap
                        </label>
                        <div class="relative">
                            <input type="date" name="swap_date" id="swap_date" 
                                   class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                   min="<?= $today ?>" max="<?= date('Y-m-d', strtotime('+30 days')) ?>"
                                   required>
                            <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                                <i class="far fa-calendar"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2 flex items-center">
                            <i class="fas fa-lightbulb mr-1"></i>
                            Select the specific date when you need the substitute
                        </p>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                            <div class="h-8 w-8 rounded-lg bg-blue-100 flex items-center justify-center mr-2">
                                <i class="fas fa-sticky-note text-blue-600 text-sm"></i>
                            </div>
                            Notes for Substitute Teacher
                        </label>
                        <div class="relative">
                            <textarea name="request_notes" rows="3" 
                                      class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Any special instructions, topics to cover, materials needed..."></textarea>
                            <div class="absolute left-3 top-3 text-gray-400">
                                <i class="fas fa-pen"></i>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-lightbulb mr-1"></i>
                            Clear instructions help the substitute teacher prepare better
                        </p>
                    </div>

                    <!-- File Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center">
                            <div class="h-8 w-8 rounded-lg bg-blue-100 flex items-center justify-center mr-2">
                                <i class="fas fa-file-upload text-blue-600 text-sm"></i>
                            </div>
                            Upload Lecture Materials (Optional)
                        </label>
                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-blue-400 transition-colors"
                             id="uploadArea">
                            <input type="file" name="lecture_file" id="lecture_file" 
                                   class="hidden" accept=".pdf,.ppt,.pptx,.doc,.docx,.jpg,.png,.zip">
                            <label for="lecture_file" class="cursor-pointer">
                                <div class="h-16 w-16 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-cloud-upload-alt text-2xl text-blue-400"></i>
                                </div>
                                <p class="text-gray-700 font-medium mb-1">Click to upload or drag & drop</p>
                                <p class="text-xs text-gray-500">PDF, PowerPoint, Word, Images, ZIP (Max 10MB)</p>
                            </label>
                        </div>
                        <div id="file_name_display" class="mt-3 hidden">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-3 flex items-center">
                                <i class="fas fa-file text-green-500 mr-3 text-xl"></i>
                                <div class="flex-1">
                                    <p id="fileName" class="font-medium text-gray-800"></p>
                                    <p id="fileSize" class="text-xs text-gray-500"></p>
                                </div>
                                <button type="button" onclick="clearFile()" class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold py-4 px-4 rounded-xl transition-all duration-200 flex items-center justify-center shadow-lg hover:shadow-xl">
                            <i class="fas fa-paper-plane mr-3 text-lg"></i>
                            Send Swap Request
                        </button>
                        <p class="text-xs text-gray-500 text-center mt-3">
                            <i class="fas fa-shield-alt mr-1"></i>
                            Your request will be securely sent to the selected teacher
                        </p>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let selectedSlotId = null;
let selectedTeacherId = null;

function selectSlot(slotId, className, day, time) {
    selectedSlotId = slotId;
    document.getElementById('selected_slot_id').value = slotId;
    document.getElementById('selected_class_display').textContent = `${className} (${day} at ${time})`;
    
    // Highlight selected slot
    document.querySelectorAll('.swap-card').forEach(card => {
        card.classList.remove('border-blue-500', 'bg-blue-50', 'ring-2', 'ring-blue-200');
    });
    event.currentTarget.classList.add('border-blue-500', 'bg-blue-50', 'ring-2', 'ring-blue-200');
}

function selectTeacher(teacherId, teacherName) {
    selectedTeacherId = teacherId;
    document.getElementById('selected_receiver_id').value = teacherId;
    document.getElementById('selected_teacher_display').textContent = teacherName;
    
    // Highlight selected teacher
    document.querySelectorAll('.swap-card').forEach(card => {
        card.classList.remove('border-green-500', 'bg-green-50', 'ring-2', 'ring-green-200');
    });
    event.currentTarget.classList.add('border-green-500', 'bg-green-50', 'ring-2', 'ring-green-200');
}

// File upload handling
const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('lecture_file');
const fileNameDisplay = document.getElementById('file_name_display');
const fileName = document.getElementById('fileName');
const fileSize = document.getElementById('fileSize');

// Drag and drop
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('border-blue-500', 'bg-blue-50');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('border-blue-500', 'bg-blue-50');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('border-blue-500', 'bg-blue-50');
    
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        handleFileSelect(fileInput.files[0]);
    }
});

fileInput.addEventListener('change', (e) => {
    if (e.target.files.length) {
        handleFileSelect(e.target.files[0]);
    }
});

function handleFileSelect(file) {
    if (file.size > 10 * 1024 * 1024) { // 10MB limit
        alert('File size exceeds 10MB limit. Please select a smaller file.');
        fileInput.value = '';
        return;
    }
    
    fileName.textContent = file.name;
    fileSize.textContent = `Size: ${formatFileSize(file.size)}`;
    fileNameDisplay.classList.remove('hidden');
}

function clearFile() {
    fileInput.value = '';
    fileNameDisplay.classList.add('hidden');
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Form validation
document.getElementById('swapForm').addEventListener('submit', function(e) {
    if (!selectedSlotId || !selectedTeacherId) {
        e.preventDefault();
        showAlert('Please select both a class slot and a substitute teacher.', 'error');
        return false;
    }
    
    const swapDate = document.getElementById('swap_date').value;
    if (!swapDate) {
        e.preventDefault();
        showAlert('Please select a date for the swap.', 'error');
        return false;
    }
    
    // File size validation
    if (fileInput.files.length > 0 && fileInput.files[0].size > 10 * 1024 * 1024) {
        e.preventDefault();
        showAlert('File size exceeds 10MB limit. Please select a smaller file.', 'error');
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending Request...';
    submitBtn.disabled = true;
});

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-xl shadow-lg ${
        type === 'error' ? 'bg-red-50 border border-red-200 text-red-700' : 
        'bg-green-50 border border-green-200 text-green-700'
    }`;
    alertDiv.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'} mr-3"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentElement) {
            alertDiv.remove();
        }
    }, 5000);
}

// Initialize date picker with tomorrow as default
document.addEventListener('DOMContentLoaded', function() {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tomorrowStr = tomorrow.toISOString().split('T')[0];
    document.getElementById('swap_date').value = tomorrowStr;
    document.getElementById('swap_date').min = new Date().toISOString().split('T')[0];
});
</script>
