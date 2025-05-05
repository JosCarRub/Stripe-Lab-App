<?php
declare(strict_types=1);

namespace config;

use App\controllers\Impl\StripeWebhookControllerImpl;
use App\controllers\StripeWebhookController;
use App\repositories\Impl\PaymentRepositoryImpl;
use App\repositories\PaymentRepository;
use App\services\Impl\StripeCheckoutSessionServiceImpl;
use App\services\Impl\StripeWebhookServiceImpl;
use App\services\StripeCheckoutSessionService;
use App\services\StripeWebhookService;
use App\strategy\Impl\StripeStrategyCheckoutSessionCompleted;
use App\strategy\Impl\StripeStrategyPaymentIntentFailed;
use App\strategy\Impl\StripeStrategyPaymentIntentSucceed;
use App\mappers\StripePaymentIntentMapper;
use PDO;

/**
 * Clase de configuración para cargar todas las dependencias necesarias
 * para el procesamiento de webhooks de Stripe.
 */
class Bootstrap
{
    private static ?PDO $db = null;
    private static ?PaymentRepository $paymentRepository = null;
    private static ?StripePaymentIntentMapper $paymentIntentMapper = null;
    private static ?array $stripeStrategies = null;
    private static ?StripeWebhookService $stripeWebhookService = null;
    private static ?StripeWebhookController $stripeWebhookController = null;

    /**
     * Obtiene una instancia del controlador de webhooks de Stripe.
     *
     * @return StripeWebhookController
     */
    public static function getStripeWebhookController(): StripeWebhookController
    {
        if (self::$stripeWebhookController === null) {
            self::$stripeWebhookController = new StripeWebhookControllerImpl(
                self::getStripeWebhookService()
            );
        }

        return self::$stripeWebhookController;
    }

    /**
     * Obtiene una instancia del servicio de webhooks de Stripe.
     *
     * @return StripeWebhookService
     */
    private static function getStripeWebhookService(): StripeWebhookService
    {
        if (self::$stripeWebhookService === null) {
            self::$stripeWebhookService = new StripeWebhookServiceImpl(
                $_ENV['STRIPE_WEBHOOK_SECRET'],
                self::getStripeStrategies()
            );
        }

        return self::$stripeWebhookService;
    }

    /**
     * Obtiene un array de estrategias de Stripe.
     *
     * @return array
     */
    private static function getStripeStrategies(): array
    {
        if (self::$stripeStrategies === null) {
            self::$stripeStrategies = [
                new StripeStrategyPaymentIntentSucceed(
                    self::getPaymentRepository(),
                    self::getPaymentIntentMapper()
                ),
                new StripeStrategyPaymentIntentFailed(),
                new StripeStrategyCheckoutSessionCompleted(),
            ];
        }

        return self::$stripeStrategies;
    }

    /**
     * Obtiene una instancia del repositorio de pagos.
     *
     * @return PaymentRepository
     */
    private static function getPaymentRepository(): PaymentRepository
    {
        if (self::$paymentRepository === null) {
            self::$paymentRepository = new PaymentRepositoryImpl(
                self::getDatabase()
            );
        }

        return self::$paymentRepository;
    }

    /**
     * Obtiene una instancia del mapeador de intenciones de pago de Stripe.
     *
     * @return StripePaymentIntentMapper
     */
    private static function getPaymentIntentMapper(): StripePaymentIntentMapper
    {
        if (self::$paymentIntentMapper === null) {
            self::$paymentIntentMapper = new StripePaymentIntentMapper();
        }

        return self::$paymentIntentMapper;
    }

    /**
     * Obtiene una conexión a la base de datos.
     *
     * @return PDO
     */
    private static function getDatabase(): PDO
    {
        if (self::$db === null) {
            self::$db = DatabaseConnection::getInstance();
        }

        return self::$db;
    }

    /**
     * Test para probar los pagos unicos y para suscripciones
     */
    private static ?StripeCheckoutSessionServiceImpl $stripeCheckoutSessionService = null;

    /**
     * Obtiene una instancia del servicio de pagos Stripe.
     *
     * @return StripeCheckoutSessionService
     */
    public static function getStripePaymentService(): StripeCheckoutSessionServiceImpl
    {
        if (self::$stripeCheckoutSessionService === null) {
            self::$stripeCheckoutSessionService = new StripeCheckoutSessionServiceImpl(
                $_ENV['STRIPE_SECRET_KEY'],
                $_ENV['APP_DOMAIN']
            );
        }

        return self::$stripeCheckoutSessionService;
    }

}