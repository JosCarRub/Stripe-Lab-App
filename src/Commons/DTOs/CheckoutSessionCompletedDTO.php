<?php

declare(strict_types=1);

namespace App\Commons\DTOs;

/**
 * DTO para los datos del objeto 'checkout.session' cuando un evento 'checkout.session.completed' es recibido.
 * Contiene información sobre la sesión de pago completada, incluyendo detalles del cliente y del pago.
 */
final class CheckoutSessionCompletedDTO
{
    public function __construct(
        public readonly string $id, // ID de la sesión de Checkout (cs_...)
        public readonly string $objectType, // Siempre "checkout.session"
        public readonly ?string $clientReferenceId,
        public readonly ?string $customerId, // cus_... (ID del cliente de Stripe)
        public readonly ?string $customerEmail, // Extraído de customer_details.email
        public readonly ?string $customerName, // Extraído de customer_details.name
        public readonly ?string $paymentIntentId, // pi_... (si mode es 'payment')
        public readonly ?string $subscriptionId, // sub_... (si mode es 'subscription')
        public readonly ?int $amountSubtotal,
        public readonly ?int $amountTotal,
        public readonly ?string $currency,
        public readonly string $paymentStatus, // "paid", "unpaid", "no_payment_required"
        public readonly string $status, // "complete", "open", "expired"
        public readonly string $mode, // "payment", "setup", "subscription"
        public readonly int $createdTimestamp,
        public readonly ?array $metadata
    ) {
    }
}