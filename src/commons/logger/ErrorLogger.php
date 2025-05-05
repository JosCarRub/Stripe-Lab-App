<?php

namespace App\commons\logger;

class ErrorLogger
{
    private static string $logFile = __DIR__ . '/../../../logs/errors.log';
    public static function errorLog(string $message, array $contextInfo = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextJson = !empty($contextInfo) ? json_encode($contextInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';

        $logMessage = "[$timestamp] - [ERROR] $message  $contextJson" . PHP_EOL;

        file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
    }
}