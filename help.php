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
    <title>Help & Support - Campus Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .gradient-text {
            background: linear-gradient(90deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }
        
        /* FAQ animation */
        .faq-item {
            transition: all 0.3s ease;
        }
        
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .faq-answer.open {
            max-height: 500px;
        }
        
        /* Step indicator */
        .step-indicator {
            position: relative;
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 24px;
            left: 16px;
            width: 2px;
            height: calc(100% - 24px);
            background: rgba(255, 255, 255, 0.2);
            z-index: 0;
        }
        
        .step-indicator:last-child::before {
            display: none;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen">
    <!-- Navigation -->
    <nav class="py-4 px-6 glass-card sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center">
                    <i class="fas fa-question-circle text-white text-xl"></i>
                </div>
                <span class="text-white font-bold text-xl">Help Center</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="index.php" class="text-white/80 hover:text-white transition">
                    <i class="fas fa-home mr-2"></i>Home
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
                    <a href="register.php" class="px-4 py-2 bg-white/20 text-white rounded-lg hover:bg-white/30 transition">
                        <i class="fas fa-user-plus mr-2"></i>Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12">
        <!-- Hero Section -->
        <div class="text-center mb-16">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">
                How can we <span class="gradient-text">help</span> you today?
            </h1>
            <p class="text-xl text-white/80 max-w-3xl mx-auto mb-10">
                Everything you need to know about using the Advanced Campus Attendance System.
            </p>
            
            <!-- Search Bar -->
            <div class="max-w-2xl mx-auto relative">
                <input type="text" id="helpSearch" 
                       placeholder="Search for answers, guides, or topics..."
                       class="w-full pl-12 pr-4 py-4 bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white/30 focus:border-transparent">
                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-white/60"></i>
            </div>
        </div>

        <!-- Quick Help Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-16">
            <!-- Card 1 -->
            <a href="#student-guide" class="glass-card rounded-2xl p-6 hover:bg-white/15 transition group">
                <div class="h-14 w-14 rounded-xl bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center mb-6">
                    <i class="fas fa-user-graduate text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">Student Guide</h3>
                <p class="text-white/70 mb-4">Learn how to mark attendance, apply for leave, and check your records.</p>
                <span class="text-blue-300 group-hover:text-blue-200 transition">
                    Get Started <i class="fas fa-arrow-right ml-2"></i>
                </span>
            </a>
            
            <!-- Card 2 -->
            <a href="#teacher-guide" class="glass-card rounded-2xl p-6 hover:bg-white/15 transition group">
                <div class="h-14 w-14 rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center mb-6">
                    <i class="fas fa-chalkboard-teacher text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">Teacher Guide</h3>
                <p class="text-white/70 mb-4">Generate QR codes, manage classes, and monitor student attendance.</p>
                <span class="text-purple-300 group-hover:text-purple-200 transition">
                    Learn More <i class="fas fa-arrow-right ml-2"></i>
                </span>
            </a>
            
            <!-- Card 3 -->
            <a href="#troubleshooting" class="glass-card rounded-2xl p-6 hover:bg-white/15 transition group">
                <div class="h-14 w-14 rounded-xl bg-gradient-to-r from-yellow-500 to-orange-500 flex items-center justify-center mb-6">
                    <i class="fas fa-tools text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">Troubleshooting</h3>
                <p class="text-white/70 mb-4">Fix common issues like device problems, network errors, and login issues.</p>
                <span class="text-yellow-300 group-hover:text-yellow-200 transition">
                    Fix Issues <i class="fas fa-arrow-right ml-2"></i>
                </span>
            </a>
            
            <!-- Card 4 -->
            <a href="#faq" class="glass-card rounded-2xl p-6 hover:bg-white/15 transition group">
                <div class="h-14 w-14 rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center mb-6">
                    <i class="fas fa-question-circle text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">FAQ</h3>
                <p class="text-white/70 mb-4">Find quick answers to frequently asked questions about the system.</p>
                <span class="text-green-300 group-hover:text-green-200 transition">
                    View FAQs <i class="fas fa-arrow-right ml-2"></i>
                </span>
            </a>
        </div>

        <!-- Student Guide -->
        <section id="student-guide" class="mb-20">
            <div class="glass-card rounded-3xl p-8">
                <div class="flex items-center mb-8">
                    <div class="h-12 w-12 rounded-full bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center mr-4">
                        <i class="fas fa-user-graduate text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-white">Student User Guide</h2>
                        <p class="text-white/70">Complete guide for students using the attendance system</p>
                    </div>
                </div>
                
                <div class="step-indicator space-y-8">
                    <!-- Step 1 -->
                    <div class="flex items-start relative z-10">
                        <div class="h-8 w-8 rounded-full bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center mr-6 flex-shrink-0">
                            <span class="text-white font-bold">1</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white mb-3">First Login & Device Registration</h3>
                            <div class="bg-white/5 rounded-xl p-4 mb-4">
                                <p class="text-white/80 mb-2"><strong>Important:</strong> Your device is permanently locked on first login!</p>
                                <ul class="text-white/70 space-y-1 ml-4 list-disc">
                                    <li>Use your Roll Number as username (e.g., 0902CS231028)</li>
                                    <li>First login generates a cryptographic device fingerprint</li>
                                    <li>Cannot switch devices without admin permission</li>
                                    <li>If you lose your phone, contact admin immediately</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 2 -->
                    <div class="flex items-start relative z-10">
                        <div class="h-8 w-8 rounded-full bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center mr-6 flex-shrink-0">
                            <span class="text-white font-bold">2</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white mb-3">Marking Attendance</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div class="bg-white/5 rounded-xl p-4">
                                    <div class="text-green-400 mb-2">
                                        <i class="fas fa-check-circle text-lg"></i>
                                        <span class="ml-2 font-bold">Green Zone (0-3 min)</span>
                                    </div>
                                    <p class="text-white/70 text-sm">Scan QR within 3 minutes for full attendance</p>
                                </div>
                                <div class="bg-white/5 rounded-xl p-4">
                                    <div class="text-yellow-400 mb-2">
                                        <i class="fas fa-clock text-lg"></i>
                                        <span class="ml-2 font-bold">Yellow Zone (3-7 min)</span>
                                    </div>
                                    <p class="text-white/70 text-sm">Marked as LATE (counts as 0.5 attendance)</p>
                                </div>
                                <div class="bg-white/5 rounded-xl p-4">
                                    <div class="text-red-400 mb-2">
                                        <i class="fas fa-times-circle text-lg"></i>
                                        <span class="ml-2 font-bold">Red Zone (7+ min)</span>
                                    </div>
                                    <p class="text-white/70 text-sm">QR scanning disabled - marked ABSENT</p>
                                </div>
                            </div>
                            <p class="text-white/70"><strong>Note:</strong> You must be connected to campus WiFi or have location enabled to mark attendance.</p>
                        </div>
                    </div>
                    
                    <!-- Step 3 -->
                    <div class="flex items-start relative z-10">
                        <div class="h-8 w-8 rounded-full bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center mr-6 flex-shrink-0">
                            <span class="text-white font-bold">3</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white mb-3">Leave Application Rules</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full text-white/80">
                                    <thead class="bg-white/10">
                                        <tr>
                                            <th class="p-3 text-left">Leave Type</th>
                                            <th class="p-3 text-left">Duration</th>
                                            <th class="p-3 text-left">Document Required</th>
                                            <th class="p-3 text-left">Approval</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="border-b border-white/10">
                                            <td class="p-3">Short Leave</td>
                                            <td class="p-3">1-3 days</td>
                                            <td class="p-3">Yes (Medical/Other)</td>
                                            <td class="p-3">Class Teacher</td>
                                        </tr>
                                        <tr class="border-b border-white/10">
                                            <td class="p-3">Long Leave</td>
                                            <td class="p-3">4+ days</td>
                                            <td class="p-3">Mandatory</td>
                                            <td class="p-3">Master Admin</td>
                                        </tr>
                                        <tr>
                                            <td class="p-3">Emergency</td>
                                            <td class="p-3">Immediate</td>
                                            <td class="p-3">Post-facto</td>
                                            <td class="p-3">Admin + HOD</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-white/70 mt-4"><strong>Important:</strong> No leave applications for past dates. Apply at least 24 hours in advance.</p>
                        </div>
                    </div>
                    
                    <!-- Step 4 -->
                    <div class="flex items-start relative z-10">
                        <div class="h-8 w-8 rounded-full bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center mr-6 flex-shrink-0">
                            <span class="text-white font-bold">4</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white mb-3">Red Zone Protocol</h3>
                            <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-4">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-exclamation-triangle text-red-400 mr-3"></i>
                                    <h4 class="font-bold text-red-300">Attendance Below 35%</h4>
                                </div>
                                <ul class="text-white/70 space-y-2 ml-7">
                                    <li>Your account will be automatically locked</li>
                                    <li>You cannot mark attendance or apply for leave</li>
                                    <li>You must physically meet the Master Admin</li>
                                    <li>Admin may use "Grace Slider" to boost percentage</li>
                                    <li>Detention notice will be issued if not resolved</li>
                                </ul>
                            </div>
                            <p class="text-white/70">Monitor your attendance percentage regularly in the dashboard.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Teacher Guide -->
        <section id="teacher-guide" class="mb-20">
            <div class="glass-card rounded-3xl p-8">
                <div class="flex items-center mb-8">
                    <div class="h-12 w-12 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center mr-4">
                        <i class="fas fa-chalkboard-teacher text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-white">Teacher User Guide</h2>
                        <p class="text-white/70">Complete guide for faculty members</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Left Column -->
                    <div>
                        <h3 class="text-xl font-bold text-white mb-4">Starting a Class Session</h3>
                        <div class="space-y-4">
                            <div class="bg-white/5 rounded-xl p-4">
                                <div class="flex items-center mb-2">
                                    <div class="h-8 w-8 rounded-full bg-purple-500/30 flex items-center justify-center mr-3">
                                        <i class="fas fa-qrcode text-purple-300"></i>
                                    </div>
                                    <h4 class="font-bold text-white">QR Generation</h4>
                                </div>
                                <ul class="text-white/70 space-y-1 ml-11">
                                    <li>Click "Start Session" in your dashboard</li>
                                    <li>Select "Whole Class" or specific lab batch (A1/A2)</li>
                                    <li>QR code rotates every 10 seconds automatically</li>
                                    <li>Display the QR on projector/board for students</li>
                                </ul>
                            </div>
                            
                            <div class="bg-white/5 rounded-xl p-4">
                                <div class="flex items-center mb-2">
                                    <div class="h-8 w-8 rounded-full bg-green-500/30 flex items-center justify-center mr-3">
                                        <i class="fas fa-users text-green-300"></i>
                                    </div>
                                    <h4 class="font-bold text-white">Real-time Monitoring</h4>
                                </div>
                                <ul class="text-white/70 space-y-1 ml-11">
                                    <li>Live counter shows scans received vs total students</li>
                                    <li>Red Zone widget highlights students below 35%</li>
                                    <li>Color-coded timer shows Green/Yellow/Red zones</li>
                                    <li>Click student names for detailed view</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div>
                        <h3 class="text-xl font-bold text-white mb-4">Class Management</h3>
                        <div class="space-y-4">
                            <div class="bg-white/5 rounded-xl p-4">
                                <div class="flex items-center mb-2">
                                    <div class="h-8 w-8 rounded-full bg-blue-500/30 flex items-center justify-center mr-3">
                                        <i class="fas fa-exchange-alt text-blue-300"></i>
                                    </div>
                                    <h4 class="font-bold text-white">Class Swapping</h4>
                                </div>
                                <ul class="text-white/70 space-y-1 ml-11">
                                    <li>Swipe left on a future slot to request swap</li>
                                    <li>Select peer teacher from dropdown</li>
                                    <li>Upload lecture notes for substitute teacher</li>
                                    <li>Both teachers must approve the swap</li>
                                </ul>
                            </div>
                            
                            <div class="bg-white/5 rounded-xl p-4">
                                <div class="flex items-center mb-2">
                                    <div class="h-8 w-8 rounded-full bg-yellow-500/30 flex items-center justify-center mr-3">
                                        <i class="fas fa-clipboard-check text-yellow-300"></i>
                                    </div>
                                    <h4 class="font-bold text-white">Logbook & Corrections</h4>
                                </div>
                                <ul class="text-white/70 space-y-1 ml-11">
                                    <li>Must enter "Topic Taught" to close session</li>
                                    <li>Manual override available for 24 hours only</li>
                                    <li>Select reason from dropdown for manual marks</li>
                                    <li>All corrections logged for audit trail</li>
                                </ul>
                            </div>
                            
                            <div class="bg-white/5 rounded-xl p-4">
                                <div class="flex items-center mb-2">
                                    <div class="h-8 w-8 rounded-full bg-red-500/30 flex items-center justify-center mr-3">
                                        <i class="fas fa-thumbs-up text-red-300"></i>
                                    </div>
                                    <h4 class="font-bold text-white">Leave Approvals</h4>
                                </div>
                                <ul class="text-white/70 space-y-1 ml-11">
                                    <li>Check "Leave Inbox" for pending requests</li>
                                    <li>Only 1-3 day leaves appear in your queue</li>
                                    <li>Long leaves go directly to Master Admin</li>
                                    <li>Verify documents before approval</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section id="faq" class="mb-20">
            <div class="glass-card rounded-3xl p-8">
                <div class="flex items-center mb-8">
                    <div class="h-12 w-12 rounded-full bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center mr-4">
                        <i class="fas fa-question-circle text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold text-white">Frequently Asked Questions</h2>
                        <p class="text-white/70">Quick answers to common questions</p>
                    </div>
                </div>
                
                <div class="space-y-4" id="faqAccordion">
                    <?php
                    $faqs = [
                        [
                            'question' => 'What happens if I lose my phone or change my device?',
                            'answer' => 'Contact the Master Admin immediately. Only the Master Admin can reset your device lock. You will need to provide your Roll Number and verify your identity. A new device fingerprint will be generated on your next login.',
                            'category' => 'student'
                        ],
                        [
                            'question' => 'Why can\'t I scan the QR code?',
                            'answer' => 'Possible reasons: 1) You\'re outside the 7-minute window, 2) Not connected to campus WiFi, 3) QR code expired (rotates every 10 seconds), 4) You\'re in the wrong lab batch, 5) Your account is locked (Red Zone).',
                            'category' => 'student'
                        ],
                        [
                            'question' => 'Can I apply for leave after the class has happened?',
                            'answer' => 'No. The system strictly rejects leave applications for past dates. You must apply at least 24 hours in advance for regular leaves. For emergencies, contact your class teacher directly.',
                            'category' => 'student'
                        ],
                        [
                            'question' => 'What is the "Trusted Device" protocol?',
                            'answer' => 'If a student\'s phone battery is dead, the teacher can authorize a one-time login on their own device. The student enters their Roll Number, teacher verifies face, and marks attendance. This doesn\'t change the student\'s permanent device lock.',
                            'category' => 'teacher'
                        ],
                        [
                            'question' => 'How do I handle a student who scanned late?',
                            'answer' => 'The system automatically marks them as LATE (3-7 minutes). If there was a valid reason, you can use "Manual Override" within 24 hours. Select the student, choose a reason from dropdown, and mark as Present.',
                            'category' => 'teacher'
                        ],
                        [
                            'question' => 'What happens if WiFi goes down during class?',
                            'answer' => 'The system will accept encrypted GPS coordinates as fallback (50m radius). Students must have location enabled. Teachers can also use manual override after verifying student presence.',
                            'category' => 'general'
                        ],
                        [
                            'question' => 'How are attendance percentages calculated?',
                            'answer' => 'Present = 1, Late = 0.5, Absent = 0. Percentage = (Total Points / Total Classes) × 100. Leaves marked as EXEMPT don\'t affect the percentage.',
                            'category' => 'general'
                        ],
                        [
                            'question' => 'What is the "Red Button" for?',
                            'answer' => 'Only Master Admin has access. It cancels ALL classes campus-wide instantly. Triggers push notifications to all 2000+ devices. Used for emergencies like heavy rain, protests, or power outages.',
                            'category' => 'admin'
                        ],
                    ];
                    
                    foreach ($faqs as $index => $faq):
                        $categoryColor = [
                            'student' => 'from-blue-500 to-cyan-500',
                            'teacher' => 'from-purple-500 to-pink-500', 
                            'admin' => 'from-red-500 to-orange-500',
                            'general' => 'from-green-500 to-emerald-500'
                        ][$faq['category']];
                        
                        $categoryText = [
                            'student' => 'Student',
                            'teacher' => 'Teacher',
                            'admin' => 'Admin',
                            'general' => 'General'
                        ][$faq['category']];
                    ?>
                    <div class="faq-item bg-white/5 rounded-xl overflow-hidden hover:bg-white/10 transition">
                        <button class="w-full p-6 text-left flex justify-between items-center" 
                                onclick="toggleFAQ(<?= $index ?>)">
                            <div class="flex items-center">
                                <span class="bg-gradient-to-r <?= $categoryColor ?> text-white text-xs font-bold px-3 py-1 rounded-full mr-4">
                                    <?= $categoryText ?>
                                </span>
                                <h3 class="text-lg font-bold text-white"><?= $faq['question'] ?></h3>
                            </div>
                            <i class="fas fa-chevron-down text-white/60 transition-transform" id="faqIcon<?= $index ?>"></i>
                        </button>
                        <div class="faq-answer px-6" id="faqAnswer<?= $index ?>">
                            <div class="pb-6 text-white/70">
                                <?= $faq['answer'] ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Contact Support -->
        <section id="contact" class="mb-20">
            <div class="glass-card rounded-3xl p-8">
                <div class="grid lg:grid-cols-2 gap-12">
                    <!-- Left Column -->
                    <div>
                        <h2 class="text-3xl font-bold text-white mb-6">Still Need Help?</h2>
                        <p class="text-white/70 mb-8">
                            Our support team is available to help you with any issues or questions.
                            Contact us through any of the channels below.
                        </p>
                        
                        <div class="space-y-6">
                            <div class="flex items-center">
                                <div class="h-12 w-12 rounded-xl bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-center mr-4">
                                    <i class="fas fa-envelope text-white text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-white">Email Support</h4>
                                    <p class="text-white/70">support@acassystem.edu</p>
                                    <p class="text-sm text-white/50">Response time: 24 hours</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <div class="h-12 w-12 rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center mr-4">
                                    <i class="fas fa-phone text-white text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-white">Phone Support</h4>
                                    <p class="text-white/70">+1 (555) 123-4567</p>
                                    <p class="text-sm text-white/50">Mon-Fri, 9AM-5PM Campus Time</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <div class="h-12 w-12 rounded-xl bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center mr-4">
                                    <i class="fas fa-map-marker-alt text-white text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-white">In-Person Support</h4>
                                    <p class="text-white/70">IT Department, Admin Block, Ground Floor</p>
                                    <p class="text-sm text-white/50">Bring your ID card and device</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <div class="h-12 w-12 rounded-xl bg-gradient-to-r from-yellow-500 to-orange-500 flex items-center justify-center mr-4">
                                    <i class="fas fa-clock text-white text-xl"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-white">Emergency Support</h4>
                                    <p class="text-white/70">For immediate class-related issues</p>
                                    <p class="text-sm text-white/50">Contact your Class Teacher directly</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column - Contact Form -->
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-6">Send us a Message</h3>
                        <form id="contactForm" class="space-y-6">
                            <div>
                                <label class="block text-white/80 mb-2">Your Name</label>
                                <input type="text" 
                                       class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-white/30 focus:border-transparent"
                                       placeholder="Enter your full name"
                                       required>
                            </div>
                            
                            <div>
                                <label class="block text-white/80 mb-2">Email Address</label>
                                <input type="email" 
                                       class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-white/30 focus:border-transparent"
                                       placeholder="your.email@example.com"
                                       required>
                            </div>
                            
                            <div>
                                <label class="block text-white/80 mb-2">User Type</label>
                                <select class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-white/30 focus:border-transparent">
                                    <option value="" class="bg-gray-800">Select your role</option>
                                    <option value="student" class="bg-gray-800">Student</option>
                                    <option value="teacher" class="bg-gray-800">Teacher</option>
                                    <option value="admin" class="bg-gray-800">Administrator</option>
                                    <option value="other" class="bg-gray-800">Other</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-white/80 mb-2">Issue Type</label>
                                <select class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-white/30 focus:border-transparent">
                                    <option value="" class="bg-gray-800">Select issue type</option>
                                    <option value="login" class="bg-gray-800">Login/Device Issue</option>
                                    <option value="attendance" class="bg-gray-800">Attendance Marking</option>
                                    <option value="leave" class="bg-gray-800">Leave Application</option>
                                    <option value="technical" class="bg-gray-800">Technical Problem</option>
                                    <option value="other" class="bg-gray-800">Other</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-white/80 mb-2">Message</label>
                                <textarea rows="4" 
                                          class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-white/30 focus:border-transparent"
                                          placeholder="Describe your issue in detail..."
                                          required></textarea>
                            </div>
                            
                            <button type="submit" 
                                    class="w-full px-6 py-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl hover:from-blue-600 hover:to-purple-700 transition font-bold text-lg">
                                <i class="fas fa-paper-plane mr-3"></i>Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="border-t border-white/10 py-8 px-4">
        <div class="max-w-7xl mx-auto text-center">
            <p class="text-white/60 mb-4">
                &copy; <?= date('Y') ?> Advanced Campus Attendance System. All rights reserved.
            </p>
            <div class="flex justify-center space-x-6">
                <a href="index.php" class="text-white/60 hover:text-white transition">Home</a>
                <a href="login.php" class="text-white/60 hover:text-white transition">Login</a>
                <a href="register.php" class="text-white/60 hover:text-white transition">Register</a>
                <a href="privacy.php" class="text-white/60 hover:text-white transition">Privacy Policy</a>
                <a href="terms.php" class="text-white/60 hover:text-white transition">Terms of Service</a>
            </div>
        </div>
    </footer>

    <script>
        // FAQ Toggle Function
        function toggleFAQ(index) {
            const answer = document.getElementById('faqAnswer' + index);
            const icon = document.getElementById('faqIcon' + index);
            
            answer.classList.toggle('open');
            icon.classList.toggle('rotate-180');
        }
        
        // Search Functionality
        document.getElementById('helpSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('h3').textContent.toLowerCase();
                const answer = item.querySelector('.faq-answer').textContent.toLowerCase();
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // Contact Form Submission
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show success message (in real implementation, this would be AJAX)
            alert('Thank you for your message! Our support team will contact you within 24 hours.');
            this.reset();
        });
        
        // Scroll to section with offset for fixed header
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if(targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if(targetElement) {
                    e.preventDefault();
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