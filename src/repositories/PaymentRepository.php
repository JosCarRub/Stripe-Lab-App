<?php

namespace App\repositories;

use App\commons\entities\PaymentModel;

interface PaymentRepository
{

    public function save(PaymentModel $paymentModel): void;

}