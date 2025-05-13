<?php
declare(strict_types=1);

namespace App\Service\Impl;

use App\Service\StripeSubscriptionManagementServiceInterface;
use App\Commons\Exceptions\ConfigurationException; // Aunque no se usa directamente aquí si StripeClient se inyecta
use App\Commons\Loggers\EventLogger;
use App\Commons\Loggers\ErrorLogger;
use App\Commons\Exceptions\NotFoundException;
use Stripe\StripeClient;
use Stripe\Subscription;
use Stripe\Exception\ApiErrorException;


class StripeSubscriptionManagementServiceImpl implements StripeSubscriptionManagementServiceInterface
{
    public function __construct(private StripeClient $stripeClient)
    {

    }

    public function cancelSubscriptionNow(string $subscriptionId): Subscription
    {
        EventLogger::log(self::class . ": Solicitando cancelación inmediata vía API.", ['subscription_id' => $subscriptionId]);
        try {

            // el metodo nuevo de stripe para borrar es cancel, antes delete
            $stripeSubscriptionObject = $this->stripeClient->subscriptions->cancel($subscriptionId, []);

            EventLogger::log(self::class . ": Suscripción cancelada inmediatamente (respuesta API).", [
                'subscription_id' => $subscriptionId,
                'api_status' => $stripeSubscriptionObject->status
            ]);
            return $stripeSubscriptionObject;
        } catch (ApiErrorException $e) {

            ErrorLogger::exception($e, ['subscription_id' => $subscriptionId, 'operation' => __METHOD__]);

            if ($e->getStripeCode() === 'resource_missing') {

                throw new NotFoundException("Suscripción {$subscriptionId} no encontrada en Stripe para cancelar.", 0, $e);
            }
            throw $e;
        }
    }

    public function cancelSubscriptionAtPeriodEnd(string $subscriptionId): Subscription
    {
        EventLogger::log(self::class . ": Solicitando cancelación al final del periodo vía API.", ['subscription_id' => $subscriptionId]);
        try {
            $stripeSubscriptionObject = $this->stripeClient->subscriptions->update($subscriptionId, [
                'cancel_at_period_end' => true,
            ]);
            EventLogger::log(self::class . ": Suscripción configurada para cancelar al final del periodo (respuesta API).", [
                'subscription_id' => $subscriptionId,
                'api_cancel_at_period_end' => $stripeSubscriptionObject->cancel_at_period_end
            ]);
            return $stripeSubscriptionObject;
        } catch (ApiErrorException $e) {

            ErrorLogger::exception($e, ['subscription_id' => $subscriptionId, 'operation' => __METHOD__]);

            if ($e->getStripeCode() === 'resource_missing') {

                throw new NotFoundException("Suscripción {$subscriptionId} no encontrada en Stripe para programar cancelación.", 0, $e);
            }

            throw $e;
        }
    }
}