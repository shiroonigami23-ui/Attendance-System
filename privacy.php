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
    <title>Privacy Policy - Advanced Campus Attendance System</title>
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
        
        .privacy-section {
            position: relative;
            padding-left: 2rem;
        }
        
        .privacy-section::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            border-radius: 2px;
            background: linear-gradient(to bottom, #3b82f6, #8b5cf6);
        }
        
        .data-badge {
            border-radius: 20px;
            padding: 6px 16px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            margin: 4px;
        }
        
        .legal-point {
            position: relative;
            padding-left: 2.5rem;
        }
        
        .legal-point::before {
            content: '§';
            position: absolute;
            left: 0;
            top: 0;
            width: 2rem;
            height: 2rem;
            background: rgba(59, 130, 246, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #3b82f6;
        }
        
        .compliance-badge {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .policy-table {
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .policy-table th {
            position: sticky;
            top: 0;
            background: rgba(30, 58, 138, 0.9);
            backdrop-filter: blur(10px);
        }
        
        .policy-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .highlight-box {
            border-left: 4px solid #3b82f6;
            padding-left: 1rem;
            margin: 1.5rem 0;
        }
        
        .security-level {
            position: relative;
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }
        
        .level-1 { background: linear-gradient(90deg, rgba(16, 185, 129, 0.1), transparent); border-left: 4px solid #10b981; }
        .level-2 { background: linear-gradient(90deg, rgba(59, 130, 246, 0.1), transparent); border-left: 4px solid #3b82f6; }
        .level-3 { background: linear-gradient(90deg, rgba(139, 92, 246, 0.1), transparent); border-left: 4px solid #8b5cf6; }
    </style>
</head>
<body class="gradient-bg min-h-screen text-white">
    <!-- Navigation -->
    <nav class="py-4 px-6 glass-card sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                    <i class="fas fa-user-shield text-white text-xl"></i>
                </div>
                <span class="text-white font-bold text-xl">Privacy Policy</span>
            </div>
            <div class="flex items-center space-x-6">
                <a href="index.php" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-home mr-2"></i>Home
                </a>
                <a href="about.php" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-info-circle mr-2"></i>About
                </a>
                <a href="features.php" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-star mr-2"></i>Features
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

    <!-- Header -->
    <section class="py-12 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-12">
                <div class="inline-flex items-center px-4 py-2 rounded-full bg-gradient-to-r from-blue-500/20 to-purple-600/20 backdrop-blur-sm mb-6">
                    <div class="h-2 w-2 rounded-full bg-green-400 mr-2 animate-pulse"></div>
                    <span class="text-blue-300 text-sm">GDPR & FERPA Compliant</span>
                </div>
                
                <h1 class="text-5xl md:text-6xl font-bold mb-6">
                    Privacy <span class="gradient-text">Policy</span>
                </h1>
                
                <p class="text-xl text-white/70 max-w-3xl mx-auto mb-8 leading-relaxed">
                    This Privacy Policy explains how the Advanced Campus Attendance System collects, 
                    uses, discloses, and safeguards your information in compliance with international 
                    data protection regulations.
                </p>
                
                <div class="flex flex-wrap justify-center gap-4 mt-8">
                    <span class="compliance-badge data-badge bg-green-500/20 text-green-300">
                        <i class="fas fa-shield-check mr-2"></i>GDPR Compliant
                    </span>
                    <span class="compliance-badge data-badge bg-blue-500/20 text-blue-300">
                        <i class="fas fa-graduation-cap mr-2"></i>FERPA Compliant
                    </span>
                    <span class="compliance-badge data-badge bg-purple-500/20 text-purple-300">
                        <i class="fas fa-lock mr-2"></i>End-to-End Encrypted
                    </span>
                    <span class="compliance-badge data-badge bg-red-500/20 text-red-300">
                        <i class="fas fa-microchip mr-2"></i>Hardware Secured
                    </span>
                </div>
            </div>
            
            <!-- Last Updated & Version -->
            <div class="glass-card rounded-2xl p-6 mb-12">
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-400 mb-2">v3.0</div>
                        <div class="text-white/70">Policy Version</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-400 mb-2"><?= date('F d, Y') ?></div>
                        <div class="text-white/70">Last Updated</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-400 mb-2">A+</div>
                        <div class="text-white/70">Security Rating</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="py-8 px-4">
        <div class="max-w-7xl mx-auto">
            <!-- Table of Contents -->
            <div class="glass-card rounded-2xl p-8 mb-12">
                <h2 class="text-2xl font-bold text-white mb-6">
                    <i class="fas fa-list-ol mr-3"></i>Table of Contents
                </h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a href="#data-collection" class="flex items-center p-3 hover:bg-white/5 rounded-lg transition">
                        <div class="h-8 w-8 rounded-full bg-blue-500/20 flex items-center justify-center mr-3">
                            <span class="text-blue-300 font-bold">1</span>
                        </div>
                        <span class="text-white/80 hover:text-white">Data We Collect</span>
                    </a>
                    <a href="#data-use" class="flex items-center p-3 hover:bg-white/5 rounded-lg transition">
                        <div class="h-8 w-8 rounded-full bg-green-500/20 flex items-center justify-center mr-3">
                            <span class="text-green-300 font-bold">2</span>
                        </div>
                        <span class="text-white/80 hover:text-white">How We Use Data</span>
                    </a>
                    <a href="#data-protection" class="flex items-center p-3 hover:bg-white/5 rounded-lg transition">
                        <div class="h-8 w-8 rounded-full bg-purple-500/20 flex items-center justify-center mr-3">
                            <span class="text-purple-300 font-bold">3</span>
                        </div>
                        <span class="text-white/80 hover:text-white">Data Protection</span>
                    </a>
                    <a href="#data-sharing" class="flex items-center p-3 hover:bg-white/5 rounded-lg transition">
                        <div class="h-8 w-8 rounded-full bg-yellow-500/20 flex items-center justify-center mr-3">
                            <span class="text-yellow-300 font-bold">4</span>
                        </div>
                        <span class="text-white/80 hover:text-white">Data Sharing</span>
                    </a>
                    <a href="#your-rights" class="flex items-center p-3 hover:bg-white/5 rounded-lg transition">
                        <div class="h-8 w-8 rounded-full bg-red-500/20 flex items-center justify-center mr-3">
                            <span class="text-red-300 font-bold">5</span>
                        </div>
                        <span class="text-white/80 hover:text-white">Your Rights</span>
                    </a>
                    <a href="#contact" class="flex items-center p-3 hover:bg-white/5 rounded-lg transition">
                        <div class="h-8 w-8 rounded-full bg-cyan-500/20 flex items-center justify-center mr-3">
                            <span class="text-cyan-300 font-bold">6</span>
                        </div>
                        <span class="text-white/80 hover:text-white">Contact DPO</span>
                    </a>
                </div>
            </div>

            <!-- Section 1: Data We Collect -->
            <div id="data-collection" class="privacy-section mb-16">
                <div class="flex items-center mb-8">
                    <div class="h-12 w-12 rounded-full bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center mr-4">
                        <span class="text-white font-bold text-xl">1</span>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-white">Data We Collect</h2>
                        <p class="text-white/70">Information collected for academic attendance purposes</p>
                    </div>
                </div>
                
                <div class="glass-card rounded-2xl p-8 mb-8">
                    <h3 class="text-xl font-bold text-white mb-6">Personal Identification Data</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-bold text-blue-300 mb-4">Students</h4>
                            <ul class="space-y-3">
                                <li class="flex items-center text-white/80">
                                    <i class="fas fa-id-card text-blue-400 mr-3"></i>
                                    <span>Roll Number & Academic ID</span>
                                </li>
                                <li class="flex items-center text-white/80">
                                    <i class="fas fa-user text-blue-400 mr-3"></i>
                                    <span>Full Name & Photograph</span>
                                </li>
                                <li class="flex items-center text-white/80">
                                    <i class="fas fa-university text-blue-400 mr-3"></i>
                                    <span>Branch, Year, Section</span>
                                </li>
                                <li class="flex items-center text-white/80">
                                    <i class="fas fa-microchip text-blue-400 mr-3"></i>
                                    <span>Device Fingerprint (Hash)</span>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-bold text-purple-300 mb-4">Faculty & Staff</h4>
                            <ul class="space-y-3">
                                <li class="flex items-center text-white/80">
                                    <i class="fas fa-chalkboard-teacher text-purple-400 mr-3"></i>
                                    <span>Employee ID & Department</span>
                                </li>
                                <li class="flex items-center text-white/80">
                                    <i class="fas fa-book text-purple-400 mr-3"></i>
                                    <span>Assigned Subjects & Courses</span>
                                </li>
                                <li class="flex items-center text-white/80">
                                    <i class="fas fa-clock text-purple-400 mr-3"></i>
                                    <span>Timetable & Schedule Data</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card rounded-2xl p-8">
                    <h3 class="text-xl font-bold text-white mb-6">Operational & Technical Data</h3>
                    <div class="space-y-6">
                        <div class="security-level level-1">
                            <h4 class="font-bold text-green-300 mb-3">
                                <i class="fas fa-wifi mr-2"></i>Network & Location Data
                            </h4>
                            <ul class="text-white/80 space-y-2 ml-6">
                                <li>• Campus WiFi subnet information (for attendance validation)</li>
                                <li>• Encrypted GPS coordinates (50m radius, fallback only)</li>
                                <li>• IP address for security logging</li>
                                <li>• Device network information</li>
                            </ul>
                        </div>
                        
                        <div class="security-level level-2">
                            <h4 class="font-bold text-blue-300 mb-3">
                                <i class="fas fa-history mr-2"></i>Attendance & Academic Data
                            </h4>
                            <ul class="text-white/80 space-y-2 ml-6">
                                <li>• Class attendance records (Present/Late/Absent)</li>
                                <li>• Leave applications with supporting documents</li>
                                <li>• Academic performance metrics</li>
                                <li>• Red zone status and interventions</li>
                            </ul>
                        </div>
                        
                        <div class="security-level level-3">
                            <h4 class="font-bold text-purple-300 mb-3">
                                <i class="fas fa-cogs mr-2"></i>System & Security Data
                            </h4>
                            <ul class="text-white/80 space-y-2 ml-6">
                                <li>• Cryptographic device fingerprints (hashed)</li>
                                <li>• Login timestamps and session data</li>
                                <li>• Audit trails of all system actions</li>
                                <li>• Security event logs</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mt-8 pt-8 border-t border-white/10">
                        <div class="highlight-box">
                            <p class="text-white/90">
                                <i class="fas fa-info-circle text-blue-400 mr-2"></i>
                                <strong>Important:</strong> We do NOT collect sensitive personal data such as biometric information, 
                                health records (except medical leave documents), financial information, or private communications.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: How We Use Data -->
            <div id="data-use" class="privacy-section mb-16">
                <div class="flex items-center mb-8">
                    <div class="h-12 w-12 rounded-full bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center mr-4">
                        <span class="text-white font-bold text-xl">2</span>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-white">How We Use Your Data</h2>
                        <p class="text-white/70">Legitimate academic purposes for data processing</p>
                    </div>
                </div>
                
                <div class="glass-card rounded-2xl p-8">
                    <div class="overflow-x-auto">
                        <table class="policy-table w-full">
                            <thead>
                                <tr>
                                    <th class="p-4 text-left">Purpose</th>
                                    <th class="p-4 text-left">Data Used</th>
                                    <th class="p-4 text-left">Legal Basis</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="p-4 border-b border-white/10">Attendance Tracking</td>
                                    <td class="p-4 border-b border-white/10">
                                        <span class="data-badge bg-blue-500/20 text-blue-300">Roll No</span>
                                        <span class="data-badge bg-green-500/20 text-green-300">Device Hash</span>
                                        <span class="data-badge bg-purple-500/20 text-purple-300">Location</span>
                                    </td>
                                    <td class="p-4 border-b border-white/10">Academic Requirement</td>
                                </tr>
                                <tr>
                                    <td class="p-4 border-b border-white/10">Proxy Prevention</td>
                                    <td class="p-4 border-b border-white/10">
                                        <span class="data-badge bg-red-500/20 text-red-300">Device Lock</span>
                                        <span class="data-badge bg-yellow-500/20 text-yellow-300">WiFi Subnet</span>
                                    </td>
                                    <td class="p-4 border-b border-white/10">Academic Integrity</td>
                                </tr>
                                <tr>
                                    <td class="p-4 border-b border-white/10">Academic Reporting</td>
                                    <td class="p-4 border-b border-white/10">
                                        <span class="data-badge bg-green-500/20 text-green-300">Attendance Records</span>
                                        <span class="data-badge bg-cyan-500/20 text-cyan-300">Performance Data</span>
                                    </td>
                                    <td class="p-4 border-b border-white/10">Educational Purpose</td>
                                </tr>
                                <tr>
                                    <td class="p-4">System Security</td>
                                    <td class="p-4">
                                        <span class="data-badge bg-purple-500/20 text-purple-300">Audit Logs</span>
                                        <span class="data-badge bg-pink-500/20 text-pink-300">Security Events</span>
                                    </td>
                                    <td class="p-4">Legitimate Interest</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-8">
                        <h3 class="text-xl font-bold text-white mb-6">Data Processing Principles</h3>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="legal-point">
                                <h4 class="font-bold text-white mb-3">Lawfulness & Transparency</h4>
                                <p class="text-white/70">All data processing is conducted under legitimate academic purposes with complete transparency.</p>
                            </div>
                            <div class="legal-point">
                                <h4 class="font-bold text-white mb-3">Purpose Limitation</h4>
                                <p class="text-white/70">Data collected only for specified, explicit, and legitimate academic purposes.</p>
                            </div>
                            <div class="legal-point">
                                <h4 class="font-bold text-white mb-3">Data Minimization</h4>
                                <p class="text-white/70">Only necessary data is collected and processed for attendance purposes.</p>
                            </div>
                            <div class="legal-point">
                                <h4 class="font-bold text-white mb-3">Storage Limitation</h4>
                                <p class="text-white/70">Personal data retained only as long as necessary for academic requirements.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Data Protection -->
            <div id="data-protection" class="privacy-section mb-16">
                <div class="flex items-center mb-8">
                    <div class="h-12 w-12 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center mr-4">
                        <span class="text-white font-bold text-xl">3</span>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-white">Data Protection & Security</h2>
                        <p class="text-white/70">Enterprise-grade security measures protecting your information</p>
                    </div>
                </div>
                
                <div class="glass-card rounded-2xl p-8">
                    <h3 class="text-xl font-bold text-white mb-8">Security Implementation Layers</h3>
                    <div class="grid md:grid-cols-3 gap-8 mb-12">
                        <div class="text-center">
                            <div class="h-20 w-20 rounded-2xl bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-lock text-white text-2xl"></i>
                            </div>
                            <h4 class="text-xl font-bold text-white mb-3">Encryption</h4>
                            <ul class="text-white/70 space-y-2">
                                <li>• Argon2id Password Hashing</li>
                                <li>• AES-256 Data Encryption</li>
                                <li>• TLS 1.3 In Transit</li>
                                <li>• HTTPS Only Access</li>
                            </ul>
                        </div>
                        
                        <div class="text-center">
                            <div class="h-20 w-20 rounded-2xl bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-shield-alt text-white text-2xl"></i>
                            </div>
                            <h4 class="text-xl font-bold text-white mb-3">Access Control</h4>
                            <ul class="text-white/70 space-y-2">
                                <li>• Role-Based Permissions</li>
                                <li>• Multi-factor Authentication</li>
                                <li>• IP Whitelisting</li>
                                <li>• Session Management</li>
                            </ul>
                        </div>
                        
                        <div class="text-center">
                            <div class="h-20 w-20 rounded-2xl bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-database text-white text-2xl"></i>
                            </div>
                            <h4 class="text-xl font-bold text-white mb-3">Database Security</h4>
                            <ul class="text-white/70 space-y-2">
                                <li>• PostgreSQL Row Security</li>
                                <li>• Data Partitioning</li>
                                <li>• Regular Backups</li>
                                <li>• Audit Trails</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-r from-blue-900/30 to-purple-900/30 rounded-2xl p-6 border border-blue-500/20">
                        <h4 class="text-xl font-bold text-blue-300 mb-4">
                            <i class="fas fa-microchip mr-2"></i>Hardware Security Feature
                        </h4>
                        <p class="text-white/80 mb-4">
                            The cryptographic device fingerprint is a one-way hash generated from multiple 
                            hardware parameters. This hash cannot be reverse-engineered to identify your 
                            specific device and is used only for 1:1 matching.
                        </p>
                        <div class="flex items-center text-white/60">
                            <i class="fas fa-info-circle mr-2"></i>
                            <span class="text-sm">Device fingerprint = SHA256(CPU_ID + MAC + Storage_ID + Salt)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Data Sharing -->
            <div id="data-sharing" class="privacy-section mb-16">
                <div class="flex items-center mb-8">
                    <div class="h-12 w-12 rounded-full bg-gradient-to-r from-yellow-500 to-orange-500 flex items-center justify-center mr-4">
                        <span class="text-white font-bold text-xl">4</span>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-white">Data Sharing & Disclosure</h2>
                        <p class="text-white/70">When and with whom we share your information</p>
                    </div>
                </div>
                
                <div class="glass-card rounded-2xl p-8">
                    <div class="grid md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-xl font-bold text-white mb-6">Internal Sharing</h3>
                            <div class="space-y-4">
                                <div class="flex items-start">
                                    <div class="h-10 w-10 rounded-full bg-green-500/20 flex items-center justify-center mr-4 mt-1">
                                        <i class="fas fa-user-graduate text-green-400"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-white">Class Teachers</h4>
                                        <p class="text-white/70 text-sm">Attendance records of students in their classes only</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="h-10 w-10 rounded-full bg-blue-500/20 flex items-center justify-center mr-4 mt-1">
                                        <i class="fas fa-user-shield text-blue-400"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-white">Department Heads</h4>
                                        <p class="text-white/70 text-sm">Aggregate department statistics and reports</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="h-10 w-10 rounded-full bg-purple-500/20 flex items-center justify-center mr-4 mt-1">
                                        <i class="fas fa-chart-line text-purple-400"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-white">Academic Committee</h4>
                                        <p class="text-white/70 text-sm">Anonymized academic performance data</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-xl font-bold text-white mb-6">External Sharing</h3>
                            <div class="space-y-4">
                                <div class="flex items-start">
                                    <div class="h-10 w-10 rounded-full bg-red-500/20 flex items-center justify-center mr-4 mt-1">
                                        <i class="fas fa-gavel text-red-400"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-white">Legal Requirements</h4>
                                        <p class="text-white/70 text-sm">When required by law, court order, or government request</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="h-10 w-10 rounded-full bg-yellow-500/20 flex items-center justify-center mr-4 mt-1">
                                        <i class="fas fa-shield-alt text-yellow-400"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-white">Security Emergencies</h4>
                                        <p class="text-white/70 text-sm">To protect safety, rights, or property of individuals</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="h-10 w-10 rounded-full bg-cyan-500/20 flex items-center justify-center mr-4 mt-1">
                                        <i class="fas fa-handshake text-cyan-400"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-white">Academic Partners</h4>
                                        <p class="text-white/70 text-sm">With explicit consent for specific academic purposes</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 pt-8 border-t border-white/10">
                        <div class="highlight-box">
                            <p class="text-white/90">
                                <i class="fas fa-ban text-red-400 mr-2"></i>
                                <strong>We DO NOT:</strong> Sell, rent, or trade your personal data to third parties. 
                                We DO NOT share data with advertisers or marketing companies.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 5: Your Rights -->
            <div id="your-rights" class="privacy-section mb-16">
                <div class="flex items-center mb-8">
                    <div class="h-12 w-12 rounded-full bg-gradient-to-r from-red-500 to-rose-500 flex items-center justify-center mr-4">
                        <span class="text-white font-bold text-xl">5</span>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-white">Your Data Protection Rights</h2>
                        <p class="text-white/70">Rights you have under GDPR and other regulations</p>
                    </div>
                </div>
                
                <div class="glass-card rounded-2xl p-8">
                    <div class="grid md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-xl font-bold text-white mb-6">Core Rights</h3>
                            <div class="space-y-6">
                                <div>
                                    <h4 class="font-bold text-green-300 mb-2">Right to Access</h4>
                                    <p class="text-white/70">Request copies of your personal data stored in our system.</p>
                                </div>
                                <div>
                                    <h4 class="font-bold text-blue-300 mb-2">Right to Rectification</h4>
                                    <p class="text-white/70">Request correction of inaccurate or incomplete information.</p>
                                </div>
                                <div>
                                    <h4 class="font-bold text-purple-300 mb-2">Right to Erasure</h4>
                                    <p class="text-white/70">Request deletion of your personal data under certain conditions.</p>
                                </div>
                                <div>
                                    <h4 class="font-bold text-yellow-300 mb-2">Right to Restrict Processing</h4>
                                    <p class="text-white/70">Request restriction of processing under certain conditions.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-xl font-bold text-white mb-6">Additional Rights</h3>
                            <div class="space-y-6">
                                <div>
                                    <h4 class="font-bold text-red-300 mb-2">Right to Object</h4>
                                    <p class="text-white/70">Object to processing of your personal data.</p>
                                </div>
                                <div>
                                    <h4 class="font-bold text-cyan-300 mb-2">Right to Data Portability</h4>
                                    <p class="text-white/70">Request transfer of your data to another organization.</p>
                                </div>
                                <div>
                                    <h4 class="font-bold text-pink-300 mb-2">Right to Withdraw Consent</h4>
                                    <p class="text-white/70">Withdraw consent at any time where processing is based on consent.</p>
                                </div>
                                <div>
                                    <h4 class="font-bold text-orange-300 mb-2">Right to Complain</h4>
                                    <p class="text-white/70">Lodge a complaint with a supervisory authority.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-8 bg-gradient-to-r from-green-900/30 to-emerald-900/30 rounded-2xl p-6 border border-green-500/20">
                        <div class="flex items-center mb-4">
                            <i class="fas fa-user-cog text-green-400 text-2xl mr-4"></i>
                            <div>
                                <h4 class="text-xl font-bold text-green-300">Exercise Your Rights</h4>
                                <p class="text-white/70">To exercise any of these rights, contact our Data Protection Officer</p>
                            </div>
                        </div>
                        <p class="text-white/80">
                            Most requests will be processed within 30 days. For device fingerprint reset 
                            (due to lost/stolen device), contact the Master Admin through official channels.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 6: Contact -->
            <div id="contact" class="privacy-section mb-16">
                <div class="flex items-center mb-8">
                    <div class="h-12 w-12 rounded-full bg-gradient-to-r from-cyan-500 to-blue-500 flex items-center justify-center mr-4">
                        <span class="text-white font-bold text-xl">6</span>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-white">Contact & Data Protection Officer</h2>
                        <p class="text-white/70">How to contact us regarding privacy matters</p>
                    </div>
                </div>
                
                <div class="glass-card rounded-2xl p-8">
                    <div class="grid md:grid-cols-2 gap-8 mb-8">
                        <div>
                            <h3 class="text-xl font-bold text-white mb-6">Data Protection Officer</h3>
                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <div class="h-12 w-12 rounded-xl bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center mr-4">
                                        <i class="fas fa-user-tie text-white"></i>
                                    </div>
                                    <div>
                                        <div class="font-bold text-white">Dr. Sarah Johnson</div>
                                        <div class="text-white/70 text-sm">Data Protection Officer</div>
                                    </div>
                                </div>
                                <div class="flex items-center text-white/80">
                                    <i class="fas fa-envelope mr-3 text-blue-400"></i>
                                    <span>dpo@academicinstitution.edu</span>
                                </div>
                                <div class="flex items-center text-white/80">
                                    <i class="fas fa-phone mr-3 text-green-400"></i>
                                    <span>+1 (555) 123-4567 Ext. 101</span>
                                </div>
                                <div class="flex items-center text-white/80">
                                    <i class="fas fa-building mr-3 text-purple-400"></i>
                                    <span>Admin Block, Room 305, Campus Drive</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-xl font-bold text-white mb-6">Privacy Contact Hours</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-white/80">Monday - Friday</span>
                                    <span class="text-green-300">9:00 AM - 5:00 PM</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-white/80">Saturday</span>
                                    <span class="text-yellow-300">10:00 AM - 2:00 PM</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-white/80">Sunday</span>
                                    <span class="text-red-300">Emergency Only</span>
                                </div>
                            </div>
                            <div class="mt-6 p-4 bg-white/5 rounded-xl">
                                <p class="text-white/70 text-sm">
                                    <i class="fas fa-clock mr-2"></i>
                                    Emergency privacy concerns can be emailed 24/7 with response within 4 hours.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-t border-white/10 pt-8">
                        <h3 class="text-xl font-bold text-white mb-6">Policy Updates & Notifications</h3>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-bold text-white mb-3">Update Notification</h4>
                                <p class="text-white/70">
                                    We will notify users of material changes to this Privacy Policy 
                                    via email and system announcements at least 30 days before changes take effect.
                                </p>
                            </div>
                            <div>
                                <h4 class="font-bold text-white mb-3">Version History</h4>
                                <ul class="text-white/70 space-y-2">
                                    <li>• v3.0 (Current): <?= date('F d, Y') ?></li>
                                    <li>• v2.1: January 15, 2025</li>
                                    <li>• v2.0: August 20, 2024</li>
                                    <li>• v1.0: March 10, 2024</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Acceptance Section -->
    <section class="py-12 px-4">
        <div class="max-w-4xl mx-auto">
            <div class="glass-card rounded-3xl p-12 text-center">
                <div class="h-16 w-16 rounded-2xl bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-file-signature text-white text-2xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-white mb-6">Acceptance of Terms</h2>
                <p class="text-xl text-white/70 mb-8 max-w-2xl mx-auto">
                    By using the Advanced Campus Attendance System, you acknowledge that you have read, 
                    understood, and agree to be bound by this Privacy Policy.
                </p>
                <div class="flex flex-col sm:flex-row gap-6 justify-center">
                    <a href="index.php" 
                       class="px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl hover:from-blue-600 hover:to-purple-700 transition font-bold text-lg">
                        <i class="fas fa-home mr-3"></i>Return Home
                    </a>
                    <a href="help.php" 
                       class="px-8 py-4 glass-card text-white rounded-xl hover:bg-white/10 transition font-bold text-lg">
                        <i class="fas fa-question-circle mr-3"></i>Help Center
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
                            <i class="fas fa-user-shield text-white text-xl"></i>
                        </div>
                        <span class="text-white font-bold text-xl">Privacy First</span>
                    </div>
                    <p class="text-white/70">
                        Committed to protecting your privacy and securing your academic data 
                        with enterprise-grade security measures.
                    </p>
                </div>
                
                <div>
                    <h4 class="text-white font-bold mb-6">Legal Documents</h4>
                    <ul class="space-y-3">
                        <li><a href="privacy_policy.php" class="text-white/70 hover:text-white transition">Privacy Policy</a></li>
                        <li><a href="terms.php" class="text-white/70 hover:text-white transition">Terms of Service</a></li>
                        <li><a href="#" class="text-white/70 hover:text-white transition">Cookie Policy</a></li>
                        <li><a href="#" class="text-white/70 hover:text-white transition">Data Processing Agreement</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-white font-bold mb-6">Compliance Certifications</h4>
                    <ul class="space-y-3 text-white/70">
                        <li>• GDPR Compliant</li>
                        <li>• FERPA Compliant</li>
                        <li>• ISO 27001 Certified</li>
                        <li>• SOC 2 Type II</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-white/10 pt-8 text-center">
                <p class="text-white/60">
                    &copy; <?= date('Y') ?> Advanced Campus Attendance System. All rights reserved.
                </p>
                <p class="text-white/40 text-sm mt-2">
                    This Privacy Policy is effective as of <?= date('F d, Y') ?>. Last updated <?= date('F d, Y') ?>.
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scrolling for table of contents
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href.startsWith('#')) {
                    e.preventDefault();
                    const targetId = href.substring(1);
                    const targetElement = document.getElementById(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
        
        // Add active section highlighting
        const sections = document.querySelectorAll('.privacy-section');
        const navLinks = document.querySelectorAll('a[href^="#"]');
        
        window.addEventListener('scroll', () => {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (pageYOffset >= sectionTop - 150) {
                    current = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('text-white');
                link.classList.add('text-white/70');
                if (link.getAttribute('href') === `#${current}`) {
                    link.classList.remove('text-white/70');
                    link.classList.add('text-white');
                }
            });
        });
        
        // Compliance badge animation
        document.addEventListener('DOMContentLoaded', function() {
            const badges = document.querySelectorAll('.compliance-badge');
            badges.forEach((badge, index) => {
                badge.style.animationDelay = `${index * 0.5}s`;
            });
        });
    </script>
</body>
</html>