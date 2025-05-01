<?php

namespace App\strategy\Impl;

use App\commons\dto\PayloadDto;
use App\commons\entities\PaymentModel;
use App\commons\enums\StripeEventTypeEnum;
use App\factories\PaymentModelFactory;
use App\mappers\StripePaymentIntentMapper;
use App\repositories\Impl\PaymentRepositoryImpl;
use App\repositories\PaymentRepository;
use App\strategy\StripeStrategy;
use Stripe\Event;

class StripeStrategyPaymentIntentSucceed implements StripeStrategy
{
    private PaymentRepository $paymentRepository;
    private StripePaymentIntentMapper $PaymentIntentMapper;

    public function __construct(PaymentRepository $paymentRepository, StripePaymentIntentMapper $PaymentIntentMapper)
    {
        $this->paymentRepository = $paymentRepository;
        $this->PaymentIntentMapper = $PaymentIntentMapper;
    }

    public function isApplicable(Event $event): bool
    {
        return StripeEventTypeEnum::PAYMENT_INTENT_SUCCEEDED->value == $event->type;
    }

    public function process(Event $event): void
    {

        $logFile = __DIR__ . '/../../../logs/payment_intent_debug.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Procesando payment_intent.succeeded: " . $event->id . "\n", FILE_APPEND);
        $payloadDto = $this->PaymentIntentMapper->mapToDto($event);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - DTO creado correctamente\n", FILE_APPEND);

        $paymentModel = PaymentModelFactory::createPaymentModel($event, $payloadDto,StripeEventTypeEnum::PAYMENT_INTENT_SUCCEEDED );
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Modelo de pago creado: " . $paymentModel->getId() . "\n", FILE_APPEND);

        $this->paymentRepository->save($paymentModel);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Pago guardado exitosamente\n", FILE_APPEND);



    }

}