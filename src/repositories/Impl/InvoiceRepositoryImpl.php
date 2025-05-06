<?php

namespace App\repositories\Impl;

use App\commons\entities\InvoiceModel;
use App\repositories\InvoiceRepository;
use PDO;

class InvoiceRepositoryImpl implements InvoiceRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function saveInvoice(InvoiceModel $invoiceModel): void
    {
        $data = $invoiceModel->toArray();

        $stmt = $this->db->prepare("
        INSERT INTO invoices 
        (id_intern_invoice, invoice_id, payment_id, customer_id, invoice_number, 
        amount, currency, status, invoice_pdf, hosted_invoice_url, 
        customer_email, customer_name, subscription_id, period_start, period_end)
        VALUES 
        (:id_intern_invoice, :invoice_id, :payment_id, :customer_id, :invoice_number, 
        :amount, :currency, :status, :invoice_pdf, :hosted_invoice_url, 
        :customer_email, :customer_name, :subscription_id, :period_start, :period_end)
    ");

        $stmt->execute([
            ':id_intern_invoice' => $data['id_intern_invoice'],
            ':invoice_id' => $data['invoice_id'],
            ':payment_id' => $data['payment_id'],
            ':customer_id' => $data['customer_id'],
            ':invoice_number' => $data['invoice_number'],
            ':amount' => $data['amount'],
            ':currency' => $data['currency'],
            ':status' => $data['status'],
            ':invoice_pdf' => $data['invoice_pdf'],
            ':hosted_invoice_url' => $data['hosted_invoice_url'],
            ':customer_email' => $data['customer_email'],
            ':customer_name' => $data['customer_name'],
            ':subscription_id' => $data['subscription_id'],
            ':period_start' => $data['period_start'],
            ':period_end' => $data['period_end']
        ]);
    }

    public function getCustomerInvoices(string $customerId): array
    {
        // TODO: Implement getCustomerInvoices() method.
    }

    public function getInvoiceByIdInternId(string $id): ?array
    {
        // TODO: Implement getInvoiceByIdInternId() method.
    }
}