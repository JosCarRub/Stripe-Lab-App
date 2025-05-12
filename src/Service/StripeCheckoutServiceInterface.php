<?php

declare(strict_types=1);

namespace App\Service;

// No necesita `use Stripe\Exception\ApiErrorException;` aquí, pero la implementación sí.
// No necesita `use App\Commons\Exceptions\ConfigurationException;` aquí.
use App\Commons\Exceptions\ConfigurationException;
use Stripe\Exception\ApiErrorException;

/**
 * Interfaz para el servicio que crea sesiones de Stripe Checkout.
 */
interface StripeCheckoutServiceInterface
{
    /**
     * Crea una sesión de Stripe Checkout para un pago único.
     *
     * @param string $priceLookupKey La lookup key del precio en Stripe.
     * @return string El ID de la sesión de Checkout (`cs_...`).
     * @throws ApiErrorException Si hay un error con la API de Stripe.
     * @throws ConfigurationException Si falta configuración esencial (ej. precio no encontrado).
     */
    public function createOneTimePaymentSession(string $priceLookupKey): string;

    /**
     * Crea una sesión de Stripe Checkout para una suscripción.
     *
     * @param string $priceLookupKey La lookup key del precio de suscripción en Stripe.
     * @return string El ID de la sesión de Checkout (`cs_...`).
     * @throws ApiErrorException Si hay un error con la API de Stripe.
     * @throws ConfigurationException Si falta configuración esencial (ej. precio no encontrado).
     */
    public function createSubscriptionSession(string $priceLookupKey): string;
}