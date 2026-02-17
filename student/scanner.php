<?php
require_once '../includes/Config.php';
require_once '../includes/Auth.php';

checkAuth();
if ($_SESSION['role'] != 'STUDENT') {
    header('Location: ../dashboard.php');
    exit();
}

$user = getCurrentUser();

// Check if student has a live session for their batch/section now
$stmt = $pdo->prepare("
    SELECT ls.session_id, ls.active_totp_token, ls.started_at,
           s.code as subject_code, s.name as subject_name
    FROM live_sessions ls
    INNER JOIN timetable_slots ts ON ls.slot_id = ts.slot_id
    INNER JOIN subjects s ON ts.subject_id = s.subject_id
    WHERE ls.status = 'LIVE'
    AND ls.session_date = CURDATE()
    AND JSON_EXTRACT(ts.target_group, '$.section') = ?
    AND (JSON_EXTRACT(ts.target_group, '$.batch') = 'all' 
         OR JSON_EXTRACT(ts.target_group, '$.batch') = ?)
    LIMIT 1
");
$stmt->execute([$user['section'], $user['lab_batch'] ?: 'all']);
$live_session = $stmt->fetch();

$error = $_GET['error'] ?? '';
$success = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scanner - Student</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
</head>

<body class="bg-gray-900 min-h-screen">
    <?php include '../includes/navbar.php'; ?>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8 text-white">
            <h1 class="text-3xl font-bold mb-2">QR Attendance Scanner</h1>
            <p class="text-gray-300">Scan the QR code displayed in your classroom to mark attendance</p>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="bg-red-900/50 border-l-4 border-red-500 p-4 mb-6 text-white">
                <div class="flex">
                    <i class="fas fa-exclamation-circle text-red-300 mt-1"></i>
                    <div class="ml-3">
                        <p class="text-sm"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-900/50 border-l-4 border-green-500 p-4 mb-6 text-white">
                <div class="flex">
                    <i class="fas fa-check-circle text-green-300 mt-1"></i>
                    <div class="ml-3">
                        <p class="text-sm"><?= htmlspecialchars($success) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Scanner Interface -->
        <div class="bg-gray-800 rounded-2xl shadow-2xl overflow-hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2">
                <!-- Left: Scanner -->
                <div class="p-8">
                    <h2 class="text-xl font-bold text-white mb-4">Camera Scanner</h2>
                    <div class="relative">
                        <video id="video" class="w-full h-auto rounded-lg border-4 border-blue-500" playsinline></video>
                        <canvas id="canvas" class="hidden"></canvas>
                        <div id="scanner-overlay" class="absolute inset-0 border-2 border-white border-dashed rounded-lg m-4 pointer-events-none"></div>
                    </div>

                    <div class="mt-6 flex justify-center space-x-4">
                        <button id="startButton" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition">
                            <i class="fas fa-play mr-2"></i> Start Camera
                        </button>
                        <button id="stopButton" class="bg-gray-700 text-white px-6 py-3 rounded-lg font-medium hover:bg-gray-600 transition">
                            <i class="fas fa-stop mr-2"></i> Stop
                        </button>
                    </div>
                </div>

                <!-- Right: Session Info -->
                <div class="bg-gray-900 p-8">
                    <h2 class="text-xl font-bold text-white mb-4">Session Information</h2>

                    <?php if ($live_session):
                        $elapsed = time() - strtotime($live_session['started_at']);
                        $status = $elapsed < 180 ? 'GREEN' : ($elapsed < 420 ? 'YELLOW' : 'RED');
                    ?>
                        <div class="space-y-6">
                            <div class="p-4 bg-gray-700 rounded-lg">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="font-medium text-white">Current Class</span>
                                    <span class="px-3 py-1 text-xs font-bold rounded-full 
                                    <?= $status == 'GREEN' ? 'bg-green-600' : ($status == 'YELLOW' ? 'bg-yellow-600' : 'bg-red-600') ?>">
                                        <?= $status ?> ZONE
                                    </span>
                                </div>
                                <h3 class="text-2xl font-bold text-white"><?= htmlspecialchars($live_session['subject_name']) ?></h3>
                                <p class="text-gray-300"><?= htmlspecialchars($live_session['subject_code']) ?></p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 bg-gray-700 rounded-lg">
                                    <div class="text-sm text-gray-400">Time Elapsed</div>
                                    <div class="text-2xl font-bold text-white"><?= gmdate("i:s", $elapsed) ?></div>
                                </div>
                                <div class="p-4 bg-gray-700 rounded-lg">
                                    <div class="text-sm text-gray-400">Your Status</div>
                                    <div class="text-2xl font-bold text-yellow-400">PENDING</div>
                                </div>
                            </div>

                            <div class="p-4 bg-blue-900/30 rounded-lg border border-blue-700">
                                <div class="flex items-center">
                                    <i class="fas fa-info-circle text-blue-400 text-xl mr-3"></i>
                                    <div>
                                        <p class="font-medium text-blue-300">Scanning Rules</p>
                                        <ul class="text-sm text-blue-200 mt-2 space-y-1">
                                            <li><i class="fas fa-check-circle mr-2"></i> Green Zone (0-3 min): <strong>PRESENT</strong></li>
                                            <li><i class="fas fa-exclamation-triangle mr-2"></i> Yellow Zone (3-7 min): <strong>LATE</strong></li>
                                            <li><i class="fas fa-times-circle mr-2"></i> Red Zone (7+ min): <strong>LOCKED</strong></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div id="scanResult" class="hidden">
                                <!-- Result will appear here -->
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="text-gray-400 text-5xl mb-4">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-300 mb-2">No Active Session</h3>
                            <p class="text-gray-500">There is no live class for your section/batch at this moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Location Security Notice -->
        <div class="mt-8 bg-yellow-900/30 border border-yellow-700 rounded-xl p-6 text-yellow-200">
            <div class="flex items-center">
                <i class="fas fa-shield-alt text-3xl mr-4"></i>
                <div>
                    <h3 class="text-xl font-bold mb-2">Security Protocols Active</h3>
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        <li><strong>Device Lock:</strong> This device is cryptographically bound to your account</li>
                        <li><strong>Location Check:</strong> Attendance only accepted from campus WiFi subnet</li>
                        <li><strong>Time Window:</strong> QR valid for 7 minutes only (Green: 0-3 min, Yellow: 3-7 min)</li>
                        <li><strong>Anti-Proxy:</strong> QR changes every 10 seconds; photo-sharing blocked</li>
                    </ul>
                    <p class="mt-3 text-yellow-300">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Physical Presence Required:</strong> You must be physically present in class to scan the QR from teacher's screen.
                    </p>
                </div>
            </div>
        </div>

        <!-- Manual Entry (Fallback - Trusted Device Protocol) -->
        <div class="mt-8 bg-gray-800 rounded-xl p-6">
            <h3 class="text-lg font-bold text-white mb-4">Trusted Device Protocol</h3>
            <p class="text-gray-400 mb-4">If your device camera is not working, teacher can authorize manual entry.</p>
            <div class="flex space-x-4">
                <input type="text" id="manualToken" placeholder="Enter 16-digit token from teacher" class="flex-1 p-3 rounded-lg bg-gray-900 border border-gray-700 text-white">
                <button onclick="submitManualToken()" class="bg-green-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-700 transition">
                    <i class="fas fa-paper-plane mr-2"></i> Submit Token
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-3">
                <i class="fas fa-info-circle mr-1"></i>
                Only works if teacher has activated "Trusted Device" mode for this session.
            </p>
        </div>
    </div>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const startButton = document.getElementById('startButton');
        const stopButton = document.getElementById('stopButton');
        let scanning = false;

        // Start camera
        startButton.addEventListener('click', async () => {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: "environment"
                    }
                });
                video.srcObject = stream;
                video.play();
                scanning = true;
                scanQR();
            } catch (err) {
                alert("Camera access denied: " + err.message);
            }
        });

        // Stop camera
        stopButton.addEventListener('click', () => {
            if (video.srcObject) {
                video.srcObject.getTracks().forEach(track => track.stop());
                video.srcObject = null;
                scanning = false;
            }
        });

        function scanQR() {
            if (!scanning) return;

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            processQR();
            requestAnimationFrame(scanQR);
        }

        function processQR() {
            const imageData = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height);
            const code = jsQR(imageData.data, imageData.width, imageData.height);

            if (code) {
                scanning = false;

                // Get Location before submitting
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            submitQR(code.data, {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude
                            });
                        },
                        (error) => {
                            // If location denied or failed, send without (backend will decide if strictly required)
                            console.warn("Location access denied or failed:", error.message);
                            submitQR(code.data, null);
                        }, {
                            enableHighAccuracy: true,
                            timeout: 5000,
                            maximumAge: 0
                        }
                    );
                } else {
                    submitQR(code.data, null);
                }
            }
        }

        function submitQR(data, location) {
            try {
                const qrData = JSON.parse(data);
                if (!qrData.session_id || !qrData.token) {
                    throw new Error("Invalid QR data");
                }

                // Add location to payload if available
                if (location) {
                    qrData.lat = location.lat;
                    qrData.lng = location.lng;
                }

                // Send to server
                fetch('../api/mark_attendance.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(qrData)
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            showResult('SUCCESS', `Attendance marked as <strong>${result.status}</strong>`);
                            // Auto-redirect to timetable after 3 seconds
                            setTimeout(() => {
                                window.location.href = 'timetable.php';
                            }, 3000);
                        } else {
                            showResult('ERROR', result.error);
                        }
                    });
            } catch (err) {
                showResult('ERROR', 'Invalid QR code. Please scan the code from classroom screen.');
            }
        }

        function submitManualToken() {
            const token = document.getElementById('manualToken').value.trim();
            if (!token || token.length < 16) {
                alert("Enter valid 16-digit token from teacher");
                return;
            }

            // For now, simulate
            showResult('INFO', 'Manual token submission requires teacher authorization.');
        }

        function showResult(type, message) {
            const colors = {
                'SUCCESS': 'bg-green-900 border-green-700 text-green-300',
                'ERROR': 'bg-red-900 border-red-700 text-red-300',
                'INFO': 'bg-blue-900 border-blue-700 text-blue-300'
            };

            const resultDiv = document.getElementById('scanResult');
            resultDiv.className = `p-4 rounded-lg border ${colors[type]} mt-4`;
            resultDiv.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'SUCCESS' ? 'check-circle' : 'exclamation-circle'} text-2xl mr-3"></i>
                    <div>
                        <p class="font-bold">${type}</p>
                        <p>${message}</p>
                    </div>
                </div>
            `;
            resultDiv.classList.remove('hidden');
        }
    </script>
</body>

</html>