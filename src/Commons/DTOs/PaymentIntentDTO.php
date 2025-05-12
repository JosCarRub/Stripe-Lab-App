<?php

declare(strict_types=1);

namespace App\Commons\DTOs;

/**
 * DTO para los datos del objeto 'payment_intent' de Stripe.
 * Utilizado para eventos como 'payment_intent.succeeded'.
 * Representa la intención de cobrar a un cliente y rastrea el ciclo de vida del pago.
 */
final class PaymentIntentDTO
{
    public function __construct(
        public readonly string $id, // pi_...
        public readonly string $objectType, // Siempre "payment_intent"
        public readonly int $amount,
        public readonly int $amountReceived,
        public readonly string $currency,
        public readonly ?string $customerId, // cus_...
        public readonly ?string $description,
        public readonly ?string $invoiceId, // in_... (si está asociado a una factura)
        public readonly ?string $latestChargeId, // ch_... (ID del cargo exitoso asociado)
        public readonly ?string $receiptEmail, // Email al que se envió el recibo (si aplica)
        public readonly string $status, // ej: "succeeded", "requires_payment_method", etc.
        public readonly int $createdTimestamp,
        public readonly ?array $metadata
    ) {
    }
}