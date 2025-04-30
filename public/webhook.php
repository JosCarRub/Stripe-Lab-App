<?php
declare(strict_types=1);

use App\controllers\Impl\StripeWebhookControllerImpl;
use App\repositories\Impl\PaymentRepositoryImpl;
use App\services\Impl\StripeWebhookServiceImpl;
use App\strategy\Impl\StripeStrategyCheckoutSessionCompleted;
use App\strategy\Impl\StripeStrategyPaymentIntentFailed;
use App\strategy\Impl\StripeStrategyPaymentIntentSucceed;
use config\DatabaseConnection;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';


    // Cargar variables de entorno
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    // Obtener instancia PDO
    $pdo = DatabaseConnection::getInstance();

    // Crear las instancias de las estrategias de Stripe
    $stripeStrategies = [
        new StripeStrategyPaymentIntentSucceed(new PaymentRepositoryImpl($pdo)),
        new StripeStrategyPaymentIntentFailed(),
        new StripeStrategyCheckoutSessionCompleted(),
    ];

// Crear el servicio de webhook
    $stripeWebhookService = new StripeWebhookServiceImpl(
        $_ENV['STRIPE_WEBHOOK_SECRET'],
        $stripeStrategies
    );

// Crear el controlador del webhook
    $stripeWebhookController = new StripeWebhookControllerImpl($stripeWebhookService);

// Obtener el payload y la cabecera de la firma desde la solicitud
    $payload = file_get_contents('php://input');
    $signatureHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Manejar la solicitud del webhook
    $stripeWebhookController->handleStripeWebhook($payload, $signatureHeader);

