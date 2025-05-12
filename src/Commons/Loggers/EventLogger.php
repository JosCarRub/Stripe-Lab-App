<?php

declare(strict_types=1);

namespace App\Commons\Loggers;

class EventLogger
{
    private const LOG_FILE_PATH = __DIR__ . '/../../../logs/events.log';
    private const LOG_LEVEL_TAG = '[EVENT]';

    /**
     * Loggea eventos de la aplicación.
     * @param string $message Mensaje del evento, puede contener {placeholders}.
     * @param array $contextInfo Contexto para interpolar o añadir como JSON.
     */
    public static function log(string $message, array $contextInfo = []): void
    {
        $logDir = dirname(self::LOG_FILE_PATH);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true); // @ para suprimir errores si ya existe o falla por permisos
        }

        $timestamp = date('Y-m-d H:i:s');
        $interpolatedMessage = self::interpolate($message, $contextInfo);
        $contextString = "";

        if (!empty($contextInfo)) {
            $contextString = " - Context: " . json_encode($contextInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $logEntry = "[{$timestamp}] " . self::LOG_LEVEL_TAG . " {$interpolatedMessage}{$contextString}" . PHP_EOL;


        if (@file_put_contents(self::LOG_FILE_PATH, $logEntry, FILE_APPEND | LOCK_EX) === false) {
            error_log("Failed to write to EventLogger file (" . self::LOG_FILE_PATH . "). Message: {$logEntry}");
        }
    }

    private static function interpolate(string $message, array $context = []): string
    {
        if (strpos($message, '{') === false || empty($context)) {
            return $message;
        }
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = (string) $val;
            }
        }
        return strtr($message, $replace);
    }
}