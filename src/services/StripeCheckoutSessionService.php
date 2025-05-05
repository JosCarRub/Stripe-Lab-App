<?php

namespace App\services;

use Stripe\Checkout\Session;

interface StripeCheckoutSessionService
{

    /**
     * Create a single payment session.
     *
     * @return Session The Stripe session created.
     */
    public function createPaymentSession(): Session;

    /**
     * Create a subscription session.
     *
     * @param string $lookup_key Lookup key for the subscription plan
     * @return Session The Stripe session created.
     */
    public function createSubscriptionSession(string $lookup_key): Session;




}