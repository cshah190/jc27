<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email = strtolower(trim((string)($_POST['email'] ?? '')));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$csvFile = $dataDir . DIRECTORY_SEPARATOR . 'notify_subscribers.csv';

if (!is_dir($dataDir) && !mkdir($dataDir, 0755, true)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to create data directory.']);
    exit;
}

if (!file_exists($csvFile)) {
    $headerHandle = fopen($csvFile, 'w');
    if ($headerHandle === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Unable to create CSV file.']);
        exit;
    }
    fputcsv($headerHandle, ['created_at', 'email']);
    fclose($headerHandle);
}

$existingEmails = [];
$readHandle = fopen($csvFile, 'r');
if ($readHandle !== false) {
    while (($row = fgetcsv($readHandle)) !== false) {
        if (isset($row[1])) {
            $existingEmails[strtolower(trim($row[1]))] = true;
        }
    }
    fclose($readHandle);
}

if (isset($existingEmails[$email])) {
    echo json_encode(['success' => true, 'message' => 'Thank you! This email is already on the update list.']);
    exit;
}

$handle = fopen($csvFile, 'a');
if ($handle === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to open CSV file.']);
    exit;
}

if (!flock($handle, LOCK_EX)) {
    fclose($handle);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to lock CSV file.']);
    exit;
}

fputcsv($handle, [gmdate('c'), $email]);
fflush($handle);
flock($handle, LOCK_UN);
fclose($handle);

echo json_encode(['success' => true, 'message' => 'Thank you! We’ll notify you when updates are available.']);
