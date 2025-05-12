<?php
declare(strict_types=1);
namespace App\Commons\Enums;

enum StripeProductsTypeEnum: string
{
    case ONE_PAYMENT = 'one_payment';
    case MONTHLY_SUBSCRIPTION = 'monthly_subscriptions';
    case YEARLY_SUBSCRIPTION = 'annual_payment';
}