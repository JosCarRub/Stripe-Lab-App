<?php
declare(strict_types=1);

// 1. Definir PROJECT_ROOT
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__, 2));
}

// 2. Cargar Autoloader
require_once PROJECT_ROOT . '/vendor/autoload.php';

// 3. Inicializar Bootstrap (esto carga .env, define constantes, etc.)
\config\Bootstrap::initialize(PROJECT_ROOT);

// Usar los loggers de la aplicación
use App\Commons\Loggers\ErrorLogger;
use App\Commons\Loggers\EventLogger; // Para logs de eventos si es necesario

header('Content-Type: application/json');

try {
    EventLogger::log("Endpoint create_payment_session.php: Solicitud recibida.");

    $lookupKey = $_GET['lookup_key'] ?? null;
    if (!$lookupKey) {
        ErrorLogger::log("create_payment_session.php: Falta parámetro lookup_key.", $_GET, '[BAD_REQUEST]');
        http_response_code(400);
        echo json_encode(['error' => 'Parámetro lookup_key requerido.']);
        exit;
    }

    $checkoutService = \config\Bootstrap::getStripeCheckoutService();

    if (!$checkoutService) {

        ErrorLogger::log("create_payment_session.php: StripeCheckoutService no disponible desde Bootstrap.", [], '[CRITICAL_SYSTEM_ERROR]');
        http_response_code(503);

        echo json_encode(['error' => 'Servicio de pago no disponible temporalmente.']);
        exit;
    }

    EventLogger::log("create_payment_session.php: Llamando a createOneTimePaymentSession.", ['lookup_key' => $lookupKey]);
    $sessionId = $checkoutService->createOneTimePaymentSession($lookupKey);

    EventLogger::log("create_payment_session.php: Sesión de pago único creada.", ['session_id' => $sessionId]);
    echo json_encode(['id' => $sessionId]);
    http_response_code(200);

} catch (\Stripe\Exception\ApiErrorException $e) {

    ErrorLogger::exception($e, ['endpoint' => __FILE__, 'lookup_key' => $lookupKey ?? 'N/A'], '[STRIPE_API_ERROR_ENDPOINT]');
    http_response_code($e->getHttpStatus() ?: 500); // Usar código de Stripe o 500

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