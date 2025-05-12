<?php
declare(strict_types=1);

namespace App\Factories;

use App\Commons\DTOs\SubscriptionDTO;
use App\Commons\Entities\SubscriptionsModel;
use App\Commons\Enums\SubscriptionStatusEnum;
use App\Commons\Loggers\EventLogger;
use App\Commons\Loggers\ErrorLogger;

class SubscriptionModelFactory
{
    public function createFromSubscriptionDTO(SubscriptionDTO $subDTO, ?string $customerEmail = null): SubscriptionsModel
    {
        EventLogger::log("SubscriptionModelFactory: Creando SubscriptionsModel desde SubscriptionDTO.", ['sub_id' => $subDTO->id]);

        $statusEnum = SubscriptionStatusEnum::tryFromString($subDTO->status);
        if ($statusEnum === null) {
            ErrorLogger::log("SubscriptionModelFactory: Estado de suscripción desconocido en SubscriptionDTO.", [
                'sub_id' => $subDTO->id, 'status_string' => $subDTO->status
            ], '[ERROR]');
            $statusEnum = SubscriptionStatusEnum::INCOMPLETE; // O lanzar una excepción
        }

        $createdAtStripe = SubscriptionsModel::createDateTimeFromStripeTimestamp($subDTO->createdTimestamp);
        if ($createdAtStripe === null) {
            ErrorLogger::log("SubscriptionModelFactory: Timestamp 'created' nulo para SubscriptionDTO.", ['sub_id' => $subDTO->id], '[ERROR]');
            $createdAtStripe = new \DateTimeImmutable(); // Fallback
        }

        $currentPeriodStart = SubscriptionsModel::createDateTimeFromStripeTimestamp($subDTO->currentPeriodStartTimestamp);
        $currentPeriodEnd = SubscriptionsModel::createDateTimeFromStripeTimestamp($subDTO->currentPeriodEndTimestamp);
        $canceledAt = SubscriptionsModel::createDateTimeFromStripeTimestamp($subDTO->canceledAtTimestamp);
        $endedAt = SubscriptionsModel::createDateTimeFromStripeTimestamp($subDTO->endedAtTimestamp);

        if ($subDTO->priceId === null) {
            EventLogger::log("SubscriptionModelFactory: priceId es null en SubscriptionDTO.", ['sub_id' => $subDTO->id], '[WARNING]');
        }

        $model = new SubscriptionsModel(
            subscriptionId: $subDTO->id,
            stripeCustomerId: $subDTO->customerId,
            status: $statusEnum,
            stripePriceId: $subDTO->priceId ?? 'unknown_price_id', // Fallback
            createdAtStripe: $createdAtStripe,
            customerEmail: $customerEmail,
            interval: $subDTO->priceInterval,
            currentPeriodStart: $currentPeriodStart,
            currentPeriodEnd: $currentPeriodEnd,
            cancelAtPeriodEnd: $subDTO->cancelAtPeriodEnd,
            canceledAt: $canceledAt,
            endedAt: $endedAt,
            latestTransactionId: null // Inicialmente null, se establece después de que una factura se pague
        );

        EventLogger::log("SubscriptionModelFactory: SubscriptionsModel creado exitosamente.", ['sub_id' => $model->getSubscriptionId()]);
        return $model;
    }

    public function updateFromSubscriptionDTO(
        SubscriptionsModel $existingSubscription,
        SubscriptionDTO $subDTO,
        ?string $customerEmail = null
    ): SubscriptionsModel
    {
        EventLogger::log("SubscriptionModelFactory: Actualizando SubscriptionsModel desde SubscriptionDTO.", [
            'sub_id' => $existingSubscription->getSubscriptionId(),
            'new_status' => $subDTO->status
        ]);

        $statusEnum = SubscriptionStatusEnum::tryFromString($subDTO->status);
        if ($statusEnum === null) {
            ErrorLogger::log("SubscriptionModelFactory: Estado de suscripción desconocido al actualizar.", [
                'sub_id' => $subDTO->id, 'status_string' => $subDTO->status
            ], '[ERROR]');
            $statusEnum = $existingSubscription->getStatus(); // Mantener estado actual si el nuevo es inválido
        }
        $existingSubscription->setStatus($statusEnum);

        if ($customerEmail !== null) {
            $existingSubscription->setCustomerEmail($customerEmail);
        }

        $currentPeriodStart = SubscriptionsModel::createDateTimeFromStripeTimestamp($subDTO->currentPeriodStartTimestamp);
        if ($currentPeriodStart) $existingSubscription->setCurrentPeriodStart($currentPeriodStart);

        $currentPeriodEnd = SubscriptionsModel::createDateTimeFromStripeTimestamp($subDTO->currentPeriodEndTimestamp);
        if ($currentPeriodEnd) $existingSubscription->setCurrentPeriodEnd($currentPeriodEnd);

        $existingSubscription->setCancelAtPeriodEnd($subDTO->cancelAtPeriodEnd);

        $canceledAt = SubscriptionsModel::createDateTimeFromStripeTimestamp($subDTO->canceledAtTimestamp);
        if ($canceledAt !== null || $subDTO->canceledAtTimestamp !== null) {
            $existingSubscription->setCanceledAt($canceledAt);
        }

        $endedAt = SubscriptionsModel::createDateTimeFromStripeTimestamp($subDTO->endedAtTimestamp);
        if ($endedAt !== null || $subDTO->endedAtTimestamp !== null) {
            $existingSubscription->setEndedAt($endedAt);
        }


        EventLogger::log("SubscriptionModelFactory: SubscriptionsModel actualizado.", ['sub_id' => $existingSubscription->getSubscriptionId()]);
        return $existingSubscription;
    }
}