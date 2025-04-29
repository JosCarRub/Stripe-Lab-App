<?php

namespace App\repositories\Impl;

use App\commons\entities\PaymentModel;
use App\repositories\PaymentRepository;
use PDO;

class PaymentRepositoryImpl implements PaymentRepository
{
    public PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    public function save(PaymentModel $paymentModel): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO payments (
                id_payment, event_id, customer_id, payment_intent_id, event_type, payload
            ) VALUES (
                :id_payment, :event_id, :customer_id, :payment_intent_id, :event_type, :payload
            )'
        );

        $stmt->execute([
            'id_payment' => $paymentModel->getId(),
            'event_id' => $paymentModel->getEventId(),
            'customer_id' => $paymentModel->getCustomerId(),
            'payment_intent_id' => $paymentModel->getPaymentIntentId(),
            'event_type' => $paymentModel->getEventType()->value,
            'payload' => json_encode($paymentModel->getPayload())
        ]);

    }
}