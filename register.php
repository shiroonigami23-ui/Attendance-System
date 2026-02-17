<?php
require_once 'includes/Config.php';

$step = $_GET['step'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Campus Attendance System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
        <div class="bg-white/90 backdrop-blur-lg rounded-2xl shadow-2xl overflow-hidden border border-white/30">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-6 text-white">
                <h1 class="text-3xl font-bold"><i class="fas fa-user-graduate mr-3"></i> Campus Attendance System</h1>
                <p class="opacity-90">Secure, Hardware-Locked Academic Tracking</p>
            </div>

            <!-- Progress Steps -->
            <div class="flex justify-center py-4 border-b">
                <div class="flex items-center">
                    <div class="flex items-center <?= $step >= 1 ? 'text-blue-600' : 'text-gray-400' ?>">
                        <div class="rounded-full w-10 h-10 flex items-center justify-center border-2 <?= $step >= 1 ? 'border-blue-600 bg-blue-100' : 'border-gray-300' ?>">
                            1
                        </div>
                        <span class="ml-2 font-medium">Basic Info</span>
                    </div>
                    <div class="h-1 w-20 mx-4 <?= $step >= 2 ? 'bg-blue-600' : 'bg-gray-300' ?>"></div>
                    <div class="flex items-center <?= $step >= 2 ? 'text-blue-600' : 'text-gray-400' ?>">
                        <div class="rounded-full w-10 h-10 flex items-center justify-center border-2 <?= $step >= 2 ? 'border-blue-600 bg-blue-100' : 'border-gray-300' ?>">
                            2
                        </div>
                        <span class="ml-2 font-medium">Academic Details</span>
                    </div>
                </div>
            </div>

            <!-- Form Content -->
            <div class="p-8">
                <?php if ($step == 1): ?>
                <!-- Step 1: Role & Basic Info -->
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Create Your Account</h2>
                <form method="POST" action="register_handler.php?step=1" enctype="multipart/form-data">
                    <div class="space-y-6">
                        <!-- Role Selection -->
                        <div>
                            <label class="block text-gray-700 mb-2 font-medium">I am a</label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer hover:border-blue-500 transition <?= ($_POST['role'] ?? '') == 'STUDENT' ? 'border-blue-500 bg-blue-50' : 'border-gray-200' ?>">
                                    <input type="radio" name="role" value="STUDENT" class="mr-3" required <?= ($_POST['role'] ?? '') == 'STUDENT' ? 'checked' : '' ?>>
                                    <div>
                                        <div class="font-semibold">Student</div>
                                        <div class="text-sm text-gray-500">Mark attendance, view timetable</div>
                                    </div>
                                    <i class="fas fa-graduation-cap ml-auto text-blue-500 text-xl"></i>
                                </label>
                                <label class="flex items-center p-4 border-2 rounded-xl cursor-pointer hover:border-purple-500 transition <?= ($_POST['role'] ?? '') == 'SEMI_ADMIN' ? 'border-purple-500 bg-purple-50' : 'border-gray-200' ?>">
                                    <input type="radio" name="role" value="SEMI_ADMIN" class="mr-3" <?= ($_POST['role'] ?? '') == 'SEMI_ADMIN' ? 'checked' : '' ?>>
                                    <div>
                                        <div class="font-semibold">Teacher</div>
                                        <div class="text-sm text-gray-500">Take attendance, manage sessions</div>
                                    </div>
                                    <i class="fas fa-chalkboard-teacher ml-auto text-purple-500 text-xl"></i>
                                </label>
                            </div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-gray-700 mb-2 font-medium">Email Address</label>
                            <input type="email" name="email" placeholder="you@example.com" required
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            <p class="text-sm text-gray-500 mt-1" id="emailHint">
                                For students: use your official <strong>@rjit.ac.in</strong> email
                            </p>
                        </div>

                        <!-- Full Name & Phone -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 mb-2 font-medium">Full Name</label>
                                <input type="text" name="full_name" placeholder="John Doe" required
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2 font-medium">Phone Number</label>
                                <input type="tel" name="phone" placeholder="+91 9876543210" required
                                    class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            </div>
                        </div>

                        <!-- Profile Photo -->
                        <div>
                            <label class="block text-gray-700 mb-2 font-medium">Profile Photo (Optional)</label>
                            <div class="flex items-center justify-center w-full">
                                <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-2"></i>
                                        <p class="text-sm text-gray-500">Click to upload photo</p>
                                    </div>
                                    <input type="file" name="profile_photo" accept="image/*" class="hidden">
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Next Button -->
                    <button type="submit" class="w-full mt-8 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold py-3 rounded-lg hover:from-blue-700 hover:to-blue-800 transition duration-300 shadow-lg">
                        Continue <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </form>

                <p class="text-center text-gray-600 mt-6">
                    Already have an account? 
                    <a href="login.php" class="text-blue-600 font-semibold hover:underline">Sign In</a>
                </p>
                <?php endif; ?>

                <?php if ($step == 2): 
                    $role = $_SESSION['reg_data']['role'] ?? '';
                ?>
                <!-- Step 2: Role-Specific Details -->
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <?= $role == 'STUDENT' ? 'Academic Information' : 'Teacher Information' ?>
                </h2>
                <form method="POST" action="register_handler.php?step=2">
                    <?php if ($role == 'STUDENT'): ?>
                    <!-- Student Fields -->
                    <div class="space-y-6">
                        <div>
                            <label class="block text-gray-700 mb-2 font-medium">Roll Number</label>
                            <input type="text" name="roll_no" placeholder="0902CS231028" required
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 mb-2 font-medium">Branch</label>
                                <select name="branch_code" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                    <option value="">Select Branch</option>
                                    <option value="CS">Computer Science</option>
                                    <option value="IT">Information Technology</option>
                                    <option value="ME">Mechanical Engineering</option>
                                    <option value="CE">Civil Engineering</option>
                                    <option value="EC">Electronics & Communication</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2 font-medium">Current Semester</label>
                                <select name="current_semester" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?= $i ?>">Semester <?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 mb-2 font-medium">Section</label>
                                <select name="section" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                    <option value="">Select Section</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 mb-2 font-medium">Lab Batch (Optional)</label>
                                <select name="lab_batch" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                    <option value="">No Lab Batch</option>
                                    <option value="A1">A1</option>
                                    <option value="A2">A2</option>
                                    <option value="B1">B1</option>
                                    <option value="B2">B2</option>
                                    <option value="C1">C1</option>
                                    <option value="C2">C2</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($role == 'SEMI_ADMIN'): ?>
                    <!-- Teacher Fields -->
                    <div class="space-y-6">
                        <div>
                            <label class="block text-gray-700 mb-2 font-medium">Teacher ID</label>
                            <input type="text" name="teacher_id" placeholder="T2023CS001" required
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2 font-medium">Department</label>
                            <input type="text" name="department" placeholder="Computer Science" required
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition">
                        </div>
                        <div class="p-4 bg-purple-50 rounded-lg border border-purple-200">
                            <div class="flex items-center">
                                <i class="fas fa-info-circle text-purple-600 mr-3"></i>
                                <span class="text-purple-800">Your account will be activated after admin approval.</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full mt-8 bg-gradient-to-r from-green-600 to-green-700 text-white font-bold py-3 rounded-lg hover:from-green-700 hover:to-green-800 transition duration-300 shadow-lg">
                        Complete Registration <i class="fas fa-check ml-2"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Update email hint based on role
        document.querySelectorAll('input[name="role"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const hint = document.getElementById('emailHint');
                if (this.value === 'STUDENT') {
                    hint.innerHTML = 'Use your official <strong>@rjit.ac.in</strong> email (e.g., 0902CS231028@rjit.ac.in)';
                } else {
                    hint.innerHTML = 'Use your professional email address';
                }
            });
        });

        // File upload preview
        const fileInput = document.querySelector('input[name="profile_photo"]');
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                const fileName = e.target.files[0]?.name || 'No file chosen';
                const label = fileInput.closest('label');
                label.querySelector('p').textContent = fileName;
                label.classList.add('border-blue-500', 'bg-blue-50');
            });
        }
    </script>
</body>
</html>