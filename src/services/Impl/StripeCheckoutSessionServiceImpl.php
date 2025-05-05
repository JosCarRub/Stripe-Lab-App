<?php

namespace App\services\Impl;

use App\commons\enums\StripeProductsTypeEnum;
use App\commons\exceptions\StripePaymentException;
use App\services\StripeCheckoutSessionService;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\StripeClient;

class StripeCheckoutSessionServiceImpl implements StripeCheckoutSessionService
{
    private StripeClient $stripeClient;
    private string $domain;
    private string $logFile;

    public function __construct(string $stripeSecretKey, string $appUrl)
    {
        Stripe::setApiKey($stripeSecretKey);
        $this->stripeClient = new StripeClient($stripeSecretKey);

        // Asegurar que la URL del dominio tenga el formato correcto
        $this->domain = $this->formatDomainUrl($appUrl);

        $this->logFile = __DIR__ . '/../../../logs/stripe_service.log';

        // Asegurarse de que el directorio de logs existe
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }

        $this->log("Servicio inicializado con domain: {$this->domain}");
    }

    /**
     * Asegura que la URL del dominio tenga el formato correcto
     *
     * @param string $url La URL a formatear
     * @return string La URL formateada
     */
    private function formatDomainUrl(string $url): string
    {
        // Si la URL ya comienza con http:// o https://, no hacer nada
        if (preg_match('/^https?:\/\//', $url)) {
            return rtrim($url, '/'); // Eliminar la barra final si existe
        }

        // En entorno de desarrollo local, usar http://
        if (in_array($url, ['localhost', '127.0.0.1'])) {
            return 'http://' . $url;
        }

        // Para otras URLs, usar https:// por defecto
        return 'https://' . $url;
    }

    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    public function createPaymentSession(): Session
    {
        try {
            $this->log("Iniciando createPaymentSession()");

            // Verifica las URLs de redirección
            $successUrl = $this->domain . '/success.html';
            $cancelUrl = $this->domain . '/cancel.html';

            $this->log("URLs de redirección: success_url={$successUrl}, cancel_url={$cancelUrl}");

            return $this->stripeClient->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Pago único',
                        ],
                        'unit_amount' => 1000,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ]);

        } catch (\Exception $e) {
            $this->log("ERROR en createPaymentSession: " . $e->getMessage());
            $this->log("Traza: " . $e->getTraceAsString());
            throw new StripePaymentException('Error creating the payment session: ' . $e->getMessage());
        }
    }

    public function createSubscriptionSession(string $lookup_key): Session
    {
        try {
            $this->log("Iniciando createSubscriptionSession() con lookup_key: {$lookup_key}");

            // Verifica las URLs de redirección
            $successUrl = $this->domain . '/success.html';
            $cancelUrl = $this->domain . '/cancel.html';

            $this->log("URLs de redirección: success_url={$successUrl}, cancel_url={$cancelUrl}");

            // Retrieve the price using the lookup key
            $prices = $this->stripeClient->prices->all([
                'lookup_keys' => [$lookup_key],
            ]);

            if (empty($prices->data)) {
                $this->log("No se encontró precio con lookup_key: {$lookup_key}");
                throw new StripePaymentException('The specified subscription plan was not found');
            }

            $this->log("Precio encontrado con ID: " . $prices->data[0]->id);

            return $this->stripeClient->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => $prices->data[0]->id,
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ]);

        } catch (\Exception $e) {
            $this->log("ERROR en createSubscriptionSession: " . $e->getMessage());
            $this->log("Traza: " . $e->getTraceAsString());
            throw new StripePaymentException('Error creating the subscription session: ' . $e->getMessage());
        }
    }
}