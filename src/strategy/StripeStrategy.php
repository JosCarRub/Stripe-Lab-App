<?php

namespace App\strategy;

use Stripe\Event;

interface StripeStrategy
{
   public function isApplicable(Event $event): bool;
    public function process(Event $event): void;

}