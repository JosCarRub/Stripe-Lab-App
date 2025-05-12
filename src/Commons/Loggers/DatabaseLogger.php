<?php

declare(strict_types=1);

namespace App\Commons\Loggers;

class DatabaseLogger
{
    private const LOG_FILE_PATH = __DIR__ . '/../../../logs/database.log';

    /**
     * Registra una consulta a la base de datos.
     * @param string $query La consulta SQL.
     * @param array $params Parámetros de la consulta (no se interpolan en la query por seguridad).
     */
    public static function query(string $query, array $params = []): void
    {
        $logDir = dirname(self::LOG_FILE_PATH);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $levelTag = '[DB_QUERY]';
        $paramsString = !empty($params) ? " - Params: " . json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';

        $logEntry = "[{$timestamp}] {$levelTag} {$query}{$paramsString}" . PHP_EOL;

        if (@file_put_contents(self::LOG_FILE_PATH, $logEntry, FILE_APPEND | LOCK_EX) === false) {
            error_log("Failed to write to DatabaseLogger file (" . self::LOG_FILE_PATH . "). Message: {$logEntry}");
        }
    }

    /**
     * Registra un error de base de datos.
     * @param string $message Mensaje del error.
     * @param array $contextInfo Contexto adicional.
     */
    public static function error(string $message, array $contextInfo = []): void
    {
        $logDir = dirname(self::LOG_FILE_PATH);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $levelTag = '[DB_ERROR]';
        $interpolatedMessage = self::interpolate($message, $contextInfo);
        $contextString = "";
        if (!empty($contextInfo)) {
            $contextString = " - Context: " . json_encode($contextInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $logEntry = "[{$timestamp}] {$levelTag} {$interpolatedMessage}{$contextString}" . PHP_EOL;

        if (@file_put_contents(self::LOG_FILE_PATH, $logEntry, FILE_APPEND | LOCK_EX) === false) {
            error_log("Failed to write to DatabaseLogger file (" . self::LOG_FILE_PATH . "). Message: {$logEntry}");
        }
    }

    private static function interpolate(string $message, array $context = []): string // Misma función
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