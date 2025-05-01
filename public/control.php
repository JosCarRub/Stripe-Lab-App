<?php
// Evitar problemas de caché
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: application/json');

// Configuración de la base de datos con tus credenciales
$dbConfig = [
    'host' => '127.0.0.1',  // o la IP/dominio de tu servidor MySQL
    'user' => 'test_user',
    'password' => 'password',
    'dbname' => 'stripe_lab',
    'port' => 3307          // Puerto específico que indicaste
];

// Variable para almacenar respuesta
$response = ['success' => false];

// Verificar que se ha solicitado una acción
if (!isset($_POST['action']) && !isset($_GET['action'])) {
    $response['error'] = 'No se ha especificado ninguna acción';
    echo json_encode($response);
    exit;
}

// Obtener la acción solicitada
$action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'];

// Realizar la acción correspondiente
switch ($action) {
    case 'checkDbConnection':
        checkDbConnection();
        break;
    case 'getAvailableTables':
        getAvailableTables();
        break;
    case 'getTableData':
        $tableName = isset($_POST['tableName']) ? $_POST['tableName'] : (isset($_GET['tableName']) ? $_GET['tableName'] : '');
        if ($tableName) {
            getTableData($tableName);
        } else {
            $response['error'] = 'No se ha especificado una tabla';
            echo json_encode($response);
        }
        break;
    case 'exportTable':
        $tableName = isset($_GET['tableName']) ? $_GET['tableName'] : '';
        if ($tableName) {
            exportTable($tableName);
        } else {
            $response['error'] = 'No se ha especificado una tabla para exportar';
            echo json_encode($response);
        }
        break;
    default:
        $response['error'] = 'Acción no reconocida';
        echo json_encode($response);
        break;
}

// Función para verificar la conexión a la base de datos
function checkDbConnection() {
    global $dbConfig, $response;

    try {
        $conn = createDbConnection();

        if ($conn) {
            // Obtener información del servidor
            $serverInfo = $conn->getAttribute(PDO::ATTR_SERVER_INFO);
            $serverVersion = $conn->getAttribute(PDO::ATTR_SERVER_VERSION);

            $response = [
                'success' => true,
                'connected' => true,
                'dbName' => $dbConfig['dbname'],
                'dbHost' => $dbConfig['host'] . ':' . $dbConfig['port'],
                'dbUser' => $dbConfig['user'],
                'serverInfo' => $serverInfo,
                'serverVersion' => $serverVersion,
                'lastConnection' => date('Y-m-d H:i:s')
            ];
        }
    } catch (PDOException $e) {
        $response = [
            'success' => false,
            'connected' => false,
            'error' => $e->getMessage()
        ];
    }

    echo json_encode($response);
}

// Función para obtener las tablas disponibles
function getAvailableTables() {
    global $response;

    try {
        $conn = createDbConnection();

        if (!$conn) {
            throw new Exception('No se pudo establecer conexión con la base de datos');
        }

        // Obtener el nombre de la base de datos actual
        $dbName = $conn->query('SELECT DATABASE()')->fetchColumn();

        // Consultar las tablas disponibles
        $stmt = $conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Obtener estadísticas básicas
        $stats = [
            'tablesCount' => count($tables),
            'lastUpdate' => date('Y-m-d H:i:s')
        ];

        $response = [
            'success' => true,
            'tables' => $tables,
            'stats' => $stats
        ];
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }

    echo json_encode($response);
}

// Función para obtener los datos de una tabla
function getTableData($tableName) {
    global $response;

    try {
        $conn = createDbConnection();

        if (!$conn) {
            throw new Exception('No se pudo establecer conexión con la base de datos');
        }

        // Validar el nombre de la tabla para prevenir inyección SQL
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
            throw new Exception('Nombre de tabla inválido');
        }

        // Obtener las columnas de la tabla
        $stmt = $conn->query("SHOW COLUMNS FROM `$tableName`");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Obtener los datos de la tabla (limitados a 100 filas para evitar sobrecarga)
        $stmt = $conn->query("SELECT * FROM `$tableName` LIMIT 100");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Contar el total de filas
        $totalRowsStmt = $conn->query("SELECT COUNT(*) FROM `$tableName`");
        $totalRows = $totalRowsStmt->fetchColumn();

        $response = [
            'success' => true,
            'columns' => $columns,
            'rows' => $rows,
            'tableName' => $tableName,
            'totalRows' => $totalRows,
            'shownRows' => count($rows)
        ];

    } catch (Exception $e) {
        $response = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }

    echo json_encode($response);
}

// Función para exportar una tabla a CSV
function exportTable($tableName) {
    try {
        $conn = createDbConnection();

        if (!$conn) {
            throw new Exception('No se pudo establecer conexión con la base de datos');
        }

        // Validar el nombre de la tabla para prevenir inyección SQL
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
            throw new Exception('Nombre de tabla inválido');
        }

        // Obtener las columnas de la tabla
        $stmt = $conn->query("SHOW COLUMNS FROM `$tableName`");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Obtener los datos de la tabla
        $stmt = $conn->query("SELECT * FROM `$tableName`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Crear el archivo CSV
        $filename = $tableName . '_export_' . date('Y-m-d_H-i-s') . '.csv';

        // Establecer encabezados para la descarga
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        // Crear el archivo CSV
        $output = fopen('php://output', 'w');

        // Escribir los encabezados
        fputcsv($output, $columns);

        // Escribir los datos
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        // Cerrar el archivo
        fclose($output);

        // Como hemos enviado directamente el archivo, no necesitamos enviar una respuesta JSON
        exit;
    } catch (Exception $e) {
        // Devolver error en formato JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// Función para crear una conexión a la base de datos
function createDbConnection() {
    global $dbConfig;

    try {
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
        $conn = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        return $conn;
    } catch (PDOException $e) {
        return false;
    }
}
?>