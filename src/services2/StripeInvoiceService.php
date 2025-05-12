<?php

namespace App\services2;

use Stripe\Event;

interface StripeInvoiceService
{
    /**
     * Manage the event and process the appropriate strategy.
     *
     * @param Event $event The Stripe event to manage.
     * @throws \RuntimeException
     */
    public function processInvoiceStrategy(Event $event): void;

}