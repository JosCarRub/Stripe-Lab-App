<?php

namespace App\services;

use Stripe\Event;

interface StripeWebhookService
{
public function manageWebhook(Event $event): void;
public function constructEvent(string $payload, string $signatureHeader): Event;

}