<?php

namespace App\controllers;

interface StripeInvoiceController
{
    public function getCustomerInvoices(string $customerId): array;
    public function getInvoiceById(string $id): ?array;

}