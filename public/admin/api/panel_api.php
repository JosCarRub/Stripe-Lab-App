<?php
// Configuración de la base de datos
$dbConfig = [
    'host' => '127.0.0.1',
    'port' => 3307,
    'database' => 'stripe_lab',
    'username' => 'test_user',
    'password' => 'password'
];

// Función para conectar a la base de datos
function connectDB($config) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $pdo = new PDO($dsn, $config['username'], $config['password'], $options);

        return [
            'success' => true,
            'connection' => $pdo
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Función para obtener la estructura de una tabla
function getTableStructure($pdo, $tableName) {
    try {
        // Primero, verificar si la tabla existe
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as table_exists 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = :dbName 
            AND TABLE_NAME = :tableName
        ");

        $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
        $stmt->bindParam(':dbName', $dbName);
        $stmt->bindParam(':tableName', $tableName);
        $stmt->execute();

        $result = $stmt->fetch();
        if ((int)$result['table_exists'] === 0) {
            return [
                'success' => false,
                'error' => "La tabla '$tableName' no existe en la base de datos."
            ];
        }

        // La tabla existe, obtener su estructura
        $stmt = $pdo->prepare("DESCRIBE `" . $tableName . "`");
        $stmt->execute();
        $structure = $stmt->fetchAll();

        return [
            'success' => true,
            'structure' => $structure
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Verificar la acción solicitada
$action = isset($_GET['action']) ? $_GET['action'] : '';
$response = ['success' => false, 'error' => 'Acción no válida'];

switch ($action) {
    case 'get_table_structure':
        // Verificar el parámetro de tabla
        if (!isset($_GET['table']) || trim($_GET['table']) === '') {
            $response = ['success' => false, 'error' => 'Nombre de tabla no proporcionado'];
            break;
        }

        $tableName = $_GET['table'];
        $dbConnection = connectDB($dbConfig);

        if (!$dbConnection['success']) {
            $response = ['success' => false, 'error' => $dbConnection['error']];
            break;
        }

        $pdo = $dbConnection['connection'];
        $result = getTableStructure($pdo, $tableName);

        if ($result['success']) {
            $response = [
                'success' => true,
                'structure' => $result['structure']
            ];
        } else {
            $response = [
                'success' => false,
                'error' => $result['error']
            ];
        }
        break;

    case 'get_table_data':
        // Verificar parámetros
        if (!isset($_GET['table']) || trim($_GET['table']) === '') {
            $response = ['success' => false, 'error' => 'Nombre de tabla no proporcionado'];
            break;
        }

        $tableName = $_GET['table'];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;

        $dbConnection = connectDB($dbConfig);

        if (!$dbConnection['success']) {
            $response = ['success' => false, 'error' => $dbConnection['error']];
            break;
        }

        try {
            $pdo = $dbConnection['connection'];

            // Primero verificamos si la tabla existe
            $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
            $tableExistsStmt = $pdo->prepare("
                SELECT COUNT(*) as table_exists 
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = :dbName 
                AND TABLE_NAME = :tableName
            ");
            $tableExistsStmt->bindParam(':dbName', $dbName);
            $tableExistsStmt->bindParam(':tableName', $tableName);
            $tableExistsStmt->execute();

            $result = $tableExistsStmt->fetch();
            if ((int)$result['table_exists'] === 0) {
                $response = [
                    'success' => false,
                    'error' => "La tabla '$tableName' no existe en la base de datos."
                ];
                break;
            }

            // Obtener el total de registros
            $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM `" . $tableName . "`");
            $countStmt->execute();
            $countRow = $countStmt->fetch();
            $total = (int)$countRow['total'];
            $pages = ceil($total / $perPage);

            // Calcular el offset
            $offset = ($page - 1) * $perPage;

            // Obtener los datos
            $dataStmt = $pdo->prepare("SELECT * FROM `" . $tableName . "` LIMIT :offset, :limit");
            $dataStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $dataStmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
            $dataStmt->execute();

            // Obtener los nombres de las columnas
            $columns = [];
            $columnsCount = $dataStmt->columnCount();
            for ($i = 0; $i < $columnsCount; $i++) {
                $columnMeta = $dataStmt->getColumnMeta($i);
                $columns[] = $columnMeta['name'];
            }

            // Obtener los datos
            $rows = $dataStmt->fetchAll();

            $response = [
                'success' => true,
                'data' => [
                    'columns' => $columns,
                    'rows' => $rows,
                    'total' => $total,
                    'pages' => $pages
                ]
            ];
        } catch (PDOException $e) {
            $response = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        break;

    case 'get_tables':
        $dbConnection = connectDB($dbConfig);

        if (!$dbConnection['success']) {
            $response = ['success' => false, 'error' => $dbConnection['error']];
            break;
        }

        try {
            $pdo = $dbConnection['connection'];
            $tables = [];
            $result = $pdo->query("SHOW TABLES");

            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            $response = [
                'success' => true,
                'tables' => $tables
            ];
        } catch (PDOException $e) {
            $response = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        break;

    case 'test_connection':
        $dbConnection = connectDB($dbConfig);
        $response = [
            'success' => $dbConnection['success'],
            'error' => $dbConnection['success'] ? '' : $dbConnection['error']
        ];
        break;

    case 'update_connection':
        // Verificar los parámetros
        if (!isset($_POST['host']) || !isset($_POST['port']) || !isset($_POST['database']) ||
            !isset($_POST['username']) || !isset($_POST['password'])) {
            $response = ['success' => false, 'error' => 'Parámetros incompletos'];
            break;
        }

        $newConfig = [
            'host' => $_POST['host'],
            'port' => (int)$_POST['port'],
            'database' => $_POST['database'],
            'username' => $_POST['username'],
            'password' => $_POST['password']
        ];


        $testConnection = connectDB($newConfig);

        if (!$testConnection['success']) {
            $response = ['success' => false, 'error' => $testConnection['error']];
            break;
        }


        $response = ['success' => true];
        break;

    default:
        $response = ['success' => false, 'error' => "Acción desconocida: $action"];
        break;
}

// Devolver la respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);
?>