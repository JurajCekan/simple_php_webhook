<?php
// Include configuration file
require_once '../config.php';

// This script handles GitHub webhook events and responds accordingly

// Get the signature header from GitHub
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Read the raw POST data from GitHub
$payload = file_get_contents('php://input');

// Verify the signature to ensure the request is valid
if (!verifySignature($payload, $signature, $secret)) {
    http_response_code(403);
    file_put_contents('webhook_log.txt', "Signature verification failed\n", FILE_APPEND);
    die('Signature verification failed.');
}

// Decode the JSON payload
$data = json_decode($payload, true);

// Handle the event
if ($_SERVER['HTTP_X_GITHUB_EVENT'] == 'push') {
    handlePushEvent($data);
} else {
    http_response_code(400);
    die('Unsupported event type.');
}

/**
 * Verify the request signature
 *
 * @param string $payload
 * @param string $signature
 * @param string $secret
 * @return bool
 */
function verifySignature($payload, $signature, $secret)
{
    $hash = 'sha256=' . hash_hmac('sha256', $payload, $secret, false);
    return hash_equals($hash, $signature);
}

/**
 * Handle the push event from GitHub
 *
 * @param array $data
 * @return void
 */
function handlePushEvent($data)
{
    // Log the repository name and the branch that was pushed
    $repository = $data['repository']['full_name'] ?? 'unknown';
    $branch = explode('/', $data['ref'])[2] ?? 'unknown';
    
    file_put_contents('webhook_log.txt', "Push to $repository on branch $branch\n", FILE_APPEND);

    // You could also trigger further actions, like deploying your code, etc.
    echo "Push event handled successfully.";
}
?>
