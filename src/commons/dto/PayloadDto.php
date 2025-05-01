<?php

namespace App\commons\dto;

class PayloadDto
{
    public string $eventId;
    public string $customerId;
    public string $paymentIntentId;
    public int $amount;
    public string $currency;

    public string $status;

    public function __construct(string $eventId, string $customerId, string $paymentIntentId, int $amount, string $currency, string $status)
    {
        $this->eventId = $eventId;
        $this->customerId = $customerId;
        $this->paymentIntentId = $paymentIntentId;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->status = $status;
    }

    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'customer_id' => $this->customerId,
            'payment_intent_id' => $this->paymentIntentId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
        ];
    }

}