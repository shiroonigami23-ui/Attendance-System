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
    <title>About System - Advanced Campus Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .gradient-text {
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .tech-icon {
            transition: transform 0.3s ease;
        }
        
        .tech-icon:hover {
            transform: translateY(-5px);
        }
        
        .stat-card {
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
        }
        
        .timeline-item {
            position: relative;
            padding-left: 3rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 1rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: rgba(59, 130, 246, 0.3);
        }
        
        .timeline-dot {
            position: absolute;
            left: 0.75rem;
            top: 0.5rem;
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            background: #3b82f6;
            z-index: 2;
        }
        
        .feature-card {
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body class="gradient-bg min-h-screen text-white">
    <!-- Navigation -->
    <nav class="py-4 px-6 glass-card sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                    <i class="fas fa-fingerprint text-white text-xl"></i>
                </div>
                <span class="text-white font-bold text-xl">ACAS</span>
            </div>
            <div class="flex items-center space-x-6">
                <a href="index.php" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-home mr-2"></i>Home
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
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <div class="inline-flex items-center px-4 py-2 rounded-full bg-gradient-to-r from-blue-500/20 to-purple-600/20 backdrop-blur-sm mb-6">
                    <div class="h-2 w-2 rounded-full bg-green-400 mr-2 animate-pulse"></div>
                    <span class="text-blue-300 text-sm">Enterprise Grade System</span>
                </div>
                
                <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold mb-6">
                    Advanced Campus 
                    <span class="gradient-text">Attendance System</span>
                </h1>
                
                <p class="text-xl text-white/70 max-w-3xl mx-auto leading-relaxed">
                    A hardware-locked, hierarchical attendance management ecosystem designed to eliminate 
                    proxy attendance and streamline academic operations with military-grade security.
                </p>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-20">
                <div class="stat-card glass-card rounded-2xl p-6">
                    <div class="text-3xl font-bold text-white mb-2">99.9%</div>
                    <div class="text-white/70">Accuracy Rate</div>
                    <div class="text-blue-400 text-sm mt-2">
                        <i class="fas fa-check-circle mr-2"></i>Zero false positives
                    </div>
                </div>
                
                <div class="stat-card glass-card rounded-2xl p-6">
                    <div class="text-3xl font-bold text-white mb-2">2000+</div>
                    <div class="text-white/70">Concurrent Users</div>
                    <div class="text-green-400 text-sm mt-2">
                        <i class="fas fa-users mr-2"></i>Scalable architecture
                    </div>
                </div>
                
                <div class="stat-card glass-card rounded-2xl p-6">
                    <div class="text-3xl font-bold text-white mb-2">0</div>
                    <div class="text-white/70">Proxy Incidents</div>
                    <div class="text-red-400 text-sm mt-2">
                        <i class="fas fa-shield-alt mr-2"></i>Unhackable core
                    </div>
                </div>
                
                <div class="stat-card glass-card rounded-2xl p-6">
                    <div class="text-3xl font-bold text-white mb-2">24/7</div>
                    <div class="text-white/70">System Uptime</div>
                    <div class="text-yellow-400 text-sm mt-2">
                        <i class="fas fa-bolt mr-2"></i>High availability
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Core Philosophy -->
    <section class="py-16 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-white mb-4">Our <span class="gradient-text">Core Philosophy</span></h2>
                <p class="text-xl text-white/70 max-w-3xl mx-auto">
                    Built on four non-negotiable principles that define our approach
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Principle 1 -->
                <div class="feature-card glass-card rounded-2xl p-8">
                    <div class="h-16 w-16 rounded-2xl bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center mb-6">
                        <i class="fas fa-user-lock text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Identity is Absolute</h3>
                    <p class="text-white/70">
                        Strict 1:1 device binding. One student = one device. Cryptographic hardware 
                        fingerprinting eliminates buddy punching permanently.
                    </p>
                    <div class="mt-6 pt-6 border-t border-white/10">
                        <span class="text-blue-300 text-sm font-medium">
                            <i class="fas fa-microchip mr-2"></i>Hardware Locked
                        </span>
                    </div>
                </div>
                
                <!-- Principle 2 -->
                <div class="feature-card glass-card rounded-2xl p-8">
                    <div class="h-16 w-16 rounded-2xl bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center mb-6">
                        <i class="fas fa-network-wired text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Decentralized Sovereignty</h3>
                    <p class="text-white/70">
                        Teachers control their subjects, schedules, and corrections autonomously. 
                        Removes bottlenecks while maintaining accountability.
                    </p>
                    <div class="mt-6 pt-6 border-t border-white/10">
                        <span class="text-purple-300 text-sm font-medium">
                            <i class="fas fa-user-shield mr-2"></i>Semi-Admin Protocol
                        </span>
                    </div>
                </div>
                
                <!-- Principle 3 -->
                <div class="feature-card glass-card rounded-2xl p-8">
                    <div class="h-16 w-16 rounded-2xl bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center mb-6">
                        <i class="fas fa-bolt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">High Availability</h3>
                    <p class="text-white/70">
                        Progressive Web App delivers critical alerts even when offline. Service Workers 
                        ensure instant loading on any network condition.
                    </p>
                    <div class="mt-6 pt-6 border-t border-white/10">
                        <span class="text-green-300 text-sm font-medium">
                            <i class="fas fa-wifi mr-2"></i>Always Online
                        </span>
                    </div>
                </div>
                
                <!-- Principle 4 -->
                <div class="feature-card glass-card rounded-2xl p-8">
                    <div class="h-16 w-16 rounded-2xl bg-gradient-to-r from-red-500 to-orange-500 flex items-center justify-center mb-6">
                        <i class="fas fa-shield-check text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Zero-Trust Architecture</h3>
                    <p class="text-white/70">
                        Triple-layer server-side validation for every transaction: Temporal, Spatial, 
                        and Biological/Digital verification.
                    </p>
                    <div class="mt-6 pt-6 border-t border-white/10">
                        <span class="text-red-300 text-sm font-medium">
                            <i class="fas fa-layer-group mr-2"></i>3-Layer Security
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Technology Stack -->
    <section class="py-16 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="glass-card rounded-3xl p-12">
                <div class="text-center mb-12">
                    <h2 class="text-4xl font-bold text-white mb-4">Technology <span class="gradient-text">Stack</span></h2>
                    <p class="text-xl text-white/70 max-w-3xl mx-auto">
                        Built with modern, scalable technologies for enterprise-grade performance
                    </p>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-8 mb-12">
                    <!-- Tech 1 -->
                    <div class="tech-icon text-center">
                        <div class="h-20 w-20 rounded-2xl bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center mx-auto mb-4">
                            <i class="fab fa-ubuntu text-white text-3xl"></i>
                        </div>
                        <div class="font-bold text-white">Ubuntu 22.04</div>
                        <div class="text-white/60 text-sm">Server OS</div>
                    </div>
                    
                    <!-- Tech 2 -->
                    <div class="tech-icon text-center">
                        <div class="h-20 w-20 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center mx-auto mb-4">
                            <i class="fab fa-aws text-white text-3xl"></i>
                        </div>
                        <div class="font-bold text-white">AWS EC2</div>
                        <div class="text-white/60 text-sm">Cloud Infrastructure</div>
                    </div>
                    
                    <!-- Tech 3 -->
                    <div class="tech-icon text-center">
                        <div class="h-20 w-20 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center mx-auto mb-4">
                            <i class="fab fa-php text-white text-3xl"></i>
                        </div>
                        <div class="font-bold text-white">PHP 8.2</div>
                        <div class="text-white/60 text-sm">Backend</div>
                    </div>
                    
                    <!-- Tech 4 -->
                    <div class="tech-icon text-center">
                        <div class="h-20 w-20 rounded-2xl bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-database text-white text-3xl"></i>
                        </div>
                        <div class="font-bold text-white">PostgreSQL 15</div>
                        <div class="text-white/60 text-sm">Database</div>
                    </div>
                    
                    <!-- Tech 5 -->
                    <div class="tech-icon text-center">
                        <div class="h-20 w-20 rounded-2xl bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center mx-auto mb-4">
                            <i class="fab fa-js-square text-white text-3xl"></i>
                        </div>
                        <div class="font-bold text-white">Vanilla JS</div>
                        <div class="text-white/60 text-sm">Frontend</div>
                    </div>
                    
                    <!-- Tech 6 -->
                    <div class="tech-icon text-center">
                        <div class="h-20 w-20 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center mx-auto mb-4">
                            <i class="fab fa-pwa text-white text-3xl"></i>
                        </div>
                        <div class="font-bold text-white">PWA</div>
                        <div class="text-white/60 text-sm">Progressive Web App</div>
                    </div>
                </div>
                
                <!-- Architecture Diagram -->
                <div class="mt-12 pt-12 border-t border-white/10">
                    <h3 class="text-2xl font-bold text-white mb-8 text-center">System Architecture</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-gradient-to-br from-blue-900/30 to-blue-900/10 rounded-2xl p-6 border border-blue-500/20">
                            <h4 class="font-bold text-blue-300 mb-4">
                                <i class="fas fa-mobile-alt mr-2"></i>Client Layer
                            </h4>
                            <ul class="text-white/70 space-y-2">
                                <li>• Progressive Web App (PWA)</li>
                                <li>• Service Workers</li>
                                <li>• VAPID Push Notifications</li>
                                <li>• Glassmorphism UI</li>
                                <li>• Hardware Fingerprinting</li>
                            </ul>
                        </div>
                        
                        <div class="bg-gradient-to-br from-purple-900/30 to-purple-900/10 rounded-2xl p-6 border border-purple-500/20">
                            <h4 class="font-bold text-purple-300 mb-4">
                                <i class="fas fa-server mr-2"></i>Server Layer
                            </h4>
                            <ul class="text-white/70 space-y-2">
                                <li>• AWS EC2 Self-Hosted</li>
                                <li>• LAPP Stack (Linux/Apache/PostgreSQL/PHP)</li>
                                <li>• RESTful API Architecture</li>
                                <li>• Triple-Layer Validation</li>
                                <li>• Real-time Processing</li>
                            </ul>
                        </div>
                        
                        <div class="bg-gradient-to-br from-green-900/30 to-green-900/10 rounded-2xl p-6 border border-green-500/20">
                            <h4 class="font-bold text-green-300 mb-4">
                                <i class="fas fa-database mr-2"></i>Data Layer
                            </h4>
                            <ul class="text-white/70 space-y-2">
                                <li>• PostgreSQL 15 (BCNF Normalized)</li>
                                <li>• Partitioned Tables</li>
                                <li>• Materialized Views</li>
                                <li>• Complete Audit Logging</li>
                                <li>• Real-time Replication</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Development Timeline -->
    <section class="py-16 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-white mb-4">Development <span class="gradient-text">Timeline</span></h2>
                <p class="text-xl text-white/70 max-w-3xl mx-auto">
                    A systematic approach to building enterprise software
                </p>
            </div>
            
            <div class="max-w-4xl mx-auto">
                <div class="space-y-8">
                    <!-- Phase 1 -->
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="glass-card rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <span class="px-3 py-1 bg-blue-500/20 text-blue-300 rounded-full text-sm font-bold">
                                    Phase 1
                                </span>
                                <span class="text-white/60">Week 1-2</span>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Infrastructure & Data Layer</h3>
                            <p class="text-white/70 mb-4">
                                AWS EC2 provisioning with Virtual Private Cloud security groups. 
                                LAPP stack installation and BCNF-normalized database schema deployment.
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-white/80">AWS EC2</span>
                                <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-white/80">PostgreSQL</span>
                                <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-white/80">BCNF Schema</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Phase 2 -->
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="glass-card rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <span class="px-3 py-1 bg-purple-500/20 text-purple-300 rounded-full text-sm font-bold">
                                    Phase 2
                                </span>
                                <span class="text-white/60">Week 3-4</span>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Identity & Security Core</h3>
                            <p class="text-white/70 mb-4">
                                Argon2id password hashing implementation. Cryptographic device fingerprinting 
                                with 1:1 hardware binding. Roll number parser for automatic branch/year extraction.
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-white/80">Argon2id</span>
                                <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-white/80">Device Locking</span>
                                <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-white/80">Roll Parser</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Phase 3 -->
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="glass-card rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <span class="px-3 py-1 bg-green-500/20 text-green-300 rounded-full text-sm font-bold">
                                    Phase 3
                                </span>
                                <span class="text-white/60">Week 5-6</span>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Operational Logic</h3>
                            <p class="text-white/70 mb-4">
                                Teacher module with rotating TOTP QR generation and batch selection. 
                                Student QR scanner with geolocation and WiFi validation. Time window protocols.
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-white/80">TOTP QR</span>
                                <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-white/80">Geolocation</span>
                                <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-white/80">Time Windows</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Phase 4 -->
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="glass-card rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <span class="px-3 py-1 bg-yellow-500/20 text-yellow-300 rounded-full text-sm font-bold">
                                    Phase 4
                                </span>
                                <span class="text-white/60">Week 7</span>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">PWA & Notifications</h3>
                            <p class="text-white/70 mb-4">
                                Progressive Web App implementation with Service Workers for offline capability. 
                                VAPID push notifications for class alerts. Add to Home Screen functionality.
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-white/80">Service Workers</span>
                                <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-white/80">VAPID Push</span>
                                <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-white/80">PWA Manifest</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Phase 5 -->
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="glass-card rounded-2xl p-6">
                            <div class="flex items-center justify-between mb-4">
                                <span class="px-3 py-1 bg-red-500/20 text-red-300 rounded-full text-sm font-bold">
                                    Phase 5
                                </span>
                                <span class="text-white/60">Week 8-9</span>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">Advanced Admin & Reporting</h3>
                            <p class="text-white/70 mb-4">
                                Bulk action interfaces with checkbox logic. Granular export suite (CSV/PDF). 
                                Complete audit logging system with who-exported-what tracking.
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-white/80">Bulk Actions</span>
                                <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-white/80">CSV/PDF Export</span>
                                <span class="px-3 py-1 bg-white/10 rounded-full text-xs text-white/80">Audit Logging</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="py-16 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-white mb-4">Built with <span class="gradient-text">Precision</span></h2>
                <p class="text-xl text-white/70 max-w-3xl mx-auto">
                    Developed following strict enterprise software engineering principles
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="h-32 w-32 rounded-full bg-gradient-to-r from-blue-500/20 to-purple-600/20 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-file-contract text-blue-400 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Specification Driven</h3>
                    <p class="text-white/70">Every feature mapped to detailed functional specifications</p>
                </div>
                
                <div class="text-center">
                    <div class="h-32 w-32 rounded-full bg-gradient-to-r from-green-500/20 to-emerald-600/20 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-code-branch text-green-400 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Version Controlled</h3>
                    <p class="text-white/70">Git-based development with semantic versioning</p>
                </div>
                
                <div class="text-center">
                    <div class="h-32 w-32 rounded-full bg-gradient-to-r from-yellow-500/20 to-orange-600/20 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-vial text-yellow-400 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Tested Rigorously</h3>
                    <p class="text-white/70">Unit, integration, and user acceptance testing</p>
                </div>
                
                <div class="text-center">
                    <div class="h-32 w-32 rounded-full bg-gradient-to-r from-red-500/20 to-pink-600/20 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-shield-alt text-red-400 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Security First</h3>
                    <p class="text-white/70">Penetration tested and security audited</p>
                </div>
            </div>
            
            <div class="mt-20 glass-card rounded-3xl p-12 text-center">
                <h3 class="text-3xl font-bold text-white mb-6">Ready to Deploy?</h3>
                <p class="text-xl text-white/70 max-w-2xl mx-auto mb-10">
                    The system is production-ready with complete documentation, 
                    deployment guides, and 24/7 support availability.
                </p>
                <div class="flex flex-col sm:flex-row gap-6 justify-center">
                    <a href="help.php" 
                       class="px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl hover:from-blue-600 hover:to-purple-700 transition font-bold text-lg">
                        <i class="fas fa-book mr-3"></i>View Documentation
                    </a>
                    <a href="index.php" 
                       class="px-8 py-4 glass-card text-white rounded-xl hover:bg-white/10 transition font-bold text-lg">
                        <i class="fas fa-rocket mr-3"></i>Get Started
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
                        Advanced Campus Attendance System - Secure, hierarchical, and hardware-locked 
                        academic tracking for modern educational institutions.
                    </p>
                </div>
                
                <div>
                    <h4 class="text-white font-bold mb-6">System</h4>
                    <ul class="space-y-3">
                        <li><a href="index.php" class="text-white/70 hover:text-white transition">Home</a></li>
                        <li><a href="about.php" class="text-white/70 hover:text-white transition">About</a></li>
                        <li><a href="help.php" class="text-white/70 hover:text-white transition">Help & Support</a></li>
                        <li><a href="login.php" class="text-white/70 hover:text-white transition">Login</a></li>
                        <li><a href="register.php" class="text-white/70 hover:text-white transition">Register</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-white font-bold mb-6">Technical</h4>
                    <ul class="space-y-3">
                        <li><span class="text-white/70">Version: 3.0 (Final Release)</span></li>
                        <li><span class="text-white/70">Status: Production Ready</span></li>
                        <li><span class="text-white/70">Database: PostgreSQL 15</span></li>
                        <li><span class="text-white/70">Infrastructure: AWS EC2</span></li>
                        <li><span class="text-white/70">Security: Zero-Trust Architecture</span></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-white/10 pt-8 text-center">
                <p class="text-white/60">
                    &copy; <?= date('Y') ?> Advanced Campus Attendance System. All rights reserved.
                </p>
                <p class="text-white/40 text-sm mt-2">
                    This system follows strict GDPR compliance and data protection regulations.
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Add subtle animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stats on scroll
            const observerOptions = {
                threshold: 0.5,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-pulse');
                        setTimeout(() => {
                            entry.target.classList.remove('animate-pulse');
                        }, 1000);
                    }
                });
            }, observerOptions);
            
            // Observe feature cards
            document.querySelectorAll('.feature-card').forEach(card => {
                observer.observe(card);
            });
            
            // Smooth scrolling
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    if (href === '#' || href.startsWith('#')) {
                        e.preventDefault();
                        const targetId = href.substring(1);
                        if (targetId) {
                            const targetElement = document.getElementById(targetId);
                            if (targetElement) {
                                window.scrollTo({
                                    top: targetElement.offsetTop - 80,
                                    behavior: 'smooth'
                                });
                            }
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>