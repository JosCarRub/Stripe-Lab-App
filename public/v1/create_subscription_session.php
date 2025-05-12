<?php
declare(strict_types=1);

if (!defined('PROJECT_ROOT')) { define('PROJECT_ROOT', dirname(__DIR__, 2)); }
require_once PROJECT_ROOT . '/vendor/autoload.php';
\config\Bootstrap::initialize(PROJECT_ROOT);

use App\Commons\Loggers\ErrorLogger;
use App\Commons\Loggers\EventLogger;

header('Content-Type: application/json');

try {
    EventLogger::log("Endpoint create_subscription_session.php: Solicitud recibida.");

    $lookupKey = $_GET['lookup_key'] ?? null;

    if (!$lookupKey) {

        ErrorLogger::log("create_subscription_session.php: Falta parámetro lookup_key.", $_GET, '[BAD_REQUEST]');
        http_response_code(400);

        echo json_encode(['error' => 'Parámetro lookup_key requerido.']);
        exit;
    }

    $checkoutService = \config\Bootstrap::getStripeCheckoutService();
    if (!$checkoutService) {

        ErrorLogger::log("create_subscription_session.php: StripeCheckoutService no disponible.", [], '[CRITICAL_SYSTEM_ERROR]');
        http_response_code(503);

        echo json_encode(['error' => 'Servicio de pago no disponible temporalmente.']);

        exit;
    }

    EventLogger::log("create_subscription_session.php: Llamando a createSubscriptionSession.", ['lookup_key' => $lookupKey]);
    $sessionId = $checkoutService->createSubscriptionSession($lookupKey);

    EventLogger::log("create_subscription_session.php: Sesión de suscripción creada.", ['session_id' => $sessionId]);
    echo json_encode(['id' => $sessionId]);

    http_response_code(200);

} catch (\Stripe\Exception\ApiErrorException $e) {

    ErrorLogger::exception($e, ['endpoint' => __FILE__, 'lookup_key' => $lookupKey ?? 'N/A'], '[STRIPE_API_ERROR_ENDPOINT]');
    http_response_code($e->getHttpStatus() ?: 500);

    echo json_encode(['error' => 'Error de Stripe: ' . $e->getMessage()]);

} catch (\App\Commons\Exceptions\ConfigurationException $e) {

    ErrorLogger::exception($e, ['endpoint' => __FILE__, 'lookup_key' => $lookupKey ?? 'N/A']);
    http_response_code(400);

    echo json_encode(['error' => 'Error de configuración: ' . $e->getMessage()]);

} catch (\Throwable $e) {

    ErrorLogger::exception($e, ['endpoint' => __FILE__, 'lookup_key' => $lookupKey ?? 'N/A'], '[UNEXPECTED_ENDPOINT_ERROR]');
    http_response_code(500);

    echo json_encode(['error' => 'Ocurrió un error inesperado.']);
}
?>