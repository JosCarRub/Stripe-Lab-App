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

// Usar Loggers y Excepciones
use App\Commons\Loggers\ErrorLogger;
use App\Commons\Loggers\EventLogger;
use App\Commons\Exceptions\NotFoundException; // Para capturar del servicio de gestión

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Método no permitido. Se esperaba POST.']);
    exit;
}

$managementService = \config\Bootstrap::getStripeSubscriptionManagementService();

if (!$managementService) {
    ErrorLogger::log("api/api-manage-subscriptions.php: StripeSubscriptionManagementService no disponible.", [], '[FATAL]');
    http_response_code(503); // Service Unavailable
    echo json_encode(['error' => 'Servicio de gestión de suscripciones no disponible.']);
    exit;
}

// Obtener datos del cuerpo de la solicitud JSON
$requestData = json_decode(file_get_contents('php://input'), true);

$action = $requestData['action'] ?? null;
$subscriptionId = $requestData['subscription_id'] ?? null;

if (empty($action) || empty($subscriptionId)) {
    ErrorLogger::log("api/api-manage-subscriptions.php: Faltan parámetros 'action' o 'subscription_id'.", ['request_data' => $requestData], '[BAD_REQUEST]');
    http_response_code(400);
    echo json_encode(['error' => "Parámetros 'action' y 'subscription_id' requeridos."]);
    exit;
}

EventLogger::log("API [api-manage-subscriptions.php]: Solicitud de gestión recibida.", ['action' => $action, 'subscription_id' => $subscriptionId]);
$response = [];
$statusCode = 500; // Default a error

try {
    $updatedStripeSubscriptionObject = null; // Para almacenar el objeto Subscription devuelto por Stripe
    $successMessage = '';

    switch ($action) {
        case 'cancel_now':
            $updatedStripeSubscriptionObject = $managementService->cancelSubscriptionNow($subscriptionId);
            $successMessage = "Suscripción {$subscriptionId} cancelada inmediatamente.";
            $statusCode = 200;
            break;
        case 'cancel_at_period_end':
            $updatedStripeSubscriptionObject = $managementService->cancelSubscriptionAtPeriodEnd($subscriptionId);
            $successMessage = "Suscripción {$subscriptionId} programada para cancelación al final del periodo.";
            $statusCode = 200;
            break;
        // Podrías añadir más acciones aquí, como 'reactivate'
        default:
            $response['error'] = "Acción '{$action}' no válida.";
            $statusCode = 400;
            ErrorLogger::log("API [api-manage-subscriptions.php]: Acción no válida.", ['action' => $action], '[BAD_REQUEST]');
            break;
    }

    if ($statusCode === 200 && $updatedStripeSubscriptionObject) {
        $response['success'] = true;
        $response['message'] = $successMessage;
        // Devolver detalles clave del objeto Subscription de Stripe puede ser útil para el frontend
        $response['stripe_subscription_details'] = [
            'id' => $updatedStripeSubscriptionObject->id,
            'status' => $updatedStripeSubscriptionObject->status,
            'cancel_at_period_end' => $updatedStripeSubscriptionObject->cancel_at_period_end,
            'canceled_at' => $updatedStripeSubscriptionObject->canceled_at,
            'current_period_end' => $updatedStripeSubscriptionObject->current_period_end,
            // Puedes añadir más campos si son útiles para la UI inmediatamente después de la acción
        ];
    } elseif ($statusCode === 400 && !isset($response['error'])) { // Si la acción no era válida pero no se estableció error
        $response['error'] = "Acción '{$action}' no procesada.";
    }


} catch (NotFoundException $e) {
    ErrorLogger::exception($e, ['action' => $action, 'subscription_id' => $subscriptionId]);
    $response = ['success' => false, 'error' => $e->getMessage()];
    $statusCode = 404;
} catch (\Stripe\Exception\ApiErrorException $e) {
    ErrorLogger::exception($e, ['action' => $action, 'subscription_id' => $subscriptionId], '[STRIPE_API_ERROR]');
    $response = ['success' => false, 'error' => 'Error de Stripe: ' . $e->getMessage()];
    $statusCode = $e->getHttpStatus() ?: 500;
} catch (\Throwable $e) {
    ErrorLogger::exception($e, ['action' => $action, 'subscription_id' => $subscriptionId], '[UNEXPECTED_ERROR]');
    $response = ['success' => false, 'error' => 'Ocurrió un error inesperado al gestionar la suscripción.'];
    $statusCode = 500;
}

http_response_code($statusCode);
echo json_encode($response);
?>