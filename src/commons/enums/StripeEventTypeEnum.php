<?php
declare(strict_types=1);

namespace App\commons\enums;

enum StripeEventTypeEnum: string
{
    case CHECKOUT_SESSION_COMPLETED = 'checkout.session.completed';
    case PAYMENT_INTENT_SUCCEEDED = 'payment_intent.succeeded';
    case INVOCE_PAYMENT_SUCCEDED = 'invoice.payment_succeeded';
    case PAYMENT_INTENT_FAILED = 'payment_intent.payment_failed';


}