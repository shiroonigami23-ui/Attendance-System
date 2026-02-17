<?php
require_once 'includes/Config.php';

$step = $_GET['step'] ?? 1;

if ($step == 1) {
    // Step 1: Basic info
    $email = $_POST['email'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? '';
    $photo = $_FILES['profile_photo'] ?? null;

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        // Email exists → redirect to login
        header('Location: login.php?msg=Email+already+exists');
        exit();
    }

    // Validate student email
    if ($role == 'STUDENT' && !str_ends_with($email, '@rjit.ac.in')) {
        die("Student must use @rjit.ac.in email.");
    }

    // Handle photo upload
    $photo_path = null;
    if ($photo && $photo['error'] == UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($photo['tmp_name']);

        if (!in_array($mime, $allowed_types)) {
            header('Location: register.php?msg=Invalid+photo+format.+Only+JPG/PNG+allowed');
            exit();
        }

        $ext = $mime === 'image/jpeg' ? 'jpg' : 'png';
        $filename = uniqid('user_', true) . '.' . $ext;
        $upload_path = 'assets/uploads/' . $filename;

        if (!move_uploaded_file($photo['tmp_name'], $upload_path)) {
            header('Location: register.php?msg=Failed+to+upload+photo');
            exit();
        }
        $photo_path = $upload_path;
    }

    // Store in session for step 2
    $_SESSION['reg_data'] = [
        'email' => $email,
        'full_name' => $full_name,
        'phone' => $phone,
        'role' => $role,
        'photo_path' => $photo_path
    ];

    header('Location: register.php?step=2');
    exit();
}

if ($step == 2) {
    // Step 2: Role-specific data
    $reg_data = $_SESSION['reg_data'] ?? [];
    if (empty($reg_data)) {
        die("Session expired. Start over.");
    }

    if ($reg_data['role'] == 'STUDENT') {
        $roll_no = $_POST['roll_no'] ?? '';
        $branch_code = $_POST['branch_code'] ?? '';
        $current_semester = (int)($_POST['current_semester'] ?? 1);
        $section = $_POST['section'] ?? '';
        $lab_batch = $_POST['lab_batch'] ?? '';

        // Insert into users
        $stmt = $pdo->prepare("
            INSERT INTO users (email, full_name, phone, profile_photo, role, email_verified, approved, registration_step)
            VALUES (?, ?, ?, ?, ?, 1, 1, 1)
        ");
        $stmt->execute([
            $reg_data['email'],
            $reg_data['full_name'],
            $reg_data['phone'],
            $reg_data['photo_path'],
            $reg_data['role']
        ]);
        $user_id = $pdo->lastInsertId();

        // Insert into student_profiles
        $stmt = $pdo->prepare("
            INSERT INTO student_profiles (user_id, roll_no, branch_code, current_semester, section, lab_batch)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $roll_no, $branch_code, $current_semester, $section, $lab_batch]);

        echo "Student registration complete. <a href='login.php'>Login</a>";
    } elseif ($reg_data['role'] == 'SEMI_ADMIN') {
        // Teacher registration (pending approval)
        $teacher_id = $_POST['teacher_id'] ?? '';
        $department = $_POST['department'] ?? '';

        // Set default password based on role
        $default_password = $reg_data['role'] == 'STUDENT' ? 'student123' : 'teacher123';
        $password_hash = password_hash($default_password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
    INSERT INTO users (email, full_name, phone, profile_photo, role, email_verified, approved, registration_step, password_hash)
    VALUES (?, ?, ?, ?, ?, 1, 1, 1, ?)
");
        $stmt->execute([
            $reg_data['email'],
            $reg_data['full_name'],
            $reg_data['phone'],
            $reg_data['photo_path'],
            $reg_data['role'],
            $password_hash
        ]);

        echo "Teacher registration submitted. Awaiting admin approval.";
    }

    unset($_SESSION['reg_data']);
}
