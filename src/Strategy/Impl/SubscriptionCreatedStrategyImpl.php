<?php

declare(strict_types=1);

namespace App\Strategy\Impl;

use App\Commons\DTOs\SubscriptionDTO;
use App\Commons\DTOs\CustomerDTO; // Para obtener el email del cliente
use App\Commons\Enums\StripeEventTypeEnum;
use App\Commons\Loggers\EventLogger;
use App\Commons\Loggers\ErrorLogger;
use App\Mappers\SubscriptionMapper;
use App\Mappers\CustomerMapper;
use App\Factories\SubscriptionModelFactory;
use App\Repository\SubscriptionRepositoryInterface;
use App\Strategy\StripeWebhookStrategyInterface;
use Stripe\Event as StripeEvent;
use Stripe\StripeClient; // Para obtener el objeto Customer
use Stripe\Exception\ApiErrorException;

/**
 * Crea un nuevo registro en StripeSubscriptions.
 * NOTA: El objeto Subscription de Stripe no contiene el email del cliente.
 * Para rellenarlo en SubscriptionsModel, esta estrategia incluye lógica opcional para llamar a Stripe
 * obtener el objeto Customer, mapearlo a CustomerDTO y extraer el email.
 *
 */
class SubscriptionCreatedStrategyImpl implements StripeWebhookStrategyInterface
{
    private ?StripeClient $stripeClient;

    public function __construct(
        private SubscriptionMapper $subscriptionMapper,
        private SubscriptionModelFactory $subscriptionFactory,
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private ?CustomerMapper $customerMapper = null, // Opcional, si se inyecta StripeClient
        ?string $stripeApiKey = null
    ) {
        if ($stripeApiKey && $this->customerMapper) {
            $this->stripeClient = new StripeClient($stripeApiKey);
        } else {
            $this->stripeClient = null;
        }
    }

    public static function getSupportedEventType(): StripeEventTypeEnum
    {
        return StripeEventTypeEnum::CUSTOMER_SUBSCRIPTION_CREATED;
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

        EventLogger::log(self::class . ": Subscription DTO mapeado.", [
            'event_id' => $eventId,
            'sub_id' => $subDTO->id,
            'customer_id' => $subDTO->customerId,
            'status' => $subDTO->status
        ]);

        // Verificar si la suscripción ya existe
        $existingSubscription = $this->subscriptionRepository->findById($subDTO->id);
        if ($existingSubscription) {
            EventLogger::log(self::class . ": Suscripción ya existe.", [
                'event_id' => $eventId,
                'sub_id' => $subDTO->id
            ]);
            //  si el estado o algo más es diferente `customer.subscription.updated`
            // se encargaría de actualizarlo
            return;
        }

        // Intentar obtener el email del cliente desde la API de Stripe
        $customerEmail = null;
        if ($this->stripeClient && $this->customerMapper && $subDTO->customerId) {
            try {

                EventLogger::log(self::class . ": Intentando obtener Customer para email.", ['customer_id' => $subDTO->customerId]);
                $stripeCustomer = $this->stripeClient->customers->retrieve($subDTO->customerId);

                /** @var CustomerDTO $customerDTO */
                $customerDTO = $this->customerMapper->mapToDTO($stripeCustomer);
                $customerEmail = $customerDTO->email;

            } catch (ApiErrorException $e) {

                ErrorLogger::exception($e, ['event_id' => $eventId, 'customer_id' => $subDTO->customerId], '[WARNING]');

            } catch (\App\Commons\Exceptions\InvalidWebhookPayloadException $e) {

                ErrorLogger::exception($e, ['event_id' => $eventId, 'customer_id' => $subDTO->customerId], '[WARNING]');
            }
        }
        if ($customerEmail === null) {

            EventLogger::log(self::class . ": No se pudo obtener el email del cliente para la nueva suscripción.", ['sub_id' => $subDTO->id, 'customer_id' => $subDTO->customerId], '[INFO]');
        }


        $subscriptionModel = $this->subscriptionFactory->createFromSubscriptionDTO($subDTO, $customerEmail);

        try {
            $this->subscriptionRepository->save($subscriptionModel);

            EventLogger::log(self::class . ": Nueva suscripción creada y guardada.", [
                'event_id' => $eventId,
                'sub_id' => $subscriptionModel->getSubscriptionId()
            ]);

        } catch (\App\Commons\Exceptions\DatabaseException $e) {

            ErrorLogger::exception($e, ['event_id' => $eventId, 'sub_id' => $subDTO->id]);
            throw $e; // Dejar que el servicio lo maneje
        }

        EventLogger::log(self::class . ": Procesamiento completado.", ['event_id' => $eventId, 'sub_id' => $subDTO->id]);
    }
}