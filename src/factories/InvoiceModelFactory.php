<?php

namespace App\factories;

use App\commons\dto\InvoiceDto;
use App\commons\entities\InvoiceModel;
use App\commons\enums\StripeEventTypeEnum;
use Stripe\Event;

class InvoiceModelFactory
{
    public static function createInvoiceModel(Event $event, InvoiceDto $invoiceDto, StripeEventTypeEnum $eventType): InvoiceModel
    {
        return new InvoiceModel(
            id_intern_invoice: uniqid('inv_', true),
            invoice_id: $invoiceDto->invoiceId,
            payment_id: $event->data->object->charge ?? null,
            customer_id: $invoiceDto->customerId,
            invoice_number: $event->data->object->number ?? null,
            amount: $invoiceDto->amount,
            currency: $invoiceDto->currency,
            status: $invoiceDto->status,
            invoice_pdf: $invoiceDto->invoicePdf,
            hosted_invoice_url: $invoiceDto->hostedInvoiceUrl,
            customer_email: $invoiceDto->customerEmail,
            customer_name: $invoiceDto->customerName,
            subscription_id: $invoiceDto->subscriptionId,
            period_start: $invoiceDto->periodStart,
            period_end: $invoiceDto->periodEnd
        );
    }
}