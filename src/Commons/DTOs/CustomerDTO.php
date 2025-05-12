<?php

declare(strict_types=1);

namespace App\Commons\DTOs;

/**
 * DTO para los datos del objeto 'customer' de Stripe.
 * Utilizado para eventos como 'customer.created' o 'customer.updated'.
 * Contiene información detallada del cliente.
 */
final class CustomerDTO
{
    public function __construct(
        public readonly string $id, // cus_...
        public readonly string $objectType, // Siempre "customer"
        public readonly ?string $email,
        public readonly ?string $name,
        public readonly ?string $phone,
        public readonly int $createdTimestamp,
        public readonly ?array $metadata,
        public readonly ?array $address // Objeto Address de Stripe
    ) {
    }
}