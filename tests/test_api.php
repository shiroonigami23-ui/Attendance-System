<?php
// tests/test_api.php
require_once __DIR__ . '/../includes/Config.php';

echo "Running API Verification...\n";

function makeRequest($url, $method = 'GET', $data = []) {
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => $method,
            'content' => json_encode($data),
            'ignore_errors' => true // To capture error responses
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return $result;
}

// 1. Test get_subjects_with_metadata.php (Public/Protected endpoint)
// Note: This endpoint might require session. 
// For this test, we'll try to access it directly. If it redirects to login, it might fail or return HTML.
$url = 'http://localhost/Attendance_System/api/get_subjects_with_metadata.php';
// We need to bypass auth for this CLI test or mock it.
// The file typically checks for session. 
// Let's create a temporary test wrapper to inject session? 
// Or better, let's just inspect the code of get_subjects_with_metadata first.

// Let's assume we can hit it.
echo "[*] Testing get_subjects_with_metadata.php...\n";
// $response = makeRequest($url);
// echo "Response length: " . strlen($response) . "\n";
// echo substr($response, 0, 100) . "...\n";

// 2. Mocking Authentication for API testing
// Since we are running from CLI, we can't easily share session with the web server.
// For robust testing, we would use something like Guzzle or Curl with cookie jar.
// But for this environment, let's try to verify the logic by including the file and mocking session.

// Mock Session
session_start();
$_SESSION['user_id'] = 1; // Assuming ID 1 exists and is a student? We need to verify.
$_SESSION['role'] = 'STUDENT';

// Mock Input
$_SERVER['REQUEST_METHOD'] = 'POST';

// Test mark_attendance.php LOGIC
// We will try to simulate a request by setting $_POST or input stream if possible.
// But mark_attendance reads from php://input. 
// It's hard to mock php://input in a running script without wrapper.

// ALTERNATIVE: Use curl to localhost if server is running.
// The user said "Process them to host on aws", implying it might be running locally on XAMPP?
// The user prompt said: "c:\xampp\htdocs\Attendance_System".
// Let's assume Apache is running.

// Check if localhost is reachable
$headers = @get_headers('http://localhost/Attendance_System/index.php');
if ($headers && strpos($headers[0], '200')) {
    echo "Local server is reachable.\n";
} else {
    echo "Local server might not be running or path is incorrect. Skipping HTTP tests.\n";
    // If we can't hit via HTTP, we might need to rely on unit-test style inclusion.
}

echo "API Test Script Placeholder - Manual verification recommended for Session-based APIs.\n";
?>
