<?php
require_once 'includes/Config.php';

// If already logged in, redirect to appropriate dashboard based on role
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'STUDENT':
            header('Location: student/dashboard.php');
            exit();
        case 'SEMI_ADMIN':
            header('Location: teacher/dashboard.php');
            exit();
        case 'MASTER':
            header('Location: admin/dashboard.php');
            exit();
        default:
            header('Location: dashboard.php');
            exit();
    }
}

$error = $_GET['error'] ?? '';
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Campus Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .input-group {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="login-card p-8">
            <!-- Logo & Title -->
            <div class="text-center mb-8">
                <div class="h-16 w-16 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-fingerprint text-white text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Campus Attendance System</h1>
                <p class="text-gray-600 mt-1">Secure Login</p>
            </div>

            <!-- Error/Success Messages -->
            <?php if ($error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($msg): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span><?= htmlspecialchars($msg) ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="login_handler.php" id="loginForm">
                <!-- Email -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" name="email" placeholder="you@campus.edu" required
                               class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" name="password" placeholder="••••••••" required
                               class="w-full pl-12 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                        <button type="button" onclick="togglePassword()" 
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                    <div class="flex justify-between mt-2">
                        <p class="text-xs text-gray-500">
                            Default: student123 / teacher123 / admin123
                        </p>
                        <a href="#" class="text-xs text-blue-600 hover:text-blue-800">Forgot Password?</a>
                    </div>
                </div>

                <!-- Hidden Device Fingerprint -->
                <input type="hidden" name="device_fingerprint" id="deviceFingerprint">

                <!-- Security Note -->
                <div class="mb-6 p-3 bg-blue-50 rounded-lg border border-blue-100">
                    <div class="flex items-center text-sm text-gray-700">
                        <i class="fas fa-shield-alt text-blue-500 mr-2"></i>
                        <span>This device will be locked to your account for security</span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold py-3.5 rounded-lg hover:from-blue-700 hover:to-blue-800 transition shadow-md mb-6">
                    <i class="fas fa-sign-in-alt mr-2"></i> Sign In
                </button>
            </form>

            <!-- Register Link -->
            <div class="text-center border-t pt-6">
                <p class="text-gray-600">
                    Need an account? 
                    <a href="register.php" class="text-blue-600 font-medium hover:underline">
                        <i class="fas fa-user-plus mr-1"></i> Register here
                    </a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-white/80 text-sm">
                <i class="fas fa-copyright mr-1"></i> Advanced Campus Attendance System v3.0
            </p>
        </div>
    </div>

    <script>
        // Generate device fingerprint
        document.addEventListener('DOMContentLoaded', function() {
            const components = [
                navigator.userAgent,
                navigator.language,
                navigator.platform,
                screen.width + 'x' + screen.height,
                new Date().getTimezoneOffset()
            ].join('|');
            
            let hash = 0;
            for (let i = 0; i < components.length; i++) {
                const char = components.charCodeAt(i);
                hash = ((hash << 5) - hash) + char;
                hash = hash & hash;
            }
            
            document.getElementById('deviceFingerprint').value = 'fp_' + Math.abs(hash).toString(16).substring(0, 12);
        });
        
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.querySelector('input[name="password"]');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
        
        // Form submission loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const button = this.querySelector('button[type="submit"]');
            const originalHTML = button.innerHTML;
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Authenticating...';
            button.disabled = true;
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.disabled = false;
            }, 3000);
        });
    </script>
</body>
</html>