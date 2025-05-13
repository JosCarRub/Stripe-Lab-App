<?php

declare(strict_types=1);

namespace App\Mappers;

use App\Commons\DTOs\SubscriptionDTO;
use App\Commons\Loggers\EventLogger;
use App\Commons\Loggers\ErrorLogger;
use App\Commons\Exceptions\InvalidWebhookPayloadException;

/**
 * Mapea los datos de un objeto 'subscription' de Stripe a un SubscriptionDTO.
 * Utilizado para eventos como customer.subscription.created, .updated, o .deleted.
 */
class SubscriptionMapper
{
    /**
     * Mapea los datos de un objeto 'subscription' de Stripe a un SubscriptionDTO.
     *
     * @param object $stripePayloadSubscription El objeto data->object de un evento customer.subscription.*.
     * @return SubscriptionDTO
     * @throws InvalidWebhookPayloadException Si faltan datos esenciales o el tipo de objeto es incorrecto.
     */
    public function mapToDTO(object $stripePayloadSubscription): SubscriptionDTO
    {
        EventLogger::log("SubscriptionMapper DEBUG: Objeto payload recibido.", [
            'class' => get_class($stripePayloadSubscription),
            'json_representation_snippet' => substr(json_encode($stripePayloadSubscription, JSON_PRETTY_PRINT), 0, 1000)
        ]);

        if (!isset($stripePayloadSubscription->object) || $stripePayloadSubscription->object !== 'subscription') {
            ErrorLogger::log("SubscriptionMapper: Se esperaba un objeto 'subscription'.", [
                'received_object_type' => $stripePayloadSubscription->object ?? 'unknown'
            ], '[ERROR]');
            throw new InvalidWebhookPayloadException(
                "Tipo de objeto inesperado para SubscriptionMapper, se esperaba 'subscription'.",
                $stripePayloadSubscription->object ?? 'unknown'
            );
        }

        $id = $stripePayloadSubscription->id ?? null;
        $customerId = $stripePayloadSubscription->customer ?? null;
        $status = $stripePayloadSubscription->status ?? null;
        $createdTimestamp = $stripePayloadSubscription->created ?? null;

        if ($id === null || $customerId === null || $status === null || $createdTimestamp === null) {
            ErrorLogger::log("SubscriptionMapper: Faltan campos esenciales (id, customer, status, created).", [
                'id' => $id, 'customer' => $customerId, 'status' => $status, 'created' => $createdTimestamp,
                'payload_class_debug' => get_class($stripePayloadSubscription)
            ], '[ERROR]');
            throw new InvalidWebhookPayloadException(
                "Payload de subscription incompleto (id, customer, status, created).",
                'subscription'
            );
        }


        $currentPeriodStartTimestamp = $stripePayloadSubscription->current_period_start ?? null;
        $currentPeriodEndTimestamp = $stripePayloadSubscription->current_period_end ?? null;
        $items = $stripePayloadSubscription->items ?? null;

        if ($currentPeriodStartTimestamp === null && isset($stripePayloadSubscription->start_date)) {
            // start_date es un buen fallback para current_period_start si la suscripción está activa
            // y current_period_start no está directamente en el payload raíz.
            $currentPeriodStartTimestamp = $stripePayloadSubscription->start_date;
            EventLogger::log("SubscriptionMapper: Usando 'start_date' como 'current_period_start'.", ['sub_id' => $id], '[INFO]');
        } elseif ($currentPeriodStartTimestamp === null && $items && isset($items->data[0]->current_period_start)) {
            $currentPeriodStartTimestamp = $items->data[0]->current_period_start;
            EventLogger::log("SubscriptionMapper: Usando 'items.data[0].current_period_start'.", ['sub_id' => $id], '[INFO]');
        }

        if ($currentPeriodEndTimestamp === null && $items && isset($items->data[0]->current_period_end)) {
            $currentPeriodEndTimestamp = $items->data[0]->current_period_end;
            EventLogger::log("SubscriptionMapper: Usando 'items.data[0].current_period_end'.", ['sub_id' => $id], '[INFO]');
        }

        $objectType = $stripePayloadSubscription->object;
        $cancelAtPeriodEnd = $stripePayloadSubscription->cancel_at_period_end ?? false;
        $canceledAtTimestamp = $stripePayloadSubscription->canceled_at ?? null;
        $endedAtTimestamp = $stripePayloadSubscription->ended_at ?? null;
        $latestInvoice = $stripePayloadSubscription->latest_invoice ?? null;
        $latestInvoiceId = is_string($latestInvoice) ? $latestInvoice : ($latestInvoice->id ?? null);
        $metadata = isset($stripePayloadSubscription->metadata) ? (array) $stripePayloadSubscription->metadata : [];

        $priceId = null; $priceInterval = null; $priceType = null;

        if ($items && isset($items->data[0]->price)) {
            $priceObject = $items->data[0]->price;
            $priceId = $priceObject->id ?? null;
            $priceType = $priceObject->type ?? null; // 'recurring' o 'one_time'
            $priceInterval = $priceObject->recurring->interval ?? null;
        } elseif (isset($stripePayloadSubscription->plan)) { // Fallback para 'plan' obsoleto
            EventLogger::log("SubscriptionMapper: Usando 'plan' obsoleto.", ['subscription_id' => $id], '[INFO]');
            $planObject = $stripePayloadSubscription->plan;
            $priceId = $planObject->id ?? null;
            $priceInterval = $planObject->interval ?? null;
            $priceType = 'recurring'; // Los planes antiguos siempre eran recurrentes
        }

        if ($priceId === null) {
            ErrorLogger::log("SubscriptionMapper: Falta priceId en el payload de la suscripción.", ['subscription_id' => $id], '[ERROR]');
            throw new InvalidWebhookPayloadException("Price ID es requerido para la suscripción y no se encontró.", 'subscription');
        }

        return new SubscriptionDTO(
            id: $id,
            objectType: $objectType,
            customerId: $customerId,
            status: $status,
            currentPeriodStartTimestamp: $currentPeriodStartTimestamp !== null ? (int)$currentPeriodStartTimestamp : null,
            currentPeriodEndTimestamp: $currentPeriodEndTimestamp !== null ? (int)$currentPeriodEndTimestamp : null,
            cancelAtPeriodEnd: $cancelAtPeriodEnd,
            canceledAtTimestamp: $canceledAtTimestamp !== null ? (int)$canceledAtTimestamp : null,
            endedAtTimestamp: $endedAtTimestamp !== null ? (int)$endedAtTimestamp : null,
            latestInvoiceId: $latestInvoiceId,
            createdTimestamp: (int)$createdTimestamp,
            priceId: $priceId,
            priceInterval: $priceInterval,
            priceType: $priceType,
            metadata: $metadata
        );
    }
}