<?php
require_once 'includes/Config.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Campus Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        * {
            font-family: 'Poppins', sans-serif;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .gradient-text {
            background: linear-gradient(90deg, #667eea, #764ba2);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .pulse-ring {
            animation: pulse-ring 2s infinite;
        }

        @keyframes pulse-ring {
            0% {
                transform: scale(0.95);
                opacity: 0.7;
            }

            70% {
                transform: scale(1.1);
                opacity: 0;
            }

            100% {
                transform: scale(0.95);
                opacity: 0;
            }
        }

        .slide-up {
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body class="min-h-screen gradient-bg">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-10 left-10 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-pulse"></div>
        <div class="absolute top-40 right-10 w-72 h-72 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-pulse delay-1000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-pulse delay-500"></div>
    </div>

    <div class="relative min-h-screen flex flex-col">
        <!-- Navigation -->
        <nav class="py-6 px-4 sm:px-8">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center">
                        <i class="fas fa-fingerprint text-white text-xl"></i>
                    </div>
                    <span class="text-white font-bold text-xl">ACAS</span>
                </div>
                <div class="hidden md:flex items-center space-x-6">
                    <a href="feature.php" class="text-white/80 hover:text-white transition">Features</a>
                    <a href="about.php" class="text-white/80 hover:text-white transition">About</a>
                    <a href="contact.php" class="text-white/80 hover:text-white transition">Contact</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="login.php"
                        class="px-5 py-2.5 bg-white/10 text-white rounded-xl hover:bg-white/20 transition backdrop-blur-sm">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                    <a href="register.php"
                        class="px-5 py-2.5 bg-white text-purple-600 rounded-xl hover:bg-gray-100 transition font-medium">
                        <i class="fas fa-user-plus mr-2"></i>Register
                    </a>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <main class="flex-1 flex items-center">
            <div class="max-w-7xl mx-auto px-4 sm:px-8 py-12">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <!-- Left Column - Text -->
                    <div class="slide-up">
                        <div class="inline-flex items-center px-4 py-2 rounded-full bg-white/10 backdrop-blur-sm mb-6">
                            <div class="h-2 w-2 rounded-full bg-green-400 mr-2 animate-pulse"></div>
                            <span class="text-white text-sm">Secure & Enterprise Grade</span>
                        </div>

                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                            Advanced Campus
                            <span class="gradient-text">Attendance</span>
                            System
                        </h1>

                        <p class="text-xl text-white/80 mb-8 leading-relaxed">
                            Eliminate proxy attendance with hardware-locked, real-time tracking.
                            Secure, hierarchical, and designed for modern academic institutions.
                        </p>

                        <div class="flex flex-col sm:flex-row gap-4 mb-12">
                            <a href="login.php"
                                class="px-8 py-4 bg-white text-purple-600 rounded-xl hover:bg-gray-100 transition font-bold text-lg flex items-center justify-center">
                                <i class="fas fa-rocket mr-3"></i>Get Started Now
                            </a>
                            <a href="#features"
                                class="px-8 py-4 glass-card text-white rounded-xl hover:bg-white/10 transition font-bold text-lg flex items-center justify-center">
                                <i class="fas fa-play-circle mr-3"></i>See Features
                            </a>
                        </div>

                        <!-- Stats -->
                        <div class="flex flex-wrap gap-8">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-white mb-1">99.9%</div>
                                <div class="text-white/70">Accuracy</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-white mb-1">2000+</div>
                                <div class="text-white/70">Users</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-white mb-1">24/7</div>
                                <div class="text-white/70">Uptime</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-white mb-1">0</div>
                                <div class="text-white/70">Proxy Cases</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Visual -->
                    <div class="relative">
                        <!-- Main Card -->
                        <div class="glass-card rounded-3xl p-8 slide-up" style="animation-delay: 0.2s;">
                            <div class="flex items-center justify-between mb-8">
                                <div>
                                    <h3 class="text-2xl font-bold text-white">Secure Login Portal</h3>
                                    <p class="text-white/70">Access your academic dashboard</p>
                                </div>
                                <div class="relative">
                                    <div class="h-14 w-14 rounded-full bg-gradient-to-r from-green-400 to-blue-500 flex items-center justify-center">
                                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                                    </div>
                                    <div class="absolute -inset-1 rounded-full bg-gradient-to-r from-green-400 to-blue-500 opacity-20 pulse-ring"></div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="space-y-4 mb-8">
                                <a href="login.php"
                                    class="flex items-center p-4 bg-white/5 rounded-xl hover:bg-white/10 transition group">
                                    <div class="h-12 w-12 rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 flex items-center justify-center mr-4">
                                        <i class="fas fa-user-graduate text-white text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-bold text-white">Student Login</div>
                                        <div class="text-sm text-white/60">Access attendance, timetable & leave requests</div>
                                    </div>
                                    <i class="fas fa-arrow-right text-white/40 group-hover:text-white transition"></i>
                                </a>

                                <a href="login.php"
                                    class="flex items-center p-4 bg-white/5 rounded-xl hover:bg-white/10 transition group">
                                    <div class="h-12 w-12 rounded-lg bg-gradient-to-r from-purple-500 to-purple-600 flex items-center justify-center mr-4">
                                        <i class="fas fa-chalkboard-teacher text-white text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-bold text-white">Teacher Login</div>
                                        <div class="text-sm text-white/60">Manage classes, generate QR & monitor attendance</div>
                                    </div>
                                    <i class="fas fa-arrow-right text-white/40 group-hover:text-white transition"></i>
                                </a>

                                <a href="login.php"
                                    class="flex items-center p-4 bg-white/5 rounded-xl hover:bg-white/10 transition group">
                                    <div class="h-12 w-12 rounded-lg bg-gradient-to-r from-yellow-500 to-yellow-600 flex items-center justify-center mr-4">
                                        <i class="fas fa-user-shield text-white text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-bold text-white">Admin Login</div>
                                        <div class="text-sm text-white/60">System configuration & analytics dashboard</div>
                                    </div>
                                    <i class="fas fa-arrow-right text-white/40 group-hover:text-white transition"></i>
                                </a>
                            </div>

                            <!-- Security Badge -->
                            <div class="border-t border-white/10 pt-6">
                                <div class="flex items-center justify-center space-x-2 text-white/60 text-sm">
                                    <i class="fas fa-lock"></i>
                                    <span>End-to-End Encrypted</span>
                                    <i class="fas fa-circle text-xs"></i>
                                    <i class="fas fa-fingerprint"></i>
                                    <span>Hardware-Locked</span>
                                    <i class="fas fa-circle text-xs"></i>
                                    <i class="fas fa-shield-alt"></i>
                                    <span>GDPR Compliant</span>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Elements -->
                        <div class="absolute -top-6 -right-6 h-32 w-32 rounded-full bg-gradient-to-r from-pink-500 to-rose-500 opacity-20 blur-xl"></div>
                        <div class="absolute -bottom-6 -left-6 h-40 w-40 rounded-full bg-gradient-to-r from-cyan-500 to-blue-500 opacity-20 blur-xl"></div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Features Section -->
        <section id="features" class="py-20 px-4 sm:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-white mb-4">Why Choose Our System?</h2>
                    <p class="text-xl text-white/70 max-w-3xl mx-auto">
                        Built with cutting-edge technology to solve real academic challenges
                    </p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="glass-card rounded-2xl p-6 hover:bg-white/15 transition">
                        <div class="h-14 w-14 rounded-xl bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center mb-6">
                            <i class="fas fa-mobile-alt text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Progressive Web App</h3>
                        <p class="text-white/70">Works like a native app without downloads. Offline capable with push notifications.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="glass-card rounded-2xl p-6 hover:bg-white/15 transition">
                        <div class="h-14 w-14 rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center mb-6">
                            <i class="fas fa-fingerprint text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">1:1 Device Locking</h3>
                        <p class="text-white/70">Cryptographic hardware binding eliminates proxy attendance completely.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="glass-card rounded-2xl p-6 hover:bg-white/15 transition">
                        <div class="h-14 w-14 rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center mb-6">
                            <i class="fas fa-qrcode text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Rotating QR Codes</h3>
                        <p class="text-white/70">Time-based tokens prevent screenshot sharing. Changes every 10 seconds.</p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="glass-card rounded-2xl p-6 hover:bg-white/15 transition">
                        <div class="h-14 w-14 rounded-xl bg-gradient-to-r from-yellow-500 to-orange-500 flex items-center justify-center mb-6">
                            <i class="fas fa-wifi text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">WiFi Subnet Fencing</h3>
                        <p class="text-white/70">Prevents GPS spoofing by restricting attendance to campus WiFi networks.</p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="glass-card rounded-2xl p-6 hover:bg-white/15 transition">
                        <div class="h-14 w-14 rounded-xl bg-gradient-to-r from-red-500 to-rose-500 flex items-center justify-center mb-6">
                            <i class="fas fa-bell text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Real-time Alerts</h3>
                        <p class="text-white/70">Instant push notifications for class changes, cancellations, and deadlines.</p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="glass-card rounded-2xl p-6 hover:bg-white/15 transition">
                        <div class="h-14 w-14 rounded-xl bg-gradient-to-r from-indigo-500 to-blue-500 flex items-center justify-center mb-6">
                            <i class="fas fa-chart-line text-white text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Analytics Dashboard</h3>
                        <p class="text-white/70">Comprehensive reports and insights for administrators and faculty.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 px-4 sm:px-8">
            <div class="max-w-4xl mx-auto text-center">
                <div class="glass-card rounded-3xl p-12">
                    <h2 class="text-4xl font-bold text-white mb-6">Ready to Transform Your Campus?</h2>
                    <p class="text-xl text-white/70 mb-10 max-w-2xl mx-auto">
                        Join hundreds of institutions using our secure attendance system.
                        No credit card required to get started.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-6 justify-center">
                        <a href="register.php"
                            class="px-10 py-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl hover:from-blue-600 hover:to-purple-700 transition font-bold text-lg">
                            <i class="fas fa-rocket mr-3"></i>Create Free Account
                        </a>
                        <a href="contact.php"
                            class="px-10 py-4 glass-card text-white rounded-xl hover:bg-white/10 transition font-bold text-lg">
                            <i class="fas fa-calendar-alt mr-3"></i>Schedule Demo
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-white/10 py-10 px-4 sm:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid md:grid-cols-4 gap-8">
                    <div>
                        <div class="flex items-center space-x-2 mb-6">
                            <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center">
                                <i class="fas fa-fingerprint text-white text-xl"></i>
                            </div>
                            <span class="text-white font-bold text-xl">ACAS</span>
                        </div>
                        <p class="text-white/60 mb-6">
                            Advanced Campus Attendance System - Secure, hierarchical, and hardware-locked academic tracking.
                        </p>
                    </div>

                    <div>
                        <h4 class="text-white font-bold mb-6">Quick Links</h4>
                        <ul class="space-y-3">
                            <li><a href="login.php" class="text-white/60 hover:text-white transition">Login</a></li>
                            <li><a href="register.php" class="text-white/60 hover:text-white transition">Register</a></li>
                            <li><a href="feature.php" class="text-white/60 hover:text-white transition">Features</a></li>
                            <li><a href="help.php" class="text-white/60 hover:text-white transition">Help</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-white font-bold mb-6">Support</h4>
                        <ul class="space-y-3">
                            <li><a href="help.php" class="text-white/60 hover:text-white transition">Help Center</a></li>
                            <li><a href="privacy.php" class="text-white/60 hover:text-white transition">Privacy Policy</a></li>
                            <li><a href="terms.php" class="text-white/60 hover:text-white transition">Terms of Service</a></li>
                            <li><a href="contact.php" class="text-white/60 hover:text-white transition">Contact Support</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-white font-bold mb-6">Contact Us</h4>
                        <div class="space-y-4">
                            <div class="flex items-center text-white/60">
                                <i class="fas fa-envelope mr-3"></i>
                                <span>rjit_bsft@yahoo.com</span>
                            </div>
                            <div class="flex items-center text-white/60">
                                <i class="fas fa-phone mr-3"></i>
                                <span>+91-(07524)-274320</span>
                            </div>
                            <div class="flex items-center text-white/60">
                                <i class="fas fa-address-alt mr-3"></i>
                                <span>Address :BSF Academy, Tekanpur,Gwalior, Madhya Pradesh Pincode: 475005
                                </span>
                            </div>
                            <div class="flex items-center text-white/60">
                                <i class="fas fa-map-marker-alt mr-3"></i>
                                <span>Academic Complex, Campus Drive</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-white/10 mt-10 pt-8 text-center text-white/60">
                    <p>&copy; <?= date('Y') ?> Advanced Campus Attendance System. All rights reserved.</p>
                    <p class="mt-2 text-sm">Version 1.2 - Built with ❤️ for academic excellence</p>
                </div>
            </div>
        </footer>
    </div>

    <!-- Smooth Scroll -->
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>

</html>