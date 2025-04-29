<?php

namespace App\strategy\Impl;

use App\commons\enums\StripeEventTypeEnum;
use App\strategy\StripeStrategy;
use Stripe\Event;

class StripeStrategyCheckoutSessionCompleted implements StripeStrategy
{
    public function isApplicable(Event $event): bool
    {
        return StripeEventTypeEnum::CHECKOUT_SESSION_COMPLETED->value == $event->type;
    }

    public function process(Event $event): void
    {
        return;
    }
}