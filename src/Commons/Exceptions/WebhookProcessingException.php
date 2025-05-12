<?php

declare(strict_types=1);

namespace App\Commons\Exceptions;

/**
 * Lanzada cuando ocurre un error general dentro de una estrategia
 * o servicio durante el procesamiento de un webhook de Stripe,
 * después de que el payload ha sido considerado inicialmente válido.
 */
class WebhookProcessingException extends ApplicationException
{
    public ?string $webhookEventType = null;
    public ?string $webhookEventId = null;

    /**
     * @param string $message Descripción del error de procesamiento.
     * @param string|null $webhookEventType Tipo de evento de Stripe.
     * @param string|null $webhookEventId ID del evento de Stripe.
     * @param int $code Código de error.
     * @param \Throwable|null $previous Excepción previa.
     */
    public function __construct(
        string $message = "",
        ?string $webhookEventType = null,
        ?string $webhookEventId = null,
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->webhookEventType = $webhookEventType;
        $this->webhookEventId = $webhookEventId;
    }

    public function getWebhookEventType(): ?string
    {
        return $this->webhookEventType;
    }

    public function getWebhookEventId(): ?string
    {
        return $this->webhookEventId;
    }
}