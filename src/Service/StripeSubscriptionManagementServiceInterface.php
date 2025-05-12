<?php
declare(strict_types=1);

namespace App\Service;

use App\Commons\Exceptions\NotFoundException;
use Stripe\Exception\ApiErrorException;
use Stripe\Subscription; // Para el tipo de retorno

interface StripeSubscriptionManagementServiceInterface
{
    /**
     * Cancela una suscripción inmediatamente.
     *
     * @param string $subscriptionId
     * @return Subscription El objeto Subscription de Stripe cancelado.
     * @throws ApiErrorException
     * @throws NotFoundException Si la suscripción no existe.
     */
    public function cancelSubscriptionNow(string $subscriptionId): Subscription;

    /**
     * Configura una suscripción para que se cancele al final del periodo de facturación actual.
     *
     * @param string $subscriptionId
     * @return Subscription El objeto Subscription de Stripe actualizado.
     * @throws ApiErrorException
     * @throws NotFoundException Si la suscripción no existe.
     */
    public function cancelSubscriptionAtPeriodEnd(string $subscriptionId): Subscription;

    /**
     * Reactiva una suscripción que estaba programada para cancelarse al final del periodo.
     * (Opcional, para completar)
     * @param string $subscriptionId
     * @return Subscription
     * @throws ApiErrorException
     * @throws NotFoundException
     */
    // public function reactivateSubscription(string $subscriptionId): Subscription;
}