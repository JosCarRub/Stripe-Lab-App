<?php

declare(strict_types=1);

namespace App\Strategy\Impl;

use App\Commons\DTOs\ChargeDTO;
use App\Commons\Enums\StripeEventTypeEnum;
use App\Commons\Loggers\EventLogger;
use App\Commons\Loggers\ErrorLogger;
use App\Mappers\ChargeMapper;
use App\Repository\TransactionRepositoryInterface;
use App\Strategy\StripeWebhookStrategyInterface;
use Stripe\Event as StripeEvent;

/**
 * Propósito Principal: Enriquecer una TransactionsModel existente (creada a partir de un payment_intent.succeeded) con el receipt_url y otros detalles del Charge.
 * Búsqueda de Transacción: Intenta encontrar la transacción usando el payment_intent_id del Charge.
 */
class ChargeSucceededStrategyImpl implements StripeWebhookStrategyInterface
{
    public function __construct(
        private ChargeMapper $chargeMapper,
        private TransactionRepositoryInterface $transactionRepository
    ) {
    }

    public static function getSupportedEventType(): StripeEventTypeEnum
    {
        return StripeEventTypeEnum::CHARGE_SUCCEEDED;
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

        /** @var ChargeDTO $chargeDTO */
        $chargeDTO = $this->chargeMapper->mapToDTO($payload);

        EventLogger::log(self::class . ": Charge DTO mapeado.", [
            'event_id' => $eventId,
            'charge_id' => $chargeDTO->id,
            'pi_id' => $chargeDTO->paymentIntentId,
            'receipt_url' => $chargeDTO->receiptUrl
        ]);

        // Intentar encontrar la transacción asociada por Payment Intent ID o Charge ID
        // y actualizarla con el receipt_url(PARA LA FACTURA!) y cualquier otro detalle del Charge.
        $transaction = null;
        if ($chargeDTO->paymentIntentId) {
            $transaction = $this->transactionRepository->findByPaymentIntentId($chargeDTO->paymentIntentId);
        }


        if ($transaction) {
            $updated = false;
            if ($chargeDTO->receiptUrl && $transaction->getDocumentUrl() !== $chargeDTO->receiptUrl) {
                $transaction->setDocumentUrl($chargeDTO->receiptUrl);
                $updated = true;
            }
            // Actualizar charge_id si no estaba o es diferente (asegurar consistencia)
            if ($transaction->getStripeChargeId() !== $chargeDTO->id) {
                $transaction->setStripeChargeId($chargeDTO->id);
                $updated = true;
            }
            // Actualizar nombre/email del cliente si vienen en billing_details del charge
            // y son diferentes o no estaban en la transacción.
            if ($chargeDTO->billingDetailsName && $transaction->getCustomerName() !== $chargeDTO->billingDetailsName) {
                $transaction->setCustomerName($chargeDTO->billingDetailsName);
                $updated = true;
            }
            if ($chargeDTO->billingDetailsEmail && $transaction->getCustomerEmail() !== $chargeDTO->billingDetailsEmail) {
                $transaction->setCustomerEmail($chargeDTO->billingDetailsEmail);
                $updated = true;
            }


            if ($updated) {
                try {
                    $this->transactionRepository->save($transaction);
                    EventLogger::log(self::class . ": Transacción actualizada con detalles del Charge.", [
                        'event_id' => $eventId,
                        'transaction_id_local' => $transaction->getTransactionId(),
                        'charge_id' => $chargeDTO->id
                    ]);
                } catch (\App\Commons\Exceptions\DatabaseException $e) {
                    ErrorLogger::exception($e, [
                        'event_id' => $eventId,
                        'transaction_id_local' => $transaction->getTransactionId(),
                        'charge_id' => $chargeDTO->id
                    ]);
                    // Propagar para que el servicio lo maneje
                    throw $e;
                }
            } else {
                EventLogger::log(self::class . ": No se requirieron actualizaciones en la transacción desde el Charge.", [
                    'event_id' => $eventId,
                    'transaction_id_local' => $transaction->getTransactionId()
                ]);
            }
        } else {
            EventLogger::log(self::class . ": No se encontró transacción asociada al Payment Intent del Charge.", [
                'event_id' => $eventId,
                'pi_id' => $chargeDTO->paymentIntentId,
                'charge_id' => $chargeDTO->id
            ], '[INFO]');

        }

        EventLogger::log(self::class . ": Procesamiento completado.", ['event_id' => $eventId, 'charge_id' => $chargeDTO->id]);
    }
}