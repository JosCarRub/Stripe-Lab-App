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
            event_id: $event->id,
            customer_id: $event->data->object->customer ?? '',
            payment_intent_id: $event->data->object->payment_intent ?? '',
            eventType: $eventType,
            payload: $payloadDto
        );
    }
}
