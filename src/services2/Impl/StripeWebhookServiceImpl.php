<?php

namespace App\services2\Impl;

use App\commons\exceptions\StripeStrategyException;
use App\services2\StripeWebhookService;
use App\strategy2\StripeStrategy;
use RuntimeException;
use Stripe\Event;
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

    /**
     * @throws StripeStrategyException
     */
    public function manageWebhook(Event $event): void
    {
        $this->processStrategy($event);

    }

    /**
     * @throws SignatureVerificationException
     */
    public function constructEvent(string $payload, string $signatureHeader): Event
    {

        try {
            return \Stripe\Webhook::constructEvent(
                $payload,
                $signatureHeader,
                $this->stripeWebhookSecret
            );
        } catch (SignatureVerificationException $e) {
            throw new SignatureVerificationException($e->getMessage());
        }

    }

    /**
     * @throws StripeStrategyException
     */
    public function processStrategy(Event $event): void
    {
        try {
            foreach ($this->stripeStrategies as $strategy) {
                if ($strategy->isApplicable($event)) {
                    $strategy->process($event);

                    return;
                }
            }

        } catch (StripeStrategyException $e) {
            throw new StripeStrategyException('No applicable strategy found for event type: ' . $event->type);
        }
    }


}