<?php

declare(strict_types=1);

namespace SimplePhpWebhook\Shell;

class CommandExecutor
{
    /**
     * Execute a shell command inside a specific directory.
     *
     * @param string $command The CLI command to execute.
     * @param string $path The directory path to run the command in.
     * @return array{success: bool, output: string[], code: int}
     */
    public function execute(string $command, string $path): array
    {
        if (!is_dir($path)) {
            return [
                'success' => false,
                'output' => ['Directory does not exist'],
                'code' => -1
            ];
        }

        $originalDir = getcwd();
        if ($originalDir === false) {
            return [
                'success' => false,
                'output' => ['Failed to get current working directory'],
                'code' => -1
            ];
        }

        if (!chdir($path)) {
            return [
                'success' => false,
                'output' => ['Failed to change directory'],
                'code' => -1
            ];
        }

        $output = [];
        $returnVar = 0;
        
        exec($command, $output, $returnVar);

        // Restore original directory
        chdir($originalDir);

        return [
            'success' => $returnVar === 0,
            'output' => $output,
            'code' => $returnVar
        ];
    }
}
