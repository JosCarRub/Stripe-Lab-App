<?php
/**
 * Endpoint para obtener los datos de una tabla con paginación
 * Ubicación: /public/admin/api/table-data.php
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

// Verificar si se proporcionó el nombre de la tabla
if (!isset($_GET['table']) || empty($_GET['table'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Se requiere el parámetro "table"']);
    exit();
}

$tableName = $_GET['table'];
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Validar límites razonables
if ($page < 1) $page = 1;
if ($limit < 1) $limit = 10;
if ($limit > 100) $limit = 100; // Limitar a 100 registros por página como máximo

// Calcular el offset
$offset = ($page - 1) * $limit;

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

// Configuración de la base de datos
// Nota: Forzar 'mysql' como driver predeterminado ya que tu .env no tiene DB_CONNECTION
$dbConfig = [
    'driver' => 'mysql',  // Forzando 'mysql' como driver predeterminado
    'host' => $env['DB_HOST'] ?? '127.0.0.1',
    'port' => $env['DB_PORT'] ?? '3306',
    'database' => $env['DB_DATABASE'] ?? '',
    'username' => $env['DB_USERNAME'] ?? '',
    'password' => $env['DB_PASSWORD'] ?? ''
];

try {
    // Crear conexión
    $dsn = sprintf(
        '%s:host=%s;port=%s;dbname=%s',
        $dbConfig['driver'],
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['database']
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);

    // Validar que la tabla existe para evitar inyección SQL
    $stmt = $pdo->prepare("SHOW TABLES LIKE :tableName");
    $stmt->execute(['tableName' => $tableName]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'La tabla especificada no existe']);
        exit();
    }

    // Escapar adecuadamente el nombre de la tabla
    $tableName = str_replace('`', '``', $tableName);

    // Obtener el total de registros para la paginación
    $countSql = "SELECT COUNT(*) as total FROM `{$tableName}`";
    $stmt = $pdo->query($countSql);
    $totalResult = $stmt->fetch();
    $totalRecords = $totalResult['total'];
    $totalPages = ceil($totalRecords / $limit);

    // Obtener los datos de la tabla con paginación
    $dataSql = "SELECT * FROM `{$tableName}` LIMIT {$offset}, {$limit}";
    $stmt = $pdo->query($dataSql);
    $data = $stmt->fetchAll();

    echo json_encode([
        'table' => $tableName,
        'page' => $page,
        'limit' => $limit,
        'total_records' => $totalRecords,
        'total_pages' => $totalPages,
        'data' => $data
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}