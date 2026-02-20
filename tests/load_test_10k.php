<?php
/**
 * High-volume attendance load test (local/dev only).
 *
 * Usage:
 *   C:\xampp\php\php.exe tests\load_test_10k.php --requests=10000 --concurrency=250
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

if (!extension_loaded('curl')) {
    exit("cURL extension is required for load test.\n");
}

require_once __DIR__ . '/../includes/Config.php';

$opts = getopt('', [
    'requests::',
    'concurrency::',
    'base-url::',
    'seed-missing::',
    'cleanup::',
    'min-success-rate::',
]);

$requests = max(1, (int) ($opts['requests'] ?? 10000));
$concurrency = max(1, (int) ($opts['concurrency'] ?? 250));
$baseUrl = (string) ($opts['base-url'] ?? app_url('tests/mock_mark_attendance.php'));
$seedMissing = (int) ($opts['seed-missing'] ?? 1) === 1;
$cleanup = (int) ($opts['cleanup'] ?? 1) === 1;
$minSuccessRate = (float) ($opts['min-success-rate'] ?? 95.0);

echo "=== Attendance Load Test ===\n";
echo "Target URL   : {$baseUrl}\n";
echo "Requests     : {$requests}\n";
echo "Concurrency  : {$concurrency}\n";
echo "Seed missing : " . ($seedMissing ? 'yes' : 'no') . "\n";
echo "Cleanup      : " . ($cleanup ? 'yes' : 'no') . "\n\n";
echo "Min success% : {$minSuccessRate}\n\n";

$createdSession = true;
$createdStudentIds = [];

[$sessionId, $token] = createLoadTestSession($pdo);
$studentIds = getStudentIds($pdo, $requests);

if (count($studentIds) < $requests) {
    if (!$seedMissing) {
        exit("Not enough students for requested load. Available: " . count($studentIds) . "\n");
    }

    $need = $requests - count($studentIds);
    echo "Seeding {$need} additional students for load test...\n";
    $createdStudentIds = seedStudents($pdo, $need);
    $studentIds = getStudentIds($pdo, $requests);
}

if (count($studentIds) < $requests) {
    exit("Failed to prepare {$requests} student records. Found only " . count($studentIds) . ".\n");
}

$payloadBase = [
    'session_id' => $sessionId,
    'token' => $token,
    'lat' => CAMPUS_LAT,
    'lng' => CAMPUS_LNG
];

$mh = curl_multi_init();
$inFlight = 0;
$cursor = 0;
$completed = 0;
$success = 0;
$already = 0;
$rateLimited = 0;
$unauthorized = 0;
$otherErrors = 0;
$httpErrors = 0;
$errorSamples = [];
$decodeSamples = [];
$start = microtime(true);

$handles = [];

while ($completed < $requests || $inFlight > 0) {
    while ($inFlight < $concurrency && $cursor < $requests) {
        $studentId = (int) $studentIds[$cursor];
        $payload = json_encode($payloadBase, JSON_UNESCAPED_SLASHES);

        $ch = curl_init($baseUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Student-Id: ' . $studentId
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 60,
        ]);

        curl_multi_add_handle($mh, $ch);
        $handles[(int) $ch] = $ch;
        $inFlight++;
        $cursor++;
    }

    do {
        $status = curl_multi_exec($mh, $running);
    } while ($status === CURLM_CALL_MULTI_PERFORM);

    while ($info = curl_multi_info_read($mh)) {
        $ch = $info['handle'];
        $body = curl_multi_getcontent($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrNo = curl_errno($ch);
        $curlErr = curl_error($ch);

        if ($curlErrNo !== 0) {
            $otherErrors++;
            if (!isset($errorSamples['curl_error'])) {
                $errorSamples['curl_error'] = 0;
            }
            $errorSamples['curl_error']++;
            if (count($decodeSamples) < 3) {
                $decodeSamples[] = "cURL {$curlErrNo}: {$curlErr}";
            }
        } else {
            if ($httpCode >= 400) {
                $httpErrors++;
            }

            $resp = json_decode($body, true);
            if (is_array($resp) && isset($resp['success']) && $resp['success'] === true) {
                $success++;
            } elseif (is_array($resp)) {
                $error = strtolower((string) ($resp['error'] ?? 'unknown'));
                if (!isset($errorSamples[$error])) {
                    $errorSamples[$error] = 0;
                }
                $errorSamples[$error]++;
                if (str_contains($error, 'already marked')) {
                    $already++;
                } elseif (str_contains($error, 'too many requests')) {
                    $rateLimited++;
                } elseif (str_contains($error, 'unauthorized')) {
                    $unauthorized++;
                } else {
                    $otherErrors++;
                }
            } else {
                $otherErrors++;
                if (!isset($errorSamples['invalid_json'])) {
                    $errorSamples['invalid_json'] = 0;
                }
                $errorSamples['invalid_json']++;
                if (count($decodeSamples) < 3) {
                    $decodeSamples[] = trim(substr((string) $body, 0, 400));
                }
            }
        }

        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
        unset($handles[(int) $ch]);
        $inFlight--;
        $completed++;
    }

    if ($running > 0) {
        curl_multi_select($mh, 0.05);
    }
}

curl_multi_close($mh);

$elapsed = microtime(true) - $start;
$rps = $elapsed > 0 ? $completed / $elapsed : 0;
$successRate = $completed > 0 ? ($success / $completed) * 100 : 0;

echo "\n=== Result ===\n";
echo "Session ID        : {$sessionId}\n";
echo "Total completed   : {$completed}\n";
echo "Success           : {$success}\n";
echo "Already marked    : {$already}\n";
echo "Rate limited      : {$rateLimited}\n";
echo "Unauthorized      : {$unauthorized}\n";
echo "Other errors      : {$otherErrors}\n";
echo "HTTP >= 400       : {$httpErrors}\n";
echo "Elapsed (sec)     : " . number_format($elapsed, 2) . "\n";
echo "Req/sec           : " . number_format($rps, 2) . "\n";
echo "Success rate      : " . number_format($successRate, 2) . "%\n";
if (!empty($errorSamples)) {
    arsort($errorSamples);
    $top = array_slice($errorSamples, 0, 5, true);
    echo "Top errors        :\n";
    foreach ($top as $msg => $count) {
        echo "  - {$msg}: {$count}\n";
    }
}
if (!empty($decodeSamples)) {
    echo "Response samples   :\n";
    foreach ($decodeSamples as $sample) {
        echo "  ---\n";
        echo "  " . str_replace("\n", "\n  ", $sample) . "\n";
    }
}

if ($cleanup) {
    cleanupTestData($pdo, $sessionId, $createdSession, $createdStudentIds);
}

exit($successRate >= $minSuccessRate ? 0 : 1);

function createLoadTestSession(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT ts.slot_id, COALESCE(ts.default_teacher_id, u.user_id) AS teacher_id
        FROM timetable_slots ts
        LEFT JOIN users u ON u.role IN ('SEMI_ADMIN', 'MASTER')
        LIMIT 1
    ");
    $slot = $stmt->fetch();
    if (!$slot || empty($slot['slot_id']) || empty($slot['teacher_id'])) {
        throw new RuntimeException('Cannot create live session: missing timetable slot or teacher.');
    }

    $token = substr(hash('sha256', uniqid('load_', true) . microtime(true)), 0, 16);
    $stmt = $pdo->prepare("
        INSERT INTO live_sessions (slot_id, actual_teacher_id, session_date, status, active_totp_token, started_at)
        VALUES (?, ?, CURDATE(), 'LIVE', ?, NOW())
    ");
    $stmt->execute([(int) $slot['slot_id'], (int) $slot['teacher_id'], $token]);

    return [(int) $pdo->lastInsertId(), $token];
}

function getStudentIds(PDO $pdo, int $limit): array
{
    $stmt = $pdo->prepare("
        SELECT user_id
        FROM users
        WHERE role = 'STUDENT' AND is_active = 1
        ORDER BY user_id
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function seedStudents(PDO $pdo, int $count): array
{
    $created = [];
    $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    $passwordHash = password_hash('LoadTest@123', $algo);

    $pdo->beginTransaction();
    try {
        $insertUser = $pdo->prepare("
            INSERT INTO users (
                email, full_name, phone, password_hash, role,
                is_active, email_verified, approved, registration_step
            ) VALUES (?, ?, '', ?, 'STUDENT', 1, 1, 1, 2)
        ");
        $insertProfile = $pdo->prepare("
            INSERT INTO student_profiles (
                user_id, roll_no, branch_code, current_semester, section, lab_batch
            ) VALUES (?, ?, 'CSE', 5, 'A', 'A1')
        ");

        $base = (int) (microtime(true) * 1000);
        for ($i = 0; $i < $count; $i++) {
            $stamp = $base + $i;
            $email = "loadtest_{$stamp}@rjit.ac.in";
            $fullName = "Load Test Student {$stamp}";
            $rollNo = "LT{$stamp}";

            $insertUser->execute([$email, $fullName, $passwordHash]);
            $userId = (int) $pdo->lastInsertId();
            $insertProfile->execute([$userId, $rollNo]);
            $created[] = $userId;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return $created;
}

function cleanupTestData(PDO $pdo, int $sessionId, bool $createdSession, array $createdStudentIds): void
{
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM attendance_logs WHERE session_id = ?");
        $stmt->execute([$sessionId]);

        if (!empty($createdStudentIds)) {
            $placeholders = implode(',', array_fill(0, count($createdStudentIds), '?'));
            $params = $createdStudentIds;

            $stmt = $pdo->prepare("DELETE FROM student_profiles WHERE user_id IN ({$placeholders})");
            $stmt->execute($params);

            $stmt = $pdo->prepare("DELETE FROM student_attendance_summary WHERE student_id IN ({$placeholders})");
            $stmt->execute($params);

            $stmt = $pdo->prepare("DELETE FROM system_logs WHERE user_id IN ({$placeholders})");
            $stmt->execute($params);

            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id IN ({$placeholders})");
            $stmt->execute($params);
        }

        if ($createdSession) {
            $stmt = $pdo->prepare("DELETE FROM live_sessions WHERE session_id = ?");
            $stmt->execute([$sessionId]);
        }

        $pdo->commit();
        echo "Cleanup complete.\n";
    } catch (Throwable $e) {
        $pdo->rollBack();
        echo "Cleanup failed: " . $e->getMessage() . "\n";
    }
}
