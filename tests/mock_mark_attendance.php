<?php
/**
 * Local-only helper for load testing mark_attendance.php with per-request student context.
 * This file must never be exposed publicly.
 */
require_once __DIR__ . '/../includes/Config.php';

$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteAddr, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit();
}

$studentId = isset($_SERVER['HTTP_X_STUDENT_ID']) ? (int) $_SERVER['HTTP_X_STUDENT_ID'] : 0;
if ($studentId <= 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing X-Student-Id header']);
    exit();
}

$_SESSION['user_id'] = $studentId;
$_SESSION['role'] = 'STUDENT';
$_SESSION['email'] = 'loadtest+' . $studentId . '@rjit.ac.in';
$_SESSION['device_fp'] = 'loadtest-device-' . $studentId;

require __DIR__ . '/../api/mark_attendance.php';
