<?php

declare(strict_types=1);

namespace App\Commons\DTOs;

/**
 * DTO para los datos del objeto 'subscription' de Stripe.
 * Utilizado para eventos como 'customer.subscription.created', '.updated', o '.deleted'.
 * Representa una suscripción recurrente de un cliente a un producto o plan.
 */
final class SubscriptionDTO
{
    public function __construct(
        public readonly string $id, // sub_...
        public readonly string $objectType, // Siempre "subscription"
        public readonly string $customerId, // cus_...
        public readonly string $status, // ej: "active", "trialing", "canceled", "past_due"
        public readonly ?int $currentPeriodStartTimestamp, // CAMBIADO a ?int
        public readonly ?int $currentPeriodEndTimestamp,   // CAMBIADO a ?int
        public readonly bool $cancelAtPeriodEnd,
        public readonly ?int $canceledAtTimestamp,
        public readonly ?int $endedAtTimestamp, // Para suscripciones que han finalizado completamente
        public readonly ?string $latestInvoiceId, // in_... (ID de la última factura generada)
        public readonly int $createdTimestamp,
        public readonly ?string $priceId, // price_... (extraído de items.data[0].price.id)
        public readonly ?string $priceInterval, // "month", "year", etc. (de items.data[0].price.recurring.interval)
        public readonly ?string $priceType, // "recurring", "one_time" (de items.data[0].price.type)
        public readonly ?array $metadata
    ) {
    }
}