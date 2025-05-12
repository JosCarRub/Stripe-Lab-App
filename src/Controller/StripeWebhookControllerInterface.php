<?php

declare(strict_types=1);

namespace App\Controller;

interface StripeWebhookControllerInterface
{
    /**
     * Maneja una solicitud de webhook entrante de Stripe.
     *
     * @param string $rawPayload El cuerpo en crudo de la solicitud POST.
     * @param string|null $signatureHeader El valor de la cabecera 'Stripe-Signature'.
     * @return void Emite una respuesta HTTP.
     */
    public function handleStripeWebhook(string $rawPayload, ?string $signatureHeader): void;
}