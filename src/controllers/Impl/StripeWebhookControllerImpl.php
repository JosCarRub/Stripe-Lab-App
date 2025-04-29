<?php

namespace App\controllers\Impl;

use App\controllers\StripeWebhookController;
use App\services\StripeWebhookService;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookControllerImpl implements StripeWebhookController
{
    private StripeWebhookService $stripeWebhookService;



    public function __construct(StripeWebhookService $stripeWebhookService)
    {

        $this->stripeWebhookService = $stripeWebhookService;
    }

    public function handleStripeWebhook(string $payload, string $signatureHeader): void
    {
        //$event = $this->stripeWebhookService->constructEvent($payload, $signatureHeader);
        /*$event = \Stripe\Webhook::constructEvent(
                $payload,
                $signatureHeader,
                $_ENV['STRIPE_WEBHOOK_SECRET']
            );*/
        try {
            $event = $this->stripeWebhookService->constructEvent($payload, $signatureHeader);
            $this->stripeWebhookService->manageWebhook($event);

            http_response_code(200);

        } catch (SignatureVerificationException $e) {

            http_response_code(400);

            echo 'Webhook signature verification failed: ' . $e->getMessage();

        } catch (\UnexpectedValueException $e) {

            http_response_code(500);
            echo 'Internal server error: ' . $e->getMessage();
        }

    }
}