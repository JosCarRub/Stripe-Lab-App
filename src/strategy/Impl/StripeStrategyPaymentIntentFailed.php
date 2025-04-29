<?php

namespace App\strategy\Impl;

use App\commons\enums\StripeEventTypeEnum;
use App\strategy\StripeStrategy;
use Stripe\Event;

class StripeStrategyPaymentIntentFailed implements StripeStrategy
{

    public function isApplicable(Event $event): bool
    {
        return StripeEventTypeEnum::PAYMENT_INTENT_FAILED->value == $event->type;
    }

    public function process(Event $event): void
    {
        return;
    }
}