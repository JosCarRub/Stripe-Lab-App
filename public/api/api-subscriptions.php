<?php
declare(strict_types=1);

// 1. Definir PROJECT_ROOT
if (!defined('PROJECT_ROOT')) {
    // Asumiendo que este script está en public/api/
    define('PROJECT_ROOT', dirname(__DIR__, 2));
}

// 2. Cargar Autoloader
require_once PROJECT_ROOT . '/vendor/autoload.php';

// 3. Inicializar Bootstrap
\config\Bootstrap::initialize(PROJECT_ROOT);

// Usar Loggers
use App\Commons\Loggers\ErrorLogger;
use App\Commons\Loggers\EventLogger;

header('Content-Type: application/json');

// Este endpoint es para obtener datos, así que solo debería aceptar GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Método no permitido. Se esperaba GET.']);
    exit;
}

$subscriptionController = \config\Bootstrap::getStripeSubscriptionController();

if (!$subscriptionController) {
    ErrorLogger::log("api/api-subscriptions.php: StripeSubscriptionController no disponible.", [], '[FATAL]');
    http_response_code(503); // Service Unavailable
    echo json_encode(['error' => 'Servicio de suscripciones no disponible.']);
    exit;
}

$action = $_GET['action'] ?? 'list_all_system'; // Default para la vista de admin de todas las suscripciones
$customerId = $_GET['customer_id'] ?? null;
$subscriptionIdParam = $_GET['subscription_id'] ?? null; // Para obtener detalles de una específica
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; // Un límite por defecto

$response = ['error' => 'Acción no válida o parámetros incorrectos para listar suscripciones.'];
$statusCode = 400;

try {
    if ($action === 'list_all_system') {
        EventLogger::log("API [api-subscriptions.php]: Solicitud para listar TODAS las suscripciones del sistema.", ['page' => $page, 'limit' => $limit]);
        $response = $subscriptionController->listAllSubscriptions($page, $limit);
        $statusCode = 200;
    } elseif ($action === 'list_customer' && $customerId) {
        EventLogger::log("API [api-subscriptions.php]: Solicitud para suscripciones del cliente.", ['customer_id' => $customerId, 'page' => $page, 'limit' => $limit]);
        $response = $subscriptionController->listCustomerSubscriptions($customerId, $page, $limit);
        $statusCode = 200;
    } elseif ($action === 'get_details' && $subscriptionIdParam) {
        EventLogger::log("API [api-subscriptions.php]: Solicitud para detalles de suscripción.", ['subscription_id' => $subscriptionIdParam]);
        $data = $subscriptionController->getSubscriptionDetails($subscriptionIdParam);
        if ($data) {
            $response = ['data' => $data]; // Envolver en 'data' para consistencia si listAll/listCustomer lo hacen
            $statusCode = 200;
        } else {
            $response = ['error' => 'Suscripción no encontrada.'];
            $statusCode = 404;
        }
    } elseif (($action === 'list_customer') && !$customerId) {
        $response = ['error' => 'Parámetro customer_id requerido para la acción list_customer.'];
        ErrorLogger::log("API [api-subscriptions.php]: Falta customer_id para list_customer.", $_GET, '[BAD_REQUEST]');
        // statusCode ya es 400
    } elseif ($action === 'get_details' && !$subscriptionIdParam) {
        $response = ['error' => 'Parámetro subscription_id requerido para la acción get_details.'];
        ErrorLogger::log("API [api-subscriptions.php]: Falta subscription_id para get_details.", $_GET, '[BAD_REQUEST]');
        // statusCode ya es 400
    }

} catch (\Throwable $e) {
    ErrorLogger::exception($e, ['action' => $action, 'params' => $_GET, 'api_script' => basename(__FILE__)]);
    $response = ['error' => 'Ocurrió un error interno al procesar la solicitud de listado de suscripciones.'];
    $statusCode = 500;
}

http_response_code($statusCode);
echo json_encode($response);
?>