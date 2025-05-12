<?php

declare(strict_types=1);

namespace App\Strategy\Impl;

use App\Commons\DTOs\CustomerDTO;
use App\Commons\Enums\StripeEventTypeEnum;
use App\Commons\Loggers\EventLogger;
use App\Commons\Loggers\ErrorLogger;
use App\Mappers\CustomerMapper;
use App\Repository\SubscriptionRepositoryInterface;
use App\Strategy\StripeWebhookStrategyInterface;
use Stripe\Event as StripeEvent;

/**
 * Actualiza el customer_email en las suscripciones existentes de ese cliente
 */
class CustomerCreatedOrUpdatedStrategyImpl implements StripeWebhookStrategyInterface
{
    private const SUPPORTED_EVENT_TYPES = [
        StripeEventTypeEnum::CUSTOMER_CREATED,
        StripeEventTypeEnum::CUSTOMER_UPDATED,
    ];

    public function __construct(private CustomerMapper $customerMapper, private SubscriptionRepositoryInterface $subscriptionRepository) {
    }

    /**
     * Esta estrategia no tiene un único evento soportado, por lo que este método
     * no se usa directamente para la lógica de isApplicable, pero lo implementamos.
     * Podríamos devolver el más común o el primero de la lista.
     */
    public static function getSupportedEventType(): StripeEventTypeEnum
    {
        return self::SUPPORTED_EVENT_TYPES[0]; // Devuelve CUSTOMER_CREATED por defecto
    }

    public function isApplicable(StripeEvent $event): bool
    {
        foreach (self::SUPPORTED_EVENT_TYPES as $supportedType) {
            if ($event->type === $supportedType->value) {
                return true;
            }
        }
        return false;
    }

    public function process(StripeEvent $event): void
    {
        $payload = $event->data->object;
        $eventId = $event->id;

        EventLogger::log(self::class . ": Iniciando procesamiento.", ['event_id' => $eventId, 'event_type' => $event->type]);

        /** @var CustomerDTO $customerDTO */
        $customerDTO = $this->customerMapper->mapToDTO($payload);

        EventLogger::log(self::class . ": Customer DTO mapeado.", [
            'event_id' => $eventId,
            'customer_id' => $customerDTO->id,
            'email' => $customerDTO->email,
            'name' => $customerDTO->name
        ]);

        // Actualizar email en entidades existentes si es necesario.
        if ($customerDTO->id && ($customerDTO->email || $customerDTO->name)) {
            try {
                $subscriptions = $this->subscriptionRepository->findByStripeCustomerId($customerDTO->id);
                foreach ($subscriptions as $subscription) {
                    $updated = false;
                    if ($customerDTO->email && $subscription->getCustomerEmail() !== $customerDTO->email) {
                        $subscription->setCustomerEmail($customerDTO->email);
                        $updated = true;
                    }

                    if ($updated) {

                        $this->subscriptionRepository->save($subscription);

                        EventLogger::log(self::class . ": Datos del cliente actualizados en suscripción.", [
                            'event_id' => $eventId,
                            'subscription_id' => $subscription->getSubscriptionId(),
                            'customer_id' => $customerDTO->id
                        ]);
                    }
                }
            } catch (\App\Commons\Exceptions\DatabaseException $e) {
                ErrorLogger::exception($e, [
                    'event_id' => $eventId,
                    'customer_id' => $customerDTO->id,
                    'operation' => 'update_customer_info_in_subscriptions'
                ], '[WARNING]');
            }
        }

        EventLogger::log(self::class . ": Procesamiento completado.", ['event_id' => $eventId, 'customer_id' => $customerDTO->id]);
    }
}