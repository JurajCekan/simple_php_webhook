<?php
// Load configuration
$config = json_decode(file_get_contents('../config.json'), true);
$secret = $config['secret'];

// This script handles GitHub webhook events and responds accordingly

// Get the signature header from GitHub
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Read the raw POST data from GitHub
$payload = file_get_contents('php://input');

// Verify the signature to ensure the request is valid
if (!verifySignature($payload, $signature, $secret)) {
    http_response_code(403);
    file_put_contents($config['log_file'], "Signature verification failed\n", FILE_APPEND);
    die('Signature verification failed.');
}

// Decode the JSON payload
$data = json_decode($payload, true);

// Handle the event
if ($_SERVER['HTTP_X_GITHUB_EVENT'] == 'push') {
    handlePushEvent($data, $config);
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
function handlePushEvent($data, $config)
{
    // Log the repository name and the branch that was pushed
    $repository = $data['repository']['full_name'] ?? 'unknown';
    $branch = explode('/', $data['ref'])[2] ?? 'unknown';

    // Find the matching project in the configuration
    $projectFound = false;
    foreach ($config['projects'] as $project) {
        if ($project['github_repository'] === $repository && $project['github_branch'] === $branch) {
            $projectPath = $project['project_path'];
            // Execute git pull command
            exec("cd $projectPath && git checkout $branch && git pull origin $branch");
            $projectFound = true;
            break;
        }
    }

    // Log if no matching project configuration is found
    if (!$projectFound) {
        $logMessage = sprintf(
            "[%s] No matching project configuration found for GitHub project '%s' and branch '%s'\n",
            date('Y-m-d H:i:s'),
            $repository,
            $branch
        );
        file_put_contents($config['log_file'], $logMessage, FILE_APPEND);
        http_response_code(404);
        exit('No matching project configuration found');
    }

    // Log push event
    $logMessage = sprintf(
        "[%s] Push to '%s' on branch '%s'\n No matching project configuration found for GitHub project '%s' and branch '%s'\n",
        date('Y-m-d H:i:s'),
        $repository,
        $branch
    );
    file_put_contents($config['log_file'], $logMessage, FILE_APPEND);

    echo "Push event handled successfully.";
}
