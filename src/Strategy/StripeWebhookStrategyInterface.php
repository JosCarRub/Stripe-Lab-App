<?php

declare(strict_types=1);

namespace App\Strategy;

use App\Commons\Exceptions\DatabaseException;
use App\Commons\Exceptions\InvalidWebhookPayloadException;
use App\Commons\Exceptions\WebhookProcessingException;
use Stripe\Event as StripeEvent;
use App\Commons\Enums\StripeEventTypeEnum;
use Stripe\Exception\ApiErrorException;

interface StripeWebhookStrategyInterface
{
    /**
     * Determina si esta estrategia es aplicable al evento de Stripe dado.
     *
     * @param StripeEvent $event El evento de Stripe.
     * @return bool True si la estrategia puede manejar este evento, false de lo contrario.
     */
    public function isApplicable(StripeEvent $event): bool;

    /**
     * Procesa el evento de webhook de Stripe.
     *
     * @param StripeEvent $event El evento de Stripe verificado.
     * @return void
     * @throws InvalidWebhookPayloadException Si el payload es inválido para la lógica de la estrategia.
     * @throws WebhookProcessingException Si ocurre un error durante el procesamiento interno de la estrategia.
     * @throws DatabaseException Si hay un error de base de datos.
     * @throws ApiErrorException Si la estrategia necesita interactuar con la API de Stripe y falla.
     */
    public function process(StripeEvent $event): void;

    /**
     * Devuelve el tipo de evento de Stripe que esta estrategia maneja.
     * Esto es usado por la propia estrategia en isApplicable().
     * @return StripeEventTypeEnum
     */
    public static function getSupportedEventType(): StripeEventTypeEnum;
}