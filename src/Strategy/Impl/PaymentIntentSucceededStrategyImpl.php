<?php

declare(strict_types=1);

namespace App\Strategy\Impl;

use App\Commons\DTOs\PaymentIntentDTO;
use App\Commons\DTOs\ChargeDTO;
use App\Commons\Loggers\EventLogger;
use App\Commons\Loggers\ErrorLogger;
use App\Commons\Enums\StripeEventTypeEnum;
use App\Mappers\PaymentIntentMapper;
use App\Mappers\ChargeMapper;
use App\Factories\TransactionModelFactory;
use App\Repository\TransactionRepositoryInterface;
use App\Strategy\StripeWebhookStrategyInterface;
use Stripe\Event as StripeEvent;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

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
            $this->stripeClient = new StripeClient($stripeApiKey);
        } else {
            $this->stripeClient = null;
        }
    }

    public static function getSupportedEventType(): StripeEventTypeEnum
    {
        return StripeEventTypeEnum::PAYMENT_INTENT_SUCCEEDED;
    }

    public function isApplicable(StripeEvent $event): bool
    {
        return $event->type === self::getSupportedEventType()->value;
    }

    public function process(StripeEvent $event): void
    {
        $payload = $event->data->object; // payload del evento
        $eventId = $event->id;

        EventLogger::log(self::class . ": Iniciando procesamiento.", ['event_id' => $eventId, 'event_type' => $event->type]);

        /** @var PaymentIntentDTO $piDTO */
        $piDTO = $this->paymentIntentMapper->mapToDTO($payload);

        EventLogger::log(self::class . ": DTO mapeado.", ['event_id' => $eventId, 'pi_id' => $piDTO->id]);

        // **
        $existingTransaction = $this->transactionRepository->findByPaymentIntentId($piDTO->id);

        if ($existingTransaction) {
            EventLogger::log(self::class . ": Transacción ya existe para este PaymentIntent.", [
                'event_id' => $eventId,
                'pi_id' => $piDTO->id,
                'existing_transaction_id' => $existingTransaction->getTransactionId()
            ]);
            return; // Ya procesado
        }

        //***
        $chargeDTO = null;

        // Verificar si tenemos las dependencias necesarias (StripeClient, ChargeMapper) y el ID del cargo
        if ($this->stripeClient && $this->chargeMapper && $piDTO->latestChargeId) {
            try {

                EventLogger::log(self::class . ": Intentando obtener Charge.", ['charge_id' => $piDTO->latestChargeId]);
                // Llama a la API de Stripe
                $stripeCharge = $this->stripeClient->charges->retrieve($piDTO->latestChargeId);
                $chargeDTO = $this->chargeMapper->mapToDTO($stripeCharge);

            } catch (ApiErrorException $e) {

                ErrorLogger::exception($e, ['event_id' => $eventId, 'charge_id' => $piDTO->latestChargeId], '[WARNING]');

            } catch (\App\Commons\Exceptions\InvalidWebhookPayloadException $e) {

                ErrorLogger::exception($e, ['event_id' => $eventId, 'charge_id' => $piDTO->latestChargeId], '[WARNING]');
            }
        }

        // 7. Crear el Modelo/Entidad de Transacción usando el Factory:
        //    El factory toma el PaymentIntentDTO (y opcionalmente el ChargeDTO)
        //    y crea una instancia de TransactionsModel lista para ser guardada.
        $transaction = $this->transactionFactory->createFromPaymentIntentDTO($piDTO, $chargeDTO);

        $this->transactionRepository->save($transaction);

        EventLogger::log(self::class . ": Transacción creada.", [
            'event_id' => $eventId,
            'pi_id' => $piDTO->id,
            'transaction_id_local' => $transaction->getTransactionId()
        ]);

        EventLogger::log(self::class . ": Procesamiento completado.", ['event_id' => $eventId]);
    }
}
//** IMPORTANTE */

//    Comprobación de Idempotencia/Duplicados:
//    Verificar si ya hemos procesado este PaymentIntent y creado una transacción para él.
//    Stripe garantiza la entrega "al menos una vez", por lo que podríamos recibir el mismo evento varias veces.



//***
//  Obtención Opcional de Datos Adicionales (Objeto Charge):
//    El PaymentIntent tiene `latest_charge_id`. Si queremos el `receipt_url` del Charge,
//    necesitamos obtener el objeto Charge completo haciendo una llamada a la API de Stripe.
//    Esto es opcional y añade una dependencia de red.