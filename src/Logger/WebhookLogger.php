<?php

declare(strict_types=1);

namespace SimplePhpWebhook\Logger;

class WebhookLogger
{
    private string $logFile;

    /**
     * WebhookLogger constructor.
     *
     * @param string $logFile Path to the log file.
     */
    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
    }

    /**
     * Log a message with a timestamp.
     *
     * @param string $message
     * @return void
     */
    public function log(string $message): void
    {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = sprintf("[%s] %s\n", $timestamp, $message);

        file_put_contents($this->logFile, $formattedMessage, FILE_APPEND);
    }
}
