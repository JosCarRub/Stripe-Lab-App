<?php
declare(strict_types=1);
namespace App\Commons\Enums;

/**
 * Para distinguir entre los tipos de estado de las suscripciones que registro en la tabla StripeSubscriptions
 */
enum TransactionTypeEnum: string
{
    case ONE_TIME_RECEIPT = 'one_time_receipt';
    case SUBSCRIPTION_INVOICE = 'subscription_invoice';

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