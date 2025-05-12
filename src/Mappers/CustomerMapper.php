<?php

declare(strict_types=1);

namespace App\Mappers;

use App\Commons\DTOs\CustomerDTO;
use App\Commons\Loggers\EventLogger;
use App\Commons\Exceptions\InvalidWebhookPayloadException;

/**
 * Para los eventos customer.created y customer.updated.
 */
class CustomerMapper
{
    /**
     * Mapea los datos de un objeto 'customer' de Stripe a un CustomerDTO.
     *
     * @param object $stripePayloadCustomer El objeto data->object de un evento customer.*.
     * @return CustomerDTO
     * @throws InvalidWebhookPayloadException Si faltan datos esenciales.
     */
    public function mapToDTO(object $stripePayloadCustomer): CustomerDTO
    {
        if (!isset($stripePayloadCustomer->id, $stripePayloadCustomer->object, $stripePayloadCustomer->created)) {
            EventLogger::log("CustomerMapper: Faltan campos esenciales en el payload.", [
                'payload_keys' => array_keys((array)$stripePayloadCustomer)
            ]);
            throw new InvalidWebhookPayloadException(
                "Payload de customer incompleto, faltan campos esenciales.",
                $stripePayloadCustomer->object ?? 'customer'
            );
        }

        if ($stripePayloadCustomer->object !== 'customer') {
            EventLogger::log("CustomerMapper: Se esperaba un objeto 'customer'.", [
                'received_object_type' => $stripePayloadCustomer->object
            ]);
            throw new InvalidWebhookPayloadException(
                "Tipo de objeto inesperado para CustomerMapper, se esperaba 'customer'.",
                $stripePayloadCustomer->object
            );
        }

        $id = $stripePayloadCustomer->id;
        $objectType = $stripePayloadCustomer->object;
        $email = $stripePayloadCustomer->email ?? null;
        $name = $stripePayloadCustomer->name ?? null;
        $phone = $stripePayloadCustomer->phone ?? null;
        $createdTimestamp = $stripePayloadCustomer->created;
        $metadata = isset($stripePayloadCustomer->metadata) ? (array) $stripePayloadCustomer->metadata : [];
        $address = isset($stripePayloadCustomer->address) ? (array) $stripePayloadCustomer->address : null;

        return new CustomerDTO(
            id: $id,
            objectType: $objectType,
            email: $email,
            name: $name,
            phone: $phone,
            createdTimestamp: $createdTimestamp,
            metadata: $metadata,
            address: $address
        );
    }
}