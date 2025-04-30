<?php
declare(strict_types=1);


error_reporting(E_ALL & ~E_WARNING);

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\controllers\Impl\StripeWebhookControllerImpl;
use App\services\Impl\StripeWebhookServiceImpl;
use App\repositories\Impl\PaymentRepositoryImpl;
use App\strategy\Impl\StripeStrategyCheckoutSessionCompleted;
use App\strategy\Impl\StripeStrategyPaymentIntentFailed;
use App\strategy\Impl\StripeStrategyPaymentIntentSucceed;
use App\commons\entities\PaymentModel;
use App\commons\enums\StripeEventTypeEnum;

// Restaurar el nivel de error
error_reporting(E_ALL);

// Cargar variables de entorno
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Configuración de la base de datos
$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$database = $_ENV['DB_NAME'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];


$webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];

// Función para conectar a la base de datos
function connectDB($host, $port, $database, $username, $password) {
    try {
        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$database",
            $username,
            $password
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

// Conectar a la base de datos
$pdo = connectDB($host, $port, $database, $username, $password);

// Configurar Stripe si está disponible
if (isset($_ENV['STRIPE_SECRET_KEY'])) {
    \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
}

// Verificar y crear la tabla payments si no existe
function ensurePaymentsTableExists($pdo) {
    if (!$pdo) return false;

    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'payments'");
        $tableExists = $stmt->rowCount() > 0;

        if (!$tableExists) {
            $createTableSQL = "CREATE TABLE payments (
                id_payment VARCHAR(36) PRIMARY KEY,
                event_id VARCHAR(255) NOT NULL,
                customer_id VARCHAR(255) NOT NULL,
                payment_intent_id VARCHAR(255) NOT NULL,
                event_type VARCHAR(100) NOT NULL,
                payload TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";

            $pdo->exec($createTableSQL);
        }

        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Función para obtener los últimos eventos
function getRecentEvents($pdo, $limit = 10) {
    if (!$pdo) return [];

    try {
        $stmt = $pdo->query("SELECT * FROM payments ORDER BY created_at DESC LIMIT $limit");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Función para obtener detalles de un evento específico
function getEventDetails($pdo, $eventId) {
    if (!$pdo) return null;

    try {
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE id_payment = ?");
        $stmt->execute([$eventId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// Función para simular un evento de Stripe
function simulateStripeEvent($pdo, $eventType, $customerId = null, $paymentIntentId = null) {
    if (!$pdo) return false;

    try {
        // Crear un PaymentModel simulado
        $id_payment = uniqid('sim_', true);
        $event_id = 'evt_sim_' . time();
        $customer_id = $customerId ?? 'cus_sim_' . time();
        $payment_intent_id = $paymentIntentId ?? 'pi_sim_' . time();

        // Convertir el string a enum
        $eventTypeEnum = null;
        switch ($eventType) {
            case 'checkout.session.completed':
                $eventTypeEnum = StripeEventTypeEnum::CHECKOUT_SESSION_COMPLETED;
                break;
            case 'payment_intent.succeeded':
                $eventTypeEnum = StripeEventTypeEnum::PAYMENT_INTENT_SUCCEEDED;
                break;
            case 'payment_intent.payment_failed':
                $eventTypeEnum = StripeEventTypeEnum::PAYMENT_INTENT_FAILED;
                break;
            default:
                return false;
        }

        // Crear un payload simulado
        $payload = [
            'id' => $event_id,
            'type' => $eventType,
            'data' => [
                'object' => [
                    'id' => $payment_intent_id,
                    'customer' => $customer_id,
                    'status' => $eventType === 'payment_intent.succeeded' ? 'succeeded' : 'failed',
                    'amount' => 1999,
                    'currency' => 'usd',
                    'created' => time(),
                    'simulation' => true
                ]
            ]
        ];

        // Crear el modelo
        $paymentModel = new PaymentModel(
            $id_payment,
            $event_id,
            $customer_id,
            $payment_intent_id,
            $eventTypeEnum,
            $payload
        );

        // Guardar directamente en la base de datos
        $stmt = $pdo->prepare(
            'INSERT INTO payments (
                id_payment, event_id, customer_id, payment_intent_id, event_type, payload
            ) VALUES (
                :id_payment, :event_id, :customer_id, :payment_intent_id, :event_type, :payload
            )'
        );

        $stmt->execute([
            'id_payment' => $paymentModel->getId(),
            'event_id' => $paymentModel->getEventId(),
            'customer_id' => $paymentModel->getCustomerId(),
            'payment_intent_id' => $paymentModel->getPaymentIntentId(),
            'event_type' => $paymentModel->getEventType()->value,
            'payload' => json_encode($paymentModel->getPayload())
        ]);

        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Procesar acción de simular evento si es solicitada
$simulationResult = null;
if (isset($_POST['simulate_event']) && $pdo) {
    $eventType = $_POST['event_type'] ?? '';
    if (!empty($eventType)) {
        $customerId = $_POST['customer_id'] ?? null;
        $paymentIntentId = $_POST['payment_intent_id'] ?? null;
        $simulationResult = simulateStripeEvent($pdo, $eventType, $customerId, $paymentIntentId);
    }
}

// Verificar y crear la tabla si no existe
$tableExists = false;
if ($pdo) {
    $tableExists = ensurePaymentsTableExists($pdo);
}

// Procesar petición AJAX para obtener detalles de un evento
if (isset($_GET['api']) && $_GET['api'] === 'event-details' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $eventDetails = getEventDetails($pdo, $_GET['id']);

    if ($eventDetails) {
        // Decodificar el payload JSON para mostrarlo formateado
        $eventDetails['payload'] = json_decode($eventDetails['payload'], true);
        echo json_encode(['success' => true, 'data' => $eventDetails]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Evento no encontrado']);
    }
    exit;
}

// Definir las rutas
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Ruta para el webhook de Stripe
if ($uri === '/v1/stripe' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$pdo) {
        http_response_code(500);
        echo 'Error: No se pudo conectar a la base de datos';
        exit;
    }

    // Asegurar que la tabla existe
    ensurePaymentsTableExists($pdo);

    // Obtener el payload y la cabecera de firma
    $payload = file_get_contents('php://input');
    $signatureHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    // Si no hay cabecera de firma pero estamos en desarrollo, podríamos estar probando manualmente
    $isTestRequest = empty($signatureHeader) && isset($_SERVER['HTTP_X_TEST_REQUEST']);

    try {
        if ($isTestRequest) {
            // Procesar como una solicitud de prueba
            $data = json_decode($payload, true);
            $eventType = $data['type'] ?? '';
            $customerId = $data['data']['object']['customer'] ?? 'cus_manual_' . time();
            $paymentIntentId = $data['data']['object']['id'] ?? 'pi_manual_' . time();

            $success = simulateStripeEvent($pdo, $eventType, $customerId, $paymentIntentId);

            if ($success) {
                http_response_code(200);
                echo json_encode(['status' => 'success', 'message' => 'Evento simulado procesado correctamente']);
            } else {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Error al procesar el evento simulado']);
            }
        } else {
            // Inicializar repositorios
            $paymentRepository = new PaymentRepositoryImpl($pdo);

            // Inicializar estrategias
            $stripeStrategyPaymentIntentSucceed = new StripeStrategyPaymentIntentSucceed($paymentRepository);
            $stripeStrategyPaymentIntentFailed = new StripeStrategyPaymentIntentFailed();
            $stripeStrategyCheckoutSessionCompleted = new StripeStrategyCheckoutSessionCompleted();

            $strategies = [
                $stripeStrategyPaymentIntentSucceed,
                $stripeStrategyPaymentIntentFailed,
                $stripeStrategyCheckoutSessionCompleted
            ];

            // Inicializar servicios - usando el webhook secret correcto
            $stripeWebhookService = new StripeWebhookServiceImpl($webhookSecret, $strategies);

            // Inicializar controladores
            $stripeWebhookController = new StripeWebhookControllerImpl($stripeWebhookService);

            // Manejar el webhook
            $stripeWebhookController->handleStripeWebhook($payload, $signatureHeader);

            // Registrar en el log para depuración
            error_log("Webhook procesado exitosamente: " . substr($payload, 0, 100) . "...");
        }
    } catch (Exception $e) {
        // Registrar el error para depuración
        error_log("Error al procesar webhook: " . $e->getMessage());

        http_response_code(500);
        echo 'Error: ' . $e->getMessage();
    }
    exit;
}

// Cualquier otra ruta muestra la interfaz de usuario
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Webhook Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --stripe-purple: #635BFF;
            --stripe-light-purple: #857fff;
            --stripe-dark: #0A2540;
            --stripe-light: #f6f9fc;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background-color: #f7f9fc;
            color: #3c4257;
        }

        .navbar {
            background-color: var(--stripe-dark);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            color: white;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
            transition: color 0.15s ease;
        }

        .navbar-nav .nav-link:hover {
            color: white;
        }

        .btn-primary {
            background-color: var(--stripe-purple);
            border-color: var(--stripe-purple);
            font-weight: 500;
        }

        .btn-primary:hover, .btn-primary:focus {
            background-color: var(--stripe-light-purple);
            border-color: var(--stripe-light-purple);
        }

        .btn-outline-primary {
            color: var(--stripe-purple);
            border-color: var(--stripe-purple);
        }

        .btn-outline-primary:hover {
            background-color: var(--stripe-purple);
            border-color: var(--stripe-purple);
        }

        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(50, 50, 93, 0.1), 0 1px 1px rgba(0, 0, 0, 0.03);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(50, 50, 93, 0.15), 0 2px 4px rgba(0, 0, 0, 0.06);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            padding: 1rem 1.25rem;
        }

        .status-circle {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-success {
            background-color: #32CD32;
        }

        .status-error {
            background-color: #FF6347;
        }

        .status-warning {
            background-color: #FFA500;
        }

        .alert-success {
            background-color: #EAFAF1;
            color: #27AE60;
            border-color: #A9DFBF;
        }

        .alert-danger {
            background-color: #FEF5F5;
            color: #E74C3C;
            border-color: #F5B7B1;
        }

        .event-table th, .event-table td {
            vertical-align: middle;
        }

        .event-type {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .event-type.succeeded {
            background-color: #E8F5E9;
            color: #2E7D32;
        }

        .event-type.failed {
            background-color: #FFEBEE;
            color: #C62828;
        }

        .event-type.completed {
            background-color: #E3F2FD;
            color: #1565C0;
        }

        .mono {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.9em;
        }

        #loading {
            display: none;
            margin-left: 10px;
        }

        .system-status .list-group-item {
            border-left: 4px solid transparent;
            transition: background-color 0.15s ease;
        }

        .system-status .list-group-item:hover {
            background-color: rgba(99, 91, 255, 0.05);
        }

        .system-status .list-group-item.success {
            border-left-color: #32CD32;
        }

        .system-status .list-group-item.error {
            border-left-color: #FF6347;
        }

        .system-status .list-group-item.warning {
            border-left-color: #FFA500;
        }

        .json-container {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 15px;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.85rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .key {
            color: #7c4dff;
        }

        .string {
            color: #4caf50;
        }

        .number {
            color: #f44336;
        }

        .boolean {
            color: #ff9800;
        }

        .null {
            color: #9e9e9e;
        }

        /* Efecto de glow para el ID de clave */
        .secret-key-glow {
            position: relative;
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background-color: rgba(99, 91, 255, 0.1);
            border-radius: 4px;
            color: var(--stripe-purple);
            font-weight: 500;
        }

        .secret-key-glow::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            z-index: -1;
            border-radius: 6px;
            background-color: var(--stripe-purple);
            opacity: 0.2;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 0.2;
                transform: scale(1);
            }
            50% {
                opacity: 0.1;
                transform: scale(1.05);
            }
            100% {
                opacity: 0.2;
                transform: scale(1);
            }
        }

        .webhook-url {
            background-color: var(--stripe-light);
            border: 1px solid rgba(99, 91, 255, 0.2);
            border-radius: 4px;
            padding: 0.5rem 0.75rem;
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.9rem;
            color: var(--stripe-dark);
        }

        .copy-button {
            background-color: transparent;
            border: none;
            color: var(--stripe-purple);
            cursor: pointer;
            transition: transform 0.15s ease;
        }

        .copy-button:hover {
            transform: scale(1.1);
        }

        .event-count-badge {
            background-color: var(--stripe-purple);
            color: white;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-bolt me-2"></i> Stripe Webhook Manager
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="https://dashboard.stripe.com/test/webhooks" target="_blank">
                        <i class="fas fa-external-link-alt me-1"></i> Dashboard de Stripe
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://stripe.com/docs/webhooks" target="_blank">
                        <i class="fas fa-book me-1"></i> Documentación
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mb-5">
    <?php if ($simulationResult !== null): ?>
        <?php if ($simulationResult): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <strong>¡Éxito!</strong> El evento de Stripe ha sido simulado y registrado correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php else: ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-times-circle me-2"></i>
                <strong>Error:</strong> No se pudo simular el evento de Stripe. Verifica la conexión a la base de datos y la configuración.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i> Panel de control</h5>
                    <span class="badge bg-primary">v1.0</span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3"><i class="fas fa-server me-2"></i> Estado del sistema</h6>
                            <ul class="list-group mb-4 system-status">
                                <li class="list-group-item d-flex justify-content-between align-items-center <?php echo version_compare(PHP_VERSION, '7.4.0', '>=') ? 'success' : 'error'; ?>">
                                    <div>
                                        <i class="fab fa-php me-2"></i> PHP
                                    </div>
                                    <div>
                                        <span class="status-circle <?php echo version_compare(PHP_VERSION, '7.4.0', '>=') ? 'status-success' : 'status-error'; ?>"></span>
                                        v<?php echo phpversion(); ?>
                                    </div>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center <?php echo in_array('mysql', PDO::getAvailableDrivers()) ? 'success' : 'error'; ?>">
                                    <div>
                                        <i class="fas fa-database me-2"></i> PDO MySQL
                                    </div>
                                    <?php if (in_array('mysql', PDO::getAvailableDrivers())): ?>
                                        <span><span class="status-circle status-success"></span> Disponible</span>
                                    <?php else: ?>
                                        <span><span class="status-circle status-error"></span> No disponible</span>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $pdo ? 'success' : 'error'; ?>">
                                    <div>
                                        <i class="fas fa-plug me-2"></i> Conexión DB
                                    </div>
                                    <?php if ($pdo): ?>
                                        <span><span class="status-circle status-success"></span> Conectado</span>
                                    <?php else: ?>
                                        <span><span class="status-circle status-error"></span> Error de conexión</span>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center <?php echo $tableExists ? 'success' : 'error'; ?>">
                                    <div>
                                        <i class="fas fa-table me-2"></i> Tabla 'payments'
                                    </div>
                                    <?php if ($tableExists): ?>
                                        <span><span class="status-circle status-success"></span> Existe</span>
                                    <?php else: ?>
                                        <span><span class="status-circle status-error"></span> No existe</span>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center <?php echo isset($_ENV['STRIPE_SECRET_KEY']) ? 'success' : 'warning'; ?>">
                                    <div>
                                        <i class="fas fa-key me-2"></i> API Key de Stripe
                                    </div>
                                    <?php if (isset($_ENV['STRIPE_SECRET_KEY'])): ?>
                                        <span><span class="status-circle status-success"></span> Configurada</span>
                                    <?php else: ?>
                                        <span><span class="status-circle status-warning"></span> No configurada</span>
                                    <?php endif; ?>
                                </li>

                            </ul>

                            <h6 class="mb-3"><i class="fas fa-link me-2"></i> Endpoint del Webhook</h6>
                            <div class="d-flex align-items-center mb-4">
                                <div class="webhook-url flex-grow-1">
                                    <?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/v1/stripe'; ?>
                                </div>
                                <button class="copy-button ms-2" onclick="copyToClipboard('<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/v1/stripe'; ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>

                            <h6 class="mb-3"><i class="fas fa-terminal me-2"></i> Probar con Stripe CLI</h6>
                            <div class="webhook-url mb-2 font-monospace">
                                stripe trigger payment_intent.succeeded
                            </div>
                            <div class="webhook-url">
                                stripe listen --forward-to <?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/v1/stripe'; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="mb-3"><i class="fas fa-bolt me-2"></i> Simular evento de Stripe</h6>
                            <form method="post" action="" id="simulateForm" class="mb-4">
                                <div class="mb-3">
                                    <label for="event_type" class="form-label">Tipo de evento</label>
                                    <select class="form-select" id="event_type" name="event_type" required>
                                        <option value="">Selecciona un tipo de evento</option>
                                        <option value="payment_intent.succeeded">payment_intent.succeeded</option>
                                        <option value="payment_intent.payment_failed">payment_intent.payment_failed</option>
                                        <option value="checkout.session.completed">checkout.session.completed</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="customer_id" class="form-label">ID de Cliente (opcional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">cus_</span>
                                        <input type="text" class="form-control" id="customer_id" name="customer_id" placeholder="Ejemplo: a1b2c3d4">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="payment_intent_id" class="form-label">ID de Payment Intent (opcional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">pi_</span>
                                        <input type="text" class="form-control" id="payment_intent_id" name="payment_intent_id" placeholder="Ejemplo: e5f6g7h8">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary" name="simulate_event" value="1">
                                    <i class="fas fa-bolt me-2"></i> Simular evento
                                </button>
                            </form>

                            <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i> Información de conexión</h6>
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <div class="mb-2">
                                        <strong><i class="fas fa-server me-1"></i> Host:</strong> <?php echo htmlspecialchars($host); ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong><i class="fas fa-network-wired me-1"></i> Puerto:</strong> <?php echo htmlspecialchars($port); ?>
                                    </div>
                                    <div class="mb-2">
                                        <strong><i class="fas fa-database me-1"></i> Base de datos:</strong> <?php echo htmlspecialchars($database); ?>
                                    </div>
                                    <div>
                                        <strong><i class="fas fa-user me-1"></i> Usuario:</strong> <?php echo htmlspecialchars($username); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i> Eventos recibidos
                        <?php
                        $recentEvents = getRecentEvents($pdo);
                        $eventCount = count($recentEvents);
                        if ($eventCount > 0):
                            ?>
                            <span class="event-count-badge ms-2"><?php echo $eventCount; ?></span>
                        <?php endif; ?>
                    </h5>
                    <button class="btn btn-sm btn-outline-primary" onclick="refreshEvents()">
                        <i class="fas fa-sync-alt me-1"></i> Refrescar
                        <div class="spinner-border spinner-border-sm" id="loading" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive" id="events-container">
                        <?php
                        if (empty($recentEvents)):
                            ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x mb-3 text-muted"></i>
                                <p class="mb-1 text-muted">No hay eventos registrados</p>
                                <p class="text-muted">Usa el formulario para simular eventos o configura webhooks reales de Stripe</p>
                            </div>
                        <?php else: ?>
                            <table class="table table-hover event-table">
                                <thead>
                                <tr>
                                    <th>Fecha/Hora</th>
                                    <th>Tipo de evento</th>
                                    <th>Event ID</th>
                                    <th>Customer ID</th>
                                    <th>Payment Intent ID</th>
                                    <th>Acciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recentEvents as $event): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($event['created_at'])); ?></td>
                                        <td>
                                            <?php
                                            $eventTypeClass = '';
                                            switch ($event['event_type']) {
                                                case 'payment_intent.succeeded':
                                                    $eventTypeClass = 'succeeded';
                                                    break;
                                                case 'payment_intent.payment_failed':
                                                    $eventTypeClass = 'failed';
                                                    break;
                                                case 'checkout.session.completed':
                                                    $eventTypeClass = 'completed';
                                                    break;
                                            }
                                            ?>
                                            <span class="event-type <?php echo $eventTypeClass; ?>">
                                                        <?php echo htmlspecialchars($event['event_type']); ?>
                                                    </span>
                                        </td>
                                        <td><span class="mono"><?php echo htmlspecialchars($event['event_id']); ?></span></td>
                                        <td><span class="mono"><?php echo htmlspecialchars($event['customer_id']); ?></span></td>
                                        <td><span class="mono"><?php echo htmlspecialchars($event['payment_intent_id']); ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"
                                                    onclick="viewEventDetails('<?php echo $event['id_payment']; ?>')">
                                                <i class="fas fa-eye me-1"></i> Ver detalles
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles del evento -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventDetailsModalLabel">
                    <i class="fas fa-info-circle me-2"></i> Detalles del Evento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="eventDetailsBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando detalles del evento...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Función para formatear JSON con colores
    function syntaxHighlight(json) {
        if (typeof json !== 'string') {
            json = JSON.stringify(json, undefined, 2);
        }
        json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
            let cls = 'number';
            if (/^"/.test(match)) {
                if (/:$/.test(match)) {
                    cls = 'key';
                } else {
                    cls = 'string';
                }
            } else if (/true|false/.test(match)) {
                cls = 'boolean';
            } else if (/null/.test(match)) {
                cls = 'null';
            }
            return '<span class="' + cls + '">' + match + '</span>';
        });
    }

    // Función para ver detalles del evento
    function viewEventDetails(eventId) {
        const modal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));
        modal.show();

        // Hacer una llamada AJAX para obtener los detalles del evento
        fetch(`?api=event-details&id=${eventId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let eventDetails = data.data;

                    // Construir el HTML para los detalles del evento
                    let html = `
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <p><strong>ID del Evento:</strong> <span class="mono">${eventDetails.event_id}</span></p>
                                    <p><strong>ID de Payment:</strong> <span class="mono">${eventDetails.id_payment}</span></p>
                                    <p><strong>Tipo de Evento:</strong> <span class="event-type ${getEventTypeClass(eventDetails.event_type)}">${eventDetails.event_type}</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>ID de Cliente:</strong> <span class="mono">${eventDetails.customer_id}</span></p>
                                    <p><strong>ID de Payment Intent:</strong> <span class="mono">${eventDetails.payment_intent_id}</span></p>
                                    <p><strong>Fecha de Creación:</strong> ${new Date(eventDetails.created_at).toLocaleString()}</p>
                                </div>
                            </div>

                            <h6>Payload JSON:</h6>
                            <div class="json-container">
                                <pre>${syntaxHighlight(eventDetails.payload)}</pre>
                            </div>
                        `;

                    document.getElementById('eventDetailsBody').innerHTML = html;
                } else {
                    document.getElementById('eventDetailsBody').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i> ${data.message}
                            </div>
                        `;
                }
            })
            .catch(error => {
                document.getElementById('eventDetailsBody').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i> Error al cargar los detalles del evento: ${error.message}
                        </div>
                    `;
            });
    }

    // Función para determinar la clase de estilo según el tipo de evento
    function getEventTypeClass(eventType) {
        switch (eventType) {
            case 'payment_intent.succeeded':
                return 'succeeded';
            case 'payment_intent.payment_failed':
                return 'failed';
            case 'checkout.session.completed':
                return 'completed';
            default:
                return '';
        }
    }

    // Función para refrescar la lista de eventos
    function refreshEvents() {
        document.getElementById('loading').style.display = 'inline-block';

        // Recargar la página para obtener eventos actualizados
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }

    // Función para copiar al portapapeles
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            // Crear un tooltip o notificación
            alert('URL copiada al portapapeles');
        }).catch(err => {
            console.error('Error al copiar: ', err);
        });
    }

    // Enviar evento de prueba con AJAX
    function sendTestEvent() {
        const eventType = document.getElementById('event_type').value;
        if (!eventType) {
            alert('Por favor selecciona un tipo de evento');
            return;
        }

        // Construir el objeto de evento
        const eventData = {
            type: eventType,
            data: {
                object: {
                    id: document.getElementById('payment_intent_id').value ?
                        'pi_' + document.getElementById('payment_intent_id').value :
                        `pi_test_${Date.now()}`,
                    customer: document.getElementById('customer_id').value ?
                        'cus_' + document.getElementById('customer_id').value :
                        `cus_test_${Date.now()}`
                }
            }
        };

        // Enviar mediante fetch
        fetch('/v1/stripe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Test-Request': 'true'
            },
            body: JSON.stringify(eventData)
        })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    refreshEvents();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al enviar el evento de prueba');
            });
    }

    // Verificar el estado de conexión periódicamente
    function checkConnectionStatus() {
        // Aquí podrías implementar una verificación periódica del estado
        // Por ahora, simplemente lo simulamos con un cambio de estilo
        const connectionStatusElement = document.querySelector('.system-status li:nth-child(3)');

        // Esta función podría llamarse con setInterval para verificar periódicamente
        // setInterval(checkConnectionStatus, 30000);
    }

    // Inicializar la página
    document.addEventListener('DOMContentLoaded', function() {
        // Iniciar verificación de conexión
        checkConnectionStatus();

        // Añadir efecto de hover a las filas de la tabla
        const tableRows = document.querySelectorAll('.event-table tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseover', function() {
                this.style.backgroundColor = 'rgba(99, 91, 255, 0.05)';
            });
            row.addEventListener('mouseout', function() {
                this.style.backgroundColor = '';
            });
        });
    });
</script>
</body>
</html>