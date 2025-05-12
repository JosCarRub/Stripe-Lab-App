<?php

namespace App\services2\Impl;

use App\commons\exceptions\StripeStrategyException;
use App\services2\StripeInvoiceService;
use App\strategy2\StripeStrategy;
use Stripe\Event;

class StripeInvoiceServiceImpl implements StripeInvoiceService
{
    /** @var StripeStrategy[] */
    private array $stripeStrategies;

    public function __construct(array $stripeStrategies)
    {
        $this->stripeStrategies = $stripeStrategies;
    }


    public function processInvoiceStrategy(Event $event): void
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