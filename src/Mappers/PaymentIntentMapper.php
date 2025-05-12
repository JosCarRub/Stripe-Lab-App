<?php

declare(strict_types=1);

namespace App\Mappers;

use App\Commons\DTOs\PaymentIntentDTO;
use App\Commons\Loggers\EventLogger;
use App\Commons\Exceptions\InvalidWebhookPayloadException;

/**
 * Para el evento payment_intent.succeeded.
 */
class PaymentIntentMapper
{
    /**
     * Mapea los datos de un objeto 'payment_intent' de Stripe a un PaymentIntentDTO.
     *
     * @param object $stripePayloadPaymentIntent El objeto data->object de un evento payment_intent.*.
     * @return PaymentIntentDTO
     * @throws InvalidWebhookPayloadException Si faltan datos esenciales.
     */
    public function mapToDTO(object $stripePayloadPaymentIntent): PaymentIntentDTO
    {
        if (!isset(
            $stripePayloadPaymentIntent->id,
            $stripePayloadPaymentIntent->object,
            $stripePayloadPaymentIntent->amount,
            $stripePayloadPaymentIntent->currency,
            $stripePayloadPaymentIntent->status,
            $stripePayloadPaymentIntent->created
        )) {
            EventLogger::log("PaymentIntentMapper: Faltan campos esenciales en el payload.", [
                'payload_keys' => array_keys((array)$stripePayloadPaymentIntent)
            ]);
            throw new InvalidWebhookPayloadException(
                "Payload de payment_intent incompleto, faltan campos esenciales.",
                $stripePayloadPaymentIntent->object ?? 'payment_intent'
            );
        }

        if ($stripePayloadPaymentIntent->object !== 'payment_intent') {
            EventLogger::log("PaymentIntentMapper: Se esperaba un objeto 'payment_intent'.", [
                'received_object_type' => $stripePayloadPaymentIntent->object
            ]);
            throw new InvalidWebhookPayloadException(
                "Tipo de objeto inesperado para PaymentIntentMapper, se esperaba 'payment_intent'.",
                $stripePayloadPaymentIntent->object
            );
        }

        $id = $stripePayloadPaymentIntent->id;
        $objectType = $stripePayloadPaymentIntent->object;
        $amount = $stripePayloadPaymentIntent->amount;
        // amount_received podría no estar presente si el PI no está 'succeeded' aún,
        // pero para 'payment_intent.succeeded' debería estar.
        $amountReceived = $stripePayloadPaymentIntent->amount_received ?? $amount; // Fallback a amount si no está
        $currency = $stripePayloadPaymentIntent->currency;
        $rawCustomer = $stripePayloadPaymentIntent->customer ?? null;
        $customerId = null;
        if (is_string($rawCustomer)) {
            $customerId = $rawCustomer;
        } elseif (is_object($rawCustomer) && isset($rawCustomer->id)) {
            $customerId = $rawCustomer->id;
        }
        $description = $stripePayloadPaymentIntent->description ?? null;
        $invoiceId = $stripePayloadPaymentIntent->invoice ?? null;
        // latest_charge puede ser un ID de cargo o un objeto Charge expandido
        $rawLatestCharge = $stripePayloadPaymentIntent->latest_charge ?? null;
        $latestChargeId = null;
        if (is_string($rawLatestCharge)) {
            $latestChargeId = $rawLatestCharge;
        } elseif (is_object($rawLatestCharge) && isset($rawLatestCharge->id)) {
            $latestChargeId = $rawLatestCharge->id;
        }

        $receiptEmail = $stripePayloadPaymentIntent->receipt_email ?? null;
        $status = $stripePayloadPaymentIntent->status;
        $createdTimestamp = $stripePayloadPaymentIntent->created;
        $metadata = isset($stripePayloadPaymentIntent->metadata) ? (array) $stripePayloadPaymentIntent->metadata : [];


        return new PaymentIntentDTO(
            id: $id,
            objectType: $objectType,
            amount: $amount,
            amountReceived: $amountReceived,
            currency: $currency,
            customerId: $customerId,
            description: $description,
            invoiceId: $invoiceId,
            latestChargeId: $latestChargeId,
            receiptEmail: $receiptEmail,
            status: $status,
            createdTimestamp: $createdTimestamp,
            metadata: $metadata
        );
    }
}