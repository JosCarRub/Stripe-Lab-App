<?php

namespace App\strategy\Impl;

use App\commons\entities\PaymentModel;
use App\commons\enums\StripeEventTypeEnum;
use App\repositories\Impl\PaymentRepositoryImpl;
use App\repositories\PaymentRepository;
use App\strategy\StripeStrategy;
use Stripe\Event;

class StripeStrategyPaymentIntentSucceed implements StripeStrategy
{
    private PaymentRepository $paymentRepository;

    public function __construct(PaymentRepository $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    public function isApplicable(Event $event): bool
    {
        return StripeEventTypeEnum::PAYMENT_INTENT_SUCCEEDED->value == $event->type;
    }

    public function process(Event $event): void
    {
        $eventData = $event->data['object'];


        $id_payment = uniqid('pay_', true);
        $event_id = $event->id;
        $customer_id = $eventData['customer'] ?? 'unknown_customer';
        $payment_intent_id = $eventData['id'];
        $eventType = StripeEventTypeEnum::PAYMENT_INTENT_SUCCEEDED;
        $payload = $event->toArray();

        $paymentModel = new PaymentModel(
            $id_payment,
            $event_id,
            $customer_id,
            $payment_intent_id,
            $eventType,
            $payload
        );

        $this->paymentRepository->save($paymentModel);

    }

}