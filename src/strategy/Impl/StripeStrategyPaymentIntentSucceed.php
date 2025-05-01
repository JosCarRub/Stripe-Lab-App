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
        $payloadDto = $this->PaymentIntentMapper->mapToDto($event);
        var_dump($event);
        exit;
        $paymentModel = PaymentModelFactory::createPaymentModel($event, $payloadDto,StripeEventTypeEnum::PAYMENT_INTENT_SUCCEEDED );
        $this->paymentRepository->save($paymentModel);

    }

}