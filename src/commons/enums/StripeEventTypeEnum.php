<?php
declare(strict_types=1);

namespace App\commons\enums;

enum StripeEventTypeEnum: string
{
    case CHECKOUT_SESSION_COMPLETED = 'checkout.session.completed';
    case PAYMENT_INTENT_SUCCEEDED = 'payment_intent.succeeded';
    case PAYMENT_INTENT_FAILED = 'payment_intent.payment_failed';


}