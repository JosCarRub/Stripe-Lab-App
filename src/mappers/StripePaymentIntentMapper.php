<?php

namespace App\mappers;

use App\commons\dto\PayloadDto;
use Stripe\Event;

class StripePaymentIntentMapper
{
    public function mapToDto(Event $event): PayloadDto
    {
        $data = $event->data['object'];

        return new PayloadDto(
            eventId: $event->id,
            customerId: $data['customer'] ?? 'unknown',
            paymentIntentId: $data['id'],
            amount: $data['amount_received'],
            currency: $data['currency'],
            status: $data['status']
        );
    }
}