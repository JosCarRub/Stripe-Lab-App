<?php

namespace App\commons\dto;

class InvoiceDto
{
    public function __construct(
        public string $eventId,
        public string $invoiceId,
        public string $customerId,
        public int $amount,
        public string $currency,
        public string $status,
        public ?string $invoicePdf,
        public ?string $hostedInvoiceUrl,
        public ?string $customerEmail,
        public ?string $customerName,
        public ?string $subscriptionId,
        public ?int $periodStart,
        public ?int $periodEnd,
    ) {
        $this->eventId = $eventId;
        $this->invoiceId = $invoiceId;
        $this->customerId = $customerId;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->status = $status;
        $this->invoicePdf = $invoicePdf;
        $this->hostedInvoiceUrl = $hostedInvoiceUrl;
        $this->customerEmail = $customerEmail;
        $this->customerName = $customerName;
        $this->subscriptionId = $subscriptionId;
        $this->periodStart = $periodStart;
        $this->periodEnd = $periodEnd;
    }

    public function toArray(): array
    {
        return [
            'eventId' => $this->eventId,
            'invoiceId' => $this->invoiceId,
            'customerId' => $this->customerId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'invoicePdf' => $this->invoicePdf,
            'hostedInvoiceUrl' => $this->hostedInvoiceUrl,
            'customerEmail' => $this->customerEmail,
            'customerName' => $this->customerName,
            'subscriptionId' => $this->subscriptionId,
            'periodStart' => $this->periodStart,
            'periodEnd' => $this->periodEnd,
        ];
    }
}