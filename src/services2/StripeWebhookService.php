<?php

namespace App\services2;

use Stripe\Event;

interface StripeWebhookService
{
public function manageWebhook(Event $event): void;
public function constructEvent(string $payload, string $signatureHeader): Event;

}