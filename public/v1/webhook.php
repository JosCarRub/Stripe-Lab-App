<?php
declare(strict_types=1);

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__, 2)); // Asume webhook.php en public/v1/
}

// 2. Cargar el autoloader de Composer
require_once PROJECT_ROOT . '/vendor/autoload.php';

// 3. Inicializar el Bootstrap
// No es necesario require_once para config\Bootstrap si tu autoloader PSR-4 para 'config' funciona.
\config\Bootstrap::initialize(PROJECT_ROOT);

// 4. Obtener el controlador
$stripeWebhookController = \config\Bootstrap::getStripeWebhookController();

if (!$stripeWebhookController) {
    http_response_code(503);
    echo "Error interno: Servicio de webhook no disponible.";
    // Loguear este error crítico si Bootstrap no pudo crear el controlador
    if (class_exists('\App\Commons\Loggers\ErrorLogger')) { // Comprobar si el logger está disponible
        \App\Commons\Loggers\ErrorLogger::log("webhook.php: StripeWebhookController no pudo ser obtenido de Bootstrap.", [], '[FATAL]');
    } else {
        error_log("webhook.php: StripeWebhookController no pudo ser obtenido de Bootstrap Y ErrorLogger no disponible.");
    }
    exit;
}

// 5. Obtener payload y firma
$payload = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;

// 6. Manejar el webhook
$stripeWebhookController->handleStripeWebhook($payload, $signatureHeader);
?>