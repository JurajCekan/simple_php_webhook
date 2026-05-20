<?php

declare(strict_types=1);

namespace SimplePhpWebhook\Config;

use RuntimeException;

class ConfigLoader
{
    private string $configPath;

    /**
     * ConfigLoader constructor.
     *
     * @param string $configPath Path to the configuration JSON file.
     */
    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    /**
     * Load, decode, and validate the configuration file.
     *
     * @return array
     * @throws RuntimeException If validation fails.
     */
    public function load(): array
    {
        if (!file_exists($this->configPath)) {
            throw new RuntimeException('Configuration file not found.');
        }

        $content = file_get_contents($this->configPath);
        if ($content === false) {
            throw new RuntimeException('Failed to read configuration file.');
        }

        $config = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($config)) {
            throw new RuntimeException('Failed to parse configuration JSON.');
        }

        if (empty($config['secret'])) {
            throw new RuntimeException('Secret not found in configuration.');
        }

        if (!isset($config['projects']) || !is_array($config['projects'])) {
            throw new RuntimeException('Projects not found in configuration.');
        }

        foreach ($config['projects'] as $project) {
            if (!is_array($project)
                || empty($project['github_repository'])
                || empty($project['github_branch'])
                || empty($project['project_path'])
            ) {
                throw new RuntimeException('Invalid project configuration.');
            }
        }

        return $config;
    }
}
