<?php
require_once 'includes/Config.php';
require_once 'includes/Auth.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null;
$userName = $_SESSION['full_name'] ?? 'Guest';

// Admission status check
$admissionOpen = true; // Would come from database
$lastDate = '2024-03-31';
$availableSeats = 150;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admissions - RJIT College of BSF</title>
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
        
        .admission-timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .admission-timeline::before {
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
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -38px;
            top: 5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: white;
            border: 4px solid #003049;
        }
        
        .timeline-item.active::before {
            border-color: #d62828;
            background: #d62828;
            animation: pulse 2s infinite;
        }
        
        .timeline-item.completed::before {
            border-color: #28a745;
            background: #28a745;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(214, 40, 40, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(214, 40, 40, 0); }
            100% { box-shadow: 0 0 0 0 rgba(214, 40, 40, 0); }
        }
        
        .requirement-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border-left: 4px solid #003049;
            transition: all 0.3s ease;
        }
        
        .requirement-card:hover {
            border-left-color: #d62828;
            transform: translateX(5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .eligibility-tag {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.875rem;
            font-weight: 500;
            margin: 0.25rem;
        }
        
        .mandatory-tag { background: rgba(214, 40, 40, 0.1); color: #d62828; border: 1px solid rgba(214, 40, 40, 0.3); }
        .optional-tag { background: rgba(0, 48, 73, 0.1); color: #003049; border: 1px solid rgba(0, 48, 73, 0.3); }
        
        .admission-counter {
            background: linear-gradient(135deg, #003049, #1a4b7a);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .admission-counter::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            opacity: 0.3;
        }
        
        .counter-number {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .form-step {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .form-step.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 3rem;
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e2e8f0;
            z-index: 1;
        }
        
        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            width: 100px;
        }
        
        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin: 0 auto 0.5rem;
            transition: all 0.3s ease;
        }
        
        .step.active .step-circle {
            background: #d62828;
            color: white;
        }
        
        .step.completed .step-circle {
            background: #28a745;
            color: white;
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
                <a href="academics.php" class="text-gray-700 hover:text-blue-600 transition">
                    <i class="fas fa-graduation-cap mr-2"></i>Academics
                </a>
                <a href="admission.php" class="text-blue-600 font-bold">
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
                        <i class="fas fa-user-plus mr-2"></i>Apply Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="py-16 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-12">
                <span class="eligibility-tag mandatory-tag mb-4">
                    <i class="fas fa-calendar-check mr-2"></i>
                    <?= $admissionOpen ? 'Admissions Open' : 'Admissions Closed' ?>
                </span>
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">
                    Join the <span class="bsf-text-gradient">BSF Academy</span>
                </h1>
                <p class="text-xl text-white/80 max-w-3xl mx-auto">
                    Begin your journey to become a Border Security Force officer. 
                    Rigorous selection process ensures only the best join our ranks.
                </p>
            </div>
            
            <!-- Admission Counter -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <div class="admission-counter">
                    <div class="counter-number"><?= $availableSeats ?></div>
                    <p class="text-white/80">Seats Available</p>
                </div>
                <div class="admission-counter">
                    <div class="counter-number"><?= date('d M', strtotime($lastDate)) ?></div>
                    <p class="text-white/80">Last Date to Apply</p>
                </div>
                <div class="admission-counter">
                    <div class="counter-number">5:1</div>
                    <p class="text-white/80">Selection Ratio</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 pb-16">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column - Process & Requirements -->
            <div class="lg:col-span-2">
                <!-- Admission Process -->
                <div class="bsf-card rounded-2xl p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 section-title">Admission Process</h2>
                    
                    <div class="admission-timeline">
                        <div class="timeline-item completed">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">1. Online Application</h3>
                            <p class="text-gray-600 mb-4">Submit application form with required documents</p>
                            <div class="text-sm text-green-600">
                                <i class="fas fa-check-circle mr-2"></i>Currently accepting applications
                            </div>
                        </div>
                        
                        <div class="timeline-item active">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">2. Written Examination</h3>
                            <p class="text-gray-600 mb-4">General knowledge, reasoning, and security awareness test</p>
                            <div class="text-sm text-red-600">
                                <i class="fas fa-calendar-alt mr-2"></i>15 April 2024
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">3. Physical Fitness Test</h3>
                            <p class="text-gray-600 mb-4">Endurance, strength, and combat readiness assessment</p>
                        </div>
                        
                        <div class="timeline-item">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">4. Medical Examination</h3>
                            <p class="text-gray-600 mb-4">Comprehensive health and fitness evaluation</p>
                        </div>
                        
                        <div class="timeline-item">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">5. Personal Interview</h3>
                            <p class="text-gray-600 mb-4">Panel interview with senior BSF officers</p>
                        </div>
                        
                        <div class="timeline-item">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">6. Security Clearance</h3>
                            <p class="text-gray-600 mb-4">Background verification and security check</p>
                        </div>
                        
                        <div class="timeline-item">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">7. Final Selection</h3>
                            <p class="text-gray-600 mb-4">Merit list publication and admission offer</p>
                        </div>
                    </div>
                </div>
                
                <!-- Eligibility Requirements -->
                <div class="bsf-card rounded-2xl p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 section-title">Eligibility Requirements</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="requirement-card">
                            <div class="flex items-center mb-4">
                                <div class="h-10 w-10 rounded-full bsf-red flex items-center justify-center mr-4">
                                    <i class="fas fa-user-check text-white"></i>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">Nationality</h3>
                            </div>
                            <ul class="text-gray-600 space-y-2">
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                    <span>Indian citizen</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                    <span>Valid proof of citizenship</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-times text-red-500 mr-2 mt-1"></i>
                                    <span>No dual citizenship allowed</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="requirement-card">
                            <div class="flex items-center mb-4">
                                <div class="h-10 w-10 rounded-full bsf-blue flex items-center justify-center mr-4">
                                    <i class="fas fa-birthday-cake text-white"></i>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">Age Limit</h3>
                            </div>
                            <ul class="text-gray-600 space-y-2">
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                    <span>18-23 years (General)</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                    <span>18-26 years (OBC/SC/ST)</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                    <span>Age as on 1st July 2024</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="requirement-card">
                            <div class="flex items-center mb-4">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-r from-green-500 to-emerald-600 flex items-center justify-center mr-4">
                                    <i class="fas fa-graduation-cap text-white"></i>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">Educational Qualification</h3>
                            </div>
                            <ul class="text-gray-600 space-y-2">
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                    <span>10+2 from recognized board</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                                    <span>Minimum 60% aggregate</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-star text-yellow-500 mr-2 mt-1"></i>
                                    <span>Mathematics compulsory for B.Tech</span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="requirement-card">
                            <div class="flex items-center mb-4">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-r from-purple-500 to-pink-600 flex items-center justify-center mr-4">
                                    <i class="fas fa-running text-white"></i>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900">Physical Standards</h3>
                            </div>
                            <ul class="text-gray-600 space-y-2">
                                <li class="flex items-start">
                                    <i class="fas fa-ruler-vertical text-blue-500 mr-2 mt-1"></i>
                                    <span>Height: 167 cm (Male), 157 cm (Female)</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-weight text-blue-500 mr-2 mt-1"></i>
                                    <span>Proportionate weight</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-eye text-blue-500 mr-2 mt-1"></i>
                                    <span>6/6 vision (correctable)</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Apply Form -->
                <div class="bsf-card rounded-2xl p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 section-title">Quick Application</h2>
                    
                    <div class="step-indicator">
                        <div class="step active" data-step="1">
                            <div class="step-circle">1</div>
                            <div class="text-sm font-medium">Personal</div>
                        </div>
                        <div class="step" data-step="2">
                            <div class="step-circle">2</div>
                            <div class="text-sm font-medium">Academic</div>
                        </div>
                        <div class="step" data-step="3">
                            <div class="step-circle">3</div>
                            <div class="text-sm font-medium">Documents</div>
                        </div>
                        <div class="step" data-step="4">
                            <div class="step-circle">4</div>
                            <div class="text-sm font-medium">Submit</div>
                        </div>
                    </div>
                    
                    <form id="quickApplyForm">
                        <!-- Step 1: Personal Information -->
                        <div class="form-step active" id="step-1">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-gray-700 mb-2 font-medium">Full Name</label>
                                    <input type="text" class="form-input w-full" placeholder="Enter your full name" required>
                                </div>
                                <div>
                                    <label class="block text-gray-700 mb-2 font-medium">Date of Birth</label>
                                    <input type="date" class="form-input w-full" required>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <div></div>
                                <button type="button" class="bsf-red text-white px-6 py-2 rounded-lg" onclick="nextStep(2)">
                                    Next <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 2: Academic Information -->
                        <div class="form-step" id="step-2">
                            <div class="mb-6">
                                <label class="block text-gray-700 mb-2 font-medium">Highest Qualification</label>
                                <select class="form-input w-full" required>
                                    <option value="">Select qualification</option>
                                    <option value="12th">10+2 / Intermediate</option>
                                    <option value="graduate">Graduate</option>
                                    <option value="postgraduate">Post Graduate</option>
                                </select>
                            </div>
                            <div class="flex justify-between">
                                <button type="button" class="px-6 py-2 border border-gray-300 rounded-lg" onclick="prevStep(1)">
                                    <i class="fas fa-arrow-left mr-2"></i> Back
                                </button>
                                <button type="button" class="bsf-red text-white px-6 py-2 rounded-lg" onclick="nextStep(3)">
                                    Next <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 3: Documents -->
                        <div class="form-step" id="step-3">
                            <div class="mb-6">
                                <label class="block text-gray-700 mb-4 font-medium">Upload Documents</label>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between p-4 border border-dashed border-gray-300 rounded-lg">
                                        <div>
                                            <i class="fas fa-file-pdf text-red-500 mr-3"></i>
                                            <span>10th Marksheet</span>
                                        </div>
                                        <input type="file" accept=".pdf,.jpg,.png" class="hidden" id="marksheet10">
                                        <label for="marksheet10" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg cursor-pointer hover:bg-blue-200">
                                            Upload
                                        </label>
                                    </div>
                                    <div class="flex items-center justify-between p-4 border border-dashed border-gray-300 rounded-lg">
                                        <div>
                                            <i class="fas fa-file-pdf text-red-500 mr-3"></i>
                                            <span>12th Marksheet</span>
                                        </div>
                                        <input type="file" accept=".pdf,.jpg,.png" class="hidden" id="marksheet12">
                                        <label for="marksheet12" class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg cursor-pointer hover:bg-blue-200">
                                            Upload
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <button type="button" class="px-6 py-2 border border-gray-300 rounded-lg" onclick="prevStep(2)">
                                    <i class="fas fa-arrow-left mr-2"></i> Back
                                </button>
                                <button type="button" class="bsf-red text-white px-6 py-2 rounded-lg" onclick="nextStep(4)">
                                    Next <i class="fas fa-arrow-right ml-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 4: Review & Submit -->
                        <div class="form-step" id="step-4">
                            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle text-green-500 text-2xl mr-4"></i>
                                    <div>
                                        <h4 class="font-bold text-green-800">Ready to Submit</h4>
                                        <p class="text-green-700 text-sm">Review your information before final submission</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-between">
                                <button type="button" class="px-6 py-2 border border-gray-300 rounded-lg" onclick="prevStep(3)">
                                    <i class="fas fa-arrow-left mr-2"></i> Back
                                </button>
                                <button type="submit" class="bsf-red text-white px-6 py-2 rounded-lg hover:opacity-90">
                                    <i class="fas fa-paper-plane mr-2"></i> Submit Application
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Right Column - Important Info -->
            <div class="lg:col-span-1">
                <!-- Important Dates -->
                <div class="bsf-card rounded-2xl p-6 mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-calendar-alt mr-3 text-red-600"></i>Important Dates
                    </h3>
                    
                    <div class="space-y-4">
                        <div class="p-4 border-l-4 border-blue-500 bg-blue-50">
                            <div class="text-sm text-blue-700 font-medium">Application Start</div>
                            <div class="text-lg font-bold text-gray-900">01 Jan 2024</div>
                        </div>
                        
                        <div class="p-4 border-l-4 border-red-500 bg-red-50">
                            <div class="text-sm text-red-700 font-medium">Last Date to Apply</div>
                            <div class="text-lg font-bold text-gray-900">31 Mar 2024</div>
                        </div>
                        
                        <div class="p-4 border-l-4 border-green-500 bg-green-50">
                            <div class="text-sm text-green-700 font-medium">Exam Date</div>
                            <div class="text-lg font-bold text-gray-900">15 Apr 2024</div>
                        </div>
                        
                        <div class="p-4 border-l-4 border-purple-500 bg-purple-50">
                            <div class="text-sm text-purple-700 font-medium">Result Declaration</div>
                            <div class="text-lg font-bold text-gray-900">30 May 2024</div>
                        </div>
                    </div>
                </div>
                
                <!-- Application Fee -->
                <div class="bsf-card rounded-2xl p-6 mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-rupee-sign mr-3 text-green-600"></i>Application Fee
                    </h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-700">General / OBC</span>
                            <span class="font-bold text-gray-900">₹1200</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-700">SC / ST</span>
                            <span class="font-bold text-gray-900">₹600</span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-700">Female Candidates</span>
                            <span class="font-bold text-gray-900">₹600</span>
                        </div>
                    </div>
                    
                    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-sm text-yellow-800">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Fee is non-refundable. Payment confirmation required within 48 hours.
                        </p>
                    </div>
                </div>
                
                <!-- Helpline -->
                <div class="bsf-card rounded-2xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-headset mr-3 text-blue-600"></i>Admission Helpline
                    </h3>
                    
                    <div class="space-y-4">
                        <div>
                            <div class="text-2xl font-bold text-gray-900 mb-1">+91-11-2345-6789</div>
                            <p class="text-sm text-gray-600">General Admissions</p>
                        </div>
                        
                        <div>
                            <div class="text-lg font-bold text-gray-900 mb-1">admissions@rjitbsf.edu.in</div>
                            <p class="text-sm text-gray-600">Email Support</p>
                        </div>
                        
                        <div class="mt-6">
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-clock mr-2"></i>
                                <span>Monday to Friday: 10:00 AM - 5:00 PM</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600 mt-2">
                                <i class="fas fa-clock mr-2"></i>
                                <span>Saturday: 10:00 AM - 2:00 PM</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                        <p class="text-sm text-gray-700">
                            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                            <strong>Note:</strong> All admission-related communications are recorded for security purposes.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Security Clearance Notice -->
        <div class="mt-12">
            <div class="bsf-card rounded-2xl p-8">
                <div class="flex items-start">
                    <div class="mr-6">
                        <div class="h-16 w-16 rounded-full bsf-red flex items-center justify-center">
                            <i class="fas fa-user-shield text-white text-2xl"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Security Clearance Process</h3>
                        <p class="text-gray-700 mb-6">
                            All selected candidates must undergo comprehensive security clearance. 
                            This includes background verification, police records check, and 
                            interviews with security agencies. The process takes 4-6 weeks and 
                            is mandatory for admission confirmation.
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <span class="eligibility-tag mandatory-tag">
                                <i class="fas fa-fingerprint mr-2"></i>Background Verification
                            </span>
                            <span class="eligibility-tag mandatory-tag">
                                <i class="fas fa-file-contract mr-2"></i>Police Clearance
                            </span>
                            <span class="eligibility-tag mandatory-tag">
                                <i class="fas fa-user-check mr-2"></i>Personal Interview
                            </span>
                        </div>
                    </div>
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
                        Premier institution for Border Security Force training, 
                        education, and research.
                    </p>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">Admission Links</h4>
                    <ul class="space-y-3">
                        <li><a href="admission.php" class="text-gray-400 hover:text-white transition">Apply Online</a></li>
                        <li><a href="academics.php" class="text-gray-400 hover:text-white transition">Programs Offered</a></li>
                        <li><a href="eligibility.php" class="text-gray-400 hover:text-white transition">Eligibility Criteria</a></li>
                        <li><a href="scholarship.php" class="text-gray-400 hover:text-white transition">Scholarships</a></li>
                        <li><a href="faq.php" class="text-gray-400 hover:text-white transition">FAQ</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">Important Downloads</h4>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-file-pdf text-red-500 mr-3 mt-1"></i>
                            <a href="#" class="text-gray-400 hover:text-white transition">Prospectus 2024</a>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-file-alt text-red-500 mr-3 mt-1"></i>
                            <a href="#" class="text-gray-400 hover:text-white transition">Application Form</a>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-list-alt text-red-500 mr-3 mt-1"></i>
                            <a href="#" class="text-gray-400 hover:text-white transition">Syllabus</a>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-calendar text-red-500 mr-3 mt-1"></i>
                            <a href="#" class="text-gray-400 hover:text-white transition">Exam Schedule</a>
                        </li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">Admission Support</h4>
                    <div class="space-y-4">
                        <div>
                            <div class="text-2xl font-bold text-blue-400 mb-1">+91-11-2345-6789</div>
                            <p class="text-gray-400 text-sm">Helpline (10AM-5PM)</p>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-green-400 mb-1">help@rjitbsf.edu.in</div>
                            <p class="text-gray-400 text-sm">Email Support</p>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-yellow-400 mb-1">Walk-in Counseling</div>
                            <p class="text-gray-400 text-sm">Mon-Sat: 10AM-4PM</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-12 pt-8 border-t border-gray-800 text-center">
                <p class="text-gray-500 text-sm">
                    &copy; <?= date('Y') ?> RJIT College of BSF. All Rights Reserved.
                </p>
                <p class="text-gray-600 text-xs mt-2">
                    Admissions are subject to security clearance and final approval by BSF Headquarters.
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Form step functionality
        let currentStep = 1;
        const totalSteps = 4;
        
        function nextStep(step) {
            if (step > currentStep) {
                // Validate current step
                const currentStepElement = document.getElementById(`step-${currentStep}`);
                const inputs = currentStepElement.querySelectorAll('input[required], select[required]');
                let valid = true;
                
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        valid = false;
                        input.classList.add('border-red-500');
                    } else {
                        input.classList.remove('border-red-500');
                    }
                });
                
                if (!valid) {
                    alert('Please fill all required fields before proceeding.');
                    return;
                }
            }
            
            // Update steps
            document.getElementById(`step-${currentStep}`).classList.remove('active');
            document.querySelector(`.step[data-step="${currentStep}"]`).classList.remove('active');
            
            currentStep = step;
            
            document.getElementById(`step-${currentStep}`).classList.add('active');
            document.querySelector(`.step[data-step="${currentStep}"]`).classList.add('active');
            
            // Mark previous steps as completed
            for (let i = 1; i < currentStep; i++) {
                document.querySelector(`.step[data-step="${i}"]`).classList.add('completed');
            }
        }
        
        function prevStep(step) {
            nextStep(step);
        }
        
        // Form submission
        document.getElementById('quickApplyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show success message
            const form = document.getElementById('quickApplyForm');
            form.innerHTML = `
                <div class="text-center py-12">
                    <div class="h-20 w-20 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-check text-green-600 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Application Submitted!</h3>
                    <p class="text-gray-600 mb-8">
                        Your application has been received. Application ID: <strong>BSF2024-${Math.floor(Math.random() * 10000)}</strong><br>
                        You will receive confirmation email within 24 hours.
                    </p>
                    <div class="flex justify-center space-x-4">
                        <a href="admission.php" class="bsf-red text-white px-6 py-2 rounded-lg hover:opacity-90">
                            <i class="fas fa-print mr-2"></i>Print Receipt
                        </a>
                        <a href="dashboard.php" class="px-6 py-2 border-2 border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-tachometer-alt mr-2"></i>Track Status
                        </a>
                    </div>
                </div>
            `;
            
            // Update step indicator
            document.querySelectorAll('.step').forEach(step => {
                step.classList.add('completed');
            });
        });
        
        // File upload preview
        document.addEventListener('DOMContentLoaded', function() {
            const fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const label = this.parentElement.querySelector('label');
                    if (this.files.length > 0) {
                        label.innerHTML = `<i class="fas fa-check mr-2"></i>Uploaded`;
                        label.classList.remove('bg-blue-100', 'text-blue-700');
                        label.classList.add('bg-green-100', 'text-green-700');
                    }
                });
            });
            
            // Admission counter animation
            const counterNumber = document.querySelector('.counter-number:first-child');
            if (counterNumber) {
                let currentNumber = 0;
                const targetNumber = <?= $availableSeats ?>;
                const duration = 2000; // 2 seconds
                const steps = 60;
                const increment = targetNumber / steps;
                const interval = duration / steps;
                
                const timer = setInterval(() => {
                    currentNumber += increment;
                    if (currentNumber >= targetNumber) {
                        currentNumber = targetNumber;
                        clearInterval(timer);
                    }
                    counterNumber.textContent = Math.floor(currentNumber);
                }, interval);
            }
        });
    </script>
</body>
</html>