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

    public function __construct(InvoiceRepository $invoiceRepository, StripeInvoiceMapper $InvoiceMapper)
    {
        $this->invoiceRepository = $invoiceRepository;
        $this->InvoiceMapper = $InvoiceMapper;
    }

    /**
     * @param Event $event
     * @return bool
     */
    public function isApplicable(Event $event): bool
    {
        return StripeEventTypeEnum::INVOCE_PAYMENT_SUCCEDED->value == $event->type;
    }

    /**
     * @param Event $event
     * @return void
     */
    public function process(Event $event): void
    {
        // Registro de evento recibido
        $eventMessageReceived = 'Evento de factura recibido: ' . $event->type;
        $receivedContext = [
            'event_id' => $event->id,
            'invoice_id' => $event->data->object->id
        ];
        EventLogger::eventLog($eventMessageReceived, $receivedContext);

        $invoiceDto = $this->InvoiceMapper->mapToDto($event);

        EventLogger::eventLog("FACTURA MAPEADA");

        $invoiceModel = InvoiceModelFactory::createInvoiceModel($event, $invoiceDto, StripeEventTypeEnum::INVOCE_PAYMENT_SUCCEDED);

        $this->invoiceRepository->saveInvoice($invoiceModel);

        // Registro de evento procesado exitosamente
        $eventMessageProcessed = 'Factura procesada y guardada exitosamente';
        $processedContext = [
            'id_intern_invoice' => $invoiceModel->getId(),
            'invoice_id' => $invoiceModel->getInvoiceId(),
            'amount' => $invoiceModel->getAmount() . ' ' . $invoiceModel->getCurrency(),
            'status' => $invoiceModel->getStatus()
        ];
        EventLogger::eventLog($eventMessageProcessed, $processedContext);
    }
}