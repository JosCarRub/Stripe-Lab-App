<?php

declare(strict_types=1);

namespace App\Commons\Loggers;

class UnhandledStripeEventLogger
{

    private const LOG_FILE_PATH = __DIR__ . '/../../../logs/unhandled_stripe_events.log';
    private const LOG_LEVEL_TAG = '[UNHANDLED_STRIPE_EVENT]';

    /**
     * Registra el payload de un evento de Stripe para el cual no se encontró una estrategia.
     *
     * @param string $eventType El tipo de evento de Stripe (ej. 'product.created').
     * @param string $eventId El ID del evento de Stripe.
     * @param object|array $payload El objeto $event->data->object.
     */
    public static function log(string $eventType, string $eventId, object|array $payload): void
    {
        $logDir = dirname(self::LOG_FILE_PATH);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true); // Suprimir errores si ya existe o falla por permisos
        }

        $timestamp = date('Y-m-d H:i:s');
        $payloadJson = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payloadJson === false) {
            $payloadJson = "Error al codificar payload a JSON: " . json_last_error_msg();
        }

        $logHeader = "Event Type: {$eventType}, Event ID: {$eventId}";
        $logEntry = "[{$timestamp}] " . self::LOG_LEVEL_TAG . " {$logHeader}" . PHP_EOL . $payloadJson . PHP_EOL . "---" . PHP_EOL;

        if (@file_put_contents(self::LOG_FILE_PATH, $logEntry, FILE_APPEND | LOCK_EX) === false) {
            // Fallback si la escritura falla
            error_log("Failed to write to UnhandledStripeEventLogger file (" . self::LOG_FILE_PATH . "). Event ID: {$eventId}");
        }
    }
}