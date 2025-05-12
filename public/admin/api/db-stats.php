<?php
/**
 * Endpoint para obtener estadísticas generales de la base de datos usando PDO
 * Ubicación: /public/admin/api/db-stats.php
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Si es una solicitud OPTIONS (preflight), responder con 200 OK
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar que sea una solicitud GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Método no permitido. Use GET']);
    exit();
}

// Cargar el archivo .env
$dotenv = __DIR__ . '/../../../.env';

if (file_exists($dotenv)) {
    $lines = file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];

    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;

        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
                $value = substr($value, 1, -1);
            } elseif (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1) {
                $value = substr($value, 1, -1);
            }

            $env[$name] = $value;
        }
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo cargar la configuración de la base de datos']);
    exit();
}

// Obtener valores del .env
$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$database = $env['DB_DATABASE'] ?? '';
$username = $env['DB_USERNAME'] ?? '';
$password = $env['DB_PASSWORD'] ?? '';

try {
    // Crear DSN para PDO
    $dsn = "mysql:host={$host};port={$port};dbname={$database}";

    // Opciones PDO estándar
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    // Crear conexión PDO
    $pdo = new PDO($dsn, $username, $password, $options);

    // Obtener la lista de tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $totalTables = count($tables);

    // Inicializar contadores
    $totalFields = 0;
    $totalTransactions = 0;
    $totalSubscriptions = 0;

    // Para cada tabla, contar sus columnas
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
        $columns = $stmt->fetchAll();
        $totalFields += count($columns);

        // Contar transacciones si existe la tabla StripeTransactions
        if ($table === 'StripeTransactions') {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM `{$table}`");
            $result = $stmt->fetch();
            $totalTransactions = $result['total'];
        }

        // Contar suscripciones si existe la tabla StripeSubscriptions
        if ($table === 'StripeSubscriptions') {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM `{$table}`");
            $result = $stmt->fetch();
            $totalSubscriptions = $result['total'];
        }
    }

    // Preparar la respuesta
    $response = [
        'total_tables' => $totalTables,
        'total_fields' => $totalFields,
        'total_transactions' => $totalTransactions,
        'total_subscriptions' => $totalSubscriptions,
        'tables' => $tables
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}