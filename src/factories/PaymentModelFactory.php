<?php

namespace App\factories;

use App\commons\dto\PayloadDto;
use App\commons\entities\PaymentModel;
use App\commons\enums\StripeEventTypeEnum;
use Stripe\Event;

class PaymentModelFactory
{
    public static function createPaymentModel(Event $event, PayloadDto $payloadDto, StripeEventTypeEnum $eventType): PaymentModel
    {
        return new PaymentModel(
            id_payment: uniqid('pay_', true),
            event_id: $event->eventId,
            customer_id: $event->customerId,
            payment_intent_id: $event->paymentIntentId,
            eventType: $eventType,
            payload: $payloadDto
        );
    }
}
