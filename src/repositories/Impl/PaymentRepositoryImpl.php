<?php

namespace App\repositories\Impl;

use App\commons\entities\PaymentModel;
use App\repositories\PaymentRepository;
use PDO;

class PaymentRepositoryImpl implements PaymentRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function save(PaymentModel $paymentModel): void
    {
        $data = $paymentModel->toArray();

        $stmt = $this->db->prepare("
            INSERT INTO payments 
            (id_payment, event_id, customer_id, payment_intent_id, event_type, payload)
            VALUES 
            (:id_payment, :event_id, :customer_id, :payment_intent_id, :event_type, :payload)
        ");

        $stmt->execute([
            ':id_payment' => $data['id_payment'],
            ':event_id' => $data['event_id'],
            ':customer_id' => $data['customer_id'],
            ':payment_intent_id' => $data['payment_intent_id'],
            ':event_type' => $data['event_type'],
            ':payload' => $data['payload']
        ]);
    }
}
?>
