<?php

declare(strict_types=1);

namespace App\Mappers;

use App\Commons\DTOs\CheckoutSessionCompletedDTO;
use App\Commons\Loggers\EventLogger;
use App\Commons\Exceptions\InvalidWebhookPayloadException;

class CheckoutSessionMapper
{
    /**
     * Mapea los datos de un objeto 'checkout.session' de Stripe a un CheckoutSessionCompletedDTO.
     *
     * @param object $stripePayloadCheckoutSession El objeto data->object de un evento checkout.session.completed.
     * @return CheckoutSessionCompletedDTO
     * @throws InvalidWebhookPayloadException Si faltan datos esenciales para el mapeo.
     */
    public function mapToDTO(object $stripePayloadCheckoutSession): CheckoutSessionCompletedDTO
    {
        // Validaciones básicas de que los campos esperados existen
        if (!isset(
            $stripePayloadCheckoutSession->id,
            $stripePayloadCheckoutSession->object,
            $stripePayloadCheckoutSession->payment_status,
            $stripePayloadCheckoutSession->status,
            $stripePayloadCheckoutSession->mode,
            $stripePayloadCheckoutSession->created
        )) {
            EventLogger::log("CheckoutSessionMapper: Faltan campos esenciales en el payload.", [
                'payload_keys' => array_keys((array)$stripePayloadCheckoutSession)
            ]);
            throw new InvalidWebhookPayloadException(
                "Payload de checkout.session incompleto, faltan campos esenciales.",
                $stripePayloadCheckoutSession->object ?? 'checkout.session'
            );
        }

        if ($stripePayloadCheckoutSession->object !== 'checkout.session') {
            EventLogger::log("CheckoutSessionMapper: Se esperaba un objeto 'checkout.session'.", [
                'received_object_type' => $stripePayloadCheckoutSession->object
            ]);
            throw new InvalidWebhookPayloadException(
                "Tipo de objeto inesperado para CheckoutSessionMapper, se esperaba 'checkout.session'.",
                $stripePayloadCheckoutSession->object
            );
        }

        // Extracción y asignación de datos
        $id = $stripePayloadCheckoutSession->id;
        $objectType = $stripePayloadCheckoutSession->object;
        $clientReferenceId = $stripePayloadCheckoutSession->client_reference_id ?? null;
        $rawCustomer = $stripePayloadCheckoutSession->customer ?? null;
        $customerId = null;
        if (is_string($rawCustomer)) {
            $customerId = $rawCustomer;
        } elseif (is_object($rawCustomer) && isset($rawCustomer->id)) {
            $customerId = $rawCustomer->id; // Si 'customer' está expandido
        }


        $customerEmail = $stripePayloadCheckoutSession->customer_details->email ?? null;
        $customerName = $stripePayloadCheckoutSession->customer_details->name ?? null;

        $paymentIntentId = $stripePayloadCheckoutSession->payment_intent ?? null;
        $subscriptionId = $stripePayloadCheckoutSession->subscription ?? null;
        $amountSubtotal = $stripePayloadCheckoutSession->amount_subtotal ?? null;
        $amountTotal = $stripePayloadCheckoutSession->amount_total ?? null;
        $currency = $stripePayloadCheckoutSession->currency ?? null;
        $paymentStatus = $stripePayloadCheckoutSession->payment_status;
        $status = $stripePayloadCheckoutSession->status;
        $mode = $stripePayloadCheckoutSession->mode;
        $createdTimestamp = $stripePayloadCheckoutSession->created;
        $metadata = isset($stripePayloadCheckoutSession->metadata) ? (array) $stripePayloadCheckoutSession->metadata : [];

        return new CheckoutSessionCompletedDTO(
            id: $id,
            objectType: $objectType,
            clientReferenceId: $clientReferenceId,
            customerId: $customerId,
            customerEmail: $customerEmail,
            customerName: $customerName,
            paymentIntentId: $paymentIntentId,
            subscriptionId: $subscriptionId,
            amountSubtotal: $amountSubtotal,
            amountTotal: $amountTotal,
            currency: $currency,
            paymentStatus: $paymentStatus,
            status: $status,
            mode: $mode,
            createdTimestamp: $createdTimestamp,
            metadata: $metadata
        );
    }
}