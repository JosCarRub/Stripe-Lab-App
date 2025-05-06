<?php

namespace App\repositories;

use App\commons\entities\InvoiceModel;

interface InvoiceRepository
{

    public function saveInvoice(InvoiceModel $invoiceModel): void;
    public function getCustomerInvoices(string $customerId): array;

    public function getInvoiceByIdInternId(string $id): ?array;

}