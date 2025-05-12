<?php

declare(strict_types=1);

namespace App\Mappers;

use App\Commons\DTOs\InvoiceDTO;
use App\Commons\Loggers\EventLogger;
use App\Commons\Loggers\ErrorLogger;
use App\Commons\Exceptions\InvalidWebhookPayloadException;

/**
 * Mapea los datos de un objeto 'invoice' de Stripe a un InvoiceDTO.
 * Principalmente utilizado para el evento 'invoice.paid'.
 */
class InvoiceMapper
{
    /**
     * Mapea los datos de un objeto 'invoice' de Stripe a un InvoiceDTO.
     *
     * @param object $stripePayloadInvoice El objeto data->object de un evento invoice.*.
     * @return InvoiceDTO
     * @throws InvalidWebhookPayloadException Si faltan datos esenciales o el tipo de objeto es incorrecto.
     */
    public function mapToDTO(object $stripePayloadInvoice): InvoiceDTO
    {
        EventLogger::log("InvoiceMapper DEBUG: Objeto payload recibido.", [
            'class' => get_class($stripePayloadInvoice),
            'json_representation_snippet' => substr(json_encode($stripePayloadInvoice, JSON_PRETTY_PRINT), 0, 500)
        ]);

        // Comprobación de tipo de objeto
        if (!isset($stripePayloadInvoice->object) || $stripePayloadInvoice->object !== 'invoice') {
            ErrorLogger::log("InvoiceMapper: Se esperaba un objeto 'invoice'.", [
                'received_object_type' => $stripePayloadInvoice->object ?? 'unknown'
            ], '[ERROR]');
            throw new InvalidWebhookPayloadException(
                "Tipo de objeto inesperado para InvoiceMapper, se esperaba 'invoice'.",
                $stripePayloadInvoice->object ?? 'unknown'
            );
        }

        // Validar campos esenciales
        $id = $stripePayloadInvoice->id ?? null;
        $amountPaid = $stripePayloadInvoice->amount_paid ?? null; // Para invoice.paid, amount_paid es crucial
        $currency = $stripePayloadInvoice->currency ?? null;
        $status = $stripePayloadInvoice->status ?? null;
        $createdTimestamp = $stripePayloadInvoice->created ?? null;
        $periodStartTimestamp = $stripePayloadInvoice->period_start ?? null;
        $periodEndTimestamp = $stripePayloadInvoice->period_end ?? null;

        if (
            $id === null || $amountPaid === null || $currency === null || $status === null ||
            $createdTimestamp === null || $periodStartTimestamp === null || $periodEndTimestamp === null
        ) {
            ErrorLogger::log("InvoiceMapper: Faltan campos esenciales en el payload de invoice.", [
                'id' => $id, 'amount_paid' => $amountPaid, 'currency' => $currency, 'status' => $status,
                'created' => $createdTimestamp, 'period_start' => $periodStartTimestamp, 'period_end' => $periodEndTimestamp,
                'payload_class_debug' => get_class($stripePayloadInvoice)
            ], '[ERROR]');
            throw new InvalidWebhookPayloadException(
                "Payload de invoice incompleto, faltan campos esenciales.",
                'invoice'
            );
        }

        $objectType = $stripePayloadInvoice->object; // Ya sabemos que es 'invoice'

        $rawCustomer = $stripePayloadInvoice->customer ?? null;
        $customerId = is_string($rawCustomer) ? $rawCustomer : ($rawCustomer->id ?? null);

        $customerEmail = $stripePayloadInvoice->customer_email ?? null;
        $customerName = $stripePayloadInvoice->customer_name ?? null;

        // Lógica mejorada para obtener subscriptionId
        $subscriptionId = null;
        if (isset($stripePayloadInvoice->subscription)) {
            if (is_string($stripePayloadInvoice->subscription)) {
                $subscriptionId = $stripePayloadInvoice->subscription;
            } elseif (is_object($stripePayloadInvoice->subscription) && isset($stripePayloadInvoice->subscription->id)) {
                $subscriptionId = $stripePayloadInvoice->subscription->id;
            }
        }
        // Fallbacks si no está en el nivel raíz
        if ($subscriptionId === null) {
            if (isset($stripePayloadInvoice->lines->data[0]->subscription) && is_string($stripePayloadInvoice->lines->data[0]->subscription)) {
                $subscriptionId = $stripePayloadInvoice->lines->data[0]->subscription;
                EventLogger::log("InvoiceMapper: subscription_id obtenido de lines.data[0].subscription", ['invoice_id' => $id], '[DEBUG]');
            } elseif (isset($stripePayloadInvoice->lines->data[0]->parent->subscription_item_details->subscription) && is_string($stripePayloadInvoice->lines->data[0]->parent->subscription_item_details->subscription)) {
                $subscriptionId = $stripePayloadInvoice->lines->data[0]->parent->subscription_item_details->subscription;
                EventLogger::log("InvoiceMapper: subscription_id obtenido de lines.data[0].parent.subscription_item_details.subscription", ['invoice_id' => $id], '[DEBUG]');
            } elseif (isset($stripePayloadInvoice->parent->subscription_details->subscription) && is_string($stripePayloadInvoice->parent->subscription_details->subscription)) {
                $subscriptionId = $stripePayloadInvoice->parent->subscription_details->subscription;
                EventLogger::log("InvoiceMapper: subscription_id obtenido de parent.subscription_details.subscription", ['invoice_id' => $id], '[DEBUG]');
            }
        }


        if ($subscriptionId === null && isset($stripePayloadInvoice->billing_reason) &&
            ($stripePayloadInvoice->billing_reason === 'subscription_create' || $stripePayloadInvoice->billing_reason === 'subscription_cycle')) {
            EventLogger::log("InvoiceMapper: No se pudo determinar subscription_id para una factura de suscripción.", [
                'invoice_id' => $id,
                'billing_reason' => $stripePayloadInvoice->billing_reason
            ], '[WARNING]');
        }

        $rawPaymentIntent = $stripePayloadInvoice->payment_intent ?? null;
        $paymentIntentId = is_string($rawPaymentIntent) ? $rawPaymentIntent : ($rawPaymentIntent->id ?? null);

        $rawCharge = $stripePayloadInvoice->charge ?? null;
        $chargeId = is_string($rawCharge) ? $rawCharge : ($rawCharge->id ?? null);

        $amountDue = $stripePayloadInvoice->amount_due ?? 0;
        $hostedInvoiceUrl = $stripePayloadInvoice->hosted_invoice_url ?? null;
        $invoicePdf = $stripePayloadInvoice->invoice_pdf ?? null;
        $metadata = isset($stripePayloadInvoice->metadata) ? (array) $stripePayloadInvoice->metadata : [];
        $lines = isset($stripePayloadInvoice->lines->data) ? $stripePayloadInvoice->lines->data : [];

        return new InvoiceDTO(
            id: $id,
            objectType: $objectType,
            customerId: $customerId,
            customerEmail: $customerEmail,
            customerName: $customerName,
            subscriptionId: $subscriptionId,
            paymentIntentId: $paymentIntentId,
            chargeId: $chargeId,
            amountPaid: (int)$amountPaid, // Asegurar int
            amountDue: (int)$amountDue,   // Asegurar int
            currency: $currency,
            status: $status,
            hostedInvoiceUrl: $hostedInvoiceUrl,
            invoicePdf: $invoicePdf,
            periodStartTimestamp: (int)$periodStartTimestamp,
            periodEndTimestamp: (int)$periodEndTimestamp,
            createdTimestamp: (int)$createdTimestamp,
            metadata: $metadata,
            lines: $lines
        );
    }
}