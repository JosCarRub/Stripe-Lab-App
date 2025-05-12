<?php

declare(strict_types=1);

namespace App\Commons\DTOs;

/**
 * DTO para los datos del objeto 'invoice' de Stripe.
 * Utilizado para eventos como 'invoice.paid', 'invoice.payment_failed', etc.
 * Representa una factura emitida a un cliente.
 */
final class InvoiceDTO
{
    public function __construct(
        public readonly string $id, // in_...
        public readonly string $objectType, // Siempre "invoice"
        public readonly ?string $customerId, // cus_...
        public readonly ?string $customerEmail, // Email del cliente en la factura
        public readonly ?string $customerName, // Nombre del cliente en la factura
        public readonly ?string $subscriptionId, // sub_... (si la factura es de una suscripción)
        public readonly ?string $paymentIntentId, // pi_... (si se pagó con PaymentIntent)
        public readonly ?string $chargeId, // ch_... (ID del cargo asociado al pago de esta factura)
        public readonly int $amountPaid, // En centavos
        public readonly int $amountDue, // En centavos
        public readonly string $currency,
        public readonly string $status, // ej: "paid", "open", "void", "uncollectible"
        public readonly ?string $hostedInvoiceUrl, // URL de la factura hosteada
        public readonly ?string $invoicePdf, // URL del PDF de la factura
        public readonly int $periodStartTimestamp,
        public readonly int $periodEndTimestamp,
        public readonly int $createdTimestamp,
        public readonly ?array $metadata,
        public readonly array $lines // Array de datos de líneas de factura (data de `lines`)
    ) {
    }
}