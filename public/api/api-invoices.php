<?php
declare(strict_types=1);

// Temporalmente para depurar:
// error_reporting(E_ALL);
// ini_set('display_errors', '1');

if (!defined('PROJECT_ROOT')) {
    // SI api-invoices.php está en public/api/
    define('PROJECT_ROOT', dirname(__DIR__, 2));
    // SI api-invoices.php está en public/v1/api/
    // define('PROJECT_ROOT', dirname(__DIR__, 3)); // <-- ESTO PARECE INCORRECTO si es public/api/
}
require_once PROJECT_ROOT . '/vendor/autoload.php';

// Es crucial que config\Bootstrap y config\DatabaseConnection se puedan cargar.
// Si están en project-root/config/ y tu PSR-4 para 'config' es 'config/', el autoloader debería funcionar.
// Si no, los require_once explícitos son necesarios ANTES de \config\Bootstrap::initialize.
// require_once PROJECT_ROOT . '/config/Bootstrap.php';
// require_once PROJECT_ROOT . '/config/DatabaseConnection.php';

\config\Bootstrap::initialize(PROJECT_ROOT);

header('Content-Type: application/json');

// Prueba simple:
// echo json_encode(['status' => 'ok', 'message' => 'API endpoint for invoices reached']);
// exit;

use App\Commons\Loggers\ErrorLogger;
use App\Commons\Loggers\EventLogger;

$invoiceController = \config\Bootstrap::getStripeInvoiceController();

if (!$invoiceController) {
    // ErrorLogger::log("api/api-invoices.php: StripeInvoiceController no disponible.", [], '[FATAL]'); // Ya lo tienes
    http_response_code(503);
    echo json_encode(['error' => 'Servicio de facturas no disponible (controlador nulo).']);
    exit;
}

$action = $_GET['action'] ?? 'list_all';
$customerId = $_GET['customer_id'] ?? null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

$response = ['error' => 'Acción no válida o parámetros incorrectos en API.'];
$statusCode = 400;

try {
    if ($action === 'list_all') {
        EventLogger::log("API Invoices: Solicitud para listar todas las facturas.", ['page' => $page, 'limit' => $limit]);
        $response = $invoiceController->listAllInvoices($page, $limit);
        $statusCode = 200;
    } elseif ($action === 'list_customer' && $customerId) {
        EventLogger::log("API Invoices: Solicitud para facturas del cliente.", ['customer_id' => $customerId, 'page' => $page, 'limit' => $limit]);
        $response = $invoiceController->listCustomerInvoices($customerId, $page, $limit);
        $statusCode = 200;
    } elseif ($action === 'list_customer' && !$customerId) {
        $response = ['error' => 'Parámetro customer_id requerido para la acción list_customer.'];
        ErrorLogger::log("API Invoices: Falta customer_id para list_customer.", $_GET, '[BAD_REQUEST]');
        // statusCode ya es 400
    }
} catch (\Throwable $e) {
    ErrorLogger::exception($e, ['action' => $action, 'customer_id' => $customerId, 'api_script' => basename(__FILE__)]);
    $response = ['error' => 'Ocurrió un error interno al procesar la solicitud de facturas. Consulte los logs.'];
    $statusCode = 500;
}

http_response_code($statusCode);
echo json_encode($response);
?>