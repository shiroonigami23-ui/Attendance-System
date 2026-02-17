<?php
require_once 'includes/Config.php';
require_once 'includes/Auth.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null;
$userName = $_SESSION['full_name'] ?? 'Guest';

// Sample faculty data (in real app, would come from database)
$facultyDepartments = [
    'Security Studies' => [
        [
            'name' => 'Dr. Rajesh Kumar',
            'designation' => 'Professor & Head',
            'qualification' => 'Ph.D. in Security Studies, IIT Delhi',
            'experience' => '25 years',
            'specialization' => 'Border Management, Counter-Terrorism',
            'email' => 'rkumar@rjitbsf.edu.in',
            'image_color' => 'bg-gradient-to-r from-blue-500 to-cyan-500'
        ],
        [
            'name' => 'Col. (Retd) Sunil Sharma',
            'designation' => 'Senior Professor',
            'qualification' => 'M.Sc. Defense Studies, NDA',
            'experience' => '32 years (BSF)',
            'specialization' => 'Field Tactics, Combat Training',
            'email' => 'ssharma@rjitbsf.edu.in',
            'image_color' => 'bg-gradient-to-r from-red-500 to-orange-500'
        ]
    ],
    'Technology & Cyber Security' => [
        [
            'name' => 'Dr. Priya Singh',
            'designation' => 'Associate Professor',
            'qualification' => 'Ph.D. Cybersecurity, IIIT Hyderabad',
            'experience' => '18 years',
            'specialization' => 'Cyber Defense, Cryptography',
            'email' => 'psingh@rjitbsf.edu.in',
            'image_color' => 'bg-gradient-to-r from-purple-500 to-pink-500'
        ],
        [
            'name' => 'Prof. Amit Patel',
            'designation' => 'Assistant Professor',
            'qualification' => 'M.Tech in Information Security',
            'experience' => '12 years',
            'specialization' => 'Network Security, Digital Forensics',
            'email' => 'apatel@rjitbsf.edu.in',
            'image_color' => 'bg-gradient-to-r from-green-500 to-emerald-500'
        ]
    ],
    'Combat & Field Training' => [
        [
            'name' => 'Maj. Gen. (Retd) Ramesh Yadav',
            'designation' => 'Director of Training',
            'qualification' => 'M.Sc. Military Science, DSSC',
            'experience' => '35 years (Army)',
            'specialization' => 'Urban Warfare, Special Operations',
            'email' => 'ryadav@rjitbsf.edu.in',
            'image_color' => 'bg-gradient-to-r from-yellow-500 to-amber-500'
        ],
        [
            'name' => 'Wg. Cdr. (Retd) Neha Verma',
            'designation' => 'Training Officer',
            'qualification' => 'M.Sc. Aeronautics',
            'experience' => '20 years (IAF)',
            'specialization' => 'Aerial Surveillance, Drone Operations',
            'email' => 'nverma@rjitbsf.edu.in',
            'image_color' => 'bg-gradient-to-r from-indigo-500 to-blue-500'
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Directory - RJIT College of BSF</title>
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
        
        .faculty-card {
            transition: all 0.3s ease;
            border-top: 4px solid transparent;
        }
        
        .faculty-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            border-top-color: #d62828;
        }
        
        .department-badge {
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .department-badge.active {
            background: linear-gradient(90deg, #d62828, #f77f00);
            color: white;
        }
        
        .department-badge:not(.active) {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .department-badge:not(.active):hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .faculty-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0 auto;
        }
        
        .expertise-tag {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: rgba(0, 48, 73, 0.1);
            color: #003049;
            border-radius: 15px;
            font-size: 0.75rem;
            margin: 0.25rem;
        }
        
        .stats-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: white;
            font-weight: bold;
        }
        
        .leadership-card {
            background: linear-gradient(135deg, rgba(0, 48, 73, 0.9), rgba(26, 75, 122, 0.9));
            color: white;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
        }
        
        .leadership-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.05)"/></svg>');
            background-size: cover;
        }
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .research-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin: 0.25rem;
        }
        
        .publication-badge { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .patent-badge { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .project-badge { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
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
                <a href="academics.php" class="text-gray-700 hover:text-blue-600 transition">
                    <i class="fas fa-graduation-cap mr-2"></i>Academics
                </a>
                <a href="faculty.php" class="text-blue-600 font-bold">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>Faculty
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
                <span class="department-badge mb-4">
                    <i class="fas fa-users mr-2"></i>Expert Faculty
                </span>
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">
                    Meet Our <span class="bsf-text-gradient">Faculty</span>
                </h1>
                <p class="text-xl text-white/80 max-w-3xl mx-auto">
                    Distinguished experts, former defense officers, and leading researchers 
                    dedicated to training the next generation of Border Security Force leaders.
                </p>
            </div>
            
            <!-- Faculty Statistics -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
                <div class="text-center">
                    <div class="stats-circle bsf-red mx-auto mb-4">
                        <div class="text-3xl">150+</div>
                    </div>
                    <p class="text-white font-medium">Faculty Members</p>
                </div>
                
                <div class="text-center">
                    <div class="stats-circle bsf-blue mx-auto mb-4">
                        <div class="text-3xl">85%</div>
                    </div>
                    <p class="text-white font-medium">PhD Holders</p>
                </div>
                
                <div class="text-center">
                    <div class="stats-circle bg-gradient-to-r from-green-500 to-emerald-600 mx-auto mb-4">
                        <div class="text-3xl">40+</div>
                    </div>
                    <p class="text-white font-medium">Years Avg. Experience</p>
                </div>
                
                <div class="text-center">
                    <div class="stats-circle bg-gradient-to-r from-purple-500 to-pink-600 mx-auto mb-4">
                        <div class="text-3xl">25</div>
                    </div>
                    <p class="text-white font-medium">Countries Served</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 pb-16">
        <!-- Department Filter -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-6">Browse by Department</h2>
            <div class="flex flex-wrap gap-3">
                <button class="department-badge active" onclick="filterFaculty('all')">
                    <i class="fas fa-th-large mr-2"></i>All Faculty
                </button>
                <?php foreach (array_keys($facultyDepartments) as $index => $dept): ?>
                <button class="department-badge" onclick="filterFaculty('<?= strtolower(str_replace(' ', '-', $dept)) ?>')">
                    <i class="fas fa-<?= 
                        $index == 0 ? 'shield-alt' : 
                        ($index == 1 ? 'laptop-code' : 
                        ($index == 2 ? 'dumbbell' : 'graduation-cap')) 
                    ?> mr-2"></i>
                    <?= $dept ?>
                </button>
                <?php endforeach; ?>
                <button class="department-badge" onclick="filterFaculty('leadership')">
                    <i class="fas fa-user-tie mr-2"></i>Leadership
                </button>
            </div>
        </div>
        
        <!-- Leadership Team -->
        <div class="mb-16 faculty-section leadership-section">
            <h2 class="text-3xl font-bold text-white mb-8 section-title">Leadership Team</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="leadership-card p-8">
                    <div class="flex flex-col md:flex-row items-center">
                        <div class="faculty-image bg-gradient-to-r from-yellow-500 to-amber-500 mb-6 md:mb-0 md:mr-8">
                            <span>AD</span>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold mb-2">Air Marshal (Retd) Devendra Singh</h3>
                            <p class="text-blue-200 mb-4">Director General, RJIT College of BSF</p>
                            <p class="text-white/80 mb-6">
                                40+ years of distinguished service in Indian Air Force. 
                                Expert in aerial surveillance and defense strategy.
                            </p>
                            <div class="flex items-center space-x-4">
                                <a href="#" class="text-white hover:text-blue-300 transition">
                                    <i class="fas fa-envelope text-xl"></i>
                                </a>
                                <a href="#" class="text-white hover:text-blue-300 transition">
                                    <i class="fas fa-file-pdf text-xl"></i>
                                </a>
                                <a href="#" class="text-white hover:text-blue-300 transition">
                                    <i class="fas fa-linkedin text-xl"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="leadership-card p-8">
                    <div class="flex flex-col md:flex-row items-center">
                        <div class="faculty-image bg-gradient-to-r from-purple-500 to-pink-500 mb-6 md:mb-0 md:mr-8">
                            <span>SM</span>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold mb-2">Dr. Sunita Mehta</h3>
                            <p class="text-blue-200 mb-4">Dean of Academics</p>
                            <p class="text-white/80 mb-6">
                                PhD in Security Studies from JNU. 25+ years in defense education 
                                and strategic research.
                            </p>
                            <div class="flex flex-wrap gap-2 mb-6">
                                <span class="research-badge publication-badge">
                                    <i class="fas fa-book mr-1"></i> 45 Publications
                                </span>
                                <span class="research-badge project-badge">
                                    <i class="fas fa-project-diagram mr-1"></i> 12 Projects
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Faculty Grid -->
        <?php foreach ($facultyDepartments as $department => $facultyList): ?>
        <div class="mb-16 faculty-section <?= strtolower(str_replace(' ', '-', $department)) ?>-section">
            <h2 class="text-3xl font-bold text-white mb-8 section-title"><?= $department ?></h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($facultyList as $faculty): ?>
                <div class="faculty-card bsf-card rounded-2xl p-6">
                    <div class="faculty-image <?= $faculty['image_color'] ?> mb-6">
                        <span><?= substr($faculty['name'], 0, 2) ?></span>
                    </div>
                    
                    <h3 class="text-xl font-bold text-gray-900 text-center mb-2"><?= $faculty['name'] ?></h3>
                    <p class="text-blue-600 text-center font-medium mb-4"><?= $faculty['designation'] ?></p>
                    
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-graduation-cap text-blue-500 mr-3"></i>
                            <span><?= $faculty['qualification'] ?></span>
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-briefcase text-green-500 mr-3"></i>
                            <span><?= $faculty['experience'] ?> Experience</span>
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-envelope text-red-500 mr-3"></i>
                            <span><?= $faculty['email'] ?></span>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <h4 class="font-bold text-gray-800 mb-2">Specialization:</h4>
                        <div class="flex flex-wrap">
                            <?php 
                            $specializations = explode(', ', $faculty['specialization']);
                            foreach ($specializations as $spec): ?>
                            <span class="expertise-tag"><?= $spec ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button onclick="showFacultyDetail('<?= $faculty['name'] ?>')" 
                            class="w-full bsf-red text-white py-2 rounded-lg hover:opacity-90 transition">
                        <i class="fas fa-eye mr-2"></i> View Profile
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Visiting Faculty -->
        <div class="bsf-card rounded-2xl p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 section-title">Visiting Faculty & Experts</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="p-4 text-left text-gray-700 font-bold">Name</th>
                            <th class="p-4 text-left text-gray-700 font-bold">Organization</th>
                            <th class="p-4 text-left text-gray-700 font-bold">Expertise</th>
                            <th class="p-4 text-left text-gray-700 font-bold">Course</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="p-4">Brig. (Retd) Arvind Kumar</td>
                            <td class="p-4">Ministry of Home Affairs</td>
                            <td class="p-4">Border Infrastructure</td>
                            <td class="p-4">Advanced Border Management</td>
                        </tr>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="p-4">Dr. Anjali Deshpande</td>
                            <td class="p-4">DRDO</td>
                            <td class="p-4">Defense Technology</td>
                            <td class="p-4">Weapon Systems</td>
                        </tr>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="p-4">Cmde. (Retd) Rohan Kapoor</td>
                            <td class="p-4">Indian Navy</td>
                            <td class="p-4">Coastal Security</td>
                            <td class="p-4">Maritime Operations</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="p-4">Prof. Michael Chen</td>
                            <td class="p-4">INTERPOL</td>
                            <td class="p-4">International Security</td>
                            <td class="p-4">Global Threat Analysis</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Faculty Detail Modal -->
    <div id="facultyModal" class="modal-overlay">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">Faculty Profile</h3>
                    <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
                
                <div id="modalContent">
                    <!-- Dynamic content will be loaded here -->
                </div>
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
                        Distinguished faculty shaping future BSF leaders through 
                        excellence in teaching and research.
                    </p>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">Faculty Resources</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Faculty Directory</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Research Publications</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Faculty Development</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Academic Calendar</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Internal Portal</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">Departments</h4>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-shield-alt text-red-500 mr-3 mt-1"></i>
                            <a href="#" class="text-gray-400 hover:text-white transition">Security Studies</a>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-laptop-code text-blue-500 mr-3 mt-1"></i>
                            <a href="#" class="text-gray-400 hover:text-white transition">Cyber Security</a>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-dumbbell text-green-500 mr-3 mt-1"></i>
                            <a href="#" class="text-gray-400 hover:text-white transition">Combat Training</a>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-graduation-cap text-purple-500 mr-3 mt-1"></i>
                            <a href="#" class="text-gray-400 hover:text-white transition">Academic Support</a>
                        </li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">Contact Faculty</h4>
                    <div class="space-y-4">
                        <div>
                            <div class="text-2xl font-bold text-blue-400 mb-1">+91-11-2345-6790</div>
                            <p class="text-gray-400 text-sm">Faculty Office</p>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-green-400 mb-1">faculty@rjitbsf.edu.in</div>
                            <p class="text-gray-400 text-sm">General Inquiries</p>
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
                <p class="text-gray-600 text-xs mt-2">
                    Faculty profiles are updated annually. Last update: January 2024.
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Filter faculty by department
        function filterFaculty(department) {
            // Update active button
            document.querySelectorAll('.department-badge').forEach(btn => {
                btn.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Show/hide sections
            document.querySelectorAll('.faculty-section').forEach(section => {
                if (department === 'all') {
                    section.style.display = 'block';
                } else if (section.classList.contains(`${department}-section`)) {
                    section.style.display = 'block';
                } else {
                    section.style.display = 'none';
                }
            });
        }
        
        // Show faculty detail modal
        function showFacultyDetail(facultyName) {
            // In a real application, you would fetch this data from the server
            const facultyData = {
                'Dr. Rajesh Kumar': {
                    name: 'Dr. Rajesh Kumar',
                    designation: 'Professor & Head, Security Studies',
                    qualification: 'Ph.D. in Security Studies, IIT Delhi<br>M.Sc. in Defense Studies, DSSC Wellington',
                    experience: '25 years of teaching and research experience<br>15 years with BSF Advisory Board<br>10 years as UN Security Consultant',
                    specialization: 'Border Management, Counter-Terrorism, Intelligence Analysis, Strategic Security',
                    email: 'rkumar@rjitbsf.edu.in',
                    phone: '+91-9876543210',
                    office: 'Room 205, Security Studies Block',
                    publications: '45 research papers, 8 books, 12 government reports',
                    awards: 'President\'s Medal for Distinguished Service (2018)<br>BSF Excellence Award (2020)<br>Best Faculty Award (2022)',
                    imageColor: 'bg-gradient-to-r from-blue-500 to-cyan-500'
                },
                'Col. (Retd) Sunil Sharma': {
                    name: 'Col. (Retd) Sunil Sharma',
                    designation: 'Senior Professor, Field Training',
                    qualification: 'M.Sc. Defense Studies, National Defense Academy<br>Advanced Infantry Course, Mhow',
                    experience: '32 years service in Border Security Force<br>Commander of 15th BSF Battalion<br>Chief Instructor at BSF Academy',
                    specialization: 'Field Tactics, Combat Training, Border Patrol Operations, Crisis Management',
                    email: 'ssharma@rjitbsf.edu.in',
                    phone: '+91-9876543211',
                    office: 'Field Training Command Center',
                    publications: '22 field manuals, 15 training modules',
                    awards: 'Shaurya Chakra (1999)<br>Police Medal for Gallantry (2005)<br>BSF Director General\'s Commendation (2010)',
                    imageColor: 'bg-gradient-to-r from-red-500 to-orange-500'
                }
            };
            
            const faculty = facultyData[facultyName] || facultyData['Dr. Rajesh Kumar'];
            
            const modalContent = `
                <div class="flex flex-col lg:flex-row gap-8">
                    <div class="lg:w-1/3">
                        <div class="faculty-image ${faculty.imageColor} mb-6">
                            <span>${faculty.name.split(' ').map(n => n[0]).join('')}</span>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <h4 class="font-bold text-gray-700 mb-1">Contact Information</h4>
                                <p class="text-gray-600"><i class="fas fa-envelope mr-2 text-red-500"></i> ${faculty.email}</p>
                                <p class="text-gray-600"><i class="fas fa-phone mr-2 text-green-500"></i> ${faculty.phone}</p>
                                <p class="text-gray-600"><i class="fas fa-map-marker-alt mr-2 text-blue-500"></i> ${faculty.office}</p>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-700 mb-1">Office Hours</h4>
                                <p class="text-gray-600">Monday - Friday: 10:00 AM - 4:00 PM</p>
                                <p class="text-gray-600">By appointment only</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="lg:w-2/3">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">${faculty.name}</h3>
                        <p class="text-blue-600 font-medium mb-6">${faculty.designation}</p>
                        
                        <div class="space-y-6">
                            <div>
                                <h4 class="font-bold text-gray-800 mb-2">Qualifications</h4>
                                <div class="text-gray-700">${faculty.qualification}</div>
                            </div>
                            
                            <div>
                                <h4 class="font-bold text-gray-800 mb-2">Experience</h4>
                                <div class="text-gray-700">${faculty.experience}</div>
                            </div>
                            
                            <div>
                                <h4 class="font-bold text-gray-800 mb-2">Specialization & Expertise</h4>
                                <div class="text-gray-700">${faculty.specialization}</div>
                            </div>
                            
                            <div>
                                <h4 class="font-bold text-gray-800 mb-2">Research & Publications</h4>
                                <div class="text-gray-700">${faculty.publications}</div>
                            </div>
                            
                            <div>
                                <h4 class="font-bold text-gray-800 mb-2">Awards & Recognition</h4>
                                <div class="text-gray-700">${faculty.awards}</div>
                            </div>
                            
                            <div class="flex space-x-4 pt-6">
                                <a href="#" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition">
                                    <i class="fas fa-file-pdf mr-2"></i> Download CV
                                </a>
                                <a href="#" class="px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition">
                                    <i class="fas fa-book mr-2"></i> Publications
                                </a>
                                <a href="contact.php" class="px-4 py-2 bsf-red text-white rounded-lg hover:opacity-90 transition">
                                    <i class="fas fa-envelope mr-2"></i> Contact
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('modalContent').innerHTML = modalContent;
            document.getElementById('facultyModal').style.display = 'flex';
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('facultyModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('facultyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Set first department as active
            filterFaculty('all');
            
            // Add animation to faculty cards on scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            document.querySelectorAll('.faculty-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(card);
            });
        });
    </script>
</body>
</html>