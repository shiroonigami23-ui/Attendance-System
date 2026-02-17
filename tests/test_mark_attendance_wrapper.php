<?php
// tests/test_mark_attendance_wrapper.php
// Mock Session
session_start();
$_SESSION['user_id'] = 3; // Student ID we found
$_SESSION['role'] = 'STUDENT';
$_SESSION['email'] = 'test@example.com'; 

// Include the API file
// The API file reads from php://input, so we can pipe data to this script.
require_once __DIR__ . '/../api/mark_attendance.php';
?>
