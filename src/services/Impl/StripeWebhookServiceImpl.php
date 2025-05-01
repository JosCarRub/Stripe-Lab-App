<?php

namespace App\services\Impl;

use App\services\StripeWebhookService;
use App\strategy\Impl\StripeStrategyPaymentIntentSucceed;
use App\strategy\StripeStrategy;
use RuntimeException;
use Stripe\Event;
use config;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookServiceImpl implements StripeWebhookService
{
    private string $stripeWebhookSecret;


    /** @var StripeStrategy[] */
    private array $stripeStrategies;


    public function __construct(string $stripeWebhookSecret,  array $stripeStrategies )
    {
        $this->stripeWebhookSecret = $stripeWebhookSecret;
        $this->stripeStrategies = $stripeStrategies;

    }

    public function manageWebhook(Event $event): void
    {
        $this->processStrategy($event);

    }

    public function constructEvent(string $payload, string $signatureHeader): Event
    {

        try {
            return \Stripe\Webhook::constructEvent(
                $payload,
                $signatureHeader,
                $this->stripeWebhookSecret
            );
        } catch (SignatureVerificationException $e) {
            throw new RuntimeException($e->getMessage());
        }

    }

    public function processStrategy(Event $event): void
    {
        try {
            foreach ($this->stripeStrategies as $strategy) {
                if ($strategy->isApplicable($event)) {
                    $strategy->process($event);
                }
            }

        } catch (RuntimeException $e) {
            throw new RuntimeException('No applicable strategy found for event type: ' . $event->type);
        }
    }


}