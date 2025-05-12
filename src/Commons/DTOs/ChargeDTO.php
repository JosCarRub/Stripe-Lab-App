<?php

declare(strict_types=1);

namespace App\Commons\DTOs;

/**
 * DTO para los datos del objeto 'charge' de Stripe.
 * Utilizado para eventos como 'charge.succeeded'.
 * Representa un intento de cargo a una tarjeta de crédito o a otra fuente de pago.
 */
final class ChargeDTO
{
    public function __construct(
        public readonly string $id, // ch_...
        public readonly string $objectType, // Siempre "charge"
        public readonly int $amount,
        public readonly int $amountCaptured,
        public readonly string $currency,
        public readonly ?string $customerId, // cus_...
        public readonly ?string $description,
        public readonly ?string $invoiceId, // in_... (si está asociado a una factura)
        public readonly ?string $paymentIntentId, // pi_...
        public readonly ?string $receiptUrl, // URL del recibo hosteado por Stripe
        public readonly string $status, // ej: "succeeded", "pending", "failed"
        public readonly int $createdTimestamp,
        public readonly ?string $billingDetailsName, // Extraído de billing_details.name
        public readonly ?string $billingDetailsEmail, // Extraído de billing_details.email
        public readonly ?array $metadata
    ) {
    }
}