<?php
require_once 'includes/Config.php';
require_once 'includes/Auth.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null;
$userName = $_SESSION['full_name'] ?? 'Guest';

// Sample research data
$researchCenters = [
    [
        'name' => 'Center for Border Security Studies',
        'director' => 'Dr. Rajesh Kumar',
        'focus' => 'Border management, surveillance, cross-border security',
        'projects' => 15,
        'publications' => 45,
        'color' => 'from-blue-500 to-cyan-500'
    ],
    [
        'name' => 'Cyber Defense Research Lab',
        'director' => 'Dr. Priya Singh',
        'focus' => 'Cybersecurity, digital forensics, threat intelligence',
        'projects' => 12,
        'publications' => 38,
        'color' => 'from-purple-500 to-pink-500'
    ],
    [
        'name' => 'Advanced Surveillance Technology Center',
        'director' => 'Col. (Retd) Sunil Sharma',
        'focus' => 'Drone technology, satellite imaging, sensor networks',
        'projects' => 8,
        'publications' => 22,
        'color' => 'from-green-500 to-emerald-500'
    ],
    [
        'name' => 'Combat & Tactical Research Unit',
        'director' => 'Maj. Gen. (Retd) Ramesh Yadav',
        'focus' => 'Urban warfare, special operations, field tactics',
        'projects' => 10,
        'publications' => 28,
        'color' => 'from-red-500 to-orange-500'
    ]
];

$recentPublications = [
    [
        'title' => 'AI-Powered Border Surveillance System',
        'authors' => 'Dr. Rajesh Kumar, Dr. Priya Singh',
        'journal' => 'International Journal of Security Studies',
        'year' => 2024,
        'type' => 'journal'
    ],
    [
        'title' => 'Advanced Drone Detection Algorithms',
        'authors' => 'Col. Sunil Sharma, Prof. Amit Patel',
        'journal' => 'Defense Technology Review',
        'year' => 2023,
        'type' => 'conference'
    ],
    [
        'title' => 'Cyber Threat Intelligence Framework',
        'authors' => 'Dr. Priya Singh et al.',
        'journal' => 'IEEE Security & Privacy',
        'year' => 2023,
        'type' => 'journal'
    ],
    [
        'title' => 'Urban Combat Training Simulation',
        'authors' => 'Maj. Gen. Ramesh Yadav',
        'journal' => 'Military Operations Research',
        'year' => 2023,
        'type' => 'book'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Centers - RJIT College of BSF</title>
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
        
        .research-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .research-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            border-left-color: #d62828;
        }
        
        .center-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-bottom: 1.5rem;
        }
        
        .stats-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            margin: 0.25rem;
        }
        
        .projects-badge { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .pubs-badge { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .patents-badge { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .grants-badge { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
        
        .publication-card {
            border-left: 4px solid #003049;
            padding: 1.5rem;
            background: #f8f9fa;
            margin-bottom: 1rem;
            border-radius: 0 8px 8px 0;
            transition: all 0.3s ease;
        }
        
        .publication-card:hover {
            border-left-color: #d62828;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .pub-type {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .journal-pub { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .conference-pub { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .book-pub { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(to bottom, #003049, #d62828);
            border-radius: 2px;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            padding-left: 2rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -33px;
            top: 5px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: white;
            border: 4px solid #003049;
        }
        
        .research-tab {
            padding: 1rem 2rem;
            border-radius: 10px 10px 0 0;
            background: #f8f9fa;
            color: #6c757d;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .research-tab.active {
            background: white;
            color: #003049;
            border-top: 3px solid #d62828;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .lab-equipment {
            background: linear-gradient(135deg, rgba(0, 48, 73, 0.9), rgba(26, 75, 122, 0.9));
            color: white;
            border-radius: 15px;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .lab-equipment::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: radial-gradient(circle at top right, rgba(255,255,255,0.1) 1%, transparent 20%);
        }
        
        .achievement-badge {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: white;
            font-weight: bold;
            margin: 0 auto;
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
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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
                <a href="research.php" class="text-blue-600 font-bold">
                    <i class="fas fa-flask mr-2"></i>Research
                </a>
                <a href="faculty.php" class="text-gray-700 hover:text-blue-600 transition">
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
                <span class="stats-badge projects-badge mb-4">
                    <i class="fas fa-flask mr-2"></i>Cutting-Edge Research
                </span>
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">
                    Research & <span class="bsf-text-gradient">Innovation</span>
                </h1>
                <p class="text-xl text-white/80 max-w-3xl mx-auto">
                    Pioneering security research that shapes national border protection strategies. 
                    From advanced surveillance to cybersecurity, our work protects the nation.
                </p>
            </div>
            
            <!-- Research Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
                <div class="text-center">
                    <div class="achievement-badge bsf-red mx-auto mb-4">
                        <div class="text-3xl">45+</div>
                    </div>
                    <p class="text-white font-medium">Active Projects</p>
                </div>
                
                <div class="text-center">
                    <div class="achievement-badge bsf-blue mx-auto mb-4">
                        <div class="text-3xl">150+</div>
                    </div>
                    <p class="text-white font-medium">Publications</p>
                </div>
                
                <div class="text-center">
                    <div class="achievement-badge bg-gradient-to-r from-green-500 to-emerald-600 mx-auto mb-4">
                        <div class="text-3xl">₹25Cr+</div>
                    </div>
                    <p class="text-white font-medium">Research Grants</p>
                </div>
                
                <div class="text-center">
                    <div class="achievement-badge bg-gradient-to-r from-purple-500 to-pink-600 mx-auto mb-4">
                        <div class="text-3xl">12</div>
                    </div>
                    <p class="text-white font-medium">Patents Filed</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 pb-16">
        <!-- Research Centers -->
        <div class="mb-16">
            <h2 class="text-3xl font-bold text-white mb-8 section-title">Research Centers</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php foreach ($researchCenters as $center): ?>
                <div class="research-card bsf-card rounded-2xl p-8">
                    <div class="center-icon bg-gradient-to-r <?= $center['color'] ?>">
                        <i class="fas fa-<?= 
                            $center['name'] === 'Center for Border Security Studies' ? 'shield-alt' : 
                            ($center['name'] === 'Cyber Defense Research Lab' ? 'laptop-code' : 
                            ($center['name'] === 'Advanced Surveillance Technology Center' ? 'eye' : 'dumbbell')) 
                        ?>"></i>
                    </div>
                    
                    <h3 class="text-2xl font-bold text-gray-900 mb-4"><?= $center['name'] ?></h3>
                    <p class="text-gray-600 mb-6"><?= $center['focus'] ?></p>
                    
                    <div class="mb-6">
                        <h4 class="font-bold text-gray-800 mb-2">Director:</h4>
                        <p class="text-gray-700"><?= $center['director'] ?></p>
                    </div>
                    
                    <div class="flex flex-wrap gap-2 mb-8">
                        <span class="stats-badge projects-badge">
                            <i class="fas fa-project-diagram mr-1"></i> <?= $center['projects'] ?> Projects
                        </span>
                        <span class="stats-badge pubs-badge">
                            <i class="fas fa-book mr-1"></i> <?= $center['publications'] ?> Publications
                        </span>
                    </div>
                    
                    <button onclick="showCenterDetail('<?= $center['name'] ?>')" 
                            class="w-full bsf-red text-white py-3 rounded-lg hover:opacity-90 transition font-medium">
                        <i class="fas fa-info-circle mr-2"></i> Learn More
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Research Tabs -->
        <div class="mb-16">
            <div class="flex flex-wrap border-b mb-8">
                <div class="research-tab active" onclick="showResearchTab('publications')">
                    <i class="fas fa-book mr-2"></i>Recent Publications
                </div>
                <div class="research-tab" onclick="showResearchTab('projects')">
                    <i class="fas fa-project-diagram mr-2"></i>Ongoing Projects
                </div>
                <div class="research-tab" onclick="showResearchTab('collaborations')">
                    <i class="fas fa-handshake mr-2"></i>Collaborations
                </div>
                <div class="research-tab" onclick="showResearchTab('facilities')">
                    <i class="fas fa-microscope mr-2"></i>Research Facilities
                </div>
            </div>
            
            <!-- Publications Tab -->
            <div id="publications" class="research-tab-content">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-6">Recent Publications</h3>
                        <div class="space-y-4">
                            <?php foreach ($recentPublications as $pub): ?>
                            <div class="publication-card">
                                <div class="flex justify-between items-start mb-3">
                                    <span class="pub-type <?= 
                                        $pub['type'] === 'journal' ? 'journal-pub' : 
                                        ($pub['type'] === 'conference' ? 'conference-pub' : 'book-pub')
                                    ?>">
                                        <i class="fas fa-<?= 
                                            $pub['type'] === 'journal' ? 'newspaper' : 
                                            ($pub['type'] === 'conference' ? 'users' : 'book')
                                        ?> mr-1"></i>
                                        <?= ucfirst($pub['type']) ?>
                                    </span>
                                    <span class="text-gray-500 text-sm"><?= $pub['year'] ?></span>
                                </div>
                                <h4 class="font-bold text-gray-900 mb-2"><?= $pub['title'] ?></h4>
                                <p class="text-gray-600 text-sm mb-2"><?= $pub['authors'] ?></p>
                                <p class="text-blue-600 text-sm">
                                    <i class="fas fa-book-open mr-1"></i> <?= $pub['journal'] ?>
                                </p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-8 text-center">
                            <a href="#" class="inline-flex items-center bsf-red text-white px-6 py-3 rounded-lg hover:opacity-90 transition">
                                <i class="fas fa-download mr-3"></i> Download Publications List
                                <i class="fas fa-arrow-right ml-3"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-6">Research Timeline</h3>
                        <div class="bsf-card rounded-2xl p-8">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <h4 class="font-bold text-gray-900 mb-2">2024 - Current</h4>
                                    <p class="text-gray-600">AI-powered border surveillance system deployment</p>
                                </div>
                                <div class="timeline-item">
                                    <h4 class="font-bold text-gray-900 mb-2">2023</h4>
                                    <p class="text-gray-600">Cyber defense framework for critical infrastructure</p>
                                </div>
                                <div class="timeline-item">
                                    <h4 class="font-bold text-gray-900 mb-2">2022</h4>
                                    <p class="text-gray-600">Advanced drone detection technology patent</p>
                                </div>
                                <div class="timeline-item">
                                    <h4 class="font-bold text-gray-900 mb-2">2021</h4>
                                    <p class="text-gray-600">Smart border fencing system implementation</p>
                                </div>
                                <div class="timeline-item">
                                    <h4 class="font-bold text-gray-900 mb-2">2020</h4>
                                    <p class="text-gray-600">Biometric identification system for border checkpoints</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Projects Tab -->
            <div id="projects" class="research-tab-content hidden">
                <div class="bsf-card rounded-2xl p-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Ongoing Research Projects</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="p-4 text-left text-gray-700 font-bold">Project Title</th>
                                    <th class="p-4 text-left text-gray-700 font-bold">Center</th>
                                    <th class="p-4 text-left text-gray-700 font-bold">Duration</th>
                                    <th class="p-4 text-left text-gray-700 font-bold">Funding</th>
                                    <th class="p-4 text-left text-gray-700 font-bold">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="p-4 font-medium">AI-Based Threat Prediction System</td>
                                    <td class="p-4">Cyber Defense Lab</td>
                                    <td class="p-4">2023-2025</td>
                                    <td class="p-4">₹5.2 Cr</td>
                                    <td class="p-4"><span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Active</span></td>
                                </tr>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="p-4 font-medium">Smart Border Surveillance Network</td>
                                    <td class="p-4">Border Security Center</td>
                                    <td class="p-4">2022-2024</td>
                                    <td class="p-4">₹8.5 Cr</td>
                                    <td class="p-4"><span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">Testing</span></td>
                                </tr>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="p-4 font-medium">Quantum Encryption for Secure Comms</td>
                                    <td class="p-4">Technology Center</td>
                                    <td class="p-4">2024-2026</td>
                                    <td class="p-4">₹12 Cr</td>
                                    <td class="p-4"><span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">Planning</span></td>
                                </tr>
                                <tr class="hover:bg-gray-50">
                                    <td class="p-4 font-medium">Urban Combat Simulation Platform</td>
                                    <td class="p-4">Combat Research Unit</td>
                                    <td class="p-4">2023-2024</td>
                                    <td class="p-4">₹3.8 Cr</td>
                                    <td class="p-4"><span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Active</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Facilities Tab -->
            <div id="facilities" class="research-tab-content hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="lab-equipment">
                        <h3 class="text-2xl font-bold text-white mb-6">Advanced Research Facilities</h3>
                        <ul class="space-y-4">
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-400 mr-3"></i>
                                <span>Quantum Computing Lab</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-400 mr-3"></i>
                                <span>Cyber Range (Attack/Defense Simulation)</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-400 mr-3"></i>
                                <span>Drone Technology Testing Facility</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-400 mr-3"></i>
                                <span>Secure Communication Lab</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-green-400 mr-3"></i>
                                <span>Forensic Analysis Center</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="bsf-card rounded-2xl p-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6">Equipment & Resources</h3>
                        <div class="space-y-6">
                            <div>
                                <h4 class="font-bold text-gray-800 mb-2">High-Performance Computing</h4>
                                <p class="text-gray-600">200+ node cluster for AI/ML research and simulation</p>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800 mb-2">Surveillance Technology</h4>
                                <p class="text-gray-600">Thermal imaging, night vision, satellite monitoring systems</p>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800 mb-2">Testing Ranges</h4>
                                <p class="text-gray-600">25-acre field testing facility for border security solutions</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Research Opportunities -->
        <div class="bsf-card rounded-2xl p-8 mb-16">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 section-title">Research Opportunities</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="h-20 w-20 rounded-full bsf-red flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-user-graduate text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">PhD Programs</h3>
                    <p class="text-gray-600">Fully-funded doctoral research in security studies and defense technology</p>
                    <a href="admission.php" class="inline-block mt-4 text-blue-600 hover:text-blue-800 font-medium">
                        Learn More <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
                
                <div class="text-center">
                    <div class="h-20 w-20 rounded-full bsf-blue flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-briefcase text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Post-Doctoral Fellowships</h3>
                    <p class="text-gray-600">Research positions for recent PhD graduates in cutting-edge security projects</p>
                    <a href="#" class="inline-block mt-4 text-blue-600 hover:text-blue-800 font-medium">
                        View Openings <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
                
                <div class="text-center">
                    <div class="h-20 w-20 rounded-full bg-gradient-to-r from-green-500 to-emerald-600 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-handshake text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Industry Collaboration</h3>
                    <p class="text-gray-600">Partnership opportunities for defense contractors and technology firms</p>
                    <a href="contact.php" class="inline-block mt-4 text-blue-600 hover:text-blue-800 font-medium">
                        Contact Us <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Security Notice -->
        <div class="bsf-card rounded-2xl p-8">
            <div class="flex items-start">
                <div class="mr-6">
                    <div class="h-16 w-16 rounded-full bsf-red flex items-center justify-center">
                        <i class="fas fa-lock text-white text-2xl"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Security Classification Notice</h3>
                    <p class="text-gray-700 mb-6">
                        All research conducted at RJIT College of BSF is subject to national security 
                        protocols. Certain projects and findings are classified under the 
                        Official Secrets Act. Publication and dissemination of research outcomes 
                        require prior clearance from the Ministry of Home Affairs.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <span class="stats-badge grants-badge">
                            <i class="fas fa-shield-alt mr-1"></i> Classified Research
                        </span>
                        <span class="stats-badge patents-badge">
                            <i class="fas fa-file-contract mr-1"></i> Patent Protected
                        </span>
                        <span class="stats-badge projects-badge">
                            <i class="fas fa-user-secret mr-1"></i> Security Clearance Required
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Research Center Detail Modal -->
    <div id="centerModal" class="modal-overlay">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">Research Center Details</h3>
                    <button onclick="closeCenterModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
                
                <div id="centerContent">
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
                        Pioneering research in border security, defense technology, 
                        and national protection strategies.
                    </p>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">Research Links</h4>
                    <ul class="space-y-3">
                        <li><a href="research.php" class="text-gray-400 hover:text-white transition">Research Centers</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Publications</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Research Projects</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Collaborations</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Research Funding</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">Quick Access</h4>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-download text-red-500 mr-3 mt-1"></i>
                            <a href="#" class="text-gray-400 hover:text-white transition">Research Reports</a>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-calendar text-red-500 mr-3 mt-1"></i>
                            <a href="#" class="text-gray-400 hover:text-white transition">Research Calendar</a>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-graduation-cap text-red-500 mr-3 mt-1"></i>
                            <a href="#" class="text-gray-400 hover:text-white transition">PhD Programs</a>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-microscope text-red-500 mr-3 mt-1"></i>
                            <a href="#" class="text-gray-400 hover:text-white transition">Lab Facilities</a>
                        </li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">Research Office</h4>
                    <div class="space-y-4">
                        <div>
                            <div class="text-2xl font-bold text-blue-400 mb-1">+91-11-2345-6795</div>
                            <p class="text-gray-400 text-sm">Research Department</p>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-green-400 mb-1">research@rjitbsf.edu.in</div>
                            <p class="text-gray-400 text-sm">Email Inquiries</p>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-yellow-400 mb-1">Building C, Floor 3</div>
                            <p class="text-gray-400 text-sm">Research Complex</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-12 pt-8 border-t border-gray-800 text-center">
                <p class="text-gray-500 text-sm">
                    &copy; <?= date('Y') ?> RJIT College of BSF. All Rights Reserved.
                </p>
                <p class="text-gray-600 text-xs mt-2">
                    Research activities are conducted under strict security protocols as per government guidelines.
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Research tab functionality
        function showResearchTab(tabName) {
            // Hide all tab content
            document.querySelectorAll('.research-tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.research-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.remove('hidden');
            
            // Add active class to clicked tab
            event.currentTarget.classList.add('active');
        }
        
        // Show research center detail
        function showCenterDetail(centerName) {
            const centerData = {
                'Center for Border Security Studies': {
                    name: 'Center for Border Security Studies',
                    director: 'Dr. Rajesh Kumar',
                    established: '2015',
                    focus: 'Border management strategies, surveillance technology, cross-border security protocols, intelligence analysis frameworks',
                    projects: [
                        'Smart Border Fencing System',
                        'Cross-Border Threat Prediction Model',
                        'Integrated Surveillance Network',
                        'Border Community Engagement Program'
                    ],
                    facilities: [
                        'Border Simulation Lab',
                        'Intelligence Analysis Center',
                        'Field Research Station',
                        'GIS Mapping Facility'
                    ],
                    achievements: [
                        'Implemented border management system across 3 states',
                        'Reduced illegal crossings by 45% in pilot areas',
                        '15 patents filed in surveillance technology',
                        'UN Recognition for Best Practices (2022)'
                    ]
                },
                'Cyber Defense Research Lab': {
                    name: 'Cyber Defense Research Lab',
                    director: 'Dr. Priya Singh',
                    established: '2018',
                    focus: 'Cybersecurity frameworks, threat intelligence, digital forensics, secure communication protocols',
                    projects: [
                        'AI-Powered Threat Detection',
                        'Quantum Encryption Systems',
                        'Critical Infrastructure Protection',
                        'Cyber Attack Simulation Platform'
                    ],
                    facilities: [
                        'Cyber Range (Attack/Defense)',
                        'Digital Forensics Lab',
                        'Secure Communication Center',
                        'Quantum Computing Testbed'
                    ],
                    achievements: [
                        'Developed national cybersecurity framework',
                        'Detected and prevented 500+ cyber attacks',
                        'Trained 2000+ cybersecurity professionals',
                        'ISO 27001 Certified Facility'
                    ]
                }
            };
            
            const center = centerData[centerName] || centerData['Center for Border Security Studies'];
            
            const content = `
                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="center-icon bg-gradient-to-r ${centerName === 'Center for Border Security Studies' ? 'from-blue-500 to-cyan-500' : 'from-purple-500 to-pink-500'}">
                            <i class="fas fa-${centerName === 'Center for Border Security Studies' ? 'shield-alt' : 'laptop-code'}"></i>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">${center.name}</h3>
                            <p class="text-gray-600">Established: ${center.established} | Director: ${center.director}</p>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-gray-800 mb-2">Research Focus</h4>
                        <p class="text-gray-700">${center.focus}</p>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-gray-800 mb-2">Major Projects</h4>
                        <ul class="list-disc pl-5 text-gray-700 space-y-1">
                            ${center.projects.map(project => `<li>${project}</li>`).join('')}
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-gray-800 mb-2">Facilities</h4>
                        <ul class="list-disc pl-5 text-gray-700 space-y-1">
                            ${center.facilities.map(facility => `<li>${facility}</li>`).join('')}
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-gray-800 mb-2">Key Achievements</h4>
                        <ul class="list-disc pl-5 text-gray-700 space-y-1">
                            ${center.achievements.map(achievement => `<li>${achievement}</li>`).join('')}
                        </ul>
                    </div>
                    
                    <div class="pt-6 border-t">
                        <div class="flex space-x-4">
                            <a href="contact.php" class="px-4 py-2 bsf-red text-white rounded-lg hover:opacity-90 transition">
                                <i class="fas fa-envelope mr-2"></i> Contact Center
                            </a>
                            <a href="#" class="px-4 py-2 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition">
                                <i class="fas fa-download mr-2"></i> Annual Report
                            </a>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('centerContent').innerHTML = content;
            document.getElementById('centerModal').style.display = 'flex';
        }
        
        // Close modal
        function closeCenterModal() {
            document.getElementById('centerModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('centerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCenterModal();
            }
        });
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Show first tab by default
            showResearchTab('publications');
            
            // Add animations to research cards
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            document.querySelectorAll('.research-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(card);
            });
            
            // Animate publication cards on scroll
            document.querySelectorAll('.publication-card').forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateX(-20px)';
                card.style.transition = `opacity 0.5s ease ${index * 0.1}s, transform 0.5s ease ${index * 0.1}s`;
                
                const pubObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateX(0)';
                        }
                    });
                }, { threshold: 0.1 });
                
                pubObserver.observe(card);
            });
        });
    </script>
</body>
</html>