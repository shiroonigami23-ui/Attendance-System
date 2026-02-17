<?php
require_once '../includes/Config.php';
require_once '../includes/Auth.php';

checkAuth();
if ($_SESSION['role'] != 'SEMI_ADMIN') {
    header('Location: ../dashboard.php');
    exit();
}

$user = getCurrentUser();
$user_id = $user['user_id'];

// Handle POST actions (create/approve/reject swap)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_swap':
                handleCreateSwap($pdo, $user_id);
                break;
            case 'approve_swap':
                handleApproveSwap($pdo, $user_id);
                break;
            case 'reject_swap':
                handleRejectSwap($pdo, $user_id);
                break;
            case 'cancel_swap':
                handleCancelSwap($pdo, $user_id);
                break;
        }
    }
}

// Function to create a swap request
function handleCreateSwap($pdo, $requester_id) {
    $slot_id = $_POST['slot_id'] ?? 0;
    $receiver_id = $_POST['receiver_id'] ?? 0;
    $swap_date = $_POST['swap_date'] ?? '';
    $request_notes = $_POST['request_notes'] ?? '';

    // Validate inputs
    if (!$slot_id || !$receiver_id || !$swap_date) {
        $_SESSION['swap_error'] = 'Please fill all required fields.';
        header('Location: swap_dashboard.php?tab=request');
        exit();
    }

    // Check if receiver is a teacher
    $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ? AND role = 'SEMI_ADMIN'");
    $stmt->execute([$receiver_id]);
    if (!$stmt->fetch()) {
        $_SESSION['swap_error'] = 'Invalid substitute teacher selected.';
        header('Location: swap_dashboard.php?tab=request');
        exit();
    }

    try {
        // Insert swap request
        $stmt = $pdo->prepare("
            INSERT INTO swap_requests 
            (requester_id, receiver_id, slot_id, requested_date, request_notes, status)
            VALUES (?, ?, ?, ?, ?, 'PENDING')
        ");
        $stmt->execute([$requester_id, $receiver_id, $slot_id, $swap_date, $request_notes]);
        $swap_id = $pdo->lastInsertId();

        // Handle file upload if present
        if (isset($_FILES['lecture_file']) && $_FILES['lecture_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['lecture_file'];
            $upload_dir = '../assets/uploads/swaps/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = uniqid() . '_' . preg_replace('/[^A-Za-z0-9\.]/', '', $file['name']);
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $stmt = $pdo->prepare("
                    INSERT INTO swap_resources 
                    (swap_id, teacher_id, file_name, file_path, file_type, file_size, upload_notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $file_type = pathinfo($file['name'], PATHINFO_EXTENSION);
                $stmt->execute([
                    $swap_id, 
                    $requester_id, 
                    $file['name'],
                    $file_path,
                    $file_type,
                    $file['size'],
                    'Lecture materials for swap request'
                ]);
            }
        }

        $_SESSION['swap_success'] = 'Swap request sent successfully!';
        header('Location: swap_dashboard.php?tab=outgoing');
        exit();

    } catch (Exception $e) {
        $_SESSION['swap_error'] = 'Error creating swap request: ' . $e->getMessage();
        header('Location: swap_dashboard.php?tab=request');
        exit();
    }
}

// Function to approve a swap request
function handleApproveSwap($pdo, $user_id) {
    $swap_id = $_POST['swap_id'] ?? 0;
    $response_notes = $_POST['response_notes'] ?? '';

    if (!$swap_id) {
        $_SESSION['swap_error'] = 'Invalid swap request.';
        header('Location: swap_dashboard.php?tab=inbox');
        exit();
    }

    try {
        // Update swap status to APPROVED
        $stmt = $pdo->prepare("
            UPDATE swap_requests 
            SET status = 'APPROVED', 
                response_notes = ?,
                responded_at = NOW()
            WHERE swap_id = ? 
            AND receiver_id = ?
            AND status = 'PENDING'
        ");
        $stmt->execute([$response_notes, $swap_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            // Get swap details to update live_sessions
            $stmt = $pdo->prepare("
                SELECT sr.slot_id, sr.requested_date, sr.requester_id
                FROM swap_requests sr
                WHERE sr.swap_id = ?
            ");
            $stmt->execute([$swap_id]);
            $swap = $stmt->fetch();

            if ($swap) {
                // Update or create live_session with substitute teacher
                $stmt = $pdo->prepare("
                    INSERT INTO live_sessions 
                    (slot_id, actual_teacher_id, session_date, status, swap_id)
                    VALUES (?, ?, ?, 'SCHEDULED', ?)
                    ON DUPLICATE KEY UPDATE 
                    actual_teacher_id = ?, swap_id = ?
                ");
                $stmt->execute([
                    $swap['slot_id'],
                    $user_id, // The approving teacher becomes the actual teacher
                    $swap['requested_date'],
                    $swap_id,
                    $user_id,
                    $swap_id
                ]);
            }

            $_SESSION['swap_success'] = 'Swap request approved!';
        } else {
            $_SESSION['swap_error'] = 'Unable to approve swap request.';
        }

        header('Location: swap_dashboard.php?tab=inbox');
        exit();

    } catch (Exception $e) {
        $_SESSION['swap_error'] = 'Error approving swap: ' . $e->getMessage();
        header('Location: swap_dashboard.php?tab=inbox');
        exit();
    }
}

// Function to reject a swap request
function handleRejectSwap($pdo, $user_id) {
    $swap_id = $_POST['swap_id'] ?? 0;
    $response_notes = $_POST['response_notes'] ?? '';

    $stmt = $pdo->prepare("
        UPDATE swap_requests 
        SET status = 'REJECTED', 
            response_notes = ?,
            responded_at = NOW()
        WHERE swap_id = ? 
        AND receiver_id = ?
        AND status = 'PENDING'
    ");
    $stmt->execute([$response_notes, $swap_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['swap_success'] = 'Swap request rejected.';
    } else {
        $_SESSION['swap_error'] = 'Unable to reject swap request.';
    }

    header('Location: swap_dashboard.php?tab=inbox');
    exit();
}

// Function to cancel a swap request
function handleCancelSwap($pdo, $user_id) {
    $swap_id = $_POST['swap_id'] ?? 0;

    $stmt = $pdo->prepare("
        UPDATE swap_requests 
        SET status = 'CANCELLED'
        WHERE swap_id = ? 
        AND requester_id = ?
        AND status = 'PENDING'
    ");
    $stmt->execute([$swap_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['swap_success'] = 'Swap request cancelled.';
    } else {
        $_SESSION['swap_error'] = 'Unable to cancel swap request.';
    }

    header('Location: swap_dashboard.php?tab=outgoing');
    exit();
}

// Now, let's update the tab files to use this single handler
// We'll create functions to display each tab's content

function displayInbox($pdo, $user_id) {
    // Get pending swap requests received by this teacher
    $stmt = $pdo->prepare("
        SELECT sr.*, 
               u.full_name as requester_name,
               u.email as requester_email,
               s.code as subject_code,
               s.name as subject_name,
               ts.day_of_week,
               ts.start_time,
               ts.end_time,
               ts.target_group
        FROM swap_requests sr
        INNER JOIN users u ON sr.requester_id = u.user_id
        INNER JOIN timetable_slots ts ON sr.slot_id = ts.slot_id
        INNER JOIN subjects s ON ts.subject_id = s.subject_id
        WHERE sr.receiver_id = ?
        AND sr.status = 'PENDING'
        ORDER BY sr.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $incoming_requests = $stmt->fetchAll();

    if (empty($incoming_requests)) {
        echo '<div class="text-center py-12">
                <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900">No pending swap requests</h3>
                <p class="text-gray-500 mt-1">You don\'t have any pending swap requests at the moment.</p>
              </div>';
        return;
    }

    echo '<h3 class="text-xl font-bold text-gray-800 mb-6">Pending Swap Requests</h3>';
    echo '<div class="space-y-6">';
    
    foreach ($incoming_requests as $request) {
        $target = json_decode($request['target_group'], true);
        ?>
        <div class="border border-gray-200 rounded-xl p-6 hover:shadow-md transition">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h4 class="font-bold text-lg text-gray-800">
                        <?= htmlspecialchars($request['subject_code']) ?>: <?= htmlspecialchars($request['subject_name']) ?>
                    </h4>
                    <div class="flex items-center text-sm text-gray-600 mt-2 space-x-4">
                        <span>
                            <i class="far fa-calendar-alt mr-1"></i>
                            <?= $request['day_of_week'] ?> | <?= date('h:i A', strtotime($request['start_time'])) ?>
                        </span>
                        <span>
                            <i class="fas fa-user mr-1"></i>
                            Requested by: <?= htmlspecialchars($request['requester_name']) ?>
                        </span>
                        <span>
                            <i class="far fa-clock mr-1"></i>
                            Date: <?= date('M j, Y', strtotime($request['requested_date'])) ?>
                        </span>
                    </div>
                </div>
                <span class="bg-yellow-100 text-yellow-800 text-sm font-semibold px-3 py-1 rounded-full">
                    Pending
                </span>
            </div>

            <?php if ($request['request_notes']): ?>
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                <p class="text-sm text-gray-700">
                    <strong>Notes from requester:</strong><br>
                    <?= nl2br(htmlspecialchars($request['request_notes'])) ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Check for uploaded resources -->
            <?php
            $stmt = $pdo->prepare("SELECT * FROM swap_resources WHERE swap_id = ?");
            $stmt->execute([$request['swap_id']]);
            $resources = $stmt->fetchAll();
            
            if (!empty($resources)) {
                echo '<div class="mb-4">';
                echo '<p class="text-sm font-medium text-gray-700 mb-2">Attached Resources:</p>';
                foreach ($resources as $resource) {
                    echo '<div class="flex items-center bg-gray-50 rounded-lg p-3 mb-2">';
                    echo '<i class="fas fa-file-pdf text-red-500 mr-3 text-xl"></i>';
                    echo '<div class="flex-1">';
                    echo '<p class="font-medium text-gray-800">' . htmlspecialchars($resource['file_name']) . '</p>';
                    echo '<p class="text-xs text-gray-500">' . formatFileSize($resource['file_size']) . ' • Uploaded on ' . 
                         date('M j, Y', strtotime($resource['uploaded_at'])) . '</p>';
                    echo '</div>';
                    echo '<a href="' . htmlspecialchars($resource['file_path']) . '" target="_blank" 
                           class="text-blue-600 hover:text-blue-800 ml-2">
                            <i class="fas fa-download"></i>
                          </a>';
                    echo '</div>';
                }
                echo '</div>';
            }
            ?>

            <!-- Response Form -->
            <form method="POST" action="swap_requests.php" class="mt-4 border-t pt-4">
                <input type="hidden" name="action" value="approve_swap">
                <input type="hidden" name="swap_id" value="<?= $request['swap_id'] ?>">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-comment mr-1"></i>
                        Your Response Notes (Optional)
                    </label>
                    <textarea name="response_notes" rows="2" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Add any notes or conditions for this swap..."></textarea>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="submit" name="reject_action" value="reject" 
                            formnovalidate
                            formaction="swap_requests.php"
                            class="px-6 py-2 border border-red-300 text-red-700 font-medium rounded-lg hover:bg-red-50 transition">
                        <i class="fas fa-times mr-2"></i>Reject
                    </button>
                    <button type="submit" 
                            class="px-6 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-check mr-2"></i>Approve Swap
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
    
    echo '</div>';
}

function displayOutgoing($pdo, $user_id) {
    // Get swap requests sent by this teacher
    $stmt = $pdo->prepare("
        SELECT sr.*, 
               u.full_name as receiver_name,
               u.email as receiver_email,
               s.code as subject_code,
               s.name as subject_name,
               ts.day_of_week,
               ts.start_time,
               ts.end_time
        FROM swap_requests sr
        INNER JOIN users u ON sr.receiver_id = u.user_id
        INNER JOIN timetable_slots ts ON sr.slot_id = ts.slot_id
        INNER JOIN subjects s ON ts.subject_id = s.subject_id
        WHERE sr.requester_id = ?
        AND sr.status IN ('PENDING', 'APPROVED', 'REJECTED')
        ORDER BY sr.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $outgoing_requests = $stmt->fetchAll();

    if (empty($outgoing_requests)) {
        echo '<div class="text-center py-12">
                <i class="fas fa-paper-plane text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900">No swap requests sent</h3>
                <p class="text-gray-500 mt-1">You haven\'t sent any swap requests yet.</p>
                <a href="swap_dashboard.php?tab=request" class="inline-block mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>Request a Swap
                </a>
              </div>';
        return;
    }

    echo '<h3 class="text-xl font-bold text-gray-800 mb-6">My Swap Requests</h3>';
    echo '<div class="space-y-6">';
    
    foreach ($outgoing_requests as $request) {
        $status_color = [
            'PENDING' => 'bg-yellow-100 text-yellow-800',
            'APPROVED' => 'bg-green-100 text-green-800',
            'REJECTED' => 'bg-red-100 text-red-800',
            'CANCELLED' => 'bg-gray-100 text-gray-800',
            'COMPLETED' => 'bg-blue-100 text-blue-800'
        ][$request['status']] ?? 'bg-gray-100 text-gray-800';
        ?>
        <div class="border border-gray-200 rounded-xl p-6 hover:shadow-md transition">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h4 class="font-bold text-lg text-gray-800">
                        <?= htmlspecialchars($request['subject_code']) ?>: <?= htmlspecialchars($request['subject_name']) ?>
                    </h4>
                    <div class="flex items-center text-sm text-gray-600 mt-2 space-x-4">
                        <span>
                            <i class="far fa-calendar-alt mr-1"></i>
                            <?= $request['day_of_week'] ?> | <?= date('h:i A', strtotime($request['start_time'])) ?>
                        </span>
                        <span>
                            <i class="fas fa-user mr-1"></i>
                            To: <?= htmlspecialchars($request['receiver_name']) ?>
                        </span>
                        <span>
                            <i class="far fa-clock mr-1"></i>
                            Date: <?= date('M j, Y', strtotime($request['requested_date'])) ?>
                        </span>
                        <span>
                            <i class="far fa-calendar mr-1"></i>
                            Sent: <?= date('M j, Y', strtotime($request['created_at'])) ?>
                        </span>
                    </div>
                </div>
                <span class="<?= $status_color ?> text-sm font-semibold px-3 py-1 rounded-full">
                    <?= $request['status'] ?>
                </span>
            </div>

            <?php if ($request['request_notes']): ?>
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-3">
                <p class="text-sm text-gray-700">
                    <strong>Your notes:</strong><br>
                    <?= nl2br(htmlspecialchars($request['request_notes'])) ?>
                </p>
            </div>
            <?php endif; ?>

            <?php if ($request['response_notes']): ?>
            <div class="bg-gray-50 border-l-4 border-gray-400 p-4 mb-3">
                <p class="text-sm text-gray-700">
                    <strong>Response from <?= htmlspecialchars($request['receiver_name']) ?>:</strong><br>
                    <?= nl2br(htmlspecialchars($request['response_notes'])) ?>
                </p>
            </div>
            <?php endif; ?>

            <?php if ($request['status'] == 'PENDING'): ?>
            <div class="mt-4 pt-4 border-t flex justify-end">
                <form method="POST" action="swap_requests.php" class="inline">
                    <input type="hidden" name="action" value="cancel_swap">
                    <input type="hidden" name="swap_id" value="<?= $request['swap_id'] ?>">
                    <button type="submit" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition"
                            onclick="return confirm('Are you sure you want to cancel this swap request?')">
                        <i class="fas fa-times mr-2"></i>Cancel Request
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    echo '</div>';
}

function displayHistory($pdo, $user_id) {
    // Get completed swap history
    $stmt = $pdo->prepare("
        SELECT sr.*, 
               requester.full_name as requester_name,
               receiver.full_name as receiver_name,
               s.code as subject_code,
               s.name as subject_name,
               ts.day_of_week,
               ts.start_time,
               ts.end_time
        FROM swap_requests sr
        INNER JOIN users requester ON sr.requester_id = requester.user_id
        INNER JOIN users receiver ON sr.receiver_id = receiver.user_id
        INNER JOIN timetable_slots ts ON sr.slot_id = ts.slot_id
        INNER JOIN subjects s ON ts.subject_id = s.subject_id
        WHERE (sr.requester_id = ? OR sr.receiver_id = ?)
        AND sr.status IN ('COMPLETED', 'APPROVED', 'REJECTED', 'CANCELLED')
        ORDER BY sr.requested_date DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id, $user_id]);
    $history = $stmt->fetchAll();

    if (empty($history)) {
        echo '<div class="text-center py-12">
                <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900">No swap history</h3>
                <p class="text-gray-500 mt-1">Your swap history will appear here.</p>
              </div>';
        return;
    }

    echo '<h3 class="text-xl font-bold text-gray-800 mb-6">Swap History</h3>';
    echo '<div class="overflow-x-auto">';
    echo '<table class="min-w-full divide-y divide-gray-200">';
    echo '<thead class="bg-gray-50">';
    echo '<tr>';
    echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>';
    echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date/Time</th>';
    echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parties</th>';
    echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>';
    echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody class="bg-white divide-y divide-gray-200">';
    
    foreach ($history as $item) {
        $status_color = [
            'APPROVED' => 'bg-green-100 text-green-800',
            'COMPLETED' => 'bg-blue-100 text-blue-800',
            'REJECTED' => 'bg-red-100 text-red-800',
            'CANCELLED' => 'bg-gray-100 text-gray-800'
        ][$item['status']] ?? 'bg-gray-100 text-gray-800';
        
        $is_requester = $item['requester_id'] == $user_id;
        ?>
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($item['subject_code']) ?></div>
                <div class="text-sm text-gray-500"><?= htmlspecialchars($item['subject_name']) ?></div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900"><?= date('M j, Y', strtotime($item['requested_date'])) ?></div>
                <div class="text-sm text-gray-500"><?= $item['day_of_week'] ?> <?= date('h:i A', strtotime($item['start_time'])) ?></div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm">
                    <span class="font-medium">From:</span> <?= htmlspecialchars($item['requester_name']) ?><br>
                    <span class="font-medium">To:</span> <?= htmlspecialchars($item['receiver_name']) ?>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $status_color ?>">
                    <?= $item['status'] ?>
                </span>
                <?php if ($item['responded_at']): ?>
                <div class="text-xs text-gray-500 mt-1">
                    <?= date('M j, Y', strtotime($item['responded_at'])) ?>
                </div>
                <?php endif; ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <a href="#" class="text-blue-600 hover:text-blue-900 mr-4">
                    <i class="fas fa-eye"></i> View
                </a>
                <?php if ($item['status'] == 'APPROVED' && !$is_requester): ?>
                <a href="#" class="text-green-600 hover:text-green-900">
                    <i class="fas fa-chalkboard-teacher"></i> Teach
                </a>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return number_format($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// Display messages if any
if (isset($_SESSION['swap_success'])) {
    echo '<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6" id="successMessage">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span>' . $_SESSION['swap_success'] . '</span>
                <button type="button" class="ml-auto text-green-700 hover:text-green-900" onclick="document.getElementById(\'successMessage\').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
          </div>';
    unset($_SESSION['swap_success']);
}

if (isset($_SESSION['swap_error'])) {
    echo '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6" id="errorMessage">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span>' . $_SESSION['swap_error'] . '</span>
                <button type="button" class="ml-auto text-red-700 hover:text-red-900" onclick="document.getElementById(\'errorMessage\').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
          </div>';
    unset($_SESSION['swap_error']);
}

// Determine which content to display based on GET parameter
$display = $_GET['display'] ?? 'inbox';

switch ($display) {
    case 'inbox':
        displayInbox($pdo, $user_id);
        break;
    case 'outgoing':
        displayOutgoing($pdo, $user_id);
        break;
    case 'history':
        displayHistory($pdo, $user_id);
        break;
    default:
        displayInbox($pdo, $user_id);
}
?>