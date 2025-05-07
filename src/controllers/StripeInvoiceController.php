<?php

namespace App\controllers;

interface StripeInvoiceController
{
    public function getAllInvoices(): array;
    public function getCustomerInvoices(string $customerId): ?array;

}