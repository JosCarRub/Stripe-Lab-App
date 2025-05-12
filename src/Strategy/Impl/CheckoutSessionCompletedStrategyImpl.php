<?php
declare(strict_types=1);

namespace App\Strategy\Impl;

use App\Commons\DTOs\CheckoutSessionCompletedDTO;
use App\Commons\Enums\StripeEventTypeEnum;
use App\Commons\Loggers\EventLogger;
use App\Commons\Loggers\ErrorLogger;
use App\Mappers\CheckoutSessionMapper;
use App\Factories\TransactionModelFactory;
use App\Repository\TransactionRepositoryInterface;
use App\Repository\SubscriptionRepositoryInterface;
use App\Strategy\StripeWebhookStrategyInterface;
use Stripe\Event as StripeEvent;
// No necesita WebhookProcessingException aquí, el servicio la maneja.
// No necesita DatabaseException aquí, el servicio la maneja.

/**
 * Estrategia para manejar el evento 'checkout.session.completed' de Stripe.
 * Este evento se dispara cuando un cliente completa una sesión de Stripe Checkout.
 *
 * La estrategia se encarga de:
 *
 * - Mapear el payload a un DTO.
 *
 * - Si es un pago único ('payment' mode) y está pagado, crear una transacción preliminar.
 *
 * - Si es una suscripción ('subscription' mode), actualizar el email del cliente en la
 *   suscripción local si ya existe y el email de la sesión de checkout está disponible.
 */
class CheckoutSessionCompletedStrategyImpl implements StripeWebhookStrategyInterface
{
    public function __construct(
        private CheckoutSessionMapper $checkoutSessionMapper,
        private TransactionModelFactory $transactionFactory,
        private TransactionRepositoryInterface $transactionRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository
    ) {
    }

    public static function getSupportedEventType(): StripeEventTypeEnum
    {
        return StripeEventTypeEnum::CHECKOUT_SESSION_COMPLETED;
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

        /** @var CheckoutSessionCompletedDTO $csDTO */
        $csDTO = $this->checkoutSessionMapper->mapToDTO($payload);

        EventLogger::log(self::class . ": DTO CheckoutSession mapeado.", [
            'event_id' => $eventId, 'cs_id' => $csDTO->id, 'mode' => $csDTO->mode,
            'customer_id_stripe' => $csDTO->customerId, 'customer_email' => $csDTO->customerEmail,
            'payment_intent_id' => $csDTO->paymentIntentId, 'subscription_id' => $csDTO->subscriptionId
        ]);

        if ($csDTO->mode === 'payment' && $csDTO->paymentStatus === 'paid') {
            if ($csDTO->paymentIntentId) {
                $existingTransaction = $this->transactionRepository->findByPaymentIntentId($csDTO->paymentIntentId);
                if (!$existingTransaction) {
                    $transaction = $this->transactionFactory->createFromCheckoutSessionDTO($csDTO);
                    if ($transaction) {
                        $this->transactionRepository->save($transaction);
                        EventLogger::log(self::class . ": Transacción preliminar creada para pago único vía Checkout.", [
                            'event_id' => $eventId, 'cs_id' => $csDTO->id,
                            'local_transaction_id' => $transaction->getTransactionId(),
                            'pi_id' => $csDTO->paymentIntentId
                        ]);
                    }
                } else {
                    EventLogger::log(self::class . ": Transacción ya existente para PI de Checkout (pago único).", ['pi_id' => $csDTO->paymentIntentId, 'local_tx_id' => $existingTransaction->getTransactionId()]);
                }
            } else {
                EventLogger::log(self::class . ": CS modo pago pero sin paymentIntentId.", ['cs_id' => $csDTO->id], '[INFO]');
            }

        } elseif ($csDTO->mode === 'subscription') {
            if ($csDTO->subscriptionId && $csDTO->customerEmail && $csDTO->customerId) {
                $subscription = $this->subscriptionRepository->findById($csDTO->subscriptionId);
                if ($subscription) {
                    $emailChanged = ($subscription->getCustomerEmail() === null && $csDTO->customerEmail !== null) ||
                        ($subscription->getCustomerEmail() !== null && $subscription->getCustomerEmail() !== $csDTO->customerEmail);

                    if ($emailChanged) {
                        $subscription->setCustomerEmail($csDTO->customerEmail);
                        if ($subscription->getStripeCustomerId() !== $csDTO->customerId) {
                            ErrorLogger::log(self::class.": Discrepancia Customer ID en suscripción.", ['sub_id' => $csDTO->subscriptionId], '[WARNING]');
                        }
                        $this->subscriptionRepository->save($subscription);
                        EventLogger::log(self::class . ": Email del cliente actualizado en suscripción desde Checkout.", ['sub_id' => $csDTO->subscriptionId]);
                    }
                } else {
                    EventLogger::log(self::class . ": Suscripción de Checkout aún no en BD (se esperará a customer.subscription.created).", [
                        'sub_id' => $csDTO->subscriptionId, 'email_from_cs' => $csDTO->customerEmail
                    ]);
                }
            }
        }
        EventLogger::log(self::class . ": Procesamiento completado.", ['event_id' => $eventId, 'cs_id' => $csDTO->id]);
    }
}