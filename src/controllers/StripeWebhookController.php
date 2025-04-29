<?php

namespace App\controllers;

interface StripeWebhookController
{
    public function handleStripeWebhook(string $payload, string $signatureHeader): void;
}