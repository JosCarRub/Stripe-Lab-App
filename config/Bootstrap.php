<?php
declare(strict_types=1);

namespace config;

// --- Vendor ---

use App\Controller\Impl\StripeSubscriptionControllerImpl;
use App\Controller\StripeSubscriptionControllerInterface;
use App\Service\StripeSubscriptionManagementServiceInterface;
use Dotenv\Dotenv;
use PDO;
use Stripe\StripeClient;
use Stripe\Exception\AuthenticationException as StripeAuthenticationException; // Alias para evitar colisión


// Commons
use App\Commons\Loggers\EventLogger;
use App\Commons\Loggers\ErrorLogger;
use App\Commons\Loggers\DatabaseLogger;
use App\Commons\Loggers\StripePayloadLogger;
use App\Commons\Enums\StripeProductsTypeEnum;
use App\Commons\Exceptions\ConfigurationException;

// Controllers
use App\Controller\Impl\StripeWebhookControllerImpl;
use App\Controller\StripeWebhookControllerInterface;
use App\Controller\Impl\StripeInvoiceControllerImpl;
use App\Controller\StripeInvoiceControllerInterface;


// Mappers
use App\Mappers\CheckoutSessionMapper;
use App\Mappers\CustomerMapper;
use App\Mappers\PaymentIntentMapper;
use App\Mappers\ChargeMapper;
use App\Mappers\SubscriptionMapper;
use App\Mappers\InvoiceMapper;

// Factories
use App\Factories\TransactionModelFactory;
use App\Factories\SubscriptionModelFactory;

// Repositories
use App\Repository\Impl\TransactionRepositoryImpl;
use App\Repository\Impl\SubscriptionRepositoryImpl;
use App\Repository\TransactionRepositoryInterface;
use App\Repository\SubscriptionRepositoryInterface;
use App\Repository\Impl\InvoiceRepositoryImpl;
use App\Repository\InvoiceRepositoryInterface;

// Services
use App\Service\Impl\StripeCheckoutSessionServiceImpl;
use App\Service\Impl\StripeWebhookServiceImpl;
use App\Service\StripeCheckoutServiceInterface;
use App\Service\StripeWebhookServiceInterface;
use App\Service\Impl\StripeSubscriptionManagementServiceImpl;



// Strategies
use App\Strategy\Impl\CheckoutSessionCompletedStrategyImpl;
use App\Strategy\Impl\CustomerCreatedOrUpdatedStrategyImpl;
use App\Strategy\Impl\PaymentIntentSucceededStrategyImpl;
use App\Strategy\Impl\ChargeSucceededStrategyImpl;
use App\Strategy\Impl\SubscriptionCreatedStrategyImpl;
use App\Strategy\Impl\SubscriptionUpdatedStrategyImpl;
use App\Strategy\Impl\SubscriptionDeletedStrategyImpl;
use App\Strategy\Impl\InvoicePaidStrategyImpl;
use App\Strategy\StripeWebhookStrategyInterface;


class Bootstrap
{
    private static bool $initialized = false;

    private static ?PDO $pdo = null;
    private static ?StripeClient $stripeClientGlobal = null;
    private static array $displayablePlans = [];

    private static ?CheckoutSessionMapper $checkoutSessionMapper = null;
    private static ?CustomerMapper $customerMapper = null;
    private static ?PaymentIntentMapper $paymentIntentMapper = null;
    private static ?ChargeMapper $chargeMapper = null;
    private static ?SubscriptionMapper $subscriptionMapper = null;
    private static ?InvoiceMapper $invoiceMapper = null;

    private static ?TransactionModelFactory $transactionFactory = null;
    private static ?SubscriptionModelFactory $subscriptionFactory = null;

    private static ?TransactionRepositoryInterface $transactionRepository = null;
    private static ?SubscriptionRepositoryInterface $subscriptionRepository = null;

    /** @var StripeWebhookStrategyInterface[]|null */
    private static ?array $stripeStrategies = null;

    private static ?StripeWebhookServiceInterface $stripeWebhookService = null;
    private static ?StripeCheckoutServiceInterface $stripeCheckoutService = null;
    private static ?StripeWebhookControllerInterface $stripeWebhookController = null;
    private static ?InvoiceRepositoryInterface $invoiceRepository = null; // Nueva
    private static ?StripeInvoiceControllerInterface $stripeInvoiceController = null; // Nueva
    private static ?StripeSubscriptionManagementServiceInterface $stripeSubscriptionManagementService = null;
    private static ?StripeSubscriptionControllerInterface $stripeSubscriptionController = null;



    public static function initialize(string $projectRootPath): void
    {
        if (self::$initialized) {
            return;
        }

        try {
            if (file_exists($projectRootPath . '/.env')) {
                $dotenv = Dotenv::createImmutable($projectRootPath);
                $dotenv->load();

                EventLogger::log("Bootstrap: .env cargado.");
            } else {
                EventLogger::log("Bootstrap: .env file not found at {$projectRootPath}. Relying on server environment variables.", [], '[INFO]');
            }
        } catch (\Throwable $e) { // Captura cualquier error de Dotenv
            ErrorLogger::log("Bootstrap: Error al intentar cargar .env: " . $e->getMessage(), ['exception' => get_class($e)], '[CRITICAL_CONFIG]');

        }


        define('STRIPE_PUBLISHABLE_KEY', $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '');
        define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY'] ?? '');
        define('STRIPE_WEBHOOK_SECRET', $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '');
        define('APP_DOMAIN', $_ENV['APP_DOMAIN'] ?? 'http://localhost:8000');

        EventLogger::log("Bootstrap: Inicializado. Constantes definidas.");

        self::$displayablePlans = [
            StripeProductsTypeEnum::MONTHLY_SUBSCRIPTION->value => [
                'name' => 'Suscripción Mensual',
                'description' => 'Acceso completo con pago mensual',
                'price' => $_ENV['PRICE_DISPLAY_MONTHLY'] ?? '3,00 €',
                'period' => 'mes',
                'highlight' => false,
                'lookup_key' => $_ENV['STRIPE_PRICE_LOOKUP_KEY_MONTHLY'] ?? 'monthly_lookup_key_default',
                'features' => [
                    'Acceso a todas las funcionalidades', 'Soporte técnico estándar',
                    'Actualizaciones mensuales', 'Hasta 3 proyectos'
                ]
            ],
            StripeProductsTypeEnum::YEARLY_SUBSCRIPTION->value => [
                'name' => 'Suscripción Anual',
                'description' => 'Acceso completo con pago anual (ahorro del 20%)',
                'price' => $_ENV['PRICE_DISPLAY_YEARLY'] ?? '15,00 €',
                'period' => 'año',
                'highlight' => true,
                'lookup_key' => $_ENV['STRIPE_PRICE_LOOKUP_KEY_YEARLY'] ?? 'annual_lookup_key_default',
                'features' => [
                    'Acceso a todas las funcionalidades', 'Soporte técnico prioritario',
                    'Actualizaciones en primicia', 'Proyectos ilimitados', '2 meses gratis'
                ]
            ],
            StripeProductsTypeEnum::ONE_PAYMENT->value => [
                'name' => 'Acceso Estándar',
                'description' => 'Acceso completo a todas las funcionalidades con un pago único. Ideal para probar nuestra plataforma.',
                'price' => $_ENV['PRICE_DISPLAY_ONE_TIME'] ?? '10,00 €',
                'period' => 'único',
                'lookup_key' => $_ENV['STRIPE_PRICE_LOOKUP_KEY_ONE_TIME'] ?? 'one_time_lookup_key_default',
                'features' => ['Todas las funcionalidades base', 'Soporte por email']
            ]
        ];
        self::$initialized = true;
    }

    public static function getDisplayPlans(): array
    {
        if (!self::$initialized) {
            ErrorLogger::log("Bootstrap: getDisplayPlans() llamado antes de initialize().", [], '[WARNING]');
        }
        return self::$displayablePlans;
    }

    private static function getPdo(): PDO
    {

        return self::$pdo ??= DatabaseConnection::getInstance();
    }

    // --- MAPPERS ---
    private static function getCheckoutSessionMapper(): CheckoutSessionMapper {
        return self::$checkoutSessionMapper ??= new CheckoutSessionMapper();
    }
    private static function getCustomerMapper(): CustomerMapper {
        return self::$customerMapper ??= new CustomerMapper();
    }
    private static function getPaymentIntentMapper(): PaymentIntentMapper {
        return self::$paymentIntentMapper ??= new PaymentIntentMapper();
    }
    private static function getChargeMapper(): ChargeMapper {
        return self::$chargeMapper ??= new ChargeMapper();
    }
    private static function getSubscriptionMapper(): SubscriptionMapper {
        return self::$subscriptionMapper ??= new SubscriptionMapper();
    }
    private static function getInvoiceMapper(): InvoiceMapper {
        return self::$invoiceMapper ??= new InvoiceMapper();
    }

    // --- FACTORIES ---
    private static function getTransactionFactory(): TransactionModelFactory {
        return self::$transactionFactory ??= new TransactionModelFactory();
    }
    private static function getSubscriptionFactory(): SubscriptionModelFactory {
        return self::$subscriptionFactory ??= new SubscriptionModelFactory();
    }

    // --- REPOSITORIES ---
    private static function getTransactionRepository(): TransactionRepositoryInterface {
        return self::$transactionRepository ??= new TransactionRepositoryImpl(self::getPdo());
    }
    private static function getSubscriptionRepository(): SubscriptionRepositoryInterface {
        return self::$subscriptionRepository ??= new SubscriptionRepositoryImpl(self::getPdo());
    }
    // --- GETTER PARA InvoiceRepository ---
    private static function getInvoiceRepository(): InvoiceRepositoryInterface {
        return self::$invoiceRepository ??= new InvoiceRepositoryImpl(self::getPdo());
    }

    public static function getStripeSubscriptionManagementService(): ?StripeSubscriptionManagementServiceInterface {
        if (self::$stripeSubscriptionManagementService === null) {
            $stripeClient = self::getGlobalStripeClient();
            if (!$stripeClient) {
                ErrorLogger::log("Bootstrap: StripeClient no disponible para StripeSubscriptionManagementService.", [], '[CRITICAL_SERVICE_UNAVAILABLE]');
                return null;
            }
            self::$stripeSubscriptionManagementService = new StripeSubscriptionManagementServiceImpl($stripeClient);
            EventLogger::log("Bootstrap: StripeSubscriptionManagementService instanciado.");
        }
        return self::$stripeSubscriptionManagementService;
    }

    // --- STRIPE CLIENT (Global, para estrategias que lo necesiten) ---
    private static function getGlobalStripeClient(): ?StripeClient {
        if (self::$stripeClientGlobal === null) {
            // Usar la constante definida en initialize()
            $apiKey = defined('STRIPE_SECRET_KEY') ? STRIPE_SECRET_KEY : ($_ENV['STRIPE_SECRET_KEY'] ?? null);
            if (empty($apiKey)) {
                ErrorLogger::log("Bootstrap: STRIPE_SECRET_KEY no definida para GlobalStripeClient.", [], '[CRITICAL_CONFIG]');
                return null;
            }
            try {
                self::$stripeClientGlobal = new StripeClient($apiKey);
                EventLogger::log("Bootstrap: GlobalStripeClient instanciado.");
            } catch (StripeAuthenticationException $e) { // Ser específico con la excepción de Stripe
                ErrorLogger::exception($e, ['key_used_first_chars' => substr($apiKey, 0, 8)], '[CRITICAL_STRIPE_AUTH]');
                return null;
            } catch (\Throwable $e) {
                ErrorLogger::exception($e, [], '[ERROR_GLOBAL_STRIPE_CLIENT_INIT]');
                return null;
            }
        }
        return self::$stripeClientGlobal;
    }

    /** @return StripeWebhookStrategyInterface[] */
    private static function getStripeStrategies(): array {
        if (self::$stripeStrategies === null) {
            $apiKeyForStrategies = defined('STRIPE_SECRET_KEY') ? STRIPE_SECRET_KEY : ($_ENV['STRIPE_SECRET_KEY'] ?? null);

            self::$stripeStrategies = [
                new CheckoutSessionCompletedStrategyImpl(
                    self::getCheckoutSessionMapper(), self::getTransactionFactory(),
                    self::getTransactionRepository(), self::getSubscriptionRepository()
                ),
                new CustomerCreatedOrUpdatedStrategyImpl(
                    self::getCustomerMapper(), self::getSubscriptionRepository()
                ),
                new PaymentIntentSucceededStrategyImpl(
                    self::getPaymentIntentMapper(), self::getTransactionFactory(),
                    self::getTransactionRepository(), self::getChargeMapper(), $apiKeyForStrategies
                ),
                new ChargeSucceededStrategyImpl(
                    self::getChargeMapper(), self::getTransactionRepository()
                ),
                new SubscriptionCreatedStrategyImpl(
                    self::getSubscriptionMapper(), self::getSubscriptionFactory(),
                    self::getSubscriptionRepository(), self::getCustomerMapper(), $apiKeyForStrategies
                ),
                new SubscriptionUpdatedStrategyImpl(
                    self::getSubscriptionMapper(), self::getSubscriptionFactory(),
                    self::getSubscriptionRepository(), self::getCustomerMapper(), $apiKeyForStrategies
                ),
                new SubscriptionDeletedStrategyImpl(
                    self::getSubscriptionMapper(), self::getSubscriptionRepository(), self::getSubscriptionFactory()
                ),
                new InvoicePaidStrategyImpl(
                    self::getInvoiceMapper(), self::getTransactionFactory(),
                    self::getTransactionRepository(), self::getSubscriptionRepository()
                ),
            ];
            EventLogger::log("Bootstrap: Todas las estrategias (" . count(self::$stripeStrategies) . ") instanciadas.");
        }
        return self::$stripeStrategies;
    }

    // --- SERVICES ---

    /**
     * @throws ConfigurationException
     */
    private static function getStripeWebhookService(): ?StripeWebhookServiceInterface {
        if (self::$stripeWebhookService === null) {
            $webhookSecret = defined('STRIPE_WEBHOOK_SECRET') ? STRIPE_WEBHOOK_SECRET : ($_ENV['STRIPE_WEBHOOK_SECRET'] ?? null);
            if (empty($webhookSecret)) {
                ErrorLogger::log("Bootstrap: STRIPE_WEBHOOK_SECRET no definido o vacío.", [], '[CRITICAL_CONFIG]');
                return null;
            }
            self::$stripeWebhookService = new StripeWebhookServiceImpl(
                $webhookSecret, self::getStripeStrategies()
            );
            EventLogger::log("Bootstrap: StripeWebhookService instanciado.");
        }
        return self::$stripeWebhookService;
    }

    /**
     * @throws ConfigurationException
     */
    public static function getStripeCheckoutService(): ?StripeCheckoutServiceInterface {
        if (self::$stripeCheckoutService === null) {
            $apiKey = defined('STRIPE_SECRET_KEY') ? STRIPE_SECRET_KEY : ($_ENV['STRIPE_SECRET_KEY'] ?? null);
            $appDomain = defined('APP_DOMAIN') ? APP_DOMAIN : ($_ENV['APP_DOMAIN'] ?? null);

            if (empty($apiKey)) {
                ErrorLogger::log("Bootstrap: STRIPE_SECRET_KEY no disponible para CheckoutService.", [], '[CRITICAL_CONFIG]');
                return null;
            }
            if (empty($appDomain) || !filter_var($appDomain, FILTER_VALIDATE_URL)) {
                ErrorLogger::log("Bootstrap: APP_DOMAIN no configurado o inválido para CheckoutService.", ['app_domain' => $appDomain ?? 'indefinido'], '[CRITICAL_CONFIG]');
                return null;
            }

            self::$stripeCheckoutService = new StripeCheckoutSessionServiceImpl(
                $apiKey,
                $appDomain
            );
            EventLogger::log("Bootstrap: StripeCheckoutSessionService instanciado.");
        }
        return self::$stripeCheckoutService;
    }

    // --- CONTROLLERS ---
    public static function getStripeWebhookController(): ?StripeWebhookControllerInterface {
        if (self::$stripeWebhookController === null) {
            $webhookService = self::getStripeWebhookService();
            if (!$webhookService) {
                ErrorLogger::log("Bootstrap: WebhookService no disponible para Controller.", [], '[CRITICAL_SERVICE_UNAVAILABLE]');
                return null;
            }
            self::$stripeWebhookController = new StripeWebhookControllerImpl($webhookService);
            EventLogger::log("Bootstrap: StripeWebhookController instanciado.");
        }
        return self::$stripeWebhookController;
    }

    public static function getStripeInvoiceController(): ?StripeInvoiceControllerInterface {
        if (self::$stripeInvoiceController === null) {
            $invoiceRepo = self::getInvoiceRepository();
            if (!$invoiceRepo) {
                ErrorLogger::log("Bootstrap: InvoiceRepository no disponible para StripeInvoiceController.", [], '[CRITICAL_SERVICE_UNAVAILABLE]');
                return null;
            }
            self::$stripeInvoiceController = new StripeInvoiceControllerImpl($invoiceRepo);
            EventLogger::log("Bootstrap: StripeInvoiceController instanciado.");
        }
        return self::$stripeInvoiceController;
    }

    // --- GETTER PÚBLICO PARA StripeSubscriptionController ---
    public static function getStripeSubscriptionController(): ?StripeSubscriptionControllerInterface {
        if (self::$stripeSubscriptionController === null) {
            $subscriptionRepo = self::getSubscriptionRepository();
            $subscriptionMgmtService = self::getStripeSubscriptionManagementService();

            if (!$subscriptionRepo || !$subscriptionMgmtService) {
                ErrorLogger::log("Bootstrap: Dependencias no disponibles para StripeSubscriptionController.", [
                    'repo_available' => (bool)$subscriptionRepo,
                    'mgmt_service_available' => (bool)$subscriptionMgmtService
                ], '[CRITICAL]');
                return null;
            }
            self::$stripeSubscriptionController = new StripeSubscriptionControllerImpl(
                $subscriptionRepo,
                $subscriptionMgmtService
            );
            EventLogger::log("Bootstrap: StripeSubscriptionController instanciado.");
        }
        return self::$stripeSubscriptionController;
    }
}