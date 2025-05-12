<?php
/**
 * Endpoint para probar la conexión a la base de datos usando PDO
 * Ubicación: /public/admin/api/db-connection.php
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Si es una solicitud OPTIONS (preflight), responder con 200 OK
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Método no permitido. Use POST']);
    exit();
}

// Obtener el cuerpo de la solicitud
$requestBody = file_get_contents('php://input');
$requestData = json_decode($requestBody, true);

// Si hay datos de configuración en la solicitud, usarlos
// De lo contrario, cargar del archivo .env
if ($requestData &&
    isset($requestData['host']) &&
    isset($requestData['port']) &&
    isset($requestData['database']) &&
    isset($requestData['username'])) {

    // Usar la configuración proporcionada en la solicitud
    $host = $requestData['host'];
    $port = $requestData['port'];
    $database = $requestData['database'];
    $username = $requestData['username'];
    $password = $requestData['password'] ?? '';

} else {
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
        // Si no se encuentra el archivo .env, enviar error
        http_response_code(500);
        echo json_encode(['connected' => false, 'error' => 'No se pudo cargar la configuración de la base de datos']);
        exit();
    }

    // Obtener valores del .env
    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $database = $env['DB_DATABASE'] ?? '';
    $username = $env['DB_USERNAME'] ?? '';
    $password = $env['DB_PASSWORD'] ?? '';
}

// Intentar conectar a la base de datos
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

    // Verificar si podemos obtener información del servidor
    $serverInfo = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

    // Obtener el listado de tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Si llegamos aquí, la conexión fue exitosa
    $response = [
        'connected' => true,
        'message' => 'Conexión exitosa a la base de datos',
        'server_info' => $serverInfo,
        'tables' => $tables
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    // Error en la conexión
    http_response_code(500);
    echo json_encode([
        'connected' => false,
        'error' => 'Error de conexión: ' . $e->getMessage(),
        'config' => [
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => '******' // No mostrar la contraseña real
        ]
    ]);
}