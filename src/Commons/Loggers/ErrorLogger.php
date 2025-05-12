<?php

declare(strict_types=1);

namespace App\Commons\Loggers;

class ErrorLogger
{
    private const LOG_FILE_PATH = __DIR__ . '/../../../logs/errors.log';

    /**
     * Registra un error.
     * @param string $message Mensaje del error.
     * @param array $contextInfo Contexto adicional.
     * @param string $levelTag Etiqueta del nivel, ej. "[ERROR]", "[CRITICAL]".
     */
    public static function log(string $message, array $contextInfo = [], string $levelTag = '[ERROR]'): void
    {
        $logDir = dirname(self::LOG_FILE_PATH);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $interpolatedMessage = self::interpolate($message, $contextInfo);
        $contextString = "";
        if (!empty($contextInfo)) {
            $contextString = " - Context: " . json_encode($contextInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $logEntry = "[{$timestamp}] {$levelTag} {$interpolatedMessage}{$contextString}" . PHP_EOL;

        if (@file_put_contents(self::LOG_FILE_PATH, $logEntry, FILE_APPEND | LOCK_EX) === false) {
            error_log("Failed to write to ErrorLogger file (" . self::LOG_FILE_PATH . "). Message: {$logEntry}");
        }
    }

    public static function exception(\Throwable $exception, array $additionalContext = [], string $levelTag = '[EXCEPTION]'): void
    {
        $context = array_merge($additionalContext, [
            'exception_type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'trace_snippet' => implode(" -> ", array_slice(explode("\n", $exception->getTraceAsString()), 0, 3))
        ]);
        // Añadir propiedades específicas de nuestras excepciones
        if ($exception instanceof \App\Commons\Exceptions\WebhookProcessingException || $exception instanceof \App\Commons\Exceptions\InvalidWebhookPayloadException) {
            if (property_exists($exception, 'webhookEventType') && $exception->webhookEventType !== null) {
                $context['webhook_event_type'] = $exception->webhookEventType;
            }
            if (property_exists($exception, 'webhookEventId') && $exception->webhookEventId !== null) {
                $context['webhook_event_id_from_exception'] = $exception->webhookEventId;
            }
        }

        self::log(get_class($exception), $context, $levelTag);
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