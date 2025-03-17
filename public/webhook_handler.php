<?php
// Load configuration
$config = json_decode(file_get_contents('../config.json'), true);
if (!$config) {
    http_response_code(500);
    exit('Failed to load configuration.');
}
$secret = $config['secret'] ?? '';
if (empty($secret)) {
    http_response_code(500);
    exit('Secret not found in configuration.');
}

// This script handles GitHub webhook events and responds accordingly

// Get the signature header from GitHub
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
if (empty($signature)) {
    http_response_code(400);
    exit('Missing signature header.');
}

// Read the raw POST data from GitHub
$payload = file_get_contents('php://input');
if ($payload === false) {
    http_response_code(400);
    exit('Failed to read payload.');
}

// Verify the signature to ensure the request is valid
if (!verifySignature($payload, $signature, $secret)) {
    http_response_code(403);
    file_put_contents($config['log_file'], "Signature verification failed\n", FILE_APPEND);
    exit('Signature verification failed.');
}

// Decode the JSON payload
$data = json_decode($payload, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    exit('Invalid JSON payload.');
}

// Handle the event
if ($_SERVER['HTTP_X_GITHUB_EVENT'] == 'push') {
    handlePushEvent($data, $config);
} else {
    http_response_code(400);
    exit('Unsupported event type.');
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
 * @param array $config
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
        if (($project['github_repository'] ?? '') === $repository && ($project['github_branch'] ?? '') === $branch) {
            $projectPath = $project['project_path'] ?? '';
            if (empty($projectPath) || !is_dir($projectPath)) {
                http_response_code(500);
                exit('Invalid project path in configuration.');
            }

            // Change directory and execute git pull command with error handling
            if (!chdir($projectPath)) {
                http_response_code(500);
                exit('Failed to change directory to project path.');
            }

            $output = [];
            $returnVar = 0;
            exec("git pull origin $branch", $output, $returnVar);

            // Check if the git pull command was successful and log the result
            if ($returnVar !== 0) {
                $logMessage = sprintf(
                    "[%s] Error executing git pull for project '%s' on branch '%s': %s\n",
                    date('Y-m-d H:i:s'),
                    $repository,
                    $branch,
                    implode("\n", $output)
                );
                file_put_contents($config['log_file'], $logMessage, FILE_APPEND);
                http_response_code(500);
                exit('Failed to execute git pull command');
            } else if (($project['laravel_cache'] ?? false)) {
                // Clear Laravel cache
                exec("php artisan cache:clear");
                // Clear Laravel view cache
                exec("php artisan view:clear");
                // Clear Laravel route cache
                exec("php artisan route:clear");
                // Enable Laravel view cache
                exec("php artisan view:cache");
                // Enable Laravel route cache
                exec("php artisan route:cache");
            }

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
        "[%s] Push to '%s' on branch '%s' handled successfully\n",
        date('Y-m-d H:i:s'),
        $repository,
        $branch
    );
    file_put_contents($config['log_file'], $logMessage, FILE_APPEND);

    echo "Push event handled successfully.";
}
?>
