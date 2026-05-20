<?php

declare(strict_types=1);

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use SimplePhpWebhook\Config\ConfigLoader;
use SimplePhpWebhook\Logger\WebhookLogger;
use SimplePhpWebhook\Security\SignatureValidator;
use SimplePhpWebhook\Shell\CommandExecutor;
use SimplePhpWebhook\WebhookHandler;

// 1. Define configuration path
$configPath = __DIR__ . '/../config.json';

// 2. Load raw config to determine the logging destination (fallback is logs/webhook_log.txt)
$logFile = 'logs/webhook_log.txt';
if (file_exists($configPath)) {
    $rawConfig = json_decode(file_get_contents($configPath), true);
    if (isset($rawConfig['log_file'])) {
        $logFile = $rawConfig['log_file'];
    }
}

// 3. Initialize components
$configLoader = new ConfigLoader($configPath);
$signatureValidator = new SignatureValidator();
$executor = new CommandExecutor();
$logger = new WebhookLogger($logFile);

$handler = new WebhookHandler(
    $configLoader,
    $signatureValidator,
    $executor,
    $logger
);

// 4. Capture request raw input
$payload = file_get_contents('php://input');
if ($payload === false) {
    http_response_code(400);
    exit('Failed to read payload.');
}

// 5. Delegate processing to the WebhookHandler controller
$response = $handler->handle($_SERVER, $payload);

// 6. Return response
http_response_code($response['code']);
echo $response['body'];
