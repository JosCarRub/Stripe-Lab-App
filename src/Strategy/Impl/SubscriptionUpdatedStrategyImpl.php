<?php

declare(strict_types=1);

namespace App\Strategy\Impl;

use App\Commons\DTOs\SubscriptionDTO;
use App\Commons\DTOs\CustomerDTO; // Para obtener/actualizar email del cliente
use App\Commons\Enums\StripeEventTypeEnum;
use App\Commons\Loggers\EventLogger;
use App\Commons\Loggers\ErrorLogger;
use App\Mappers\SubscriptionMapper;
use App\Mappers\CustomerMapper;
use App\Factories\SubscriptionModelFactory;
use App\Repository\SubscriptionRepositoryInterface;
use App\Strategy\StripeWebhookStrategyInterface;
use Stripe\Event as StripeEvent;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException; // Opcional

/**
 * Actualiza un registro existente en StripeSubscriptions.
 */
class SubscriptionUpdatedStrategyImpl implements StripeWebhookStrategyInterface
{
    private ?StripeClient $stripeClient;

    public function __construct(
        private SubscriptionMapper $subscriptionMapper,
        private SubscriptionModelFactory $subscriptionFactory,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private ?CustomerMapper $customerMapper = null, // Opcional, si se inyecta StripeClient
        ?string $stripeApiKey = null                 // Opcional
    ) {
        if ($stripeApiKey && $this->customerMapper) {
            $this->stripeClient = new StripeClient($stripeApiKey);
        } else {
            $this->stripeClient = null;
        }
    }

    public static function getSupportedEventType(): StripeEventTypeEnum
    {
        return StripeEventTypeEnum::CUSTOMER_SUBSCRIPTION_UPDATED;
    }

    public function isApplicable(StripeEvent $event): bool
    {
        return $event->type === self::getSupportedEventType()->value;
    }

    public function process(StripeEvent $event): void
    {
        $payload = $event->data->object;
        $eventId = $event->id;

        EventLogger::log(self::class . ": Iniciando procesamiento.", ['event_id' => $eventId, 'event_type' => $event->type]);

        /** @var SubscriptionDTO $subDTO */
        $subDTO = $this->subscriptionMapper->mapToDTO($payload);

        EventLogger::log(self::class . ": Subscription DTO mapeado para actualización.", [
            'event_id' => $eventId,
            'sub_id' => $subDTO->id,
            'new_status' => $subDTO->status
        ]);

        $existingSubscription = $this->subscriptionRepository->findById($subDTO->id);

        if (!$existingSubscription) {
            ErrorLogger::log(self::class . ": No se encontró suscripción existente para actualizar.", [
                'event_id' => $eventId,
                'sub_id' => $subDTO->id
            ], '[WARNING]');

            return;
        }

        $customerEmail = $existingSubscription->getCustomerEmail();

        if ($this->stripeClient && $this->customerMapper && $subDTO->customerId) {
            // verificar si el email del cliente en Stripe ha cambiado.
            // El payload de subscription.updated no siempre incluye detalles del cliente actualizados.
            // Una llamada a la API customers->retrieve es lo más fiable
            try {

                EventLogger::log(self::class . ": Verificando email del cliente desde API.", ['customer_id' => $subDTO->customerId]);
                $stripeCustomer = $this->stripeClient->customers->retrieve($subDTO->customerId);
                /** @var CustomerDTO $customerDTO */
                $customerDTO = $this->customerMapper->mapToDTO($stripeCustomer);

                if ($customerDTO->email && $customerDTO->email !== $customerEmail) {

                    $customerEmail = $customerDTO->email; // Usar el email más reciente de la API
                    EventLogger::log(self::class . ": Email del cliente obtenido de la API para actualización.", ['customer_id' => $subDTO->customerId, 'new_email' => $customerEmail]);
                }
            } catch (ApiErrorException $e) {

                ErrorLogger::exception($e, ['event_id' => $eventId, 'customer_id' => $subDTO->customerId], '[WARNING]');

            } catch (\App\Commons\Exceptions\InvalidWebhookPayloadException $e) {

                ErrorLogger::exception($e, ['event_id' => $eventId, 'customer_id' => $subDTO->customerId], '[WARNING]');
            }
        }

        $updatedSubscriptionModel = $this->subscriptionFactory->updateFromSubscriptionDTO(
            $existingSubscription,
            $subDTO,
            $customerEmail
        );

        try {
            $this->subscriptionRepository->save($updatedSubscriptionModel);

            EventLogger::log(self::class . ": Suscripción actualizada y guardada.", [
                'event_id' => $eventId,
                'sub_id' => $updatedSubscriptionModel->getSubscriptionId()
            ]);

        } catch (\App\Commons\Exceptions\DatabaseException $e) {

            ErrorLogger::exception($e, ['event_id' => $eventId, 'sub_id' => $subDTO->id]);
            throw $e;
        }

        EventLogger::log(self::class . ": Procesamiento completado.", ['event_id' => $eventId, 'sub_id' => $subDTO->id]);
    }
}