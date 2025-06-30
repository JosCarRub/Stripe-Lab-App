<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__, 3));
}

require_once PROJECT_ROOT . '/vendor/autoload.php';
\config\Bootstrap::initialize(PROJECT_ROOT);

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$response = ['success' => false, 'error' => 'Acción no válida'];

try {
    $pdo = \config\DatabaseConnection::getInstance();
    $dbName = $_ENV['DB_DATABASE'];

    switch ($action) {
        case 'get_initial_data':
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            $paymentsCount = $pdo->query("SHOW TABLES LIKE 'StripeTransactions'")->rowCount() > 0 ? (int)$pdo->query("SELECT COUNT(*) FROM StripeTransactions")->fetchColumn() : 0;
            $subscriptionsCount = $pdo->query("SHOW TABLES LIKE 'StripeSubscriptions'")->rowCount() > 0 ? (int)$pdo->query("SELECT COUNT(*) FROM StripeSubscriptions")->fetchColumn() : 0;

            $stats = [
                'tables' => count($tables),
                'fields' => (int)$pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = '{$dbName}'")->fetchColumn(),
                'payments' => $paymentsCount,
                'subscriptions' => $subscriptionsCount
            ];
            $dbConfig = [
                'host' => $_ENV['DB_HOST'],
                'port' => $_ENV['DB_PORT'],
                'database' => $_ENV['DB_DATABASE'],
                'username' => $_ENV['DB_USERNAME']
            ];
            $response = [
                'success' => true,
                'connectionStatus' => 'connected',
                'dbConfig' => $dbConfig,
                'tables' => $tables,
                'stats' => $stats,
                'relationships' => []
            ];
            break;

        case 'get_table_structure':
            if (!isset($_GET['table']) || trim($_GET['table']) === '') {
                $response = ['success' => false, 'error' => 'Nombre de tabla no proporcionado'];
                break;
            }
            $tableName = $_GET['table'];
            $stmt = $pdo->prepare("DESCRIBE `" . $tableName . "`");
            $stmt->execute();
            $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = ['success' => true, 'structure' => $structure];
            break;

        case 'get_table_data':
            if (!isset($_GET['table']) || trim($_GET['table']) === '') {
                $response = ['success' => false, 'error' => 'Nombre de tabla no proporcionado'];
                break;
            }
            $tableName = $_GET['table'];
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = 10;

            $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM `" . $tableName . "`");
            $countStmt->execute();
            $total = (int)$countStmt->fetchColumn();
            $pages = ceil($total / $perPage);
            $offset = ($page - 1) * $perPage;

            $dataStmt = $pdo->prepare("SELECT * FROM `" . $tableName . "` LIMIT :offset, :limit");
            $dataStmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $dataStmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
            $dataStmt->execute();

            $columns = [];
            for ($i = 0; $i < $dataStmt->columnCount(); $i++) {
                $columnMeta = $dataStmt->getColumnMeta($i);
                $columns[] = $columnMeta['name'];
            }
            $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
            $response = [
                'success' => true,
                'data' => ['columns' => $columns, 'rows' => $rows, 'total' => $total, 'pages' => $pages]
            ];
            break;

        default:
            $response = ['success' => false, 'error' => "Acción desconocida: $action"];
            break;
    }

} catch (\PDOException $e) {
    http_response_code(500);
    $response = ['success' => false, 'error' => 'Error de base de datos: ' . $e->getMessage()];
} catch (\Throwable $e) {
    http_response_code(500);
    $response = ['success' => false, 'error' => 'Error inesperado en el servidor: ' . $e->getMessage()];
}

echo json_encode($response);
?>