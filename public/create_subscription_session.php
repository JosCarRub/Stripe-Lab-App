<?php
require_once __DIR__ . '/../vendor/autoload.php';

use config\Bootstrap;

header('Content-Type: application/json');

// Configuración de logging
$logFile = __DIR__ . '/../logs/subscription_session.log';
function logError($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

try {
    // Verificar que el lookup_key esté presente
    if (!isset($_GET['lookup_key']) || empty($_GET['lookup_key'])) {
        throw new Exception('Missing lookup_key parameter');
    }

    $lookup_key = $_GET['lookup_key'];
    logError("Iniciando creación de sesión de suscripción con lookup_key: $lookup_key");

    // Carga las variables de entorno
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
    logError("Variables de entorno cargadas correctamente");

    // Obtiene el servicio
    logError("Obteniendo servicio de pagos Stripe");
    $stripePaymentService = Bootstrap::getStripePaymentService();
    logError("Servicio obtenido correctamente");

    // Crea la sesión de suscripción
    logError("Llamando a createSubscriptionSession() con lookup_key: $lookup_key");
    $session = $stripePaymentService->createSubscriptionSession($lookup_key);
    logError("Sesión de suscripción creada exitosamente con ID: " . $session->id);

    echo json_encode(['id' => $session->id]);
} catch (Exception $e) {
    logError("ERROR: " . $e->getMessage());
    logError("Traza: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}