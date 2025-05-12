<?php

declare(strict_types=1);

namespace App\Commons\Enums;

/**
 * Enum para los tipos de eventos de webhook de Stripe que la aplicación maneja.
 */
enum StripeEventTypeEnum: string
{
    // Eventos de Checkout
    case CHECKOUT_SESSION_COMPLETED = 'checkout.session.completed';

    // Eventos de Customer
    case CUSTOMER_CREATED = 'customer.created';
    case CUSTOMER_UPDATED = 'customer.updated';

    // Eventos de PaymentIntent
    case PAYMENT_INTENT_SUCCEEDED = 'payment_intent.succeeded';

    // Eventos de Charge
    case CHARGE_SUCCEEDED = 'charge.succeeded';

    // Eventos de Subscription
    case CUSTOMER_SUBSCRIPTION_CREATED = 'customer.subscription.created';
    case CUSTOMER_SUBSCRIPTION_UPDATED = 'customer.subscription.updated';
    case CUSTOMER_SUBSCRIPTION_DELETED = 'customer.subscription.deleted';


    // Eventos de Invoice
    case INVOICE_PAID = 'invoice.paid';

    /**
     * Intenta crear una instancia del Enum a partir de un valor string.
     *
     * @param string $value El valor string del tipo de evento (ej. 'checkout.session.completed').
     * @return self|null La instancia del Enum correspondiente o null si no se encuentra.
     */
    public static function tryFromString(string $value): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        return null;
    }
}