<?php

declare(strict_types=1);

namespace SimplePhpWebhook;

use SimplePhpWebhook\Config\ConfigLoader;
use SimplePhpWebhook\Logger\WebhookLogger;
use SimplePhpWebhook\Security\SignatureValidator;
use SimplePhpWebhook\Shell\CommandExecutor;
use Exception;

class WebhookHandler
{
    private ConfigLoader $configLoader;
    private SignatureValidator $signatureValidator;
    private CommandExecutor $executor;
    private WebhookLogger $logger;

    /**
     * WebhookHandler constructor.
     *
     * @param ConfigLoader $configLoader
     * @param SignatureValidator $signatureValidator
     * @param CommandExecutor $executor
     * @param WebhookLogger $logger
     */
    public function __construct(
        ConfigLoader $configLoader,
        SignatureValidator $signatureValidator,
        CommandExecutor $executor,
        WebhookLogger $logger
    ) {
        $this->configLoader = $configLoader;
        $this->signatureValidator = $signatureValidator;
        $this->executor = $executor;
        $this->logger = $logger;
    }

    /**
     * Handle incoming webhook requests.
     *
     * @param array $server $_SERVER array
     * @param string $payload Raw request body
     * @return array{code: int, body: string}
     */
    public function handle(array $server, string $payload): array
    {
        // 1. Verify Event Type
        $event = $server['HTTP_X_GITHUB_EVENT'] ?? '';
        if (empty($event)) {
            return [
                'code' => 400,
                'body' => 'Missing event header.'
            ];
        }

        // 2. Load Configuration
        try {
            $config = $this->configLoader->load();
        } catch (Exception $e) {
            return [
                'code' => 500,
                'body' => 'Failed to load configuration.'
            ];
        }

        // 3. Verify Signature
        $signature = $server['HTTP_X_HUB_SIGNATURE_256'] ?? '';
        if (empty($signature)) {
            return [
                'code' => 400,
                'body' => 'Missing signature header.'
            ];
        }

        $secret = $config['secret'] ?? '';
        if (!$this->signatureValidator->verify($payload, $signature, $secret)) {
            $this->logger->log('Signature verification failed');
            return [
                'code' => 403,
                'body' => 'Signature verification failed.'
            ];
        }

        // 4. Decode JSON Payload
        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return [
                'code' => 400,
                'body' => 'Invalid JSON payload.'
            ];
        }

        // 5. Verify push event
        if ($event !== 'push') {
            return [
                'code' => 400,
                'body' => 'Unsupported event type.'
            ];
        }

        // 6. Parse Repository and Branch
        $repository = $data['repository']['full_name'] ?? 'unknown';
        $ref = $data['ref'] ?? '';
        $branch = 'unknown';
        if (str_starts_with($ref, 'refs/heads/')) {
            $branch = substr($ref, 11);
        } else {
            $parts = explode('/', $ref);
            $branch = $parts[count($parts) - 1] ?: 'unknown';
        }

        // 7. Find Matching Configured Project
        $matchedProject = null;
        foreach ($config['projects'] as $project) {
            if (($project['github_repository'] ?? '') === $repository
                && ($project['github_branch'] ?? '') === $branch
            ) {
                $matchedProject = $project;
                break;
            }
        }

        if ($matchedProject === null) {
            $logMessage = sprintf(
                "No matching project configuration found for GitHub project '%s' and branch '%s'",
                $repository,
                $branch
            );
            $this->logger->log($logMessage);
            return [
                'code' => 404,
                'body' => 'No matching project configuration found'
            ];
        }

        $projectPath = $matchedProject['project_path'] ?? '';

        // 8. Execute Git Pull
        $pullCommand = sprintf('git pull origin %s', escapeshellarg($branch));
        $pullResult = $this->executor->execute($pullCommand, $projectPath);

        if (!$pullResult['success']) {
            $logMessage = sprintf(
                "Error executing git pull for project '%s' on branch '%s': %s",
                $repository,
                $branch,
                implode("\n", $pullResult['output'])
            );
            $this->logger->log($logMessage);
            return [
                'code' => 500,
                'body' => 'Failed to execute git pull command'
            ];
        }

        // 9. Execute Laravel Caching Commands (If configured)
        if ($matchedProject['laravel_cache'] ?? false) {
            $laravelCommands = [
                'php artisan cache:clear',
                'php artisan view:clear',
                'php artisan route:clear',
                'php artisan view:cache',
                'php artisan route:cache'
            ];

            foreach ($laravelCommands as $command) {
                $this->executor->execute($command, $projectPath);
            }

            $logMessage = sprintf("Cache successfully cleared on '%s'", $repository);
            $this->logger->log($logMessage);
        }

        // 10. Success Logging & Response
        $successMessage = sprintf("Push to '%s' on branch '%s' handled successfully", $repository, $branch);
        $this->logger->log($successMessage);

        return [
            'code' => 200,
            'body' => 'Push event handled successfully.'
        ];
    }
}
