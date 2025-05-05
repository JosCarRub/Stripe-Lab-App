<?php

namespace App\commons\logger;

class EventLogger
{
    private static string $logFile = __DIR__ . '/../../../logs/events.log';
    public static function eventLog(string $message, array $contextInfo = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextJson = !empty($contextInfo) ? json_encode($contextInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';

        $logMessage = "[$timestamp] - [INFO] $message - $contextJson" . PHP_EOL;

        file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
    }
}