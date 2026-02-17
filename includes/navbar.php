<?php
if (!isset($user)) {
    $user = getCurrentUser();
}

// Determine correct paths based on current folder
$current_path = $_SERVER['PHP_SELF'];
$is_teacher = strpos($current_path, '/teacher/') !== false;
$is_admin = strpos($current_path, '/admin/') !== false;
$is_student = strpos($current_path, '/student/') !== false;

// Base path for links
$base = $is_teacher || $is_admin || $is_student ? '../' : '';

// Logout path
$logout_path = $base . 'logout.php';

// Dashboard path
$dashboard_path = $base . 'dashboard.php';

// Profile path  
$profile_path = $base . 'profile.php';

// Role-specific navigation
$timetable_path = $is_student ? 'timetable.php' : ($is_teacher ? '../student/timetable.php' : '#');
$teacher_classes_path = $is_teacher ? 'live_session.php' : ($is_admin ? '../teacher/live_session.php' : '#');
$admin_panel_path = $is_admin ? 'dashboard.php' : ($is_teacher ? '../admin/dashboard.php' : '#');

// Get profile photo or default
$profile_photo = $user['profile_photo'] ?? null;
$avatar_url = $profile_photo ? (strpos($profile_photo, 'http') === 0 ? $profile_photo : '../' . htmlspecialchars($profile_photo)) : 
    'https://ui-avatars.com/api/?name=' . urlencode($user['full_name'] ?? $user['email']) . '&background=random';
?>
<nav class="bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0 flex items-center">
                    <a href="<?= $dashboard_path ?>" class="flex items-center">
                        <i class="fas fa-fingerprint text-blue-600 text-2xl mr-3"></i>
                        <span class="text-xl font-bold text-gray-800">Attendance System</span>
                    </a>
                </div>
                <div class="hidden md:ml-6 md:flex md:space-x-8">
                    <a href="<?= $dashboard_path ?>" class="text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 border-blue-500 text-sm font-medium">
                        <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                    </a>
                    <?php if ($_SESSION['role'] == 'STUDENT'): ?>
                    <a href="<?= $is_student ? 'timetable.php' : 'student/timetable.php' ?>" class="text-gray-500 hover:text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 border-transparent hover:border-gray-300 text-sm font-medium">
                        <i class="fas fa-calendar-alt mr-2"></i> Timetable
                    </a>
                    <a href="<?= $is_student ? 'scanner.php' : 'student/scanner.php' ?>" class="text-gray-500 hover:text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 border-transparent hover:border-gray-300 text-sm font-medium">
                        <i class="fas fa-qrcode mr-2"></i> Scanner
                    </a>
                    <?php elseif ($_SESSION['role'] == 'SEMI_ADMIN'): ?>
                    <a href="<?= $is_teacher ? 'live_session.php' : 'teacher/live_session.php' ?>" class="text-gray-500 hover:text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 border-transparent hover:border-gray-300 text-sm font-medium">
                        <i class="fas fa-chalkboard-teacher mr-2"></i> Live Session
                    </a>
                    <?php elseif ($_SESSION['role'] == 'MASTER'): ?>
                    <a href="<?= $is_admin ? 'dashboard.php' : 'admin/dashboard.php' ?>" class="text-gray-500 hover:text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 border-transparent hover:border-gray-300 text-sm font-medium">
                        <i class="fas fa-cogs mr-2"></i> Admin Panel
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <!-- User Profile Dropdown -->
                <div class="relative group" id="user-dropdown">
                    <button class="flex items-center space-x-3 focus:outline-none hover:bg-gray-100 px-3 py-2 rounded-lg transition">
                        <img src="<?= $avatar_url ?>" 
                             alt="Profile" 
                             class="h-8 w-8 rounded-full border-2 border-white shadow-sm">
                        <div class="hidden md:block text-left">
                            <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($user['full_name'] ?? $user['email']) ?></p>
                            <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full 
                                <?= $_SESSION['role'] == 'MASTER' ? 'bg-purple-100 text-purple-800' : 
                                   ($_SESSION['role'] == 'SEMI_ADMIN' ? 'bg-yellow-100 text-yellow-800' : 
                                   'bg-blue-100 text-blue-800') ?>">
                                <?= $_SESSION['role'] ?>
                            </span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-500 text-sm"></i>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 transform origin-top-right">
                        <div class="py-2">
                            <div class="px-4 py-3 border-b">
                                <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['full_name'] ?? $user['email']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
                            </div>
                            <a href="<?= $profile_path ?>" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 transition">
                                <i class="fas fa-user-circle mr-3 text-gray-400"></i>
                                <span>My Profile</span>
                            </a>
                            <?php if ($_SESSION['role'] == 'STUDENT'): ?>
                            <a href="<?= $base . 'student/dashboard.php' ?>" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 transition">
                                <i class="fas fa-tachometer-alt mr-3 text-gray-400"></i>
                                <span>Student Dashboard</span>
                            </a>
                            <?php elseif ($_SESSION['role'] == 'SEMI_ADMIN'): ?>
                            <a href="<?= $base . 'teacher/dashboard.php' ?>" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 transition">
                                <i class="fas fa-chalkboard-teacher mr-3 text-gray-400"></i>
                                <span>Teacher Dashboard</span>
                            </a>
                            <?php elseif ($_SESSION['role'] == 'MASTER'): ?>
                            <a href="<?= $base . 'admin/dashboard.php' ?>" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 transition">
                                <i class="fas fa-cogs mr-3 text-gray-400"></i>
                                <span>Admin Dashboard</span>
                            </a>
                            <?php endif; ?>
                            <div class="border-t my-1"></div>
                            <a href="<?= $logout_path ?>" class="flex items-center px-4 py-3 text-red-600 hover:bg-red-50 transition">
                                <i class="fas fa-sign-out-alt mr-3"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile menu -->
<div class="md:hidden bg-white border-t border-gray-200">
    <div class="px-4 py-3 space-y-1">
        <a href="<?= $dashboard_path ?>" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
            <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
        </a>
        <a href="<?= $profile_path ?>" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
            <i class="fas fa-user mr-3"></i> Profile
        </a>
        <?php if ($_SESSION['role'] == 'STUDENT'): ?>
        <a href="<?= $is_student ? 'timetable.php' : '../student/timetable.php' ?>" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
            <i class="fas fa-calendar-alt mr-3"></i> Timetable
        </a>
        <a href="<?= $is_student ? 'scanner.php' : '../student/scanner.php' ?>" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
            <i class="fas fa-qrcode mr-3"></i> Scanner
        </a>
        <?php elseif ($_SESSION['role'] == 'SEMI_ADMIN'): ?>
        <a href="<?= $is_teacher ? 'live_session.php' : '../teacher/live_session.php' ?>" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
            <i class="fas fa-chalkboard-teacher mr-3"></i> Live Session
        </a>
        <?php elseif ($_SESSION['role'] == 'MASTER'): ?>
        <a href="<?= $is_admin ? 'dashboard.php' : '../admin/dashboard.php' ?>" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
            <i class="fas fa-cogs mr-3"></i> Admin Panel
        </a>
        <?php endif; ?>
        <div class="border-t pt-2 mt-2">
            <a href="<?= $logout_path ?>" class="block px-3 py-2 rounded-md text-base font-medium text-red-600 hover:text-red-800 hover:bg-red-50">
                <i class="fas fa-sign-out-alt mr-3"></i> Logout
            </a>
        </div>
    </div>
</div>

<style>
#user-dropdown:hover .dropdown-menu {
    display: block;
}
</style>