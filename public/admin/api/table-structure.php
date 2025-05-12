<?php
/**
 * Endpoint para obtener la estructura de una tabla específica usando PDO
 * Ubicación: /public/admin/api/table-structure.php
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

    // Validar que la tabla existe para evitar inyección SQL
    $stmt = $pdo->prepare("SHOW TABLES LIKE :tableName");
    $stmt->execute(['tableName' => $tableName]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'La tabla especificada no existe']);
        exit();
    }

    // Escapar adecuadamente el nombre de la tabla
    $safeTableName = '`' . str_replace('`', '``', $tableName) . '`';

    // Obtener la estructura de la tabla
    $describeSql = "DESCRIBE {$safeTableName}";
    $stmt = $pdo->query($describeSql);
    $columns = $stmt->fetchAll();

    // Obtener información sobre las claves
    $keysSql = "SHOW KEYS FROM {$safeTableName}";
    $stmt = $pdo->query($keysSql);
    $keys = $stmt->fetchAll();

    // Procesar las claves para un formato más útil
    $keyInfo = [];
    foreach ($keys as $key) {
        $columnName = $key['Column_name'];
        $keyName = $key['Key_name'];
        $nonUnique = $key['Non_unique'];

        if (!isset($keyInfo[$columnName])) {
            $keyInfo[$columnName] = [];
        }

        if ($keyName === 'PRIMARY') {
            $keyInfo[$columnName]['PRI'] = true;
        } elseif ($nonUnique == 0) {
            $keyInfo[$columnName]['UNI'] = true;
        } else {
            $keyInfo[$columnName]['MUL'] = true;
        }
    }

    // Formatear la respuesta
    $fields = [];
    foreach ($columns as $column) {
        $key = '';
        if (isset($keyInfo[$column['Field']])) {
            if (isset($keyInfo[$column['Field']]['PRI'])) {
                $key = 'PRI';
            } elseif (isset($keyInfo[$column['Field']]['UNI'])) {
                $key = 'UNI';
            } elseif (isset($keyInfo[$column['Field']]['MUL'])) {
                $key = 'MUL';
            }
        }

        $fields[] = [
            'name' => $column['Field'],
            'type' => $column['Type'],
            'nullable' => $column['Null'] === 'YES' ? 'YES' : 'NO',
            'key' => $key,
            'default' => $column['Default'],
            'extra' => $column['Extra']
        ];
    }

    echo json_encode([
        'table' => $tableName,
        'fields' => $fields
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}