<?php

namespace App\strategy\Impl;

use App\commons\enums\StripeEventTypeEnum;
use App\commons\logger\EventLogger;
use App\factories\InvoiceModelFactory;
use App\mappers\StripeInvoiceMapper;
use App\repositories\InvoiceRepository;
use App\strategy\StripeStrategy;
use Stripe\Event;

class StripeStrategyInvoicePaymentSucceeded implements StripeStrategy
{

    private StripeInvoiceMapper $InvoiceMapper;
    private InvoiceRepository $invoiceRepository;

    public function isApplicable(Event $event): bool
    {
        return StripeEventTypeEnum::INVOCE_PAYMENT_SUCCEDED->value == $event->type;
    }

    public function process(Event $event): void
    {
        $eventMessageInvoiceProcesed = 'Invoice payment succeeded processed successfully ';
        $eventContext = ([
            $event->data->object->id
        ]);
        EventLogger::eventLog($eventMessageInvoiceProcesed, $eventContext);

        $invoceDto = $this->InvoiceMapper->mapToDto($event);

        $invoceModel = InvoiceModelFactory::createInvoiceModel($event, $invoceDto, StripeEventTypeEnum::INVOCE_PAYMENT_SUCCEDED);

        $this->invoiceRepository->saveInvoice($invoceModel);
    }
}