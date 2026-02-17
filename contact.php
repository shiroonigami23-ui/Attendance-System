<?php
require_once 'includes/Config.php';
require_once 'includes/Auth.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? null;
$userName = $_SESSION['full_name'] ?? 'Guest';

// Handle contact form submission
$formSubmitted = false;
$formError = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic form validation
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $category = $_POST['category'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';

    if (empty($name) || empty($email) || empty($category) || empty($subject) || empty($message)) {
        $formError = 'All fields are required. Please fill in all the information.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formError = 'Please enter a valid email address.';
    } else {
        // In a real application, you would:
        // 1. Send email to college administration
        // 2. Save to database
        // 3. Send confirmation email to user

        $formSubmitted = true;
        $formSuccess = 'Thank you for contacting RJIT College of BSF. Your message has been received. We will get back to you within 24-48 hours.';

        // Send email using Mailer class
        require_once 'includes/Mailer.php';
        $emailBody = "
            <h3>New Contact Form Submission</h3>
            <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
            <p><strong>Category:</strong> " . htmlspecialchars($category) . "</p>
            <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>
            <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
        ";

        // Send to admin (configure admin email in Config or here)
        $adminEmail = getenv('ADMIN_EMAIL') ?: 'admin@example.com';
        Mailer::send($adminEmail, "Contact Form: $subject", $emailBody, $email);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - RJIT College of BSF</title>
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

        .bsf-border {
            border-color: #003049;
        }

        .bsf-text-gradient {
            background: linear-gradient(90deg, #003049, #d62828);
            background-clip: text;
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

        .emergency-card {
            background: linear-gradient(135deg, #d62828 0%, #b71c1c 100%);
            color: white;
            animation: pulse-emergency 2s infinite;
        }

        @keyframes pulse-emergency {
            0% {
                box-shadow: 0 0 0 0 rgba(214, 40, 40, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(214, 40, 40, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(214, 40, 40, 0);
            }
        }

        .map-container {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .bsf-badge {
            background: linear-gradient(90deg, #003049, #1a4b7a);
            color: white;
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .form-input {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            border-color: #003049;
            box-shadow: 0 0 0 3px rgba(0, 48, 73, 0.1);
            outline: none;
        }

        .bsf-button {
            background: linear-gradient(90deg, #003049, #1a4b7a);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .bsf-button:hover {
            background: linear-gradient(90deg, #d62828, #f77f00);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(214, 40, 40, 0.2);
        }

        .bsf-alert {
            border-left: 4px solid #d62828;
            padding: 1rem;
            background: linear-gradient(90deg, rgba(214, 40, 40, 0.1), transparent);
            border-radius: 0 8px 8px 0;
        }

        .bsf-success {
            border-left: 4px solid #28a745;
            padding: 1rem;
            background: linear-gradient(90deg, rgba(40, 167, 69, 0.1), transparent);
            border-radius: 0 8px 8px 0;
        }

        .department-tag {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.875rem;
            font-weight: 500;
            margin: 0.25rem;
        }

        .security-tag {
            background: rgba(214, 40, 40, 0.1);
            color: #d62828;
            border: 1px solid rgba(214, 40, 40, 0.3);
        }

        .academic-tag {
            background: rgba(0, 48, 73, 0.1);
            color: #003049;
            border: 1px solid rgba(0, 48, 73, 0.3);
        }

        .admin-tag {
            background: rgba(247, 127, 0, 0.1);
            color: #f77f00;
            border: 1px solid rgba(247, 127, 0, 0.3);
        }

        .tech-tag {
            background: rgba(0, 104, 55, 0.1);
            color: #006837;
            border: 1px solid rgba(0, 104, 55, 0.3);
        }

        .timeline-item {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 2rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #d62828;
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            left: 5px;
            top: 12px;
            width: 2px;
            height: calc(100% + 1rem);
            background: #003049;
        }

        .timeline-item:last-child::after {
            display: none;
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
                <a href="contact.php" class="text-blue-600 font-bold">
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
                <span class="bsf-badge mb-4">
                    <i class="fas fa-phone-volume mr-2"></i>Contact Center
                </span>
                <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">
                    Contact <span class="bsf-text-gradient">RJIT College of BSF</span>
                </h1>
                <p class="text-xl text-white/80 max-w-3xl mx-auto">
                    Reach out to India's premier Border Security Force training institute.
                    We're here to assist with admissions, training programs, and security education.
                </p>
            </div>

            <!-- Emergency Alert -->
            <div class="emergency-card rounded-2xl p-6 mb-12 max-w-4xl mx-auto">
                <div class="flex items-center">
                    <div class="mr-4">
                        <i class="fas fa-exclamation-triangle text-3xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">Emergency Contact</h3>
                        <p class="text-white/90">
                            For security emergencies, immediate assistance, or critical campus incidents,
                            contact the BSF Quick Response Team available 24/7.
                        </p>
                        <div class="mt-4 flex items-center">
                            <i class="fas fa-phone-volume text-2xl mr-3"></i>
                            <div>
                                <div class="text-2xl font-bold">1077 (Toll-Free)</div>
                                <div class="text-sm opacity-90">Or dial 1800-XXX-BSF</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 pb-16">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Contact Information -->
            <div class="lg:col-span-2">
                <!-- Contact Form -->
                <div class="bsf-card rounded-2xl p-8 mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 section-title">Send us a Message</h2>

                    <?php if ($formError): ?>
                        <div class="bsf-alert mb-6">
                            <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                            <?= htmlspecialchars($formError) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($formSuccess): ?>
                        <div class="bsf-success mb-6">
                            <i class="fas fa-check-circle text-green-600 mr-2"></i>
                            <?= htmlspecialchars($formSuccess) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!$formSubmitted): ?>
                        <form method="POST" action="">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-gray-700 mb-2 font-medium">
                                        <i class="fas fa-user mr-2 text-blue-600"></i>Full Name
                                    </label>
                                    <input type="text" name="name" required
                                        class="form-input w-full"
                                        placeholder="Enter your full name"
                                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                                </div>

                                <div>
                                    <label class="block text-gray-700 mb-2 font-medium">
                                        <i class="fas fa-envelope mr-2 text-blue-600"></i>Email Address
                                    </label>
                                    <input type="email" name="email" required
                                        class="form-input w-full"
                                        placeholder="your.email@example.com"
                                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mb-6">
                                <label class="block text-gray-700 mb-2 font-medium">
                                    <i class="fas fa-tag mr-2 text-blue-600"></i>Category
                                </label>
                                <select name="category" required class="form-input w-full">
                                    <option value="">Select a category</option>
                                    <option value="admission" <?= ($_POST['category'] ?? '') == 'admission' ? 'selected' : '' ?>>Admissions & Enrollment</option>
                                    <option value="academic" <?= ($_POST['category'] ?? '') == 'academic' ? 'selected' : '' ?>>Academic Programs</option>
                                    <option value="training" <?= ($_POST['category'] ?? '') == 'training' ? 'selected' : '' ?>>BSF Training Programs</option>
                                    <option value="security" <?= ($_POST['category'] ?? '') == 'security' ? 'selected' : '' ?>>Security Clearance</option>
                                    <option value="faculty" <?= ($_POST['category'] ?? '') == 'faculty' ? 'selected' : '' ?>>Faculty & Staff</option>
                                    <option value="technical" <?= ($_POST['category'] ?? '') == 'technical' ? 'selected' : '' ?>>Technical Support</option>
                                    <option value="other" <?= ($_POST['category'] ?? '') == 'other' ? 'selected' : '' ?>>Other Inquiry</option>
                                </select>
                            </div>

                            <div class="mb-6">
                                <label class="block text-gray-700 mb-2 font-medium">
                                    <i class="fas fa-pen mr-2 text-blue-600"></i>Subject
                                </label>
                                <input type="text" name="subject" required
                                    class="form-input w-full"
                                    placeholder="Brief description of your inquiry"
                                    value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
                            </div>

                            <div class="mb-8">
                                <label class="block text-gray-700 mb-2 font-medium">
                                    <i class="fas fa-comment-alt mr-2 text-blue-600"></i>Your Message
                                </label>
                                <textarea name="message" required rows="6"
                                    class="form-input w-full"
                                    placeholder="Please provide detailed information about your inquiry..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-600">
                                    <i class="fas fa-shield-alt mr-2 text-blue-600"></i>
                                    Your information is secure and confidential
                                </div>
                                <button type="submit" class="bsf-button">
                                    <i class="fas fa-paper-plane mr-2"></i>Send Message
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <div class="h-20 w-20 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-check text-green-600 text-3xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">Message Sent Successfully!</h3>
                            <p class="text-gray-600 mb-8">We've received your inquiry and will respond within 24-48 hours.</p>
                            <div class="flex justify-center space-x-4">
                                <a href="contact.php" class="bsf-button">
                                    <i class="fas fa-undo mr-2"></i>Send Another Message
                                </a>
                                <a href="index.php" class="px-6 py-3 border-2 border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition">
                                    <i class="fas fa-home mr-2"></i>Return Home
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Campus Location -->
                <div class="bsf-card rounded-2xl p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 section-title">Campus Location</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 mb-4">RJIT College of BSF Campus</h3>
                            <p class="text-gray-600 mb-6">
                                Located in a secure, state-of-the-art facility designed specifically for
                                Border Security Force training and education. Our campus features advanced
                                security systems, training grounds, and academic buildings.
                            </p>

                            <div class="space-y-4">
                                <div class="flex items-start">
                                    <div class="contact-icon bsf-red text-white mr-4">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-800">Main Campus Address</h4>
                                        <p class="text-gray-600">
                                            RJIT College of BSF<br>
                                            Border Security Force Training Complex<br>
                                            Sector 5, Security Zone<br>
                                            New Delhi - 110001<br>
                                            India
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start">
                                    <div class="contact-icon bsf-blue text-white mr-4">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-800">Visiting Hours</h4>
                                        <p class="text-gray-600">
                                            <strong>Monday - Friday:</strong> 9:00 AM - 5:00 PM<br>
                                            <strong>Saturday:</strong> 9:00 AM - 2:00 PM<br>
                                            <strong>Sunday:</strong> Closed (Security Drills)<br>
                                            <em class="text-sm">*Prior appointment required for all visitors</em>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="map-container">
                            <!-- Map Placeholder -->
                            <div class="bg-gray-200 h-full rounded-lg flex items-center justify-center">
                                <div class="text-center p-8">
                                    <i class="fas fa-map-marked-alt text-4xl text-gray-400 mb-4"></i>
                                    <h4 class="font-bold text-gray-700 mb-2">Campus Map</h4>
                                    <p class="text-gray-600 text-sm">
                                        Secure location - Map available to authorized personnel only
                                    </p>
                                    <div class="mt-4">
                                        <span class="department-tag security-tag">
                                            <i class="fas fa-shield-alt mr-1"></i>Restricted Area
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Quick Contacts -->
                <div class="bsf-card rounded-2xl p-6 mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-address-book mr-3 text-red-600"></i>Quick Contacts
                    </h3>

                    <div class="space-y-6">
                        <div>
                            <h4 class="font-bold text-gray-800 mb-2">Admissions Office</h4>
                            <p class="text-gray-600 mb-1">
                                <i class="fas fa-phone-alt text-blue-600 mr-2"></i>
                                +91-(07524)-274320
                            </p>
                            <p class="text-gray-600 mb-1">
                                <i class="fas fa-envelope text-blue-600 mr-2"></i>
                                rjit_bsft@yahoo.com
                            </p>
                            <p class="text-sm text-gray-500">Mon-Fri, 10AM-4PM</p>
                        </div>

                        <div>
                            <h4 class="font-bold text-gray-800 mb-2">Training Command</h4>
                            <p class="text-gray-600 mb-1">
                                <i class="fas fa-phone-alt text-blue-600 mr-2"></i>
                                +91-11-2345-6790
                            </p>
                            <p class="text-gray-600 mb-1">
                                <i class="fas fa-envelope text-blue-600 mr-2"></i>
                                training@rjitbsf.edu.in
                            </p>
                            <p class="text-sm text-gray-500">BSF Training Programs</p>
                        </div>

                        <div>
                            <h4 class="font-bold text-gray-800 mb-2">Security Clearance</h4>
                            <p class="text-gray-600 mb-1">
                                <i class="fas fa-phone-alt text-blue-600 mr-2"></i>
                                +91-11-2345-6791
                            </p>
                            <p class="text-gray-600 mb-1">
                                <i class="fas fa-envelope text-blue-600 mr-2"></i>
                                security@rjitbsf.edu.in
                            </p>
                            <p class="text-sm text-gray-500">Verification & Clearance</p>
                        </div>

                        <div>
                            <h4 class="font-bold text-gray-800 mb-2">Technical Support</h4>
                            <p class="text-gray-600 mb-1">
                                <i class="fas fa-phone-alt text-blue-600 mr-2"></i>
                                +91-11-2345-6792
                            </p>
                            <p class="text-gray-600 mb-1">
                                <i class="fas fa-envelope text-blue-600 mr-2"></i>
                                support@rjitbsf.edu.in
                            </p>
                            <p class="text-sm text-gray-500">24/7 System Support</p>
                        </div>
                    </div>
                </div>

                <!-- Response Timeline -->
                <div class="bsf-card rounded-2xl p-6 mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-business-time mr-3 text-orange-600"></i>Response Timeline
                    </h3>

                    <div class="space-y-1">
                        <div class="timeline-item">
                            <h4 class="font-bold text-gray-800">Immediate Response</h4>
                            <p class="text-gray-600 text-sm">Security emergencies: Within 5 minutes</p>
                        </div>

                        <div class="timeline-item">
                            <h4 class="font-bold text-gray-800">Urgent Inquiries</h4>
                            <p class="text-gray-600 text-sm">Training-related: Within 2 hours</p>
                        </div>

                        <div class="timeline-item">
                            <h4 class="font-bold text-gray-800">Standard Inquiries</h4>
                            <p class="text-gray-600 text-sm">Admissions & Academics: 24-48 hours</p>
                        </div>

                        <div class="timeline-item">
                            <h4 class="font-bold text-gray-800">Complex Requests</h4>
                            <p class="text-gray-600 text-sm">Security clearance: 3-5 working days</p>
                        </div>
                    </div>
                </div>

                <!-- Department Tags -->
                <div class="bsf-card rounded-2xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-building mr-3 text-purple-600"></i>Contact Departments
                    </h3>

                    <div class="flex flex-wrap gap-2">
                        <span class="department-tag security-tag">
                            <i class="fas fa-shield-alt mr-1"></i>Security
                        </span>
                        <span class="department-tag academic-tag">
                            <i class="fas fa-graduation-cap mr-1"></i>Academics
                        </span>
                        <span class="department-tag admin-tag">
                            <i class="fas fa-users-cog mr-1"></i>Administration
                        </span>
                        <span class="department-tag tech-tag">
                            <i class="fas fa-laptop-code mr-1"></i>Technology
                        </span>
                        <span class="department-tag security-tag">
                            <i class="fas fa-dumbbell mr-1"></i>Training
                        </span>
                        <span class="department-tag academic-tag">
                            <i class="fas fa-book-medical mr-1"></i>Medical
                        </span>
                        <span class="department-tag admin-tag">
                            <i class="fas fa-user-tie mr-1"></i>HR
                        </span>
                        <span class="department-tag tech-tag">
                            <i class="fas fa-wifi mr-1"></i>Communications
                        </span>
                    </div>

                    <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                        <p class="text-sm text-gray-700">
                            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                            <strong>Note:</strong> All communications are monitored and recorded for security purposes as per BSF protocols.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Notice -->
        <div class="mt-12">
            <div class="bsf-alert">
                <div class="flex items-start">
                    <div class="mr-4">
                        <i class="fas fa-user-shield text-2xl text-red-600"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 mb-2">Security Notice</h4>
                        <p class="text-gray-700">
                            RJIT College of BSF operates under strict security protocols. All communications are
                            subject to monitoring and recording. Unauthorized disclosure of information is
                            prohibited under the Official Secrets Act. For security clearance verification,
                            please contact the Security Desk with your BSF ID number.
                        </p>
                        <div class="mt-3 flex items-center text-sm text-gray-600">
                            <i class="fas fa-lock mr-2"></i>
                            <span>All data transmitted is encrypted using 256-bit SSL encryption</span>
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
                        education, and research. Committed to national security excellence.
                    </p>
                </div>

                <div>
                    <h4 class="text-lg font-bold mb-6">Quick Links</h4>
                    <ul class="space-y-3">
                        <li><a href="about.php" class="text-gray-400 hover:text-white transition">About College</a></li>
                        <li><a href="academics.php" class="text-gray-400 hover:text-white transition">Training Programs</a></li>
                        <li><a href="admission.php" class="text-gray-400 hover:text-white transition">Admissions</a></li>
                        <li><a href="faculty.php" class="text-gray-400 hover:text-white transition">Faculty</a></li>
                        <li><a href="research.php" class="text-gray-400 hover:text-white transition">Security Research</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-lg font-bold mb-6">Contact Info</h4>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt text-red-500 mr-3 mt-1"></i>
                            <span class="text-gray-400">Security Zone, Sector 5, New Delhi</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-phone text-red-500 mr-3 mt-1"></i>
                            <span class="text-gray-400">+91-11-2345-6789</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-envelope text-red-500 mr-3 mt-1"></i>
                            <span class="text-gray-400">info@rjitbsf.edu.in</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-clock text-red-500 mr-3 mt-1"></i>
                            <span class="text-gray-400">Mon-Fri: 9AM-5PM</span>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-lg font-bold mb-6">Security Hotlines</h4>
                    <div class="space-y-4">
                        <div>
                            <div class="text-2xl font-bold text-red-400 mb-1">1077</div>
                            <p class="text-gray-400 text-sm">National Emergency</p>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-yellow-400 mb-1">1800-XXX-BSF</div>
                            <p class="text-gray-400 text-sm">Training Emergency</p>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-green-400 mb-1">1965</div>
                            <p class="text-gray-400 text-sm">Medical Emergency</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-gray-800 text-center">
                <p class="text-gray-500 text-sm">
                    &copy; <?= date('Y') ?> RJIT College of BSF. All Rights Reserved. |
                    <span class="text-red-400">Classified Information - For Authorized Personnel Only</span>
                </p>
                <p class="text-gray-600 text-xs mt-2">
                    This institution operates under the Ministry of Home Affairs, Government of India.
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Form enhancement
        document.addEventListener('DOMContentLoaded', function() {
            // Category select enhancement
            const categorySelect = document.querySelector('select[name="category"]');
            if (categorySelect) {
                categorySelect.addEventListener('change', function() {
                    const selected = this.options[this.selectedIndex];
                    if (selected.value === 'security') {
                        showSecurityNotice();
                    }
                });
            }

            // Character counter for message
            const messageTextarea = document.querySelector('textarea[name="message"]');
            if (messageTextarea) {
                const counter = document.createElement('div');
                counter.className = 'text-sm text-gray-500 text-right mt-1';
                messageTextarea.parentNode.appendChild(counter);

                function updateCounter() {
                    const length = messageTextarea.value.length;
                    counter.textContent = `${length}/2000 characters`;

                    if (length > 1900) {
                        counter.classList.add('text-red-600');
                        counter.classList.remove('text-gray-500');
                    } else {
                        counter.classList.remove('text-red-600');
                        counter.classList.add('text-gray-500');
                    }
                }

                messageTextarea.addEventListener('input', updateCounter);
                updateCounter(); // Initial call
            }

            // Phone number formatting for emergency contacts
            const emergencyNumbers = document.querySelectorAll('.emergency-card .text-2xl');
            emergencyNumbers.forEach(number => {
                number.addEventListener('click', function() {
                    const phoneNumber = this.textContent.replace(/\D/g, '');
                    if (confirm(`Call ${this.textContent}?`)) {
                        window.location.href = `tel:${phoneNumber}`;
                    }
                });
            });

            // Form submission confirmation
            const contactForm = document.querySelector('form');
            if (contactForm && !<?= $formSubmitted ? 'true' : 'false' ?>) {
                contactForm.addEventListener('submit', function(e) {
                    const securityCategory = document.querySelector('select[name="category"]').value;
                    if (securityCategory === 'security') {
                        if (!confirm('Security-related inquiries require additional verification. Do you have your BSF ID number ready?')) {
                            e.preventDefault();
                            return false;
                        }
                    }

                    const message = document.querySelector('textarea[name="message"]').value;
                    if (message.length < 20) {
                        alert('Please provide a more detailed message (minimum 20 characters).');
                        e.preventDefault();
                        return false;
                    }
                });
            }
        });

        function showSecurityNotice() {
            const notice = `
                <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-3 mt-1"></i>
                        <div>
                            <h5 class="font-bold text-red-800 mb-1">Security Clearance Notice</h5>
                            <p class="text-red-700 text-sm">
                                For security-related inquiries, please have your BSF ID number ready. 
                                All security communications are logged and monitored. Response time 
                                may be extended for verification purposes.
                            </p>
                        </div>
                    </div>
                </div>
            `;

            const categoryField = document.querySelector('select[name="category"]');
            if (categoryField && !document.querySelector('.security-notice')) {
                const noticeDiv = document.createElement('div');
                noticeDiv.className = 'security-notice';
                noticeDiv.innerHTML = notice;
                categoryField.parentNode.appendChild(noticeDiv);
            }
        }
    </script>
</body>

</html>