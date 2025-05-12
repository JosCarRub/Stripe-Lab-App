<?php

declare(strict_types=1);

namespace App\Mappers;

use App\Commons\DTOs\ChargeDTO;
use App\Commons\Loggers\EventLogger;
use App\Commons\Exceptions\InvalidWebhookPayloadException;

/**
 * Para el evento charge.succeeded.
 */
class ChargeMapper
{
    /**
     * Mapea los datos de un objeto 'charge' de Stripe a un ChargeDTO.
     *
     * @param object $stripePayloadCharge El objeto data->object de un evento charge.*.
     * @return ChargeDTO
     * @throws InvalidWebhookPayloadException Si faltan datos esenciales.
     */
    public function mapToDTO(object $stripePayloadCharge): ChargeDTO
    {
        if (!isset(
            $stripePayloadCharge->id,
            $stripePayloadCharge->object,
            $stripePayloadCharge->amount,
            $stripePayloadCharge->currency,
            $stripePayloadCharge->status,
            $stripePayloadCharge->created
        )) {
            EventLogger::log("ChargeMapper: Faltan campos esenciales en el payload.", [
                'payload_keys' => array_keys((array)$stripePayloadCharge)
            ]);
            throw new InvalidWebhookPayloadException(
                "Payload de charge incompleto, faltan campos esenciales.",
                $stripePayloadCharge->object ?? 'charge'
            );
        }

        if ($stripePayloadCharge->object !== 'charge') {
            EventLogger::log("ChargeMapper: Se esperaba un objeto 'charge'.", [
                'received_object_type' => $stripePayloadCharge->object
            ]);
            throw new InvalidWebhookPayloadException(
                "Tipo de objeto inesperado para ChargeMapper, se esperaba 'charge'.",
                $stripePayloadCharge->object
            );
        }

        $id = $stripePayloadCharge->id;
        $objectType = $stripePayloadCharge->object;
        $amount = $stripePayloadCharge->amount;
        $amountCaptured = $stripePayloadCharge->amount_captured ?? $amount; // Si no está captured, puede ser amount
        $currency = $stripePayloadCharge->currency;

        $rawCustomer = $stripePayloadCharge->customer ?? null;
        $customerId = null;
        if (is_string($rawCustomer)) {
            $customerId = $rawCustomer;
        } elseif (is_object($rawCustomer) && isset($rawCustomer->id)) {
            $customerId = $rawCustomer->id;
        }

        $description = $stripePayloadCharge->description ?? null;
        $invoiceId = $stripePayloadCharge->invoice ?? null; // Puede ser ID o objeto Invoice
        if (is_object($invoiceId) && isset($invoiceId->id)) {
            $invoiceId = $invoiceId->id;
        }


        $paymentIntentId = $stripePayloadCharge->payment_intent ?? null; // Puede ser ID o objeto PaymentIntent
        if (is_object($paymentIntentId) && isset($paymentIntentId->id)) {
            $paymentIntentId = $paymentIntentId->id;
        }

        $receiptUrl = $stripePayloadCharge->receipt_url ?? null;
        $status = $stripePayloadCharge->status;
        $createdTimestamp = $stripePayloadCharge->created;

        $billingDetailsName = $stripePayloadCharge->billing_details->name ?? null;
        $billingDetailsEmail = $stripePayloadCharge->billing_details->email ?? null;

        $metadata = isset($stripePayloadCharge->metadata) ? (array) $stripePayloadCharge->metadata : [];

        return new ChargeDTO(
            id: $id,
            objectType: $objectType,
            amount: $amount,
            amountCaptured: $amountCaptured,
            currency: $currency,
            customerId: $customerId,
            description: $description,
            invoiceId: $invoiceId,
            paymentIntentId: $paymentIntentId,
            receiptUrl: $receiptUrl,
            status: $status,
            createdTimestamp: $createdTimestamp,
            billingDetailsName: $billingDetailsName,
            billingDetailsEmail: $billingDetailsEmail,
            metadata: $metadata
        );
    }
}