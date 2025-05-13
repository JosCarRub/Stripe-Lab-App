<?php

declare(strict_types=1);

namespace App\Strategy\Impl;

use App\Commons\DTOs\SubscriptionDTO;
use App\Commons\Entities\SubscriptionsModel;
use App\Commons\Enums\StripeEventTypeEnum;
use App\Commons\Enums\SubscriptionStatusEnum; // Para establecer el estado a CANCELED
use App\Commons\Loggers\EventLogger;
use App\Commons\Loggers\ErrorLogger;
use App\Mappers\SubscriptionMapper;
use App\Factories\SubscriptionModelFactory;
use App\Repository\SubscriptionRepositoryInterface;
use App\Strategy\StripeWebhookStrategyInterface;
use Stripe\Event as StripeEvent;
use DateTimeImmutable;

/**
 * Marca una suscripción como finalizada (usualmente cambiando su estado y registrando canceled_at o ended_at).
 */
class SubscriptionDeletedStrategyImpl implements StripeWebhookStrategyInterface
{
    public function __construct(
        private SubscriptionMapper $subscriptionMapper,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private SubscriptionModelFactory $subscriptionFactory // Para el método updateFromSubscriptionDTO
    ) {
    }

    public static function getSupportedEventType(): StripeEventTypeEnum
    {
        return StripeEventTypeEnum::CUSTOMER_SUBSCRIPTION_DELETED;
    }

    public function isApplicable(StripeEvent $event): bool
    {
        return $event->type === self::getSupportedEventType()->value;
    }

    public function process(StripeEvent $event): void
    {
        $payload = $event->data->object; // Este payload es el objeto Subscription en su estado final (cancelado/terminado)
        $eventId = $event->id;

        EventLogger::log(self::class . ": Iniciando procesamiento.", ['event_id' => $eventId, 'event_type' => $event->type]);

        /** @var SubscriptionDTO $subDTO */
        $subDTO = $this->subscriptionMapper->mapToDTO($payload);

        EventLogger::log(self::class . ": Subscription DTO mapeado para eliminación/cancelación.", [
            'event_id' => $eventId,
            'sub_id' => $subDTO->id,
            'status_from_payload' => $subDTO->status // Debería ser 'canceled' o un estado final
        ]);

        $existingSubscription = $this->subscriptionRepository->findById($subDTO->id);

        if (!$existingSubscription) {
            ErrorLogger::log(self::class . ": No se encontró suscripción existente para marcar como eliminada/cancelada.", [
                'event_id' => $eventId,
                'sub_id' => $subDTO->id
            ], '[WARNING]');

            return;
        }


        $updatedSubscriptionModel = $this->subscriptionFactory->updateFromSubscriptionDTO(
            $existingSubscription,
            $subDTO
        );

        // Asegurarnos de que el estado sea CANCELED si el DTO no lo refleja claramente
        // y que las fechas de cancelación/finalización estén bien.
        if ($updatedSubscriptionModel->getStatus() !== SubscriptionStatusEnum::CANCELED &&
            $updatedSubscriptionModel->getStatus() !== SubscriptionStatusEnum::UNPAID && // UNPAID también es un estado final
            $updatedSubscriptionModel->getStatus() !== SubscriptionStatusEnum::INCOMPLETE_EXPIRED) { // También estado final
            EventLogger::log(self::class . ": Forzando estado a CANCELED para suscripción eliminada.", [
                'sub_id' => $subDTO->id,
                'original_status' => $updatedSubscriptionModel->getStatus()->value
            ], '[INFO]');
            $updatedSubscriptionModel->setStatus(SubscriptionStatusEnum::CANCELED);
        }

        // El DTO (y el payload de Stripe) debería tener `canceled_at` o `ended_at` relleno.
        // El `updateFromSubscriptionDTO` del factory ya se encarga de estos.
        // Si `canceled_at` no está en el payload pero `ended_at` sí, se puede usar `ended_at`.
        // O si `ended_at` está y `canceled_at` no, `ended_at` toma precedencia para marcar el final.
        if ($subDTO->endedAtTimestamp && !$updatedSubscriptionModel->getEndedAt()) {
            $updatedSubscriptionModel->setEndedAt(SubscriptionsModel::createDateTimeFromStripeTimestamp($subDTO->endedAtTimestamp));
        }
        if ($subDTO->canceledAtTimestamp && !$updatedSubscriptionModel->getCanceledAt()) {
            $updatedSubscriptionModel->setCanceledAt(SubscriptionsModel::createDateTimeFromStripeTimestamp($subDTO->canceledAtTimestamp));
        }
        // Si ninguna está, pero el evento es .deleted, al menos poner canceled_at a ahora.

        if (!$updatedSubscriptionModel->getCanceledAt() && !$updatedSubscriptionModel->getEndedAt()){
            $now = new DateTimeImmutable();
            $updatedSubscriptionModel->setCanceledAt($now);
            EventLogger::log(self::class . ": Estableciendo canceled_at a 'ahora' ya que no vino en el payload.", [
                'sub_id' => $subDTO->id
            ], '[INFO]');
        }


        try {

            $this->subscriptionRepository->save($updatedSubscriptionModel);
            EventLogger::log(self::class . ": Suscripción marcada como eliminada/cancelada y guardada.", [
                'event_id' => $eventId,
                'sub_id' => $updatedSubscriptionModel->getSubscriptionId(),
                'final_status' => $updatedSubscriptionModel->getStatus()->value
            ]);
        } catch (\App\Commons\Exceptions\DatabaseException $e) {
            ErrorLogger::exception($e, ['event_id' => $eventId, 'sub_id' => $subDTO->id]);
            throw $e;
        }

        EventLogger::log(self::class . ": Procesamiento completado.", ['event_id' => $eventId, 'sub_id' => $subDTO->id]);
    }
}