<?php
declare(strict_types=1);

namespace App\Service\Impl;

use App\Service\StripeCheckoutServiceInterface;
use App\Commons\Exceptions\ConfigurationException;
use App\Commons\Loggers\EventLogger;
use App\Commons\Loggers\ErrorLogger;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripeCheckoutSessionServiceImpl implements StripeCheckoutServiceInterface
{
    private StripeClient $stripeClient;
    private string $appDomainBase;

    /**
     * @param string $stripeSecretKey  clave secreta de Stripe.
     * @param string $appUrlBase URL base de la aplicación (en mi caso: http://localhost:8000).
     *                           Las rutas a success/cancel se añadirán a esto.
     */
    public function __construct(string $stripeSecretKey, string $appUrlBase)
    {
        if (empty($stripeSecretKey)) {
            throw new ConfigurationException("Stripe secret key no proporcionada a StripeCheckoutSessionService.");
        }
        //  usar el cliente instanciado.
        $this->stripeClient = new StripeClient($stripeSecretKey);

        if (empty($appUrlBase) || !filter_var($appUrlBase, FILTER_VALIDATE_URL)) {
            throw new ConfigurationException("URL base de la aplicación no configurada o es inválida.");
        }
        $this->appDomainBase = rtrim($appUrlBase, '/'); // Quitar barra final

        EventLogger::log(self::class . " inicializado con appDomainBase: " . $this->appDomainBase);
    }

    /**
     * Obtiene el ID de un precio de Stripe usando su lookup key.
     */
    private function getPriceIdFromLookupKey(string $lookupKey, string $contextForLog): string
    {
        EventLogger::log(self::class . ": Buscando Price ID.", ['lookup_key' => $lookupKey, 'context' => $contextForLog]);
        if (empty($lookupKey)) {

            ErrorLogger::log(self::class . ": Lookup key vacía para {$contextForLog}.", [], '[CONFIG_ERROR]');

            throw new ConfigurationException("Lookup key no puede estar vacía para {$contextForLog}.");
        }
        try {
            $prices = $this->stripeClient->prices->all(['lookup_keys' => [$lookupKey], 'active' => true, 'limit' => 1]);
            if (empty($prices->data)) {
                ErrorLogger::log(self::class . ": Precio no encontrado/inactivo para lookup_key '{$lookupKey}' ({$contextForLog}).", [], '[CONFIG_ERROR]');
                throw new ConfigurationException("Precio no encontrado o inactivo para lookup_key: " . htmlspecialchars($lookupKey));
            }

            $priceId = $prices->data[0]->id;

            EventLogger::log(self::class . ": Price ID encontrado.", ['lookup_key' => $lookupKey, 'price_id' => $priceId]);
            return $priceId;

        } catch (ApiErrorException $e) {

            ErrorLogger::exception($e, ['lookup_key' => $lookupKey, 'operation' => 'retrieve_price_by_lookup_key']);

            throw $e;
        }
    }

    public function createOneTimePaymentSession(string $priceLookupKey): string
    {
        EventLogger::log(self::class . ": Creando sesión de pago único.", ['lookup_key' => $priceLookupKey]);
        try {
            $priceId = $this->getPriceIdFromLookupKey($priceLookupKey, "pago único");


            $successUrl = $this->appDomainBase . '/success.html?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = $this->appDomainBase . '/cancel.html';
            EventLogger::log(self::class . ": URLs para pago único.", ['success' => $successUrl, 'cancel' => $cancelUrl]);


            $session = $this->stripeClient->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $priceId, // Usar el Price ID obtenido
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ]);
            EventLogger::log(self::class . ": Sesión de pago único creada.", ['session_id' => $session->id, 'price_id' => $priceId]);
            return $session->id;

        } catch (ApiErrorException $e) {

            ErrorLogger::exception($e, ['lookup_key' => $priceLookupKey, 'operation' => __METHOD__]);
            throw $e;

        } catch (ConfigurationException $e) {

            ErrorLogger::exception($e, ['lookup_key' => $priceLookupKey, 'operation' => __METHOD__]);
            throw $e;
        }
    }

    public function createSubscriptionSession(string $priceLookupKey): string
    {
        EventLogger::log(self::class . ": Creando sesión de suscripción.", ['lookup_key' => $priceLookupKey]);
        try {
            $priceId = $this->getPriceIdFromLookupKey($priceLookupKey, "suscripción");

            $successUrl = $this->appDomainBase . '/success.html?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = $this->appDomainBase . '/cancel.html';
            EventLogger::log(self::class . ": URLs para suscripción.", ['success' => $successUrl, 'cancel' => $cancelUrl]);

            $session = $this->stripeClient->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ]);
            EventLogger::log(self::class . ": Sesión de suscripción creada.", ['session_id' => $session->id, 'price_id' => $priceId]);
            return $session->id;

        } catch (ApiErrorException $e) {

            ErrorLogger::exception($e, ['lookup_key' => $priceLookupKey, 'operation' => __METHOD__]);

            throw $e;

        } catch (ConfigurationException $e) {

            ErrorLogger::exception($e, ['lookup_key' => $priceLookupKey, 'operation' => __METHOD__]);

            throw $e;
        }
    }
}