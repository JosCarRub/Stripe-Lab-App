<?php

namespace App\services\Impl;

use App\commons\enums\StripeProductsTypeEnum;
use App\commons\exceptions\StripePaymentException;
use App\services\StripeCheckoutSessionService;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\StripeClient;

class StripeCheckoutSessionServiceImpl implements StripeCheckoutSessionService
{
    private StripeClient $stripeClient;
    private string $appUrl;


    public function __construct(string $stripeSecretKey, string $appUrl)
    {
        Stripe::setApiKey($stripeSecretKey);
        $this->stripeClient = new StripeClient($stripeSecretKey);
        $this->domain = $appUrl;
    }

    public function createPaymentSession(): Session
    {
        try {
            $prices = $this->stripeClient->prices->all([
                'lookup_keys' => [StripeProductsTypeEnum::ONE_PAYMENT->value],
            ]);
            // Check if the price was found

            if (empty($prices->data)) {

                throw new StripePaymentException('No price found for the one-off payment');
            }


            return $this->stripeClient->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Pago único',
                        ],
                        'unit_amount' => 1000,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->domain . '/success.html',
                'cancel_url' => $this->domain . '/cancel.html',
            ]);

        } catch (StripePaymentException $e) {

            throw new StripePaymentException('Error creating the payment session: ' . $e->getMessage());
        }
    }

    public function createSubscriptionSession(string $lookup_key): Session
    {
        try {
            // Retrieve the price using the lookup key
            $prices = $this->stripeClient->prices->all([
                'lookup_keys' => [$lookup_key],
            ]);

            if (empty($prices->data)) {
                throw new StripePaymentException('The specified subscription plan was not found');
            }

            return $this->stripeClient->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $prices->data[0]->id,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $this->domain . '/public/success.html',
                'cancel_url' => $this->domain . '/public/cancel.html',
            ]);

        } catch (StripePaymentException $e) {

            throw new StripePaymentException('Error creating the subscription session: ' . $e->getMessage());
        }
    }
}