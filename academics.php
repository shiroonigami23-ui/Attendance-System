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
    <title>Academic Programs - RJIT College of BSF</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        .bsf-gradient-bg {
            background: linear-gradient(135deg, #0c2d4e 0%, #1a4b7a 100%);
        }
        
        .bsf-red {
            background: linear-gradient(135deg, #d62828 0%, #f77f00 100%);
        }
        
        .bsf-blue {
            background: linear-gradient(135deg, #003049 0%, #1a4b7a 100%);
        }
        
        .bsf-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(0, 48, 73, 0.1);
        }
        
        .bsf-text-gradient {
            background: linear-gradient(90deg, #003049, #d62828);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .section-title {
            font-family: 'Montserrat', sans-serif;
            position: relative;
            padding-bottom: 1rem;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #d62828, #f77f00);
        }
        
        .program-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-top: 4px solid #003049;
        }
        
        .program-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border-top-color: #d62828;
        }
        
        .level-badge {
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .undergrad-badge { background: rgba(0, 48, 73, 0.1); color: #003049; }
        .postgrad-badge { background: rgba(214, 40, 40, 0.1); color: #d62828; }
        .diploma-badge { background: rgba(247, 127, 0, 0.1); color: #f77f00; }
        .certificate-badge { background: rgba(0, 104, 55, 0.1); color: #006837; }
        
        .duration-tag {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: rgba(0, 48, 73, 0.05);
            border-radius: 25px;
            font-size: 0.875rem;
            margin: 0.25rem;
        }
        
        .tab-button {
            padding: 1rem 2rem;
            border-radius: 10px 10px 0 0;
            background: #f8f9fa;
            color: #6c757d;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .tab-button.active {
            background: white;
            color: #003049;
            border-top: 3px solid #d62828;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }
        
        .stats-number {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(90deg, #003049, #d62828);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        }
        
        .curriculum-item {
            padding: 1rem;
            border-left: 4px solid #003049;
            background: #f8f9fa;
            margin-bottom: 1rem;
            border-radius: 0 8px 8px 0;
        }
        
        .curriculum-item:hover {
            border-left-color: #d62828;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body class="bsf-gradient-bg min-h-screen">
    <!-- Navigation -->
    <nav class="py-4 px-6 bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="h-12 w-12 rounded-full bsf-red flex items-center justify-center">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900">RJIT College of BSF</h1>
                    <p class="text-sm text-gray-600">Border Security Force Training Institute</p>
                </div>
            </div>
            <div class="flex items-center space-x-6">
                <a href="index.php" class="text-gray-700 hover:text-blue-600 transition">
                    <i class="fas fa-home mr-2"></i>Home
                </a>
                <a href="about.php" class="text-gray-700 hover:text-blue-600 transition">
                    <i class="fas fa-info-circle mr-2"></i>About
                </a>
                <a href="academics.php" class="text-blue-600 font-bold">
                    <i class="fas fa-graduation-cap mr-2"></i>Academics
                </a>
                <a href="admission.php" class="text-gray-700 hover:text-blue-600 transition">
                    <i class="fas fa-user-graduate mr-2"></i>Admissions
                </a>
                <a href="contact.php" class="text-gray-700 hover:text-blue-600 transition">
                    <i class="fas fa-phone-alt mr-2"></i>Contact
                </a>
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="text-gray-700 hover:text-blue-600 transition">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                    </a>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-700">
                        <i class="fas fa-user mr-2"></i><?= htmlspecialchars($userName) ?>
                    </span>
                <?php else: ?>
                    <a href="login.php" class="text-gray-700 hover:text-blue-600 transition">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                    <a href="register.php" class="px-4 py-2 bsf-red text-white rounded-lg hover:opacity-90 transition">
                        <i class="fas fa-user-plus mr-2"></i>Enroll Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="py-16 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-12">
                <span class="level-badge undergrad-badge mb-4">
                    <i class="fas fa-graduation-cap mr-2"></i>Academic Excellence
                </span>
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">
                    Academic <span class="bsf-text-gradient">Programs</span>
                </h1>
                <p class="text-xl text-white/80 max-w-3xl mx-auto">
                    Specialized programs designed for future Border Security Force officers. 
                    Combining rigorous academics with practical field training.
                </p>
            </div>
            
            <!-- Quick Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
                <div class="stats-card">
                    <div class="stats-number">15+</div>
                    <p class="text-gray-600">Academic Programs</p>
                </div>
                <div class="stats-card">
                    <div class="stats-number">98%</div>
                    <p class="text-gray-600">Placement Rate</p>
                </div>
                <div class="stats-card">
                    <div class="stats-number">500+</div>
                    <p class="text-gray-600">Expert Faculty</p>
                </div>
                <div class="stats-card">
                    <div class="stats-number">25</div>
                    <p class="text-gray-600">Training Facilities</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 pb-16">
        <!-- Program Tabs -->
        <div class="mb-12">
            <div class="flex flex-wrap border-b">
                <button class="tab-button active" onclick="showTab('undergraduate')">
                    <i class="fas fa-user-graduate mr-2"></i>Undergraduate
                </button>
                <button class="tab-button" onclick="showTab('postgraduate')">
                    <i class="fas fa-user-tie mr-2"></i>Postgraduate
                </button>
                <button class="tab-button" onclick="showTab('diploma')">
                    <i class="fas fa-certificate mr-2"></i>Diploma
                </button>
                <button class="tab-button" onclick="showTab('certificate')">
                    <i class="fas fa-award mr-2"></i>Certificate
                </button>
                <button class="tab-button" onclick="showTab('training')">
                    <i class="fas fa-dumbbell mr-2"></i>Special Training
                </button>
            </div>
            
            <!-- Undergraduate Programs -->
            <div id="undergraduate" class="tab-content">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mt-8">
                    <div class="program-card bsf-card rounded-2xl p-6">
                        <div class="flex justify-between items-start mb-4">
                            <span class="level-badge undergrad-badge">B.Sc.</span>
                            <span class="duration-tag"><i class="fas fa-clock mr-2"></i>3 Years</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">B.Sc. in Border Security</h3>
                        <p class="text-gray-600 mb-6">
                            Comprehensive study of border management, security protocols, and intelligence gathering.
                        </p>
                        <div class="mb-6">
                            <h4 class="font-bold text-gray-800 mb-2">Core Subjects:</h4>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">Border Management</span>
                                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">Intelligence Analysis</span>
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Law Enforcement</span>
                            </div>
                        </div>
                        <a href="admission.php" class="bsf-red text-white px-6 py-2 rounded-lg inline-block hover:opacity-90 transition">
                            Apply Now <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                    
                    <div class="program-card bsf-card rounded-2xl p-6">
                        <div class="flex justify-between items-start mb-4">
                            <span class="level-badge undergrad-badge">B.Tech</span>
                            <span class="duration-tag"><i class="fas fa-clock mr-2"></i>4 Years</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">B.Tech in Security Technology</h3>
                        <p class="text-gray-600 mb-6">
                            Engineering program focusing on surveillance systems, cybersecurity, and defense technology.
                        </p>
                        <div class="mb-6">
                            <h4 class="font-bold text-gray-800 mb-2">Core Subjects:</h4>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">Cyber Security</span>
                                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">Surveillance Tech</span>
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Drone Operations</span>
                            </div>
                        </div>
                        <a href="admission.php" class="bsf-red text-white px-6 py-2 rounded-lg inline-block hover:opacity-90 transition">
                            Apply Now <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                    
                    <div class="program-card bsf-card rounded-2xl p-6">
                        <div class="flex justify-between items-start mb-4">
                            <span class="level-badge undergrad-badge">BA</span>
                            <span class="duration-tag"><i class="fas fa-clock mr-2"></i>3 Years</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">BA in Defense Studies</h3>
                        <p class="text-gray-600 mb-6">
                            Study of defense policies, international relations, and strategic security studies.
                        </p>
                        <div class="mb-6">
                            <h4 class="font-bold text-gray-800 mb-2">Core Subjects:</h4>
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">Defense Policy</span>
                                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">International Relations</span>
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Strategic Studies</span>
                            </div>
                        </div>
                        <a href="admission.php" class="bsf-red text-white px-6 py-2 rounded-lg inline-block hover:opacity-90 transition">
                            Apply Now <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Postgraduate Programs -->
            <div id="postgraduate" class="tab-content hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8">
                    <div class="program-card bsf-card rounded-2xl p-6">
                        <div class="flex justify-between items-start mb-4">
                            <span class="level-badge postgrad-badge">M.Sc.</span>
                            <span class="duration-tag"><i class="fas fa-clock mr-2"></i>2 Years</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">M.Sc. in Advanced Security Studies</h3>
                        <p class="text-gray-600 mb-6">
                            Advanced research in national security, counter-terrorism, and strategic intelligence.
                        </p>
                        <div class="text-sm text-gray-500 mb-4">
                            <i class="fas fa-user-graduate mr-2"></i>Requires BSF clearance
                        </div>
                        <a href="admission.php" class="bsf-red text-white px-6 py-2 rounded-lg inline-block hover:opacity-90 transition">
                            Apply Now <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                    
                    <div class="program-card bsf-card rounded-2xl p-6">
                        <div class="flex justify-between items-start mb-4">
                            <span class="level-badge postgrad-badge">MBA</span>
                            <span class="duration-tag"><i class="fas fa-clock mr-2"></i>2 Years</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">MBA in Defense Management</h3>
                        <p class="text-gray-600 mb-6">
                            Management program specialized in defense logistics, procurement, and administration.
                        </p>
                        <div class="text-sm text-gray-500 mb-4">
                            <i class="fas fa-briefcase mr-2"></i>Minimum 2 years experience required
                        </div>
                        <a href="admission.php" class="bsf-red text-white px-6 py-2 rounded-lg inline-block hover:opacity-90 transition">
                            Apply Now <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Curriculum Highlights -->
        <div class="mb-16">
            <h2 class="text-3xl font-bold text-white mb-8 section-title">Curriculum Highlights</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="bsf-card rounded-2xl p-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Academic Structure</h3>
                    <div class="space-y-4">
                        <div class="curriculum-item">
                            <h4 class="font-bold text-gray-800 mb-2">Core Security Studies (40%)</h4>
                            <p class="text-gray-600">Border management, intelligence, surveillance, and counter-terrorism</p>
                        </div>
                        <div class="curriculum-item">
                            <h4 class="font-bold text-gray-800 mb-2">Technical Training (30%)</h4>
                            <p class="text-gray-600">Weapon handling, communication systems, cybersecurity, drone operations</p>
                        </div>
                        <div class="curriculum-item">
                            <h4 class="font-bold text-gray-800 mb-2">Field Exercises (20%)</h4>
                            <p class="text-gray-600">Live drills, border patrol simulations, emergency response training</p>
                        </div>
                        <div class="curriculum-item">
                            <h4 class="font-bold text-gray-800 mb-2">Research & Analysis (10%)</h4>
                            <p class="text-gray-600">Case studies, threat analysis, strategic planning projects</p>
                        </div>
                    </div>
                </div>
                
                <div class="bsf-card rounded-2xl p-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Training Facilities</h3>
                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div class="h-12 w-12 rounded-full bsf-red flex items-center justify-center mr-4">
                                <i class="fas fa-dumbbell text-white"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800">Combat Training Grounds</h4>
                                <p class="text-gray-600">25-acre facility with urban warfare simulation zones</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="h-12 w-12 rounded-full bsf-blue flex items-center justify-center mr-4">
                                <i class="fas fa-laptop-code text-white"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800">Cyber Range</h4>
                                <p class="text-gray-600">State-of-the-art cybersecurity training and simulation center</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="h-12 w-12 rounded-full bg-gradient-to-r from-green-500 to-emerald-600 flex items-center justify-center mr-4">
                                <i class="fas fa-first-aid text-white"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800">Medical Training Center</h4>
                                <p class="text-gray-600">Combat medical training with trauma simulation labs</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="h-12 w-12 rounded-full bg-gradient-to-r from-purple-500 to-pink-600 flex items-center justify-center mr-4">
                                <i class="fas fa-satellite-dish text-white"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800">Communication Hub</h4>
                                <p class="text-gray-600">Advanced radio, satellite, and encrypted communication training</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Career Pathways -->
        <div class="bsf-card rounded-2xl p-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-8 section-title">Career Pathways</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="h-20 w-20 rounded-full bsf-red flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-user-shield text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">BSF Officer</h3>
                    <p class="text-gray-600">Direct commissioning into Border Security Force with leadership roles</p>
                </div>
                
                <div class="text-center">
                    <div class="h-20 w-20 rounded-full bsf-blue flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-brain text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Intelligence Analyst</h3>
                    <p class="text-gray-600">Security analysis, threat assessment, and strategic planning roles</p>
                </div>
                
                <div class="text-center">
                    <div class="h-20 w-20 rounded-full bg-gradient-to-r from-green-500 to-emerald-600 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-laptop-code text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Security Technologist</h3>
                    <p class="text-gray-600">Cybersecurity, surveillance technology, and defense systems development</p>
                </div>
            </div>
            
            <div class="mt-12 text-center">
                <a href="admission.php" class="inline-flex items-center bsf-red text-white px-8 py-3 rounded-lg hover:opacity-90 transition text-lg">
                    <i class="fas fa-file-alt mr-3"></i> View Admission Requirements
                    <i class="fas fa-arrow-right ml-3"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="h-12 w-12 rounded-full bsf-red flex items-center justify-center">
                            <i class="fas fa-shield-alt text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">RJIT College of BSF</h3>
                            <p class="text-sm text-gray-400">Border Security Force Training Institute</p>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm">
                        Premier institution for Border Security Force training, 
                        education, and research.
                    </p>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">Quick Links</h4>
                    <ul class="space-y-3">
                        <li><a href="academics.php" class="text-gray-400 hover:text-white transition">Academic Programs</a></li>
                        <li><a href="admission.php" class="text-gray-400 hover:text-white transition">Admission Process</a></li>
                        <li><a href="faculty.php" class="text-gray-400 hover:text-white transition">Faculty Directory</a></li>
                        <li><a href="research.php" class="text-gray-400 hover:text-white transition">Research Centers</a></li>
                        <li><a href="contact.php" class="text-gray-400 hover:text-white transition">Contact Academics</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">Program Info</h4>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-calendar-alt text-red-500 mr-3 mt-1"></i>
                            <span class="text-gray-400">Academic Calendar 2024</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-book text-red-500 mr-3 mt-1"></i>
                            <span class="text-gray-400">Course Catalog</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-graduation-cap text-red-500 mr-3 mt-1"></i>
                            <span class="text-gray-400">Scholarships</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-chart-line text-red-500 mr-3 mt-1"></i>
                            <span class="text-gray-400">Placement Statistics</span>
                        </li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">Academic Support</h4>
                    <div class="space-y-4">
                        <div>
                            <div class="text-2xl font-bold text-blue-400 mb-1">+91-11-2345-6789</div>
                            <p class="text-gray-400 text-sm">Academic Office</p>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-green-400 mb-1">academics@rjitbsf.edu.in</div>
                            <p class="text-gray-400 text-sm">Email Support</p>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-yellow-400 mb-1">Mon-Fri 9AM-5PM</div>
                            <p class="text-gray-400 text-sm">Office Hours</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-12 pt-8 border-t border-gray-800 text-center">
                <p class="text-gray-500 text-sm">
                    &copy; <?= date('Y') ?> RJIT College of BSF. All Rights Reserved.
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.remove('hidden');
            
            // Add active class to clicked tab
            event.currentTarget.classList.add('active');
        }
        
        // Program card animations
        document.addEventListener('DOMContentLoaded', function() {
            const programCards = document.querySelectorAll('.program-card');
            programCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    const badge = this.querySelector('.level-badge');
                    if (badge.classList.contains('undergrad-badge')) {
                        badge.style.background = 'rgba(214, 40, 40, 0.2)';
                        badge.style.color = '#d62828';
                    }
                });
                
                card.addEventListener('mouseleave', function() {
                    const badge = this.querySelector('.level-badge');
                    if (badge.classList.contains('undergrad-badge')) {
                        badge.style.background = 'rgba(0, 48, 73, 0.1)';
                        badge.style.color = '#003049';
                    }
                });
            });
            
            // Smooth scroll for anchors
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
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
        });
    </script>
</body>
</html>