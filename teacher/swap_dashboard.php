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

// Get count of pending swap requests for badge
$stmt = $pdo->prepare("
    SELECT COUNT(*) as pending_count 
    FROM swap_requests 
    WHERE receiver_id = ? 
    AND status = 'PENDING'
");
$stmt->execute([$user_id]);
$result = $stmt->fetch();
$pending_count = $result['pending_count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swap Classes - Teacher Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tab-active {
            border-bottom: 3px solid #3b82f6;
            color: #3b82f6;
            font-weight: 600;
        }
        .swap-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.2s;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen">
    <!-- Navbar -->
    <nav class="glass-effect shadow-lg sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-blue-600 flex items-center justify-center mr-3">
                            <i class="fas fa-exchange-alt text-white text-xl"></i>
                        </div>
                        <div>
                            <span class="text-xl font-bold text-gray-800">Collaboration Hub</span>
                            <p class="text-xs text-gray-500">Class Swap System</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="dashboard.php" class="flex items-center text-gray-700 hover:text-blue-600 transition">
                        <i class="fas fa-arrow-left mr-2"></i>
                        <span>Back to Dashboard</span>
                    </a>
                    <div class="flex items-center space-x-3">
                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-yellow-400 to-orange-400 flex items-center justify-center">
                            <i class="fas fa-chalkboard-teacher text-white"></i>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($user['full_name'] ?? 'Teacher') ?></p>
                            <span class="text-xs text-gray-500">Teacher</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                        <i class="fas fa-plus-circle text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">New Request</p>
                        <p class="text-2xl font-bold text-gray-800">Create</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-red-500">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center mr-4">
                        <i class="fas fa-inbox text-red-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Pending Requests</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $pending_count ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center mr-4">
                        <i class="fas fa-paper-plane text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">My Requests</p>
                        <p class="text-2xl font-bold text-gray-800">Sent</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center">
                    <div class="h-12 w-12 rounded-full bg-purple-100 flex items-center justify-center mr-4">
                        <i class="fas fa-history text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Swap History</p>
                        <p class="text-2xl font-bold text-gray-800">Archive</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-8">
            <div class="border-b border-gray-200">
                <nav class="flex">
                    <a href="?tab=request" 
                       class="<?= (!isset($_GET['tab']) || $_GET['tab'] == 'request') ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> py-4 px-6 text-sm font-medium flex items-center">
                        <i class="fas fa-plus-circle mr-2 text-lg"></i>
                        Request Swap
                    </a>
                    <a href="?tab=inbox" 
                       class="<?= ($_GET['tab'] ?? '') == 'inbox' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> py-4 px-6 text-sm font-medium flex items-center relative">
                        <i class="fas fa-inbox mr-2 text-lg"></i>
                        Swap Inbox
                        <?php if ($pending_count > 0): ?>
                        <span class="ml-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 inline-flex items-center justify-center animate-pulse">
                            <?= $pending_count ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <a href="?tab=outgoing" 
                       class="<?= ($_GET['tab'] ?? '') == 'outgoing' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> py-4 px-6 text-sm font-medium flex items-center">
                        <i class="fas fa-paper-plane mr-2 text-lg"></i>
                        My Requests
                    </a>
                    <a href="?tab=history" 
                       class="<?= ($_GET['tab'] ?? '') == 'history' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> py-4 px-6 text-sm font-medium flex items-center">
                        <i class="fas fa-history mr-2 text-lg"></i>
                        Swap History
                    </a>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <?php
                // Display success/error messages from swap_requests.php
                if (isset($_SESSION['swap_success'])) {
                    echo '<div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg" id="successMessage">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle mr-2"></i>
                                <span>' . htmlspecialchars($_SESSION['swap_success']) . '</span>
                                <button type="button" class="ml-auto text-green-700 hover:text-green-900" onclick="document.getElementById(\'successMessage\').remove()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                          </div>';
                    unset($_SESSION['swap_success']);
                }

                if (isset($_SESSION['swap_error'])) {
                    echo '<div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg" id="errorMessage">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                <span>' . htmlspecialchars($_SESSION['swap_error']) . '</span>
                                <button type="button" class="ml-auto text-red-700 hover:text-red-900" onclick="document.getElementById(\'errorMessage\').remove()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                          </div>';
                    unset($_SESSION['swap_error']);
                }

                $tab = $_GET['tab'] ?? 'request';
                
                // Instead of including separate files, we'll include the logic from swap_requests.php
                // But we need to pass the tab parameter to it
                switch ($tab) {
                    case 'request':
                        // Include the request form directly
                        include 'swap_request_form.php';
                        break;
                    case 'inbox':
                    case 'outgoing':
                    case 'history':
                        // Pass the display parameter to swap_requests.php
                        $_GET['display'] = $tab;
                        include 'swap_requests.php';
                        break;
                    default:
                        include 'swap_request_form.php';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Quick Stats Footer -->
    <footer class="bg-gradient-to-r from-gray-800 to-gray-900 text-white py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="text-3xl font-bold mb-2"><?= $pending_count ?></div>
                    <p class="text-gray-400">Pending Requests</p>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold mb-2">24/7</div>
                    <p class="text-gray-400">Swap Availability</p>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold mb-2">100%</div>
                    <p class="text-gray-400">Secure & Verified</p>
                </div>
            </div>
            <div class="text-center mt-8 pt-8 border-t border-gray-700">
                <p class="text-gray-400">
                    <i class="fas fa-exchange-alt mr-2"></i>
                    Collaboration Hub | Advanced Campus Attendance System v3.0
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const successMsg = document.getElementById('successMessage');
            const errorMsg = document.getElementById('errorMessage');
            if (successMsg) successMsg.style.display = 'none';
            if (errorMsg) errorMsg.style.display = 'none';
        }, 5000);

        // Add animation to cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.swap-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.transform = 'translateY(-4px)';
                });
                card.addEventListener('mouseleave', () => {
                    card.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>