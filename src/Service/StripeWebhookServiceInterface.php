<?php

declare(strict_types=1);

namespace App\Service;

use App\Commons\Exceptions\WebhookProcessingException;
use Stripe\Event as StripeEvent;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

// Alias para evitar colisión con nombres de evento

interface StripeWebhookServiceInterface
{
    /**
     * Construye y verifica un evento de Stripe a partir del payload y la firma.
     *
     * @param string $rawPayload
     * @param string $signatureHeader
     * @return StripeEvent
     * @throws SignatureVerificationException Si la firma es inválida.
     * @throws UnexpectedValueException
     */
    public function constructEvent(string $rawPayload, string $signatureHeader): StripeEvent;

    /**
     * Procesa un evento de Stripe verificado, dirigiéndolo a la estrategia apropiada.
     *
     * @param StripeEvent $event
     * @return void
     * @throws WebhookProcessingException Si no se encuentra una estrategia o hay un error.
     */
    public function processWebhookEvent(StripeEvent $event): void;
}