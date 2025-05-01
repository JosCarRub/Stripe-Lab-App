<?php
declare(strict_types=1);

namespace App\commons\entities;

use App\commons\dto\PayloadDto;
use App\commons\enums\StripeEventTypeEnum;

class PaymentModel
{
    public string $id_payment;
    public string $event_id;
    public string $customer_id;
    public string $payment_intent_id;
    public StripeEventTypeEnum $eventType;

    public PayloadDto $payload; // JSON decodificado

    public function __construct(string $id_payment , string $event_id, string $customer_id, string $payment_intent_id, StripeEventTypeEnum $eventType, PayloadDto $payload) {

        $this->id_payment = $id_payment;
        $this->event_id = $event_id;
        $this->customer_id = $customer_id;
        $this->payment_intent_id = $payment_intent_id;
        $this->eventType = $eventType;
        $this->payload = $payload;
    }

    public function toArray(): array
    {
        return [
            'id_payment' => $this->id_payment,
            'event_id' => $this->event_id,
            'customer_id' => $this->customer_id,
            'payment_intent_id' => $this->payment_intent_id,
            'event_type' => $this->eventType->value,
            'payload' => json_encode($this->payload->toArray())
        ];
    }

    public function getId(): string { return $this->id_payment; }
    public function getEventId(): string { return $this->event_id; }
    public function getCustomerId(): string { return $this->customer_id; }
    public function getPaymentIntentId(): string { return $this->payment_intent_id; }
    public function getEventType(): StripeEventTypeEnum { return $this->eventType; }

    public function getPayload(): array { return $this->payload->toArray(); }



}