<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use config\Bootstrap;
use App\controllers\StripeInvoiceController;

// Configuración de cabeceras para API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar solicitudes OPTIONS (para CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar que sea una solicitud GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener la acción a realizar
$action = $_GET['action'] ?? '';

// Obtener el controlador
$invoiceController = Bootstrap::getStripeInvoiceController();

switch ($action) {
    case 'all':
        // Obtener todas las facturas
        $invoices = $invoiceController->getAllInvoices();
        echo json_encode(['success' => true, 'data' => $invoices]);
        break;

    case 'customer':
        // Verificar que se ha proporcionado un ID de cliente
        if (!isset($_GET['customer_id']) || empty($_GET['customer_id'])) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'Se requiere un ID de cliente']);
            exit;
        }

        // Obtener facturas del cliente
        $customerId = $_GET['customer_id'];
        $invoices = $invoiceController->getCustomerInvoices($customerId);
        echo json_encode(['success' => true, 'data' => $invoices]);
        break;

    default:
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Acción no válida']);
        break;
}