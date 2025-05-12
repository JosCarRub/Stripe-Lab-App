<?php

declare(strict_types=1);

namespace App\Strategy\Impl;

use App\Commons\DTOs\InvoiceDTO;
use App\Commons\Enums\StripeEventTypeEnum;
use App\Commons\Loggers\EventLogger;
use App\Commons\Loggers\ErrorLogger;
use App\Mappers\InvoiceMapper;
use App\Factories\TransactionModelFactory;
use App\Repository\TransactionRepositoryInterface;
use App\Repository\SubscriptionRepositoryInterface; // Para actualizar latest_transaction_id
use App\Strategy\StripeWebhookStrategyInterface;
use Stripe\Event as StripeEvent;

/**
 * Crea un registro en StripeTransactions para la factura pagada y actualiza el latest_transaction_id en la StripeSubscriptions asociada si la hay
 */
class InvoicePaidStrategyImpl implements StripeWebhookStrategyInterface
{
    public function __construct(
        private InvoiceMapper $invoiceMapper,
        private TransactionModelFactory $transactionFactory,
        private TransactionRepositoryInterface $transactionRepository,
        private SubscriptionRepositoryInterface $subscriptionRepository
    ) {
    }

    public static function getSupportedEventType(): StripeEventTypeEnum
    {
        return StripeEventTypeEnum::INVOICE_PAID;
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

        /** @var InvoiceDTO $invoiceDTO */
        $invoiceDTO = $this->invoiceMapper->mapToDTO($payload);

        EventLogger::log(self::class . ": Invoice DTO mapeado.", [
            'event_id' => $eventId,
            'invoice_id' => $invoiceDTO->id,
            'subscription_id' => $invoiceDTO->subscriptionId,
            'amount_paid' => $invoiceDTO->amountPaid
        ]);

        // Verificar si ya existe una transacción para esta factura (idempotencia)
        $existingTransaction = $this->transactionRepository->findByInvoiceId($invoiceDTO->id);
        if ($existingTransaction) {
            EventLogger::log(self::class . ": Transacción ya existe para esta Invoice.", [
                'event_id' => $eventId,
                'invoice_id' => $invoiceDTO->id,
                'existing_transaction_id' => $existingTransaction->getTransactionId()
            ]);

            return; // Ya procesado o manejado.
        }

        $transactionModel = $this->transactionFactory->createFromInvoiceDTO($invoiceDTO);

        try {
            $this->transactionRepository->save($transactionModel);
            EventLogger::log(self::class . ": Transacción creada para Invoice pagada.", [
                'event_id' => $eventId,
                'invoice_id' => $invoiceDTO->id,
                'transaction_id_local' => $transactionModel->getTransactionId()
            ]);

            // Si la factura está asociada a una suscripción, actualizar latest_transaction_id en la suscripción
            if ($invoiceDTO->subscriptionId && $transactionModel->getTransactionId() !== null) {

                $subscription = $this->subscriptionRepository->findById($invoiceDTO->subscriptionId);

                if ($subscription) {

                    $subscription->setLatestTransactionId($transactionModel->getTransactionId());
                    $this->subscriptionRepository->save($subscription);

                    EventLogger::log(self::class . ": latest_transaction_id actualizado en la suscripción.", [
                        'event_id' => $eventId,
                        'subscription_id' => $invoiceDTO->subscriptionId,
                        'latest_transaction_id' => $transactionModel->getTransactionId()
                    ]);
                } else {
                    EventLogger::log(self::class . ": No se encontró suscripción para actualizar latest_transaction_id.", [
                        'event_id' => $eventId,
                        'subscription_id' => $invoiceDTO->subscriptionId
                    ], '[WARNING]');
                }
            }

        } catch (\App\Commons\Exceptions\DatabaseException $e) {

            ErrorLogger::exception($e, ['event_id' => $eventId, 'invoice_id' => $invoiceDTO->id]);
            throw $e; // Dejar que el servicio lo maneje
        }

        EventLogger::log(self::class . ": Procesamiento completado.", ['event_id' => $eventId, 'invoice_id' => $invoiceDTO->id]);
    }
}