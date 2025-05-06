<?php

namespace App\mappers;

use App\commons\dto\InvoiceDto;
use Stripe\Event;

class StripeInvoiceMapper
{
    public function mapToDto(Event $event): InvoiceDto
    {
        $data = $event->data['object'];

        return new InvoiceDto(
            eventId: $event->id,
            invoiceId: $data['id'],
            customerId: $data['customer'] ?? 'unknown',
            amount: $data['amount_due'],
            currency: $data['currency'],
            status: $data['status'],
            invoicePdf: $data['invoice_pdf'] ?? null,
            hostedInvoiceUrl: $data['hosted_invoice_url'] ?? null,
            customerEmail: $data['customer_email'] ?? null,
            customerName: $data['customer_name'] ?? null,
            subscriptionId: $data['subscription'] ?? null,
            periodStart: $data['period_start'] ?? null,
            periodEnd: $data['period_end'] ?? null
        );
    }
}