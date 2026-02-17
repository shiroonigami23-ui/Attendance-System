<?php
require_once 'includes/Config.php';
require_once 'includes/Auth.php';

checkAuth();
$user = getCurrentUser();
$role = $_SESSION['role'];

// Determine back URL based on role
$back_url = '';
switch($role) {
    case 'MASTER':
        $back_url = 'admin/dashboard.php';
        break;
    case 'SEMI_ADMIN':
        $back_url = 'teacher/dashboard.php';
        break;
    case 'STUDENT':
        $back_url = 'student/dashboard.php';
        break;
    default:
        $back_url = 'dashboard.php';
}

// Handle profile photo update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_photo'])) {
    $photo = $_FILES['profile_photo'];
    
    if ($photo['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($photo['type'], $allowed_types)) {
            $photo_error = "Invalid image type. Only JPG, PNG, GIF, WebP allowed.";
        } elseif ($photo['size'] > $max_size) {
            $photo_error = "Image too large. Max 5MB.";
        } else {
            // Delete old photo if exists
            if ($user['profile_photo'] && file_exists($user['profile_photo'])) {
                unlink($user['profile_photo']);
            }
            
            // Upload new
            $ext = pathinfo($photo['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $upload_path = '../assets/uploads/' . $filename;
            
            if (move_uploaded_file($photo['tmp_name'], $upload_path)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE user_id = ?");
                $stmt->execute([$upload_path, $user['user_id']]);
                $success = "Profile photo updated successfully.";
                // Refresh user data
                $user = getCurrentUser();
            } else {
                $photo_error = "Upload failed.";
            }
        }
    }
}

// Handle name update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['full_name'])) {
    $new_name = trim($_POST['full_name']);
    if (strlen($new_name) >= 2 && strlen($new_name) <= 100) {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
        $stmt->execute([$new_name, $user['user_id']]);
        $success = "Name updated successfully.";
        $user['full_name'] = $new_name;
    } else {
        $name_error = "Name must be 2-100 characters.";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['current_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    if (!password_verify($current, $user['password_hash'])) {
        $pass_error = "Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $pass_error = "New passwords do not match.";
    } elseif (strlen($new) < 6) {
        $pass_error = "Password must be at least 6 characters.";
    } else {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $stmt->execute([$new_hash, $user['user_id']]);
        $success = "Password changed successfully.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Campus Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>
    
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header with Back Button -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <a href="<?= $back_url ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-2">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
                <h1 class="text-3xl font-bold text-gray-900">My Profile</h1>
                <p class="text-gray-600">Manage your account settings and personal information</p>
            </div>
            <div class="text-right">
                <span class="text-sm text-gray-500">Logged in as</span>
                <p class="font-semibold"><?= htmlspecialchars($user['full_name'] ?? $user['email']) ?></p>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($success)): ?>
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
            <!-- Left Column: Profile Photo & Basic Info -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <!-- Profile Photo -->
                    <div class="text-center mb-6">
                        <div class="relative inline-block">
                            <img src="<?= htmlspecialchars($user['profile_photo'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name'] ?? $user['email']) . '&background=random') ?>" 
                                 alt="Profile Photo"
                                 class="h-48 w-48 rounded-full object-cover border-4 border-white shadow-lg mx-auto">
                            <form method="POST" enctype="multipart/form-data" class="mt-4">
                                <label class="block">
                                    <span class="sr-only">Choose profile photo</span>
                                    <input type="file" name="profile_photo" accept="image/*" 
                                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                </label>
                                <button type="submit" class="mt-2 w-full bg-blue-600 text-white py-2 rounded-lg font-medium hover:bg-blue-700 transition">
                                    <i class="fas fa-upload mr-2"></i> Update Photo
                                </button>
                            </form>
                            <?php if (isset($photo_error)): ?>
                            <p class="text-red-600 text-sm mt-2"><?= htmlspecialchars($photo_error) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- User Info -->
                    <div class="space-y-4">
                        <div>
                            <h3 class="font-semibold text-gray-500 text-sm uppercase tracking-wide">Role</h3>
                            <div class="flex items-center mt-1">
                                <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full 
                                    <?= $role == 'MASTER' ? 'bg-purple-100 text-purple-800' : 
                                       ($role == 'SEMI_ADMIN' ? 'bg-yellow-100 text-yellow-800' : 
                                       'bg-blue-100 text-blue-800') ?>">
                                    <?= $role ?>
                                </span>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-500 text-sm uppercase tracking-wide">Device Fingerprint</h3>
                            <p class="text-sm font-mono bg-gray-100 p-2 rounded mt-1 break-all">
                                <?= htmlspecialchars(substr($_SESSION['device_fp'], 0, 24)) ?>...
                            </p>
                            <p class="text-xs text-gray-500 mt-1">This device is locked to your account.</p>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-500 text-sm uppercase tracking-wide">Account Created</h3>
                            <p class="text-sm"><?= date('F j, Y', strtotime($user['created_at'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Editable Info -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Personal Information Card -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Personal Information</h2>
                    <form method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 mb-2">Full Name</label>
                                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                <?php if (isset($name_error)): ?>
                                <p class="text-red-600 text-sm mt-1"><?= htmlspecialchars($name_error) ?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Email Address</label>
                                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled
                                    class="w-full p-3 border border-gray-300 bg-gray-100 rounded-lg">
                                <p class="text-xs text-gray-500 mt-1">Email cannot be changed.</p>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" disabled
                                    class="w-full p-3 border border-gray-300 bg-gray-100 rounded-lg">
                                <p class="text-xs text-gray-500 mt-1">Contact admin to change phone.</p>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Status</label>
                                <div class="p-3 border border-gray-300 bg-gray-100 rounded-lg">
                                    <span class="font-medium <?= $user['approved'] ? 'text-green-600' : 'text-yellow-600' ?>">
                                        <?= $user['approved'] ? 'Active' : 'Pending Approval' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="mt-6 bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition">
                            <i class="fas fa-save mr-2"></i> Save Changes
                        </button>
                    </form>
                </div>

                <!-- Teacher Specific Links -->
                <?php if ($role == 'SEMI_ADMIN'): ?>
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Teacher Tools</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="teacher/timetable.php" class="p-4 border border-gray-200 rounded-xl hover:bg-blue-50 hover:border-blue-300 transition flex items-center">
                            <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center mr-4">
                                <i class="fas fa-calendar-alt text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">My Timetable</p>
                                <p class="text-sm text-gray-500">View teaching schedule</p>
                            </div>
                        </a>
                        <a href="teacher/leave_apply.php" class="p-4 border border-gray-200 rounded-xl hover:bg-orange-50 hover:border-orange-300 transition flex items-center">
                            <div class="h-12 w-12 rounded-lg bg-orange-100 flex items-center justify-center mr-4">
                                <i class="fas fa-calendar-times text-orange-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">Apply for Leave</p>
                                <p class="text-sm text-gray-500">Request time off</p>
                            </div>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Student Specific Links -->
                <?php if ($role == 'STUDENT'): ?>
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Student Tools</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="student/timetable.php" class="p-4 border border-gray-200 rounded-xl hover:bg-blue-50 hover:border-blue-300 transition flex items-center">
                            <div class="h-12 w-12 rounded-lg bg-blue-100 flex items-center justify-center mr-4">
                                <i class="fas fa-calendar-alt text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">My Timetable</p>
                                <p class="text-sm text-gray-500">View class schedule</p>
                            </div>
                        </a>
                        <a href="student/scanner.php" class="p-4 border border-gray-200 rounded-xl hover:bg-green-50 hover:border-green-300 transition flex items-center">
                            <div class="h-12 w-12 rounded-lg bg-green-100 flex items-center justify-center mr-4">
                                <i class="fas fa-qrcode text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">QR Scanner</p>
                                <p class="text-sm text-gray-500">Mark attendance</p>
                            </div>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Academic Information (Students) -->
                <?php if ($role == 'STUDENT' && isset($user['roll_no'])): ?>
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Academic Information</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div>
                            <h3 class="font-semibold text-gray-500 text-sm uppercase tracking-wide">Roll Number</h3>
                            <p class="text-lg font-bold"><?= htmlspecialchars($user['roll_no']) ?></p>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-500 text-sm uppercase tracking-wide">Branch</h3>
                            <p class="text-lg font-bold"><?= htmlspecialchars($user['branch_code']) ?></p>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-500 text-sm uppercase tracking-wide">Semester</h3>
                            <p class="text-lg font-bold"><?= $user['current_semester'] ?></p>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-500 text-sm uppercase tracking-wide">Section</h3>
                            <p class="text-lg font-bold"><?= htmlspecialchars($user['section']) ?></p>
                        </div>
                        <?php if ($user['lab_batch']): ?>
                        <div class="col-span-2">
                            <h3 class="font-semibold text-gray-500 text-sm uppercase tracking-wide">Lab Batch</h3>
                            <p class="text-lg font-bold"><?= htmlspecialchars($user['lab_batch']) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Change Password Card -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Change Password</h2>
                    <form method="POST">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-gray-700 mb-2">Current Password</label>
                                <input type="password" name="current_password" required
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">New Password</label>
                                <input type="password" name="new_password" required
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2">Confirm New Password</label>
                                <input type="password" name="confirm_password" required
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            </div>
                        </div>
                        <?php if (isset($pass_error)): ?>
                        <p class="text-red-600 text-sm mt-2"><?= htmlspecialchars($pass_error) ?></p>
                        <?php endif; ?>
                        <button type="submit" class="mt-6 bg-green-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-700 transition">
                            <i class="fas fa-key mr-2"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-400">
                Advanced Campus Attendance System v3.0 | Hardware-Locked & Hierarchical
            </p>
        </div>
    </footer>
</body>
</html>