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
    <title>Terms of Service - Advanced Campus Attendance System</title>
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
        
        .term-section {
            position: relative;
            padding-left: 2.5rem;
            margin-bottom: 3rem;
        }
        
        .term-section::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            border-radius: 2px;
            background: linear-gradient(to bottom, #3b82f6, #8b5cf6);
        }
        
        .clause-number {
            position: absolute;
            left: -2.5rem;
            top: 0;
            width: 3rem;
            height: 3rem;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 1.25rem;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .warning-box {
            border-left: 4px solid #ef4444;
            padding: 1.5rem;
            margin: 1.5rem 0;
            background: linear-gradient(90deg, rgba(239, 68, 68, 0.1), transparent);
        }
        
        .info-box {
            border-left: 4px solid #3b82f6;
            padding: 1.5rem;
            margin: 1.5rem 0;
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.1), transparent);
        }
        
        .agreement-box {
            border: 2px solid rgba(59, 130, 246, 0.3);
            border-radius: 1rem;
            padding: 2rem;
            margin: 2rem 0;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05), rgba(139, 92, 246, 0.05));
        }
        
        .user-role-tag {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin: 0 0.25rem;
        }
        
        .student-tag { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .teacher-tag { background: rgba(139, 92, 246, 0.2); color: #a78bfa; }
        .admin-tag { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        
        .penalty-level {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin: 0.5rem 0;
            border-left: 4px solid;
        }
        
        .level-1 { background: rgba(239, 68, 68, 0.1); border-color: #ef4444; }
        .level-2 { background: rgba(245, 158, 11, 0.1); border-color: #f59e0b; }
        .level-3 { background: rgba(59, 130, 246, 0.1); border-color: #3b82f6; }
        
        .terms-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        
        .terms-table th {
            position: sticky;
            top: 0;
            background: rgba(30, 58, 138, 0.9);
            backdrop-filter: blur(10px);
        }
        
        .terms-table tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .acceptance-check {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            margin: 1rem 0;
        }
        
        .acceptance-check input[type="checkbox"] {
            width: 1.25rem;
            height: 1.25rem;
        }
        
        .sticky-toc {
            position: sticky;
            top: 120px;
            max-height: calc(100vh - 140px);
            overflow-y: auto;
        }
        
        .toc-item {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin: 0.25rem 0;
            transition: all 0.3s ease;
        }
        
        .toc-item:hover {
            background: rgba(59, 130, 246, 0.1);
            transform: translateX(5px);
        }
        
        .toc-item.active {
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.2), transparent);
            border-left: 3px solid #3b82f6;
            font-weight: 600;
        }
        
        .accept-btn-pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }
        
        .highlighted-text {
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen text-white">
    <!-- Navigation -->
    <nav class="py-4 px-6 glass-card sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                    <i class="fas fa-file-contract text-white text-xl"></i>
                </div>
                <span class="text-white font-bold text-xl">Terms of Service</span>
            </div>
            <div class="flex items-center space-x-6">
                <a href="index.php" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-home mr-2"></i>Home
                </a>
                <a href="about.php" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-info-circle mr-2"></i>About
                </a>
                <a href="privacy.php" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-user-shield mr-2"></i>Privacy
                </a>
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="text-white/80 hover:text-white transition">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                    </a>
                    <span class="text-white/60">|</span>
                    <span class="text-white">
                        <i class="fas fa-user mr-2"></i><?= htmlspecialchars($userName) ?>
                        <span class="ml-2 text-sm <?php 
                            echo $userRole == 'student' ? 'student-tag' : 
                                 ($userRole == 'teacher' ? 'teacher-tag' : 
                                 ($userRole == 'admin' ? 'admin-tag' : 'bg-gray-500/20 text-gray-300')); 
                        ?>">
                            <?= ucfirst($userRole ?? 'Guest') ?>
                        </span>
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

    <!-- Main Container -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Table of Contents -->
            <div class="lg:col-span-1">
                <div class="sticky-toc">
                    <div class="glass-card rounded-2xl p-6 mb-6">
                        <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                            <i class="fas fa-list-ol mr-3 text-blue-400"></i>Table of Contents
                        </h3>
                        <nav class="space-y-1">
                            <a href="#definitions" class="toc-item block text-white/80 hover:text-white transition">
                                <i class="fas fa-book mr-2 text-blue-400"></i>1. Definitions
                            </a>
                            <a href="#registration" class="toc-item block text-white/80 hover:text-white transition">
                                <i class="fas fa-user-plus mr-2 text-green-400"></i>2. Account Registration
                            </a>
                            <a href="#acceptable-use" class="toc-item block text-white/80 hover:text-white transition">
                                <i class="fas fa-check-circle mr-2 text-yellow-400"></i>3. Acceptable Use
                            </a>
                            <a href="#attendance-rules" class="toc-item block text-white/80 hover:text-white transition">
                                <i class="fas fa-clock mr-2 text-red-400"></i>4. Attendance Rules
                            </a>
                            <a href="#intellectual-property" class="toc-item block text-white/80 hover:text-white transition">
                                <i class="fas fa-copyright mr-2 text-purple-400"></i>5. Intellectual Property
                            </a>
                            <a href="#termination" class="toc-item block text-white/80 hover:text-white transition">
                                <i class="fas fa-ban mr-2 text-red-400"></i>6. Termination
                            </a>
                            <a href="#liability" class="toc-item block text-white/80 hover:text-white transition">
                                <i class="fas fa-balance-scale mr-2 text-gray-400"></i>7. Limitation of Liability
                            </a>
                            <a href="#disputes" class="toc-item block text-white/80 hover:text-white transition">
                                <i class="fas fa-gavel mr-2 text-orange-400"></i>8. Dispute Resolution
                            </a>
                            <a href="#amendments" class="toc-item block text-white/80 hover:text-white transition">
                                <i class="fas fa-history mr-2 text-blue-400"></i>9. Amendments
                            </a>
                            <a href="#contact" class="toc-item block text-white/80 hover:text-white transition">
                                <i class="fas fa-envelope mr-2 text-green-400"></i>10. Contact
                            </a>
                        </nav>
                    </div>

                    <!-- Quick Stats -->
                    <div class="glass-card rounded-2xl p-6">
                        <h3 class="text-xl font-bold text-white mb-4">
                            <i class="fas fa-chart-bar mr-2"></i>Quick Stats
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-white/70">Word Count</span>
                                    <span class="font-bold text-white">4,832</span>
                                </div>
                                <div class="h-1 bg-white/10 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 rounded-full" style="width: 60%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-white/70">Clauses</span>
                                    <span class="font-bold text-white">10</span>
                                </div>
                                <div class="h-1 bg-white/10 rounded-full overflow-hidden">
                                    <div class="h-full bg-green-500 rounded-full" style="width: 100%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-white/70">Last Updated</span>
                                    <span class="font-bold text-white"><?= date('M d, Y') ?></span>
                                </div>
                                <div class="h-1 bg-white/10 rounded-full overflow-hidden">
                                    <div class="h-full bg-purple-500 rounded-full" style="width: 100%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-3">
                <!-- Header -->
                <section class="mb-12">
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center px-4 py-2 rounded-full bg-gradient-to-r from-blue-500/20 to-purple-600/20 backdrop-blur-sm mb-6">
                            <div class="h-2 w-2 rounded-full bg-green-400 mr-2 animate-pulse"></div>
                            <span class="text-blue-300 text-sm">Legal Agreement</span>
                        </div>
                        
                        <h1 class="text-4xl md:text-5xl font-bold mb-6">
                            Terms of <span class="gradient-text">Service</span>
                        </h1>
                        
                        <p class="text-lg text-white/70 max-w-3xl mx-auto mb-6 leading-relaxed">
                            These Terms of Service govern your use of the Advanced Campus Attendance System (ACAS). 
                            By accessing or using our services, you agree to be bound by these terms.
                        </p>
                        
                        <div class="flex flex-wrap justify-center gap-3 mt-6">
                            <span class="user-role-tag student-tag">
                                <i class="fas fa-user-graduate mr-1"></i> Students
                            </span>
                            <span class="user-role-tag teacher-tag">
                                <i class="fas fa-chalkboard-teacher mr-1"></i> Teachers
                            </span>
                            <span class="user-role-tag admin-tag">
                                <i class="fas fa-user-shield mr-1"></i> Administrators
                            </span>
                            <span class="px-3 py-1 bg-yellow-500/20 text-yellow-300 rounded-full text-sm font-medium">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Binding Agreement
                            </span>
                        </div>
                    </div>
                    
                    <!-- Effective Date & Version -->
                    <div class="glass-card rounded-2xl p-6 mb-8">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center p-4 rounded-xl bg-gradient-to-br from-blue-500/10 to-transparent border border-blue-500/20">
                                <div class="text-2xl font-bold text-blue-400 mb-2">v3.0</div>
                                <div class="text-white/70 text-sm">Terms Version</div>
                            </div>
                            <div class="text-center p-4 rounded-xl bg-gradient-to-br from-green-500/10 to-transparent border border-green-500/20">
                                <div class="text-2xl font-bold text-green-400 mb-2"><?= date('F d, Y') ?></div>
                                <div class="text-white/70 text-sm">Effective Date</div>
                            </div>
                            <div class="text-center p-4 rounded-xl bg-gradient-to-br from-purple-500/10 to-transparent border border-purple-500/20">
                                <div class="text-2xl font-bold text-purple-400 mb-2">EN</div>
                                <div class="text-white/70 text-sm">Language</div>
                            </div>
                            <div class="text-center p-4 rounded-xl bg-gradient-to-br from-red-500/10 to-transparent border border-red-500/20">
                                <div class="text-2xl font-bold text-red-400 mb-2">18+</div>
                                <div class="text-white/70 text-sm">Age Requirement</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Acceptance Notice -->
                    <div class="agreement-box">
                        <div class="flex items-center mb-6">
                            <div class="h-12 w-12 rounded-xl bg-gradient-to-r from-red-500 to-orange-500 flex items-center justify-center mr-4">
                                <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Important Notice</h3>
                                <p class="text-white/70">These terms constitute a legally binding agreement</p>
                            </div>
                        </div>
                        <div class="acceptance-check">
                            <input type="checkbox" id="readTerms" onclick="toggleAcceptButton()">
                            <label for="readTerms" class="text-white/80">
                                I acknowledge that I have read, understood, and agree to be bound by these Terms of Service.
                                <span class="text-red-400">*</span>
                            </label>
                        </div>
                        <div class="text-center">
                            <button id="acceptButton" disabled 
                                    class="px-8 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl hover:from-blue-600 hover:to-purple-700 transition font-bold text-lg opacity-50 cursor-not-allowed">
                                <i class="fas fa-check-circle mr-3"></i>Acknowledge & Continue
                            </button>
                            <p class="text-white/50 text-sm mt-3">
                                By continuing to use the system, you accept these terms
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Terms Content -->
                <div class="space-y-16">
                    <!-- 1. Definitions -->
                    <div id="definitions" class="term-section">
                        <div class="clause-number">1</div>
                        <h2 class="text-3xl font-bold text-white mb-6">Definitions</h2>
                        
                        <div class="glass-card rounded-2xl p-8 mb-6">
                            <h3 class="text-xl font-bold text-white mb-6">Key Terms</h3>
                            <div class="overflow-x-auto">
                                <table class="terms-table">
                                    <thead>
                                        <tr>
                                            <th class="p-4 text-left">Term</th>
                                            <th class="p-4 text-left">Definition</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="p-4 border-b border-white/10">"System"</td>
                                            <td class="p-4 border-b border-white/10">Advanced Campus Attendance System (ACAS)</td>
                                        </tr>
                                        <tr>
                                            <td class="p-4 border-b border-white/10">"User"</td>
                                            <td class="p-4 border-b border-white/10">Any individual registered in the system (Student, Teacher, Admin)</td>
                                        </tr>
                                        <tr>
                                            <td class="p-4 border-b border-white/10">"Device Locking"</td>
                                            <td class="p-4 border-b border-white/10">Cryptographic 1:1 binding of user identity to hardware device</td>
                                        </tr>
                                        <tr>
                                            <td class="p-4 border-b border-white/10">"Red Zone"</td>
                                            <td class="p-4 border-b border-white/10">Attendance percentage below 35% triggering account restrictions</td>
                                        </tr>
                                        <tr>
                                            <td class="p-4">"Master Admin"</td>
                                            <td class="p-4">Authorized system administrator with elevated privileges</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="info-box">
                            <p class="text-white/90">
                                <i class="fas fa-info-circle text-blue-400 mr-2"></i>
                                These definitions apply throughout these Terms of Service. Capitalized terms have the meanings specified above.
                            </p>
                        </div>
                    </div>

                    <!-- 2. Account Registration -->
                    <div id="registration" class="term-section">
                        <div class="clause-number">2</div>
                        <h2 class="text-3xl font-bold text-white mb-6">Account Registration & Eligibility</h2>
                        
                        <div class="glass-card rounded-2xl p-8">
                            <h3 class="text-xl font-bold text-white mb-6">Registration Requirements</h3>
                            
                            <div class="grid md:grid-cols-2 gap-6 mb-8">
                                <div>
                                    <h4 class="font-bold text-blue-300 mb-4">Student Registration</h4>
                                    <ul class="text-white/80 space-y-3">
                                        <li class="flex items-start">
                                            <i class="fas fa-check text-green-400 mr-3 mt-1"></i>
                                            <span>Valid institutional email address</span>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-check text-green-400 mr-3 mt-1"></i>
                                            <span>Official Roll Number verification</span>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-check text-green-400 mr-3 mt-1"></i>
                                            <span>Active enrollment status confirmation</span>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-check text-green-400 mr-3 mt-1"></i>
                                            <span>Minimum age: 18 years</span>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div>
                                    <h4 class="font-bold text-purple-300 mb-4">Faculty Registration</h4>
                                    <ul class="text-white/80 space-y-3">
                                        <li class="flex items-start">
                                            <i class="fas fa-check text-green-400 mr-3 mt-1"></i>
                                            <span>Employee ID verification</span>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-check text-green-400 mr-3 mt-1"></i>
                                            <span>Department approval</span>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-check text-green-400 mr-3 mt-1"></i>
                                            <span>Background check clearance</span>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-check text-green-400 mr-3 mt-1"></i>
                                            <span>Master Admin approval required</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="warning-box">
                                <div class="flex items-center mb-3">
                                    <i class="fas fa-microchip text-red-400 text-xl mr-3"></i>
                                    <h4 class="text-xl font-bold text-white">Device Locking Agreement</h4>
                                </div>
                                <p class="text-white/90 mb-3">
                                    By registering, you acknowledge and agree to the permanent device locking protocol. 
                                    Your first login device becomes cryptographically bound to your account.
                                </p>
                                <ul class="text-white/80 space-y-2 ml-6">
                                    <li>• Device swaps require Master Admin intervention</li>
                                    <li>• Lost/stolen devices must be reported immediately</li>
                                    <li>• Attempting to bypass device lock violates these terms</li>
                                    <li>• Device fingerprint is non-transferable</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Acceptable Use -->
                    <div id="acceptable-use" class="term-section">
                        <div class="clause-number">3</div>
                        <h2 class="text-3xl font-bold text-white mb-6">Acceptable Use Policy</h2>
                        
                        <div class="glass-card rounded-2xl p-8">
                            <h3 class="text-xl font-bold text-white mb-6">Permitted & Prohibited Activities</h3>
                            
                            <div class="grid md:grid-cols-2 gap-8 mb-8">
                                <div>
                                    <h4 class="font-bold text-green-300 mb-4">
                                        <i class="fas fa-check-circle mr-2"></i>Permitted Uses
                                    </h4>
                                    <ul class="text-white/80 space-y-3">
                                        <li>• Marking your own attendance via QR scan</li>
                                        <li>• Applying for legitimate academic leaves</li>
                                        <li>• Viewing your attendance records</li>
                                        <li>• Teachers: Generating class QR codes</li>
                                        <li>• Teachers: Monitoring student attendance</li>
                                        <li>• Admins: Managing system configurations</li>
                                    </ul>
                                </div>
                                
                                <div>
                                    <h4 class="font-bold text-red-300 mb-4">
                                        <i class="fas fa-ban mr-2"></i>Strictly Prohibited
                                    </h4>
                                    <ul class="text-white/80 space-y-3">
                                        <li>• Proxy attendance marking</li>
                                        <li>• Device fingerprint manipulation</li>
                                        <li>• GPS/WiFi spoofing attempts</li>
                                        <li>• QR code screenshot sharing</li>
                                        <li>• Account sharing or selling</li>
                                        <li>• System vulnerability exploitation</li>
                                        <li>• Data scraping or unauthorized access</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="info-box">
                                <p class="text-white/90">
                                    <i class="fas fa-shield-alt text-blue-400 mr-2"></i>
                                    The system employs multiple security layers to detect and prevent violations. 
                                    All security breach attempts are logged and may result in immediate account termination.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Attendance Rules -->
                    <div id="attendance-rules" class="term-section">
                        <div class="clause-number">4</div>
                        <h2 class="text-3xl font-bold text-white mb-6">Attendance Rules & Protocols</h2>
                        
                        <div class="glass-card rounded-2xl p-8">
                            <h3 class="text-xl font-bold text-white mb-6">Mandatory Attendance Protocols</h3>
                            
                            <div class="mb-8">
                                <h4 class="text-xl font-bold text-white mb-4">Time Window Protocol</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div class="text-center p-4 rounded-xl bg-gradient-to-b from-green-500/10 to-transparent border border-green-500/20">
                                        <div class="text-2xl font-bold text-green-400 mb-2">0-3 min</div>
                                        <div class="font-bold text-white mb-2">Green Zone</div>
                                        <p class="text-white/70 text-sm">Full attendance (PRESENT)</p>
                                    </div>
                                    <div class="text-center p-4 rounded-xl bg-gradient-to-b from-yellow-500/10 to-transparent border border-yellow-500/20">
                                        <div class="text-2xl font-bold text-yellow-400 mb-2">3-7 min</div>
                                        <div class="font-bold text-white mb-2">Yellow Zone</div>
                                        <p class="text-white/70 text-sm">Late (0.5 attendance)</p>
                                    </div>
                                    <div class="text-center p-4 rounded-xl bg-gradient-to-b from-red-500/10 to-transparent border border-red-500/20">
                                        <div class="text-2xl font-bold text-red-400 mb-2">7+ min</div>
                                        <div class="font-bold text-white mb-2">Red Zone</div>
                                        <p class="text-white/70 text-sm">Locked (ABSENT)</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="warning-box">
                                <h4 class="text-xl font-bold text-white mb-3">Red Zone Consequences</h4>
                                <p class="text-white/90 mb-4">
                                    Falling below 35% attendance triggers automatic account restrictions:
                                </p>
                                <ul class="text-white/80 space-y-2 ml-6">
                                    <li>• Attendance marking disabled</li>
                                    <li>• Leave applications blocked</li>
                                    <li>• Physical meeting with Master Admin required</li>
                                    <li>• Grace percentage adjustments at admin discretion</li>
                                    <li>• Potential academic consequences per institutional policy</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Intellectual Property -->
                    <div id="intellectual-property" class="term-section">
                        <div class="clause-number">5</div>
                        <h2 class="text-3xl font-bold text-white mb-6">Intellectual Property Rights</h2>
                        
                        <div class="glass-card rounded-2xl p-8">
                            <h3 class="text-xl font-bold text-white mb-6">Ownership & Usage Rights</h3>
                            
                            <div class="grid md:grid-cols-2 gap-8 mb-8">
                                <div>
                                    <h4 class="font-bold text-blue-300 mb-4">System Ownership</h4>
                                    <ul class="text-white/80 space-y-3">
                                        <li class="flex items-start">
                                            <i class="fas fa-code text-blue-400 mr-3 mt-1"></i>
                                            <span>All software, code, and algorithms are proprietary</span>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-database text-blue-400 mr-3 mt-1"></i>
                                            <span>Database schema and architecture are protected</span>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-palette text-blue-400 mr-3 mt-1"></i>
                                            <span>UI/UX design and interface elements are copyrighted</span>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-file-alt text-blue-400 mr-3 mt-1"></i>
                                            <span>Documentation and manuals are intellectual property</span>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div>
                                    <h4 class="font-bold text-green-300 mb-4">User-Generated Content</h4>
                                    <ul class="text-white/80 space-y-3">
                                        <li class="flex items-start">
                                            <i class="fas fa-user text-green-400 mr-3 mt-1"></i>
                                            <span>Users retain rights to their personal data</span>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-graduation-cap text-green-400 mr-3 mt-1"></i>
                                            <span>Academic records remain user property</span>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-camera text-green-400 mr-3 mt-1"></i>
                                            <span>Uploaded documents are user-owned</span>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-chalkboard text-green-400 mr-3 mt-1"></i>
                                            <span>Teachers retain rights to teaching materials</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="info-box">
                                <p class="text-white/90">
                                    <i class="fas fa-balance-scale text-blue-400 mr-2"></i>
                                    Users grant the institution a limited license to process their data for academic purposes only. 
                                    No ownership rights are transferred. The system provides a service platform for data management.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 6. Termination -->
                    <div id="termination" class="term-section">
                        <div class="clause-number">6</div>
                        <h2 class="text-3xl font-bold text-white mb-6">Termination & Account Suspension</h2>
                        
                        <div class="glass-card rounded-2xl p-8">
                            <h3 class="text-xl font-bold text-white mb-6">Account Termination Scenarios</h3>
                            
                            <div class="mb-8">
                                <h4 class="text-xl font-bold text-white mb-4">Termination Levels</h4>
                                <div class="space-y-4">
                                    <div class="penalty-level level-1">
                                        <div class="flex justify-between items-center mb-2">
                                            <h5 class="font-bold text-red-300">Immediate Termination</h5>
                                            <span class="px-3 py-1 bg-red-500/20 text-red-300 rounded-full text-sm">Permanent</span>
                                        </div>
                                        <ul class="text-white/80 text-sm space-y-1">
                                            <li>• Proxy attendance attempt detected</li>
                                            <li>• System security breach attempt</li>
                                            <li>• Device fingerprint manipulation</li>
                                            <li>• Multiple accounts for same individual</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="penalty-level level-2">
                                        <div class="flex justify-between items-center mb-2">
                                            <h5 class="font-bold text-yellow-300">Temporary Suspension</h5>
                                            <span class="px-3 py-1 bg-yellow-500/20 text-yellow-300 rounded-full text-sm">7-30 days</span>
                                        </div>
                                        <ul class="text-white/80 text-sm space-y-1">
                                            <li>• Repeated late attendance</li>
                                            <li>• Unauthorized leave applications</li>
                                            <li>• Minor policy violations</li>
                                            <li>• Red Zone status for extended period</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="penalty-level level-3">
                                        <div class="flex justify-between items-center mb-2">
                                            <h5 class="font-bold text-blue-300">Account Restrictions</h5>
                                            <span class="px-3 py-1 bg-blue-500/20 text-blue-300 rounded-full text-sm">Limited Access</span>
                                        </div>
                                        <ul class="text-white/80 text-sm space-y-1">
                                            <li>• Attendance below 35% (Red Zone)</li>
                                            <li>• Pending investigation</li>
                                            <li>• Device verification required</li>
                                            <li>• Academic probation status</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="info-box">
                                <p class="text-white/90">
                                    <i class="fas fa-user-slash text-blue-400 mr-2"></i>
                                    Upon termination, your access to the system will be immediately revoked. 
                                    Academic data will be archived according to institutional retention policies. 
                                    You may request data export within 30 days of termination.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Limitation of Liability -->
                    <div id="liability" class="term-section">
                        <div class="clause-number">7</div>
                        <h2 class="text-3xl font-bold text-white mb-6">Limitation of Liability</h2>
                        
                        <div class="glass-card rounded-2xl p-8">
                            <h3 class="text-xl font-bold text-white mb-6">Legal Limitations & Disclaimers</h3>
                            
                            <div class="space-y-6">
                                <div>
                                    <h4 class="font-bold text-white mb-3">Service Availability</h4>
                                    <p class="text-white/80">
                                        The System is provided on an "as is" and "as available" basis. We do not guarantee 
                                        uninterrupted, timely, secure, or error-free operation. Scheduled maintenance and 
                                        emergency outages may occur without prior notice.
                                    </p>
                                </div>
                                
                                <div>
                                    <h4 class="font-bold text-white mb-3">Academic Responsibility</h4>
                                    <p class="text-white/80">
                                        While the System accurately records attendance data, academic consequences resulting 
                                        from attendance records are determined by institutional policies, not system operation. 
                                        Users are responsible for monitoring their attendance percentages.
                                    </p>
                                </div>
                                
                                <div>
                                    <h4 class="font-bold text-white mb-3">Device & Network Issues</h4>
                                    <p class="text-white/80">
                                        We are not liable for attendance marking failures due to: user device malfunctions, 
                                        network connectivity issues, battery drainage, operating system problems, or user error. 
                                        The "Trusted Device" protocol is available for emergency situations.
                                    </p>
                                </div>
                                
                                <div class="warning-box">
                                    <p class="text-white/90">
                                        <i class="fas fa-gavel text-red-400 mr-2"></i>
                                        <strong>Maximum Liability:</strong> Our total liability for any claim arising from 
                                        these terms or system use shall not exceed the amount paid by the institution for 
                                        the service in the twelve months preceding the claim.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 8. Dispute Resolution -->
                    <div id="disputes" class="term-section">
                        <div class="clause-number">8</div>
                        <h2 class="text-3xl font-bold text-white mb-6">Dispute Resolution</h2>
                        
                        <div class="glass-card rounded-2xl p-8">
                            <h3 class="text-xl font-bold text-white mb-6">Conflict Resolution Process</h3>
                            
                            <div class="grid md:grid-cols-3 gap-8">
                                <div class="text-center">
                                    <div class="h-16 w-16 rounded-2xl bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center mx-auto mb-4">
                                        <span class="text-white font-bold text-xl">1</span>
                                    </div>
                                    <h4 class="font-bold text-white mb-3">Informal Resolution</h4>
                                    <p class="text-white/70 text-sm">
                                        Contact system administrators or department heads to resolve issues informally within 15 days.
                                    </p>
                                </div>
                                
                                <div class="text-center">
                                    <div class="h-16 w-16 rounded-2xl bg-gradient-to-r from-yellow-500 to-orange-500 flex items-center justify-center mx-auto mb-4">
                                        <span class="text-white font-bold text-xl">2</span>
                                    </div>
                                    <h4 class="font-bold text-white mb-3">Formal Complaint</h4>
                                    <p class="text-white/70 text-sm">
                                        Submit written complaint to academic committee with evidence and documentation.
                                    </p>
                                </div>
                                
                                <div class="text-center">
                                    <div class="h-16 w-16 rounded-2xl bg-gradient-to-r from-red-500 to-rose-500 flex items-center justify-center mx-auto mb-4">
                                        <span class="text-white font-bold text-xl">3</span>
                                    </div>
                                    <h4 class="font-bold text-white mb-3">Arbitration</h4>
                                    <p class="text-white/70 text-sm">
                                        Binding arbitration as final resolution method, conducted in institution's jurisdiction.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mt-8 pt-8 border-t border-white/10">
                                <p class="text-white/80">
                                    <i class="fas fa-map-marker-alt text-blue-400 mr-2"></i>
                                    <strong>Governing Law:</strong> These Terms shall be governed by the laws of the institution's jurisdiction. 
                                    Any legal proceedings shall be conducted in the courts of that jurisdiction.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 9. Amendments -->
                    <div id="amendments" class="term-section">
                        <div class="clause-number">9</div>
                        <h2 class="text-3xl font-bold text-white mb-6">Amendments & Updates</h2>
                        
                        <div class="glass-card rounded-2xl p-8">
                            <h3 class="text-xl font-bold text-white mb-6">Terms Modification Process</h3>
                            
                            <div class="space-y-6">
                                <div>
                                    <h4 class="font-bold text-white mb-3">Modification Rights</h4>
                                    <p class="text-white/80">
                                        We reserve the right to modify these Terms at any time. Material changes will be 
                                        communicated via email notifications and system announcements at least 30 days before 
                                        taking effect.
                                    </p>
                                </div>
                                
                                <div>
                                    <h4 class="font-bold text-white mb-3">User Responsibility</h4>
                                    <p class="text-white/80">
                                        It is your responsibility to review these Terms periodically. Continued use of the 
                                        System after changes constitutes acceptance of the modified Terms.
                                    </p>
                                </div>
                                
                                <div>
                                    <h4 class="font-bold text-white mb-3">Version Tracking</h4>
                                    <p class="text-white/80">
                                        All changes are version-controlled with detailed changelogs. Previous versions are 
                                        archived and accessible upon request for 5 years.
                                    </p>
                                </div>
                                
                                <div class="info-box">
                                    <p class="text-white/90">
                                        <i class="fas fa-history text-blue-400 mr-2"></i>
                                        <strong>Change Logs:</strong> Major version updates (e.g., 3.0 to 4.0) indicate 
                                        significant policy changes. Minor updates (e.g., 3.1 to 3.2) include clarifications 
                                        and technical updates.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 10. Contact Information -->
                    <div id="contact" class="term-section">
                        <div class="clause-number">10</div>
                        <h2 class="text-3xl font-bold text-white mb-6">Contact Information</h2>
                        
                        <div class="glass-card rounded-2xl p-8">
                            <h3 class="text-xl font-bold text-white mb-6">Get in Touch</h3>
                            
                            <div class="grid md:grid-cols-2 gap-8 mb-8">
                                <div>
                                    <h4 class="font-bold text-white mb-4">For Technical Support</h4>
                                    <ul class="text-white/80 space-y-4">
                                        <li class="flex items-start">
                                            <i class="fas fa-headset text-blue-400 mr-3 mt-1"></i>
                                            <div>
                                                <strong>Help Desk</strong>
                                                <p class="text-sm">helpdesk@campus-attendance.edu</p>
                                                <p class="text-xs text-white/60">Response time: 24-48 hours</p>
                                            </div>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-phone text-green-400 mr-3 mt-1"></i>
                                            <div>
                                                <strong>Emergency Hotline</strong>
                                                <p class="text-sm">+1 (555) 123-ATTEND</p>
                                                <p class="text-xs text-white/60">Mon-Fri, 9AM-5PM</p>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div>
                                    <h4 class="font-bold text-white mb-4">For Policy Inquiries</h4>
                                    <ul class="text-white/80 space-y-4">
                                        <li class="flex items-start">
                                            <i class="fas fa-balance-scale text-purple-400 mr-3 mt-1"></i>
                                            <div>
                                                <strong>Legal Department</strong>
                                                <p class="text-sm">legal@campus-attendance.edu</p>
                                                <p class="text-xs text-white/60">For formal term inquiries</p>
                                            </div>
                                        </li>
                                        <li class="flex items-start">
                                            <i class="fas fa-user-shield text-red-400 mr-3 mt-1"></i>
                                            <div>
                                                <strong>Privacy Officer</strong>
                                                <p class="text-sm">privacy@campus-attendance.edu</p>
                                                <p class="text-xs text-white/60">GDPR & data protection</p>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="highlighted-text">
                                <p class="text-white/90 text-center">
                                    <i class="fas fa-clock text-yellow-400 mr-2"></i>
                                    <strong>Office Hours:</strong> Monday to Friday, 9:00 AM - 5:00 PM (Local Campus Time)
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Final Acknowledgment -->
                    <div class="agreement-box">
                        <div class="text-center">
                            <h3 class="text-2xl font-bold text-white mb-6">Final Agreement</h3>
                            <div class="acceptance-check mb-6">
                                <input type="checkbox" id="finalAgreement" onclick="toggleFinalAcceptance()">
                                <label for="finalAgreement" class="text-white/80 text-lg">
                                    I have read all 10 sections and agree to be bound by these Terms of Service in their entirety.
                                </label>
                            </div>
                            <div class="flex flex-col sm:flex-row justify-center gap-4">
                                <button id="finalAcceptButton" disabled 
                                        class="px-8 py-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:from-green-600 hover:to-emerald-700 transition font-bold text-lg opacity-50 cursor-not-allowed">
                                    <i class="fas fa-file-signature mr-3"></i>I Accept All Terms
                                </button>
                                <a href="dashboard.php" class="px-8 py-4 glass-card text-white rounded-xl hover:bg-white/10 transition font-bold text-lg">
                                    <i class="fas fa-tachometer-alt mr-3"></i>Return to Dashboard
                                </a>
                            </div>
                            <p class="text-white/50 text-sm mt-4">
                                Your continued use of the Advanced Campus Attendance System confirms your acceptance of these terms.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-16 pt-8 pb-6 px-4 border-t border-white/10">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-6 md:mb-0">
                    <div class="flex items-center space-x-3">
                        <div class="h-8 w-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                            <i class="fas fa-university text-white text-sm"></i>
                        </div>
                        <span class="text-white font-bold">Campus Attendance System</span>
                    </div>
                    <p class="text-white/60 text-sm mt-2">
                        Advanced digital attendance management for educational institutions
                    </p>
                </div>
                <div class="flex flex-wrap justify-center gap-6">
                    <a href="privacy_policy.php" class="text-white/60 hover:text-white transition text-sm">
                        Privacy Policy
                    </a>
                    <a href="terms.php" class="text-white hover:text-white transition text-sm font-bold">
                        Terms of Service
                    </a>
                    <a href="about.php" class="text-white/60 hover:text-white transition text-sm">
                        About Us
                    </a>
                    <a href="contact.php" class="text-white/60 hover:text-white transition text-sm">
                        Contact
                    </a>
                </div>
            </div>
            <div class="mt-8 pt-6 border-t border-white/10 text-center text-white/40 text-sm">
                <p>&copy; <?= date('Y') ?> Advanced Campus Attendance System. All rights reserved.</p>
                <p class="mt-2">This is a legally binding agreement. Please read carefully before accepting.</p>
            </div>
        </div>
    </footer>

    <script>
        // Toggle accept button
        function toggleAcceptButton() {
            const checkbox = document.getElementById('readTerms');
            const button = document.getElementById('acceptButton');
            
            if (checkbox.checked) {
                button.disabled = false;
                button.classList.remove('opacity-50', 'cursor-not-allowed');
                button.classList.add('accept-btn-pulse');
            } else {
                button.disabled = true;
                button.classList.add('opacity-50', 'cursor-not-allowed');
                button.classList.remove('accept-btn-pulse');
            }
        }

        // Toggle final acceptance
        function toggleFinalAcceptance() {
            const checkbox = document.getElementById('finalAgreement');
            const button = document.getElementById('finalAcceptButton');
            
            if (checkbox.checked) {
                button.disabled = false;
                button.classList.remove('opacity-50', 'cursor-not-allowed');
                button.classList.add('accept-btn-pulse');
            } else {
                button.disabled = true;
                button.classList.add('opacity-50', 'cursor-not-allowed');
                button.classList.remove('accept-btn-pulse');
            }
        }

        // Table of Contents highlighting
        document.addEventListener('DOMContentLoaded', function() {
            const tocLinks = document.querySelectorAll('.toc-item');
            const sections = document.querySelectorAll('.term-section');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const id = entry.target.getAttribute('id');
                    const tocLink = document.querySelector(`.toc-item[href="#${id}"]`);
                    
                    if (entry.isIntersecting) {
                        tocLinks.forEach(link => link.classList.remove('active'));
                        if (tocLink) {
                            tocLink.classList.add('active');
                        }
                    }
                });
            }, { threshold: 0.3 });
            
            sections.forEach(section => {
                observer.observe(section);
            });

            // Accept button functionality
            document.getElementById('acceptButton')?.addEventListener('click', function() {
                if (!this.disabled) {
                    alert('Thank you for acknowledging the Terms of Service. You may continue to use the system.');
                    this.innerHTML = '<i class="fas fa-check mr-3"></i>Acknowledged ✓';
                    this.classList.remove('accept-btn-pulse');
                    this.classList.add('from-green-500', 'to-emerald-600');
                }
            });

            // Final accept button functionality
            document.getElementById('finalAcceptButton')?.addEventListener('click', function() {
                if (!this.disabled) {
                    alert('Terms of Service accepted successfully! Your acceptance has been recorded.');
                    this.innerHTML = '<i class="fas fa-check-double mr-3"></i>Terms Accepted ✓';
                    this.classList.remove('accept-btn-pulse');
                    this.classList.add('from-green-600', 'to-emerald-700');
                    
                    // In a real application, you would send this to the server
                    // fetch('accept_terms.php', { method: 'POST', body: JSON.stringify({ accepted: true }) });
                }
            });
        });

        // Smooth scrolling for TOC links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>