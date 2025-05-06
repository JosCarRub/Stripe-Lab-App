<?php
declare(strict_types=1);

namespace App\commons\entities;

class InvoiceModel
{
    private string $id_intern_invoice;
    private string $invoice_id;
    private ?string $payment_id;
    private string $customer_id;
    private ?string $invoice_number;
    private int $amount;
    private string $currency;
    private string $status;
    private ?string $invoice_pdf;
    private ?string $hosted_invoice_url;
    private ?string $customer_email;
    private ?string $customer_name;
    private ?string $subscription_id;
    private ?int $period_start;
    private ?int $period_end;
    private string $created_at;

    public function __construct(
        string $id_intern_invoice,
        string $invoice_id,
        ?string $payment_id,
        string $customer_id,
        ?string $invoice_number,
        int $amount,
        string $currency,
        string $status,
        ?string $invoice_pdf = null,
        ?string $hosted_invoice_url = null,
        ?string $customer_email = null,
        ?string $customer_name = null,
        ?string $subscription_id = null,
        ?int $period_start = null,
        ?int $period_end = null
    ) {
        $this->$id_intern_invoice = $id_intern_invoice;
        $this->invoice_id = $invoice_id;
        $this->payment_id = $payment_id;
        $this->customer_id = $customer_id;
        $this->invoice_number = $invoice_number;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->status = $status;
        $this->invoice_pdf = $invoice_pdf;
        $this->hosted_invoice_url = $hosted_invoice_url;
        $this->customer_email = $customer_email;
        $this->customer_name = $customer_name;
        $this->subscription_id = $subscription_id;
        $this->period_start = $period_start;
        $this->period_end = $period_end;
        $this->created_at = date('Y-m-d H:i:s');
    }

    public function toArray(): array
    {
        return [
            '$id_intern_invoice' => $this->id_intern_invoice,
            'invoice_id' => $this->invoice_id,
            'payment_id' => $this->payment_id,
            'customer_id' => $this->customer_id,
            'invoice_number' => $this->invoice_number,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'invoice_pdf' => $this->invoice_pdf,
            'hosted_invoice_url' => $this->hosted_invoice_url,
            'customer_email' => $this->customer_email,
            'customer_name' => $this->customer_name,
            'subscription_id' => $this->subscription_id,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'created_at' => $this->created_at
        ];
    }

    // Getters
    public function getId(): string { return $this->id_intern_invoice; }
    public function getInvoiceId(): string { return $this->invoice_id; }
    public function getPaymentId(): ?string { return $this->payment_id; }
    public function getCustomerId(): string { return $this->customer_id; }
    public function getInvoiceNumber(): ?string { return $this->invoice_number; }
    public function getAmount(): int { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
    public function getStatus(): string { return $this->status; }
    public function getInvoicePdf(): ?string { return $this->invoice_pdf; }
    public function getHostedInvoiceUrl(): ?string { return $this->hosted_invoice_url; }
    public function getCustomerEmail(): ?string { return $this->customer_email; }
    public function getCustomerName(): ?string { return $this->customer_name; }
    public function getSubscriptionId(): ?string { return $this->subscription_id; }
    public function getPeriodStart(): ?int { return $this->period_start; }
    public function getPeriodEnd(): ?int { return $this->period_end; }
    public function getCreatedAt(): string { return $this->created_at; }

}