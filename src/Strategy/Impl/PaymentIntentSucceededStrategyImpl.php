<?php
declare(strict_types=1);

namespace App\Strategy\Impl;

use App\Commons\DTOs\PaymentIntentDTO;
use App\Commons\DTOs\ChargeDTO;
use App\Commons\Enums\StripeEventTypeEnum;
use App\Commons\Enums\TransactionTypeEnum;
use App\Commons\Loggers\EventLogger;
use App\Commons\Loggers\ErrorLogger;
use App\Mappers\PaymentIntentMapper;
use App\Mappers\ChargeMapper;
use App\Factories\TransactionModelFactory;
use App\Repository\TransactionRepositoryInterface;
use App\Strategy\StripeWebhookStrategyInterface;
use Stripe\Event as StripeEvent;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

/**
 * Estrategia para manejar el evento 'payment_intent.succeeded' de Stripe.
 * - Si el PaymentIntent parece estar pagando una factura de suscripción (basado en descripción o metadatos),
 *   esta estrategia NO crea una transacción, esperando que InvoicePaidStrategy lo haga.
 *   Puede intentar enriquecer una transacción de factura existente si ya fue creada.
 * - Si el PaymentIntent parece ser un pago único directo, crea una transacción 'one_time_receipt'.
 */
class PaymentIntentSucceededStrategyImpl implements StripeWebhookStrategyInterface
{
    private ?StripeClient $stripeClient;

    public function __construct(
        private PaymentIntentMapper $paymentIntentMapper,
        private TransactionModelFactory $transactionFactory,
        private TransactionRepositoryInterface $transactionRepository,
        private ?ChargeMapper $chargeMapper = null,
        ?string $stripeApiKey = null
    ) {
        if ($stripeApiKey && $this->chargeMapper) {
            try { $this->stripeClient = new StripeClient($stripeApiKey); }
            catch (\Throwable $e) { ErrorLogger::exception($e, [], '[WARNING]'); $this->stripeClient = null; }
        } else { $this->stripeClient = null; }
    }

    public static function getSupportedEventType(): StripeEventTypeEnum {
        return StripeEventTypeEnum::PAYMENT_INTENT_SUCCEEDED;
    }

    public function isApplicable(StripeEvent $event): bool {
        return $event->type === self::getSupportedEventType()->value;
    }

    public function process(StripeEvent $event): void {
        $payload = $event->data->object;
        $eventId = $event->id;
        EventLogger::log(self::class . ": Iniciando procesamiento.", ['event_id' => $eventId, 'event_type' => $event->type]);

        /** @var PaymentIntentDTO $piDTO */
        $piDTO = $this->paymentIntentMapper->mapToDTO($payload);
        EventLogger::log(self::class . ": PaymentIntent DTO mapeado.", [
            'pi_id' => $piDTO->id,
            'description' => $piDTO->description,
            'invoice_id_in_pi_dto' => $piDTO->invoiceId // Aunque sabemos que puede ser null
        ]);

        // 1. Buscar si ya existe una transacción para este PaymentIntent ID
        $existingTransaction = $this->transactionRepository->findByPaymentIntentId($piDTO->id);

        if ($existingTransaction) {
            EventLogger::log(self::class . ": Transacción ya existe para este PaymentIntent.", [
                'pi_id' => $piDTO->id, 'local_tx_id' => $existingTransaction->getTransactionId(),
                'type' => $existingTransaction->getTransactionTypeEnum()->value
            ]);
            // Si ya existe, solo intentamos enriquecerla con detalles del charge si es necesario.
            if (!$existingTransaction->getDocumentUrl() && $piDTO->latestChargeId && $this->chargeMapper && $this->stripeClient) {
                $this->tryEnrichTransactionWithChargeDetails($existingTransaction, $piDTO->latestChargeId, $eventId);
            }
            EventLogger::log(self::class . ": Procesamiento de PI existente completado.", ['pi_id' => $piDTO->id]);
            return;
        }

        // 2. Si NO hay transacción existente para este PI:
        //    Determinar si este PI es para una factura de suscripción o un pago único.
        //     si la descripción contiene "Subscription" o "Invoice", o si tiene un invoiceId (aunque sea null)
        //    O si tiene metadatos específicos que indiquen que es para una suscripción.
        $isLikelySubscriptionPayment = false;
        if ($piDTO->invoiceId) {
            $isLikelySubscriptionPayment = true;
            EventLogger::log(self::class . ": PI DTO tiene invoiceId, marcando como probable pago de suscripción.", ['pi_id' => $piDTO->id, 'invoice_id' => $piDTO->invoiceId]);
        } elseif (isset($piDTO->description) && (
                stripos($piDTO->description, 'subscription') !== false ||
                stripos($piDTO->description, 'invoice') !== false
            )) {
            $isLikelySubscriptionPayment = true;
            EventLogger::log(self::class . ": Descripción del PI sugiere pago de suscripción/factura.", ['pi_id' => $piDTO->id, 'description' => $piDTO->description]);
        }



        if ($isLikelySubscriptionPayment) {
            EventLogger::log(self::class . ": PaymentIntent parece ser para una factura/suscripción (ID: " . ($piDTO->invoiceId ?? 'N/A') . "). No se creará una transacción 'one_time_receipt' separada. Se espera que InvoicePaidStrategy maneje la creación de la transacción para la factura.", [
                'pi_id' => $piDTO->id, 'event_id' => $eventId
            ]);

            if ($piDTO->invoiceId) {
                $invoiceTransaction = $this->transactionRepository->findByInvoiceId($piDTO->invoiceId);
                if ($invoiceTransaction && $piDTO->latestChargeId && $this->chargeMapper && $this->stripeClient) {
                    $this->tryEnrichTransactionWithChargeDetails($invoiceTransaction, $piDTO->latestChargeId, $eventId);
                }
            }
            return;
        }

        // Si llegamos aquí, se considera un pago único directo.
        EventLogger::log(self::class . ": Procesando como pago único directo.", ['pi_id' => $piDTO->id]);
        $chargeDTO = null;
        if ($this->stripeClient && $this->chargeMapper && $piDTO->latestChargeId) {
            try {
                $stripeCharge = $this->stripeClient->charges->retrieve($piDTO->latestChargeId);
                $chargeDTO = $this->chargeMapper->mapToDTO($stripeCharge);
            } catch (\Exception $e) {
                ErrorLogger::exception($e, ['context' => 'get_charge_for_one_time_pi', 'charge_id' => $piDTO->latestChargeId], '[WARNING]');
            }
        }

        $transaction = $this->transactionFactory->createFromPaymentIntentDTO($piDTO, $chargeDTO);
        $this->transactionRepository->save($transaction);
        EventLogger::log(self::class . ": Transacción de pago único directo creada.", [
            'event_id' => $eventId, 'pi_id' => $piDTO->id,
            'local_transaction_id' => $transaction->getTransactionId()
        ]);

        EventLogger::log(self::class . ": Procesamiento completado.", ['event_id' => $eventId]);
    }

    private function tryEnrichTransactionWithChargeDetails(
        \App\Commons\Entities\TransactionsModel $transaction,
        string $chargeId,
        string $eventId
    ): void {
        EventLogger::log(self::class . ": Intentando enriquecer transacción ID " . $transaction->getTransactionId() . " con detalles del Charge ID: " . $chargeId, [
            'event_id' => $eventId
        ]);
        try {

            if (!$this->stripeClient || !$this->chargeMapper) {
                EventLogger::log(self::class . ": StripeClient o ChargeMapper no disponibles para enriquecer transacción.", [], '[INFO]');
                return;
            }

            $stripeCharge = $this->stripeClient->charges->retrieve($chargeId);
            /** @var ChargeDTO $chargeDTO */
            $chargeDTO = $this->chargeMapper->mapToDTO($stripeCharge);

            $updated = false;
            if ($chargeDTO->receiptUrl && $transaction->getDocumentUrl() !== $chargeDTO->receiptUrl) {
                $transaction->setDocumentUrl($chargeDTO->receiptUrl);
                $updated = true;
            }
            if ($transaction->getStripeChargeId() !== $chargeDTO->id) {
                $transaction->setStripeChargeId($chargeDTO->id);
                $updated = true;
            }
            if ($chargeDTO->billingDetailsName && ($transaction->getCustomerName() === null || $transaction->getCustomerName() !== $chargeDTO->billingDetailsName)) {
                $transaction->setCustomerName($chargeDTO->billingDetailsName); $updated = true;
            }
            if ($chargeDTO->billingDetailsEmail && ($transaction->getCustomerEmail() === null || $transaction->getCustomerEmail() !== $chargeDTO->billingDetailsEmail)) {
                $transaction->setCustomerEmail($chargeDTO->billingDetailsEmail); $updated = true;
            }

            if ($updated) {
                $this->transactionRepository->save($transaction);
                EventLogger::log(self::class . ": Transacción existente ID " . $transaction->getTransactionId() . " actualizada con datos del Charge.", ['event_id' => $eventId]);
            } else {
                EventLogger::log(self::class . ": No se requirieron actualizaciones por Charge en transacción ID " . $transaction->getTransactionId(), ['event_id' => $eventId]);
            }
        } catch (\Exception $e) {
            ErrorLogger::exception($e, ['context' => 'enrich_transaction_with_charge', 'charge_id' => $chargeId, 'local_tx_id' => $transaction->getTransactionId()], '[WARNING]');
        }
    }
}