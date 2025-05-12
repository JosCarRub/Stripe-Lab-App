<?php
/**
 * Endpoint para depuración - muestra información detallada
 * Ubicación: /public/api/debug.php
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Información sobre PHP
$phpInfo = [
    'version' => phpversion(),
    'extensions' => get_loaded_extensions(),
    'pdo_drivers' => PDO::getAvailableDrivers(),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'post_max_size' => ini_get('post_max_size'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
];

// Información sobre el servidor
$serverInfo = [
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Desconocido',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Desconocido',
    'query_string' => $_SERVER['QUERY_STRING'] ?? 'Desconocido',
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'Desconocido',
    'http_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido',
];

// Intentar leer el archivo .env
$envInfo = ['status' => 'No encontrado'];
$dotenv = __DIR__ . '/../../../.env';

if (file_exists($dotenv)) {
    $envInfo['status'] = 'Encontrado';
    $envInfo['path'] = $dotenv;
    $envInfo['readable'] = is_readable($dotenv) ? 'Sí' : 'No';

    if (is_readable($dotenv)) {
        $lines = file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Solo mostrar las claves, no los valores por seguridad
        $envKeys = [];
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $envKeys[] = trim($key);
            }
        }

        $envInfo['keys'] = $envKeys;

        // Verificar específicamente las claves de BD
        $dbKeysNeeded = ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];
        $missingKeys = [];

        foreach ($dbKeysNeeded as $key) {
            if (!in_array($key, $envKeys)) {
                $missingKeys[] = $key;
            }
        }

        $envInfo['missing_db_keys'] = $missingKeys;
        $envInfo['all_db_keys_present'] = empty($missingKeys) ? 'Sí' : 'No';
    }
}

// Verificar conexión a la base de datos
$dbInfo = ['status' => 'No probado'];

// Obtener credenciales BD del .env si está disponible
if ($envInfo['status'] === 'Encontrado' && $envInfo['readable'] === 'Sí') {
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

    // Configuración de la base de datos
    $dbConfig = [
        'driver' => $env['DB_CONNECTION'] ?? 'mysql',
        'host' => $env['DB_HOST'] ?? 'localhost',
        'port' => $env['DB_PORT'] ?? '3306',
        'database' => $env['DB_DATABASE'] ?? '',
        'username' => $env['DB_USERNAME'] ?? '',
        'password' => isset($env['DB_PASSWORD']) ? '[OCULTA]' : 'No establecida'
    ];

    $dbInfo['config'] = $dbConfig;

    // Intentar conectar
    try {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s',
            $env['DB_CONNECTION'] ?? 'mysql',
            $env['DB_HOST'] ?? 'localhost',
            $env['DB_PORT'] ?? '3306',
            $env['DB_DATABASE'] ?? ''
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $pdo = new PDO($dsn, $env['DB_USERNAME'] ?? '', $env['DB_PASSWORD'] ?? '', $options);

        // Si llegamos aquí, la conexión fue exitosa
        $dbInfo['status'] = 'Conectado';
        $dbInfo['pdo_attributes'] = [
            'server_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
            'server_info' => $pdo->getAttribute(PDO::ATTR_SERVER_INFO),
            'client_version' => $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION),
            'connection_status' => $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS),
        ];

        // Obtener lista de tablas
        $stmt = $pdo->query('SHOW TABLES');
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $dbInfo['tables'] = $tables;

        // Verificar si existen las tablas específicas
        $dbInfo['has_stripe_transactions'] = in_array('StripeTransactions', $tables) ? 'Sí' : 'No';
        $dbInfo['has_stripe_subscriptions'] = in_array('StripeSubscriptions', $tables) ? 'Sí' : 'No';

    } catch (PDOException $e) {
        $dbInfo['status'] = 'Error';
        $dbInfo['error'] = $e->getMessage();
    }
}

// Construir respuesta completa
$response = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_info' => $phpInfo,
    'server_info' => $serverInfo,
    'env_info' => $envInfo,
    'db_info' => $dbInfo
];

echo json_encode($response, JSON_PRETTY_PRINT);