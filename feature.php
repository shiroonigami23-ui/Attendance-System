<?php
require_once 'includes/Config.php';
require_once 'includes/Auth.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null;
$userName = $_SESSION['full_name'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Features - Advanced Campus Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #3730a3 100%);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .gradient-text {
            background: linear-gradient(90deg, #60a5fa, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .feature-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #60a5fa, #8b5cf6);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover::before {
            transform: scaleX(1);
        }
        
        .pill-badge {
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
        
        .security-layer {
            position: relative;
            padding-left: 2rem;
        }
        
        .security-layer::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            border-radius: 2px;
        }
        
        .layer-1::before { background: linear-gradient(to bottom, #10b981, #34d399); }
        .layer-2::before { background: linear-gradient(to bottom, #3b82f6, #60a5fa); }
        .layer-3::before { background: linear-gradient(to bottom, #8b5cf6, #a78bfa); }
        
        .timeline-connector {
            position: relative;
        }
        
        .timeline-connector::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -20px;
            width: 40px;
            height: 2px;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .comparison-table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .comparison-table th {
            position: sticky;
            top: 0;
            background: rgba(30, 58, 138, 0.9);
            backdrop-filter: blur(10px);
        }
        
        .comparison-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .feature-badge {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .role-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .role-card:hover {
            border-color: rgba(96, 165, 250, 0.3);
            transform: scale(1.02);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen text-white">
    <!-- Navigation -->
    <nav class="py-4 px-6 glass-card sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                    <i class="fas fa-star text-white text-xl"></i>
                </div>
                <span class="text-white font-bold text-xl">Features</span>
            </div>
            <div class="flex items-center space-x-6">
                <a href="index.php" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-home mr-2"></i>Home
                </a>
                <a href="about.php" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-info-circle mr-2"></i>About
                </a>
                <a href="help.php" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-question-circle mr-2"></i>Help
                </a>
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="text-white/80 hover:text-white transition">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                    </a>
                    <span class="text-white/60">|</span>
                    <span class="text-white">
                        <i class="fas fa-user mr-2"></i><?= htmlspecialchars($userName) ?>
                    </span>
                <?php else: ?>
                    <a href="login.php" class="text-white/80 hover:text-white transition">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                    <a href="register.php" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-600 hover:to-purple-700 transition">
                        <i class="fas fa-user-plus mr-2"></i>Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="py-20 px-4">
        <div class="max-w-7xl mx-auto text-center">
            <div class="inline-flex items-center px-4 py-2 rounded-full bg-gradient-to-r from-blue-500/20 to-purple-600/20 backdrop-blur-sm mb-6">
                <div class="h-2 w-2 rounded-full bg-green-400 mr-2 animate-pulse"></div>
                <span class="text-blue-300 text-sm">Enterprise Feature Set</span>
            </div>
            
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold mb-8">
                Complete <span class="gradient-text">Feature</span> Suite
            </h1>
            
            <p class="text-xl text-white/70 max-w-3xl mx-auto mb-12 leading-relaxed">
                Every feature designed to eliminate proxy attendance while providing 
                seamless academic management for students, teachers, and administrators.
            </p>
            
            <!-- Feature Categories -->
            <div class="flex flex-wrap justify-center gap-4 mb-16">
                <button onclick="showCategory('security')" 
                        class="px-6 py-3 glass-card rounded-xl hover:bg-white/10 transition font-medium feature-category active">
                    <i class="fas fa-shield-alt mr-2"></i>Security Features
                </button>
                <button onclick="showCategory('student')" 
                        class="px-6 py-3 glass-card rounded-xl hover:bg-white/10 transition font-medium feature-category">
                    <i class="fas fa-user-graduate mr-2"></i>Student Features
                </button>
                <button onclick="showCategory('teacher')" 
                        class="px-6 py-3 glass-card rounded-xl hover:bg-white/10 transition font-medium feature-category">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>Teacher Features
                </button>
                <button onclick="showCategory('admin')" 
                        class="px-6 py-3 glass-card rounded-xl hover:bg-white/10 transition font-medium feature-category">
                    <i class="fas fa-user-shield mr-2"></i>Admin Features
                </button>
                <button onclick="showCategory('technical')" 
                        class="px-6 py-3 glass-card rounded-xl hover:bg-white/10 transition font-medium feature-category">
                    <i class="fas fa-cogs mr-2"></i>Technical Features
                </button>
            </div>
        </div>
    </section>

    <!-- Security Features -->
    <section id="security" class="feature-section py-16 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <div class="flex items-center justify-center mb-6">
                    <div class="h-14 w-14 rounded-2xl bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center mr-4">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h2 class="text-4xl font-bold text-white">Security <span class="gradient-text">Features</span></h2>
                </div>
                <p class="text-xl text-white/70 max-w-3xl mx-auto">
                    Military-grade security protocols that make proxy attendance impossible
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
                <!-- Feature 1 -->
                <div class="feature-card glass-card rounded-2xl p-8">
                    <div class="flex items-start justify-between mb-6">
                        <div class="h-14 w-14 rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center">
                            <i class="fas fa-fingerprint text-white text-2xl"></i>
                        </div>
                        <span class="pill-badge bg-green-500/20 text-green-300">
                            <i class="fas fa-microchip mr-1"></i> Hardware
                        </span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">1:1 Device Locking</h3>
                    <p class="text-white/70 mb-6">
                        Cryptographic binding of student identity to their specific hardware device. 
                        No device swaps permitted without Master Admin intervention.
                    </p>
                    <div class="space-y-3">
                        <div class="flex items-center text-white/80">
                            <i class="fas fa-check-circle text-green-400 mr-3"></i>
                            <span>Eliminates buddy punching</span>
                        </div>
                        <div class="flex items-center text-white/80">
                            <i class="fas fa-check-circle text-green-400 mr-3"></i>
                            <span>HTTPOnly Secure Cookies</span>
                        </div>
                        <div class="flex items-center text-white/80">
                            <i class="fas fa-check-circle text-green-400 mr-3"></i>
                            <span>Automatic fingerprint generation</span>
                        </div>
                    </div>
                </div>
                
                <!-- Feature 2 -->
                <div class="feature-card glass-card rounded-2xl p-8">
                    <div class="flex items-start justify-between mb-6">
                        <div class="h-14 w-14 rounded-xl bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center">
                            <i class="fas fa-wifi text-white text-2xl"></i>
                        </div>
                        <span class="pill-badge bg-blue-500/20 text-blue-300">
                            <i class="fas fa-location-arrow mr-1"></i> Network
                        </span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">WiFi Subnet Fencing</h3>
                    <p class="text-white/70 mb-6">
                        Attendance requests only accepted from whitelisted campus WiFi subnets. 
                        Prevents GPS spoofing and remote attendance marking.
                    </p>
                    <div class="space-y-3">
                        <div class="flex items-center text-white/80">
                            <i class="fas fa-check-circle text-blue-400 mr-3"></i>
                            <span>Blocks mock location apps</span>
                        </div>
                        <div class="flex items-center text-white/80">
                            <i class="fas fa-check-circle text-blue-400 mr-3"></i>
                            <span>GPS fallback (50m radius)</span>
                        </div>
                        <div class="flex items-center text-white/80">
                            <i class="fas fa-check-circle text-blue-400 mr-3"></i>
                            <span>Real-time IP validation</span>
                        </div>
                    </div>
                </div>
                
                <!-- Feature 3 -->
                <div class="feature-card glass-card rounded-2xl p-8">
                    <div class="flex items-start justify-between mb-6">
                        <div class="h-14 w-14 rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center">
                            <i class="fas fa-qrcode text-white text-2xl"></i>
                        </div>
                        <span class="pill-badge bg-purple-500/20 text-purple-300">
                            <i class="fas fa-clock mr-1"></i> Time-Based
                        </span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Rotating QR (TOTP)</h3>
                    <p class="text-white/70 mb-6">
                        Time-based One-Time Password QR codes that rotate every 10 seconds. 
                        Prevents screenshot sharing via WhatsApp or other messaging apps.
                    </p>
                    <div class="space-y-3">
                        <div class="flex items-center text-white/80">
                            <i class="fas fa-check-circle text-purple-400 mr-3"></i>
                            <span>10-second rotation cycle</span>
                        </div>
                        <div class="flex items-center text-white/80">
                            <i class="fas fa-check-circle text-purple-400 mr-3"></i>
                            <span>Server-side hash validation</span>
                        </div>
                        <div class="flex items-center text-white/80">
                            <i class="fas fa-check-circle text-purple-400 mr-3"></i>
                            <span>Automatic expiry (403 error)</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Security Layers -->
            <div class="glass-card rounded-3xl p-8 mb-16">
                <h3 class="text-2xl font-bold text-white mb-8 text-center">Triple-Layer Security Validation</h3>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="security-layer layer-1">
                        <h4 class="text-xl font-bold text-green-300 mb-4">
                            <i class="fas fa-clock mr-2"></i>Temporal Validation
                        </h4>
                        <p class="text-white/70 mb-4">
                            Is the QR token valid for this specific 10-second window?
                        </p>
                        <ul class="text-white/80 space-y-2">
                            <li>• Time-based token validation</li>
                            <li>• 3-minute Green Zone window</li>
                            <li>• Automatic session locking</li>
                            <li>• Real-time clock sync</li>
                        </ul>
                    </div>
                    
                    <div class="security-layer layer-2">
                        <h4 class="text-xl font-bold text-blue-300 mb-4">
                            <i class="fas fa-map-marker-alt mr-2"></i>Spatial Validation
                        </h4>
                        <p class="text-white/70 mb-4">
                            Is the request originating from the Campus WiFi Subnet or within Geofence?
                        </p>
                        <ul class="text-white/80 space-y-2">
                            <li>• WiFi subnet whitelisting</li>
                            <li>• GPS coordinate validation</li>
                            <li>• 50-meter radius restriction</li>
                            <li>• Network fingerprinting</li>
                        </ul>
                    </div>
                    
                    <div class="security-layer layer-3">
                        <h4 class="text-xl font-bold text-purple-300 mb-4">
                            <i class="fas fa-user-check mr-2"></i>Biological/Digital Validation
                        </h4>
                        <p class="text-white/70 mb-4">
                            Does the Device Fingerprint match the Roll Number in database?
                        </p>
                        <ul class="text-white/80 space-y-2">
                            <li>• Hardware fingerprint matching</li>
                            <li>• Cryptographic identity binding</li>
                            <li>• Real-time device verification</li>
                            <li>• Trust score heuristics</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Student Features -->
    <section id="student" class="feature-section py-16 px-4 hidden">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <div class="flex items-center justify-center mb-6">
                    <div class="h-14 w-14 rounded-2xl bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center mr-4">
                        <i class="fas fa-user-graduate text-white text-2xl"></i>
                    </div>
                    <h2 class="text-4xl font-bold text-white">Student <span class="gradient-text">Features</span></h2>
                </div>
                <p class="text-xl text-white/70 max-w-3xl mx-auto">
                    Everything students need for seamless attendance management
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-8 mb-16">
                <!-- Left Column -->
                <div class="space-y-8">
                    <div class="feature-card glass-card rounded-2xl p-8">
                        <div class="flex items-center mb-6">
                            <div class="h-12 w-12 rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center mr-4">
                                <i class="fas fa-mobile-alt text-white text-xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white">Mobile-First PWA</h3>
                        </div>
                        <p class="text-white/70 mb-6">
                            Progressive Web App that works like a native app without App Store downloads. 
                            Offline capable with instant loading.
                        </p>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-400">0s</div>
                                <div class="text-white/60 text-sm">Load Time</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-400">100%</div>
                                <div class="text-white/60 text-sm">Offline Ready</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-400">0MB</div>
                                <div class="text-white/60 text-sm">App Size</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="feature-card glass-card rounded-2xl p-8">
                        <div class="flex items-center mb-6">
                            <div class="h-12 w-12 rounded-xl bg-gradient-to-r from-yellow-500 to-orange-500 flex items-center justify-center mr-4">
                                <i class="fas fa-calendar-alt text-white text-xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white">Smart Dashboard</h3>
                        </div>
                        <p class="text-white/70 mb-6">
                            Intelligent dashboard that shows exactly what you need, when you need it.
                        </p>
                        <div class="space-y-3">
                            <div class="flex items-center text-white/80">
                                <i class="fas fa-bell text-yellow-400 mr-3"></i>
                                <span>Next class countdown timer</span>
                            </div>
                            <div class="flex items-center text-white/80">
                                <i class="fas fa-chart-pie text-green-400 mr-3"></i>
                                <span>Visual attendance percentage</span>
                            </div>
                            <div class="flex items-center text-white/80">
                                <i class="fas fa-history text-blue-400 mr-3"></i>
                                <span>Color-coded calendar view</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="space-y-8">
                    <div class="feature-card glass-card rounded-2xl p-8">
                        <div class="flex items-center mb-6">
                            <div class="h-12 w-12 rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center mr-4">
                                <i class="fas fa-file-medical text-white text-xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white">Leave Management</h3>
                        </div>
                        <p class="text-white/70 mb-6">
                            Complete leave application system with smart validation rules.
                        </p>
                        <div class="overflow-x-auto">
                            <table class="w-full text-white/80 text-sm">
                                <thead>
                                    <tr class="border-b border-white/20">
                                        <th class="pb-2 text-left">Type</th>
                                        <th class="pb-2 text-left">Duration</th>
                                        <th class="pb-2 text-left">Approval</th>
                                        <th class="pb-2 text-left">Document</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-white/10">
                                        <td class="py-2">Short Leave</td>
                                        <td class="py-2">1-3 days</td>
                                        <td class="py-2">Teacher</td>
                                        <td class="py-2"><i class="fas fa-check text-green-400"></i></td>
                                    </tr>
                                    <tr class="border-b border-white/10">
                                        <td class="py-2">Long Leave</td>
                                        <td class="py-2">4+ days</td>
                                        <td class="py-2">Master Admin</td>
                                        <td class="py-2"><i class="fas fa-check text-green-400"></i></td>
                                    </tr>
                                    <tr>
                                        <td class="py-2">Emergency</td>
                                        <td class="py-2">Immediate</td>
                                        <td class="py-2">Post-facto</td>
                                        <td class="py-2"><i class="fas fa-check text-green-400"></i></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="feature-card glass-card rounded-2xl p-8">
                        <div class="flex items-center mb-6">
                            <div class="h-12 w-12 rounded-xl bg-gradient-to-r from-red-500 to-rose-500 flex items-center justify-center mr-4">
                                <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-white">Red Zone Protection</h3>
                        </div>
                        <p class="text-white/70 mb-6">
                            Automatic safeguards for students falling below attendance thresholds.
                        </p>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-white/80">Below 35%</span>
                                <span class="px-3 py-1 bg-red-500/20 text-red-300 rounded-full text-sm">
                                    Account Locked
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-white/80">35-60%</span>
                                <span class="px-3 py-1 bg-yellow-500/20 text-yellow-300 rounded-full text-sm">
                                    Warning Zone
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-white/80">Above 75%</span>
                                <span class="px-3 py-1 bg-green-500/20 text-green-300 rounded-full text-sm">
                                    Safe Zone
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Time Window Protocol -->
            <div class="glass-card rounded-3xl p-8 mb-16">
                <h3 class="text-2xl font-bold text-white mb-8 text-center">Attendance Time Window Protocol</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center p-6 rounded-2xl bg-gradient-to-b from-green-500/10 to-transparent border border-green-500/20">
                        <div class="text-4xl font-bold text-green-400 mb-2">0-3 min</div>
                        <h4 class="text-xl font-bold text-white mb-3">Green Zone</h4>
                        <p class="text-white/70">Full attendance marked as PRESENT</p>
                        <div class="mt-4">
                            <span class="pill-badge bg-green-500/20 text-green-300">Normal Scan</span>
                        </div>
                    </div>
                    
                    <div class="text-center p-6 rounded-2xl bg-gradient-to-b from-yellow-500/10 to-transparent border border-yellow-500/20">
                        <div class="text-4xl font-bold text-yellow-400 mb-2">3-7 min</div>
                        <h4 class="text-xl font-bold text-white mb-3">Yellow Zone</h4>
                        <p class="text-white/70">Marked as LATE (0.5 attendance)</p>
                        <div class="mt-4">
                            <span class="pill-badge bg-yellow-500/20 text-yellow-300">Warning</span>
                        </div>
                    </div>
                    
                    <div class="text-center p-6 rounded-2xl bg-gradient-to-b from-red-500/10 to-transparent border border-red-500/20">
                        <div class="text-4xl font-bold text-red-400 mb-2">7+ min</div>
                        <h4 class="text-xl font-bold text-white mb-3">Red Zone</h4>
                        <p class="text-white/70">Scanning disabled - ABSENT</p>
                        <div class="mt-4">
                            <span class="pill-badge bg-red-500/20 text-red-300">Locked</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Teacher Features -->
    <section id="teacher" class="feature-section py-16 px-4 hidden">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <div class="flex items-center justify-center mb-6">
                    <div class="h-14 w-14 rounded-2xl bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center mr-4">
                        <i class="fas fa-chalkboard-teacher text-white text-2xl"></i>
                    </div>
                    <h2 class="text-4xl font-bold text-white">Teacher <span class="gradient-text">Features</span></h2>
                </div>
                <p class="text-xl text-white/70 max-w-3xl mx-auto">
                    Powerful tools for classroom management and attendance monitoring
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-8 mb-16">
                <!-- Feature 1 -->
                <div class="feature-card glass-card rounded-2xl p-8">
                    <div class="flex items-center mb-6">
                        <div class="h-12 w-12 rounded-xl bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center mr-4">
                            <i class="fas fa-qrcode text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Session Management</h3>
                            <p class="text-white/60 text-sm">Generate & control class sessions</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-white/80">Theory Mode</span>
                            <span class="text-blue-300">50 minutes</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-white/80">Lab Mode</span>
                            <span class="text-purple-300">100 minutes</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-white/80">Batch Selection</span>
                            <span class="text-green-300">A1 / A2 toggle</span>
                        </div>
                    </div>
                    <div class="mt-6 pt-6 border-t border-white/10">
                        <p class="text-white/70 text-sm">
                            <i class="fas fa-info-circle mr-2"></i>
                            QR auto-rotates every 10 seconds
                        </p>
                    </div>
                </div>
                
                <!-- Feature 2 -->
                <div class="feature-card glasscard rounded-2xl p-8">
                    <div class="flex items-center mb-6">
                        <div class="h-12 w-12 rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center mr-4">
                            <i class="fas fa-chart-line text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white">Real-time Monitoring</h3>
                            <p class="text-white/60 text-sm">Live classroom analytics</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-white/80">Scans Received</span>
                            <span class="text-green-300">45/60</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-white/80">Red Zone Students</span>
                            <span class="text-red-300">5 students</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-white/80">Current Status</span>
                            <span class="pill-badge bg-green-500/20 text-green-300">Active</span>
                        </div>
                    </div>
                    <div class="mt-6 pt-6 border-t border-white/10">
                        <p class="text-white/70 text-sm">
                            <i class="fas fa-eye mr-2"></i>
                            Click student names for detailed view
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Collaboration Features -->
            <div class="glass-card rounded-3xl p-8 mb-16">
                <h3 class="text-2xl font-bold text-white mb-8">Teacher Collaboration Suite</h3>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="h-20 w-20 rounded-2xl bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-exchange-alt text-white text-2xl"></i>
                        </div>
                        <h4 class="text-xl font-bold text-white mb-3">Class Swapping</h4>
                        <p class="text-white/70">Swap classes with peers with full resource handover</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="h-20 w-20 rounded-2xl bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-clipboard-check text-white text-2xl"></i>
                        </div>
                        <h4 class="text-xl font-bold text-white mb-3">Leave Approvals</h4>
                        <p class="text-white/70">Approve/reject student leave requests with document verification</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="h-20 w-20 rounded-2xl bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-book-open text-white text-2xl"></i>
                        </div>
                        <h4 class="text-xl font-bold text-white mb-3">Logbook System</h4>
                        <p class="text-white/70">Mandatory topic logging for complete academic audit trail</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Admin Features -->
    <section id="admin" class="feature-section py-16 px-4 hidden">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <div class="flex items-center justify-center mb-6">
                    <div class="h-14 w-14 rounded-2xl bg-gradient-to-r from-red-500 to-orange-500 flex items-center justify-center mr-4">
                        <i class="fas fa-user-shield text-white text-2xl"></i>
                    </div>
                    <h2 class="text-4xl font-bold text-white">Admin <span class="gradient-text">Features</span></h2>
                </div>
                <p class="text-xl text-white/70 max-w-3xl mx-auto">
                    Complete control and oversight for system administrators
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-8 mb-16">
                <!-- Feature 1 -->
                <div class="feature-card glass-card rounded-2xl p-8">
                    <div class="flex items-center mb-6">
                        <div class="h-12 w-12 rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center mr-4">
                            <i class="fas fa-sliders-h text-white text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Grace Console</h3>
                    </div>
                    <p class="text-white/70 mb-6">
                        Visual slider to adjust attendance percentages for detained students.
                    </p>
                    <div class="space-y-3">
                        <div class="flex items-center text-white/80">
                            <i class="fas fa-search text-blue-400 mr-3"></i>
                            <span>Search by Roll Number</span>
                        </div>
                        <div class="flex items-center text-white/80">
                            <i class="fas fa-sliders text-green-400 mr-3"></i>
                            <span>Visual percentage slider</span>
                        </div>
                        <div class="flex items-center text-white/80">
                            <i class="fas fa-clipboard-list text-yellow-400 mr-3"></i>
                            <span>Complete audit logging</span>
                        </div>
                    </div>
                </div>
                
                <!-- Feature 2 -->
                <div class="feature-card glass-card rounded-2xl p-8">
                    <div class="flex items-center mb-6">
                        <div class="h-12 w-12 rounded-xl bg-gradient-to-r from-red-500 to-orange-500 flex items-center justify-center mr-4">
                            <i class="fas fa-power-off text-white text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white">Emergency Protocols</h3>
                    </div>
                    <p class="text-white/70 mb-6">
                        Campus-wide controls for exceptional situations.
                    </p>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-white/80">Red Button</span>
                            <span class="text-red-300">Cancel all classes</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-white/80">Substitute Override</span>
                            <span class="text-blue-300">Force assign teachers</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-white/80">Semester Promotion</span>
                            <span class="text-green-300">Batch migration wizard</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Comparison Table -->
            <div class="glass-card rounded-3xl p-8 overflow-hidden">
                <h3 class="text-2xl font-bold text-white mb-8">Role Comparison</h3>
                <div class="overflow-x-auto">
                    <table class="comparison-table w-full">
                        <thead>
                            <tr>
                                <th class="p-4 text-left">Feature</th>
                                <th class="p-4 text-left">Student</th>
                                <th class="p-4 text-left">Teacher</th>
                                <th class="p-4 text-left">Master Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="p-4 border-b border-white/10">Mark Attendance</td>
                                <td class="p-4 border-b border-white/10"><i class="fas fa-check text-green-400"></i></td>
                                <td class="p-4 border-b border-white/10"><i class="fas fa-times text-red-400"></i></td>
                                <td class="p-4 border-b border-white/10"><i class="fas fa-times text-red-400"></i></td>
                            </tr>
                            <tr>
                                <td class="p-4 border-b border-white/10">Generate QR</td>
                                <td class="p-4 border-b border-white/10"><i class="fas fa-times text-red-400"></i></td>
                                <td class="p-4 border-b border-white/10"><i class="fas fa-check text-green-400"></i></td>
                                <td class="p-4 border-b border-white/10"><i class="fas fa-check text-green-400"></i></td>
                            </tr>
                            <tr>
                                <td class="p-4 border-b border-white/10">Approve Leaves</td>
                                <td class="p-4 border-b border-white/10"><i class="fas fa-times text-red-400"></i></td>
                                <td class="p-4 border-b border-white/10"><i class="fas fa-check text-green-400"></i></td>
                                <td class="p-4 border-b border-white/10"><i class="fas fa-check text-green-400"></i></td>
                            </tr>
                            <tr>
                                <td class="p-4 border-b border-white/10">Device Reset</td>
                                <td class="p-4 border-b border-white/10"><i class="fas fa-times text-red-400"></i></td>
                                <td class="p-4 border-b border-white/10"><i class="fas fa-times text-red-400"></i></td>
                                <td class="p-4 border-b border-white/10"><i class="fas fa-check text-green-400"></i></td>
                            </tr>
                            <tr>
                                <td class="p-4">Cancel Classes</td>
                                <td class="p-4"><i class="fas fa-times text-red-400"></i></td>
                                <td class="p-4"><i class="fas fa-times text-red-400"></i></td>
                                <td class="p-4"><i class="fas fa-check text-green-400"></i></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Technical Features -->
    <section id="technical" class="feature-section py-16 px-4 hidden">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <div class="flex items-center justify-center mb-6">
                    <div class="h-14 w-14 rounded-2xl bg-gradient-to-r from-yellow-500 to-orange-500 flex items-center justify-center mr-4">
                        <i class="fas fa-server text-white text-2xl"></i>
                    </div>
                    <h2 class="text-4xl font-bold text-white">Technical <span class="gradient-text">Features</span></h2>
                </div>
                <p class="text-xl text-white/70 max-w-3xl mx-auto">
                    Under-the-hood capabilities that power the system
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8 mb-16">
                <!-- Feature 1 -->
                <div class="feature-card glass-card rounded-2xl p-8">
                    <div class="h-14 w-14 rounded-xl bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center mb-6">
                        <i class="fas fa-database text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">PostgreSQL 15</h3>
                    <p class="text-white/70 mb-6">
                        BCNF-normalized schema with strict relational integrity and JSONB fields.
                    </p>
                    <div class="space-y-2">
                        <span class="block px-3 py-1 bg-white/10 rounded-full text-xs text-white/80 w-fit">Partitioned Tables</span>
                        <span class="block px-3 py-1 bg-white/10 rounded-full text-xs text-white/80 w-fit">Materialized Views</span>
                        <span class="block px-3 py-1 bg-white/10 rounded-full text-xs text-white/80 w-fit">Foreign Keys</span>
                    </div>
                </div>
                
                <!-- Feature 2 -->
                <div class="feature-card glass-card rounded-2xl p-8">
                    <div class="h-14 w-14 rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center mb-6">
                        <i class="fab fa-aws text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">AWS Infrastructure</h3>
                    <p class="text-white/70 mb-6">
                        Self-hosted EC2 instances with Virtual Private Cloud for complete data sovereignty.
                    </p>
                    <div class="space-y-2">
                        <span class="block px-3 py-1 bg-white/10 rounded-full text-xs text-white/80 w-fit">EC2 Instances</span>
                        <span class="block px-3 py-1 bg-white/10 rounded-full text-xs text-white/80 w-fit">VPC Security</span>
                        <span class="block px-3 py-1 bg-white/10 rounded-full text-xs text-white/80 w-fit">Self-Managed</span>
                    </div>
                </div>
                
                <!-- Feature 3 -->
                <div class="feature-card glass-card rounded-2xl p-8">
                    <div class="h-14 w-14 rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center mb-6">
                        <i class="fas fa-bell text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">VAPID Push</h3>
                    <p class="text-white/70 mb-6">
                        Web Push API with VAPID for background notifications even when app is closed.
                    </p>
                    <div class="space-y-2">
                        <span class="block px-3 py-1 bg-white/10 rounded-full text-xs text-white/80 w-fit">Background Alerts</span>
                        <span class="block px-3 py-1 bg-white/10 rounded-full text-xs text-white/80 w-fit">Class Cancellations</span>
                        <span class="block px-3 py-1 bg-white/10 rounded-full text-xs text-white/80 w-fit">Instant Delivery</span>
                    </div>
                </div>
            </div>
            
            <!-- Performance Metrics -->
            <div class="glass-card rounded-3xl p-8">
                <h3 class="text-2xl font-bold text-white mb-8 text-center">Performance Metrics</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="text-center p-4">
                        <div class="text-3xl font-bold text-green-400 mb-2">&lt;100ms</div>
                        <div class="text-white/70">API Response</div>
                    </div>
                    <div class="text-center p-4">
                        <div class="text-3xl font-bold text-blue-400 mb-2">2000+</div>
                        <div class="text-white/70">Concurrent Users</div>
                    </div>
                    <div class="text-center p-4">
                        <div class="text-3xl font-bold text-purple-400 mb-2">99.99%</div>
                        <div class="text-white/70">Uptime SLA</div>
                    </div>
                    <div class="text-center p-4">
                        <div class="text-3xl font-bold text-yellow-400 mb-2">24/7</div>
                        <div class="text-white/70">Monitoring</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 px-4">
        <div class="max-w-4xl mx-auto text-center">
            <div class="glass-card rounded-3xl p-12">
                <h2 class="text-4xl font-bold text-white mb-6">Ready to Experience These Features?</h2>
                <p class="text-xl text-white/70 mb-10 max-w-2xl mx-auto">
                    Join the most advanced campus attendance system with military-grade security 
                    and seamless user experience.
                </p>
                <div class="flex flex-col sm:flex-row gap-6 justify-center">
                    <a href="register.php" 
                       class="px-10 py-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl hover:from-blue-600 hover:to-purple-700 transition font-bold text-lg">
                        <i class="fas fa-user-plus mr-3"></i>Create Account
                    </a>
                    <a href="login.php" 
                       class="px-10 py-4 glass-card text-white rounded-xl hover:bg-white/10 transition font-bold text-lg">
                        <i class="fas fa-sign-in-alt mr-3"></i>Login to Demo
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-white/10 py-12 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="grid md:grid-cols-3 gap-8 mb-12">
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                            <i class="fas fa-fingerprint text-white text-xl"></i>
                        </div>
                        <span class="text-white font-bold text-xl">ACAS</span>
                    </div>
                    <p class="text-white/70">
                        Advanced Campus Attendance System - Complete feature set for modern academic institutions.
                    </p>
                </div>
                
                <div>
                    <h4 class="text-white font-bold mb-6">Explore</h4>
                    <ul class="space-y-3">
                        <li><a href="index.php" class="text-white/70 hover:text-white transition">Home</a></li>
                        <li><a href="about.php" class="text-white/70 hover:text-white transition">About System</a></li>
                        <li><a href="features.php" class="text-white/70 hover:text-white transition">Features</a></li>
                        <li><a href="help.php" class="text-white/70 hover:text-white transition">Help Center</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-white font-bold mb-6">Access</h4>
                    <ul class="space-y-3">
                        <li><a href="login.php" class="text-white/70 hover:text-white transition">Student Login</a></li>
                        <li><a href="login.php" class="text-white/70 hover:text-white transition">Teacher Login</a></li>
                        <li><a href="login.php" class="text-white/70 hover:text-white transition">Admin Login</a></li>
                        <li><a href="register.php" class="text-white/70 hover:text-white transition">New Registration</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-white/10 pt-8 text-center">
                <p class="text-white/60">
                    &copy; <?= date('Y') ?> Advanced Campus Attendance System. All features described are fully implemented.
                </p>
                <p class="text-white/40 text-sm mt-2">
                    Version 3.0 (Final Release) - Production Ready
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Feature Category Toggle
        function showCategory(category) {
            // Hide all sections
            document.querySelectorAll('.feature-section').forEach(section => {
                section.classList.add('hidden');
            });
            
            // Show selected section
            document.getElementById(category).classList.remove('hidden');
            
            // Update active button
            document.querySelectorAll('.feature-category').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Smooth scroll to section
            document.getElementById(category).scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
        
        // Initialize first category as active
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.feature-category').classList.add('active');
        });
        
        // Smooth scrolling for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href.startsWith('#')) {
                    e.preventDefault();
                    const targetId = href.substring(1);
                    const targetElement = document.getElementById(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 80,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
        
        // Add hover effects to feature cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>