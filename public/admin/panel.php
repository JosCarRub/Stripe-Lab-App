<?php

$dbConfig = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'stripe_lab',
    'username' => 'test_user',
    'password' => 'password'
];

// Función para conectar a la base de datos usando PDO
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

// Función para obtener todas las tablas de la base de datos
function getTables($pdo) {
    $tables = [];
    try {
        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
    } catch (PDOException $e) {
        // Manejar error silenciosamente
    }
    return $tables;
}

// Función para obtener estadísticas de la base de datos
function getDBStats($pdo, $dbName) {
    $stats = [
        'tables' => 0,
        'fields' => 0,
        'payments' => 0,
        'subscriptions' => 0
    ];

    try {
        // Contar tablas
        $tablesResult = $pdo->query("SHOW TABLES");
        $stats['tables'] = $tablesResult->rowCount();

        // Contar campos de todas las tablas
        $fieldsStmt = $pdo->prepare("
            SELECT COUNT(*) as total_fields
            FROM information_schema.columns
            WHERE table_schema = :dbname
        ");
        $fieldsStmt->bindParam(':dbname', $dbName, PDO::PARAM_STR);
        $fieldsStmt->execute();
        $row = $fieldsStmt->fetch();
        $stats['fields'] = (int)$row['total_fields'];

        // Contar pagos (transacciones)
        try {
            // Primero verificamos si la tabla existe
            $tableExistsStmt = $pdo->prepare("
                SELECT COUNT(*) as table_exists 
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = :dbName 
                AND TABLE_NAME = 'StripeTransactions'
            ");
            $tableExistsStmt->bindParam(':dbName', $dbName);
            $tableExistsStmt->execute();

            $result = $tableExistsStmt->fetch();
            if ((int)$result['table_exists'] > 0) {
                $paymentsStmt = $pdo->query("
                    SELECT COUNT(*) as total_payments
                    FROM `StripeTransactions`
                ");
                $row = $paymentsStmt->fetch();
                $stats['payments'] = (int)$row['total_payments'];
            }
        } catch (PDOException $e) {
            // La tabla puede no existir, mantener el valor predeterminado
        }

        // Contar suscripciones
        try {
            // Primero verificamos si la tabla existe
            $tableExistsStmt = $pdo->prepare("
                SELECT COUNT(*) as table_exists 
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = :dbName 
                AND TABLE_NAME = 'StripeSubscriptions'
            ");
            $tableExistsStmt->bindParam(':dbName', $dbName);
            $tableExistsStmt->execute();

            $result = $tableExistsStmt->fetch();
            if ((int)$result['table_exists'] > 0) {
                $subsStmt = $pdo->query("
                    SELECT COUNT(*) as total_subs
                    FROM `StripeSubscriptions`
                ");
                $row = $subsStmt->fetch();
                $stats['subscriptions'] = (int)$row['total_subs'];
            }
        } catch (PDOException $e) {
            // La tabla puede no existir, mantener el valor predeterminado
        }
    } catch (PDOException $e) {
        // Manejar error silenciosamente
    }

    return $stats;
}

// Función para obtener relaciones entre tablas
function getTableRelationships($pdo, $dbName) {
    $relationships = [];

    try {
        // Consulta para obtener las relaciones por claves foráneas
        $query = "
            SELECT
                table_name,
                column_name,
                referenced_table_name,
                referenced_column_name
            FROM
                information_schema.key_column_usage
            WHERE
                referenced_table_name IS NOT NULL AND
                table_schema = :dbname
        ";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':dbname', $dbName, PDO::PARAM_STR);
        $stmt->execute();

        while ($row = $stmt->fetch()) {
            $relationships[] = [
                'from' => $row['table_name'],
                'fromColumn' => $row['column_name'],
                'to' => $row['referenced_table_name'],
                'toColumn' => $row['referenced_column_name']
            ];
        }
    } catch (PDOException $e) {
        // Manejar error silenciosamente
    }

    // Relaciones definidas manualmente (no detectadas a través de claves foráneas)
    // Para las tablas de nuestro esquema
    $manualRelationships = [
        [
            'from' => 'StripeSubscriptions',
            'fromColumn' => 'stripe_customer_id',
            'to' => 'StripeTransactions',
            'toColumn' => 'stripe_customer_id'
        ],
        [
            'from' => 'StripeSubscriptions',
            'fromColumn' => 'latest_transaction_id',
            'to' => 'StripeTransactions',
            'toColumn' => 'transaction_id'
        ]
    ];

    $relationships = array_merge($relationships, $manualRelationships);

    return $relationships;
}

// Conectar a la base de datos
$dbConnection = connectDB($dbConfig);

// Preparar datos para el frontend
$frontendData = [
    'connectionStatus' => $dbConnection['success'] ? 'connected' : 'error',
    'dbConfig' => [
        'host' => $dbConfig['host'],
        'port' => $dbConfig['port'],
        'database' => $dbConfig['database'],
        'username' => $dbConfig['username']
    ],
    'tables' => [],
    'stats' => [
        'tables' => 0,
        'fields' => 0,
        'payments' => 0,
        'subscriptions' => 0
    ],
    'relationships' => []
];

// Si la conexión es exitosa, obtener datos adicionales
if ($dbConnection['success']) {
    $pdo = $dbConnection['connection'];
    $frontendData['tables'] = getTables($pdo);
    $frontendData['stats'] = getDBStats($pdo, $dbConfig['database']);
    $frontendData['relationships'] = getTableRelationships($pdo, $dbConfig['database']);

    // No necesitamos cerrar la conexión explícitamente con PDO, se cierra al finalizar el script
}

// Convertir a JSON para usar en JavaScript
$frontendDataJson = json_encode($frontendData);
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - StripeLabApp</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/panel.css">
</head>
<body>
<div class="app-container">
    <!-- Sidebar -->
    <aside class="app-sidebar">
        <div class="sidebar-header">
            <div class="app-brand">
                <i class="fab fa-stripe app-logo"></i>
                <a href="../index.php" class="app-link">
                    <span class="app-name">StripeLabApp</span>
                </a>
            </div>

            <label class="theme-toggle" aria-label="Cambiar modo oscuro">
                <input type="checkbox" id="theme-toggle-input">
                <span class="theme-slider">
                        <i class="fas fa-sun sun-icon"></i>
                        <i class="fas fa-moon moon-icon"></i>
                    </span>
            </label>
        </div>

        <div class="sidebar-search">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="search-input" placeholder="Buscar tablas...">
            </div>
        </div>

        <div class="nav-separator">
            <span>Tablas de la Base de Datos</span>
        </div>

        <nav class="sidebar-nav">
            <div class="tables-container">
                <ul id="table-list" class="table-list">
                    <li class="sidebar-loader">
                        <div class="spinner"></div>
                        <span>Cargando tablas...</span>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="sidebar-footer">
            <div id="db-status" class="db-status">
                <span class="status-indicator"></span>
                <span class="status-text">Verificando conexión...</span>
            </div>

            <button class="sidebar-toggle" id="sidebar-toggle">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="app-main">
        <div class="app-canvas">
            <div class="canvas-shape shape-1"></div>
            <div class="canvas-shape shape-2"></div>
            <div class="canvas-shape shape-3"></div>
        </div>

        <header class="main-header">
            <div class="header-left">
                <button class="menu-toggle" id="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h2 class="page-title">Panel de Administración</h2>
            </div>

            <div class="header-actions">
                <button id="refresh-btn" class="btn btn-outline btn-icon-text">
                    <i class="fas fa-sync-alt"></i>
                    <span>Actualizar</span>
                </button>
                <button id="export-btn" class="btn btn-primary btn-icon-text">
                    <i class="fas fa-file-export"></i>
                    <span>Exportar</span>
                </button>
            </div>
        </header>

        <div class="content-wrapper">
            <!-- Dashboard Overview -->
            <section id="dashboard-overview" class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">Resumen de Base de Datos</h2>
                    <p class="section-subtitle">Vista general del estado actual de la base de datos Stripe</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon table-icon">
                            <i class="fas fa-table"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="total-tables" class="stat-value">0</h3>
                            <p class="stat-label">Tablas</p>
                        </div>
                        <div class="stat-chart">
                            <svg viewBox="0 0 36 36" class="chart-circle">
                                <path class="chart-circle-bg"
                                      d="M18 2.0845
                                            a 15.9155 15.9155 0 0 1 0 31.831
                                            a 15.9155 15.9155 0 0 1 0 -31.831"
                                />
                                <path class="chart-circle-fill table-stroke"
                                      stroke-dasharray="50, 100"
                                      d="M18 2.0845
                                            a 15.9155 15.9155 0 0 1 0 31.831
                                            a 15.9155 15.9155 0 0 1 0 -31.831"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon field-icon">
                            <i class="fas fa-columns"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="total-fields" class="stat-value">0</h3>
                            <p class="stat-label">Campos</p>
                        </div>
                        <div class="stat-chart">
                            <svg viewBox="0 0 36 36" class="chart-circle">
                                <path class="chart-circle-bg"
                                      d="M18 2.0845
                                            a 15.9155 15.9155 0 0 1 0 31.831
                                            a 15.9155 15.9155 0 0 1 0 -31.831"
                                />
                                <path class="chart-circle-fill field-stroke"
                                      stroke-dasharray="65, 100"
                                      d="M18 2.0845
                                            a 15.9155 15.9155 0 0 1 0 31.831
                                            a 15.9155 15.9155 0 0 1 0 -31.831"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon payment-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="total-payments" class="stat-value">0</h3>
                            <p class="stat-label">Pagos</p>
                        </div>
                        <div class="stat-chart">
                            <svg viewBox="0 0 36 36" class="chart-circle">
                                <path class="chart-circle-bg"
                                      d="M18 2.0845
                                            a 15.9155 15.9155 0 0 1 0 31.831
                                            a 15.9155 15.9155 0 0 1 0 -31.831"
                                />
                                <path class="chart-circle-fill payment-stroke"
                                      stroke-dasharray="80, 100"
                                      d="M18 2.0845
                                            a 15.9155 15.9155 0 0 1 0 31.831
                                            a 15.9155 15.9155 0 0 1 0 -31.831"
                                />
                            </svg>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon subscription-icon">
                            <i class="fas fa-sync"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="total-subscriptions" class="stat-value">0</h3>
                            <p class="stat-label">Suscripciones</p>
                        </div>
                        <div class="stat-chart">
                            <svg viewBox="0 0 36 36" class="chart-circle">
                                <path class="chart-circle-bg"
                                      d="M18 2.0845
                                            a 15.9155 15.9155 0 0 1 0 31.831
                                            a 15.9155 15.9155 0 0 1 0 -31.831"
                                />
                                <path class="chart-circle-fill subscription-stroke"
                                      stroke-dasharray="35, 100"
                                      d="M18 2.0845
                                            a 15.9155 15.9155 0 0 1 0 31.831
                                            a 15.9155 15.9155 0 0 1 0 -31.831"
                                />
                            </svg>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Table Details Section -->
            <section id="table-details" class="dashboard-section hidden">
                <div class="section-header with-actions">
                    <div class="section-header-left">
                        <h2 class="section-title">Detalles de la Tabla: <span id="current-table-name" class="highlight-text">-</span></h2>
                        <p class="section-subtitle">Estructura y metadatos de la tabla seleccionada</p>
                    </div>

                    <div class="table-actions">
                        <button id="view-data-btn" class="btn btn-tab">
                            <i class="fas fa-eye"></i>
                            <span>Ver Datos</span>
                        </button>
                        <button id="structure-btn" class="btn btn-tab active">
                            <i class="fas fa-sitemap"></i>
                            <span>Estructura</span>
                        </button>
                    </div>
                </div>

                <div class="card table-card">
                    <div class="table-container">
                        <table id="fields-table" class="data-table">
                            <thead>
                            <tr>
                                <th>Campo</th>
                                <th>Tipo</th>
                                <th>Nulo</th>
                                <th>Clave</th>
                                <th>Predeterminado</th>
                                <th>Extra</th>
                            </tr>
                            </thead>
                            <tbody>
                            <!-- Los campos se cargarán dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Table Data Preview Section -->
            <section id="data-preview" class="dashboard-section hidden">
                <div class="section-header with-actions">
                    <div class="section-header-left">
                        <h2 class="section-title">Datos de: <span id="preview-table-name" class="highlight-text">-</span></h2>
                        <p class="section-subtitle">Vista previa de los registros almacenados</p>
                    </div>

                    <div class="table-pagination">
                        <button id="prev-page" class="pagination-btn">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span id="page-info" class="page-info">Página 1 de 1</span>
                        <button id="next-page" class="pagination-btn">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <div class="card table-card">
                    <div class="table-container">
                        <table id="data-table" class="data-table">
                            <thead>
                            <!-- Los encabezados se cargarán dinámicamente -->
                            </thead>
                            <tbody>
                            <!-- Los datos se cargarán dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Connection Test Section -->
            <section id="connection-test" class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">Estado de la Conexión</h2>
                    <p class="section-subtitle">Información sobre la conexión actual con la base de datos</p>
                </div>

                <div class="card connection-card">
                    <div class="connection-grid">
                        <div id="connection-details" class="connection-details">
                            <div class="connection-info-grid">
                                <div class="connection-info">
                                    <span class="info-label">Host:</span>
                                    <span id="db-host" class="info-value">-</span>
                                </div>
                                <div class="connection-info">
                                    <span class="info-label">Puerto:</span>
                                    <span id="db-port" class="info-value">-</span>
                                </div>
                                <div class="connection-info">
                                    <span class="info-label">Base de Datos:</span>
                                    <span id="db-name" class="info-value">-</span>
                                </div>
                                <div class="connection-info">
                                    <span class="info-label">Usuario:</span>
                                    <span id="db-user" class="info-value">-</span>
                                </div>
                                <div class="connection-info connection-status-info">
                                    <span class="info-label">Estado:</span>
                                    <div class="status-wrapper">
                                        <span id="connection-status-indicator" class="status-indicator-dot"></span>
                                        <span id="connection-status-text" class="status-text">Verificando...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="connection-actions">
                            <button id="test-connection-btn" class="btn btn-primary btn-icon-text">
                                <i class="fas fa-plug"></i>
                                <span>Probar Conexión</span>
                            </button>
                            <button id="config-connection-btn" class="btn btn-outline btn-icon-text">
                                <i class="fas fa-cog"></i>
                                <span>Configurar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Relationships Section -->
            <section id="relationships" class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">Relaciones entre Tablas</h2>
                    <p class="section-subtitle">Diagrama visual de las conexiones entre tablas</p>
                </div>

                <div class="card">
                    <div id="relationships-diagram" class="relationships-diagram">
                        <!-- El diagrama de relaciones se generará aquí -->
                    </div>
                </div>
            </section>
        </div>

        <footer class="app-footer">
            <div class="footer-content">
                <div class="footer-copyright">
                    © <span id="current-year"></span> StripeLabApp. Todos los derechos reservados.
                </div>
                <div class="footer-links">
                    <a href="../doc/index.html">Documentación</a>
                    <a href="../doc/api.html">API</a>
                    <a href="../index.php">Volver al Inicio</a>
                </div>
            </div>
        </footer>
    </main>
</div>

<!-- Modal for connection configuration -->
<div id="config-modal" class="modal">
    <div class="modal-backdrop"></div>
    <div class="modal-container">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Configuración de Conexión</h3>
                <button id="close-modal" class="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="connection-form" class="form">
                    <div class="form-group">
                        <label for="host" class="form-label">Host:</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-server input-icon"></i>
                            <input type="text" id="host" name="host" class="form-input" placeholder="localhost">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="port" class="form-label">Puerto:</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-network-wired input-icon"></i>
                            <input type="text" id="port" name="port" class="form-input" placeholder="3306">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="database" class="form-label">Base de Datos:</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-database input-icon"></i>
                            <input type="text" id="database" name="database" class="form-input" placeholder="stripe_db">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="username" class="form-label">Usuario:</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" id="username" name="username" class="form-input" placeholder="root">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">Contraseña:</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" id="password" name="password" class="form-input" placeholder="••••••">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" id="cancel-config" class="btn btn-outline">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Configuración</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- AJAX Handler Script -->
<script>
    // Datos precargados desde PHP
    const initialData = <?php echo $frontendDataJson; ?>;
</script>

<script src="./assets/js/panel.js"></script>
<script>
    // Inicializar los datos de la aplicación
    document.addEventListener('DOMContentLoaded', function() {
        // Establecer año actual para el copyright
        document.getElementById('current-year').textContent = new Date().getFullYear();

        // Theme Toggle
        const themeToggle = document.getElementById('theme-toggle-input');

        // Check for saved theme preference or respect OS preference
        const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

        if (localStorage.getItem('dark-mode') === 'true' || (prefersDarkMode && !localStorage.getItem('dark-mode'))) {
            document.documentElement.setAttribute('data-theme', 'dark');
            themeToggle.checked = true;
        }

        // Theme switch event listener
        themeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('dark-mode', 'true');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
                localStorage.setItem('dark-mode', 'false');
            }
        });

        // Toggle sidebar
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.app-container').classList.toggle('sidebar-collapsed');
        });

        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.querySelector('.app-container').classList.toggle('sidebar-visible');
        });

        // Modal functionality
        document.getElementById('config-connection-btn').addEventListener('click', function() {
            document.getElementById('config-modal').classList.add('modal-visible');
        });

        document.getElementById('close-modal').addEventListener('click', function() {
            document.getElementById('config-modal').classList.remove('modal-visible');
        });

        document.getElementById('cancel-config').addEventListener('click', function() {
            document.getElementById('config-modal').classList.remove('modal-visible');
        });

        document.querySelector('.modal-backdrop').addEventListener('click', function() {
            document.getElementById('config-modal').classList.remove('modal-visible');
        });

        // Tab switching for table details
        document.getElementById('view-data-btn').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('structure-btn').classList.remove('active');
            document.getElementById('data-preview').classList.remove('hidden');
            document.getElementById('table-details').classList.add('hidden');
        });

        document.getElementById('structure-btn').addEventListener('click', function() {
            this.classList.add('active');
            document.getElementById('view-data-btn').classList.remove('active');
            document.getElementById('table-details').classList.remove('hidden');
            document.getElementById('data-preview').classList.add('hidden');
        });

        // Inicializar con los datos cargados desde PHP
        initAppWithData(initialData);
    });

    // Inicializar la aplicación con los datos
    function initAppWithData(data) {
        // Actualizar indicador de conexión
        updateConnectionStatus(data.connectionStatus);

        // Actualizar detalles de conexión
        document.getElementById('db-host').textContent = data.dbConfig.host;
        document.getElementById('db-port').textContent = data.dbConfig.port;
        document.getElementById('db-name').textContent = data.dbConfig.database;
        document.getElementById('db-user').textContent = data.dbConfig.username;

        // Actualizar estadísticas
        document.getElementById('total-tables').textContent = data.stats.tables;
        document.getElementById('total-fields').textContent = data.stats.fields;
        document.getElementById('total-payments').textContent = data.stats.payments;
        document.getElementById('total-subscriptions').textContent = data.stats.subscriptions;

        // Cargar lista de tablas
        loadTableList(data.tables);

        // Inicializar diagrama de relaciones si hay relaciones
        if (data.relationships && data.relationships.length > 0) {
            initRelationshipsDiagram(data.relationships);
        } else {
            document.getElementById('relationships').classList.add('hidden');
        }
    }

    // Actualizar el indicador de estado de conexión
    function updateConnectionStatus(status) {
        const statusIndicator = document.getElementById('connection-status-indicator');
        const statusText = document.getElementById('connection-status-text');
        const sidebarStatusIndicator = document.querySelector('.sidebar-footer .status-indicator');
        const sidebarStatusText = document.querySelector('.sidebar-footer .status-text');

        if (status === 'connected') {
            statusIndicator.className = 'status-indicator-dot connected';
            statusText.textContent = 'Conectado';
            statusText.className = 'status-text connected';

            sidebarStatusIndicator.className = 'status-indicator connected';
            sidebarStatusText.textContent = 'Conectado';
        } else {
            statusIndicator.className = 'status-indicator-dot error';
            statusText.textContent = 'Error de conexión';
            statusText.className = 'status-text error';

            sidebarStatusIndicator.className = 'status-indicator error';
            sidebarStatusText.textContent = 'Error de conexión';
        }
    }

    // Cargar la lista de tablas en el sidebar
    function loadTableList(tables) {
        const tableList = document.getElementById('table-list');

        // Limpiar loader
        tableList.innerHTML = '';

        if (tables.length === 0) {
            tableList.innerHTML = '<li class="no-tables">No se encontraron tablas</li>';
            return;
        }

        // Crear elementos para cada tabla
        tables.forEach(tableName => {
            const li = document.createElement('li');
            li.className = 'table-item';
            li.innerHTML = `
                <a href="#" class="table-link" data-table="${tableName}">
                    <i class="fas fa-table table-icon"></i>
                    <span class="table-name">${tableName}</span>
                </a>
            `;
            tableList.appendChild(li);

            // Agregar evento de clic
            li.querySelector('.table-link').addEventListener('click', function(e) {
                e.preventDefault();
                selectTable(tableName);
            });
        });

        // Inicializar búsqueda de tablas
        initTableSearch();
    }

    // Inicializar búsqueda de tablas
    function initTableSearch() {
        const searchInput = document.getElementById('search-input');

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const tableItems = document.querySelectorAll('.table-item');

            tableItems.forEach(item => {
                const tableName = item.querySelector('.table-name').textContent.toLowerCase();
                if (tableName.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Seleccionar una tabla para mostrar sus detalles
    function selectTable(tableName) {
        // Actualizar elementos activos en la lista de tablas
        document.querySelectorAll('.table-item').forEach(item => {
            item.classList.remove('active');
        });

        const selectedItem = document.querySelector(`.table-link[data-table="${tableName}"]`).closest('.table-item');
        selectedItem.classList.add('active');

        // Actualizar nombres de tabla en los encabezados
        document.getElementById('current-table-name').textContent = tableName;
        document.getElementById('preview-table-name').textContent = tableName;

        // En móvil, cerrar el sidebar
        if (window.innerWidth < 992) {
            document.querySelector('.app-container').classList.remove('sidebar-visible');
        }

        // Mostrar sección de detalles y ocultar vista general
        document.getElementById('dashboard-overview').classList.add('hidden');
        document.getElementById('connection-test').classList.add('hidden');
        document.getElementById('table-details').classList.remove('hidden');
        document.getElementById('relationships').classList.add('hidden');

        // Cargar la estructura de la tabla
        loadTableStructure(tableName);
    }

    // Cargar la estructura de una tabla
    function loadTableStructure(tableName) {
        // Mostrar indicador de carga
        const fieldsTable = document.getElementById('fields-table');
        fieldsTable.querySelector('tbody').innerHTML = '<tr><td colspan="6" class="loading-cell"><div class="spinner"></div></td></tr>';

        // Realizar solicitud AJAX para obtener la estructura
        fetch(`api/panel_api.php?action=get_table_structure&table=${encodeURIComponent(tableName)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayTableStructure(data.structure);
                } else {
                    fieldsTable.querySelector('tbody').innerHTML = `<tr><td colspan="6" class="error-cell">${data.error}</td></tr>`;
                }
            })
            .catch(error => {
                console.error('Error al cargar la estructura:', error);
                fieldsTable.querySelector('tbody').innerHTML = '<tr><td colspan="6" class="error-cell">Error al cargar la estructura</td></tr>';
            });
    }

    // Mostrar la estructura de una tabla
    function displayTableStructure(structure) {
        const tbody = document.getElementById('fields-table').querySelector('tbody');
        tbody.innerHTML = '';

        structure.forEach(field => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${field.Field}</td>
                <td>${field.Type}</td>
                <td>${field.Null}</td>
                <td>${field.Key}</td>
                <td>${field.Default !== null ? field.Default : '<em>NULL</em>'}</td>
                <td>${field.Extra}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Inicializar la visualización del diagrama de relaciones
    function initRelationshipsDiagram(relationships) {
        const diagramContainer = document.getElementById('relationships-diagram');

        // Crear un conjunto de todas las tablas involucradas en relaciones
        const tables = new Set();
        relationships.forEach(rel => {
            tables.add(rel.from);
            tables.add(rel.to);
        });

        // Crear elementos HTML para las tablas
        let htmlContent = '';
        tables.forEach(table => {
            htmlContent += `
                <div class="diagram-table" data-table="${table}">
                    <div class="diagram-table-header">
                        <i class="fas fa-table"></i>
                        <span class="diagram-table-name">${table}</span>
                    </div>
                    <div class="diagram-table-body">
                        <div class="diagram-connection-points">
                            <!-- Puntos de conexión generados dinámicamente -->
                        </div>
                    </div>
                </div>
            `;
        });

        diagramContainer.innerHTML = htmlContent;

        // Agregar conexiones visuales entre tablas (se implementaría con SVG o líneas CSS)
        // Esta implementación simplificada solo muestra las tablas, sin conexiones visuales

        // Agregar evento de clic para seleccionar tablas desde el diagrama
        document.querySelectorAll('.diagram-table').forEach(tableElem => {
            tableElem.addEventListener('click', function() {
                const tableName = this.dataset.table;
                selectTable(tableName);
            });
        });
    }

    // Evento para cambiar entre la vista de estructura y datos
    document.getElementById('view-data-btn').addEventListener('click', function() {
        const tableName = document.getElementById('current-table-name').textContent;
        if (tableName !== '-') {
            loadTableData(tableName);
        }
    });

    // Cargar los datos de una tabla
    function loadTableData(tableName, page = 1) {
        // Mostrar indicador de carga
        const dataTable = document.getElementById('data-table');
        dataTable.innerHTML = '<tbody><tr><td colspan="10" class="loading-cell"><div class="spinner"></div></td></tr></tbody>';

        // Realizar solicitud AJAX para obtener los datos
        fetch(`api/panel_api.php?action=get_table_data&table=${encodeURIComponent(tableName)}&page=${page}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayTableData(data.data);
                    updatePagination(page, data.data.pages);
                } else {
                    dataTable.innerHTML = `<tbody><tr><td colspan="10" class="error-cell">${data.error}</td></tr></tbody>`;
                }
            })
            .catch(error => {
                console.error('Error al cargar los datos:', error);
                dataTable.innerHTML = '<tbody><tr><td colspan="10" class="error-cell">Error al cargar los datos</td></tr></tbody>';
            });
    }

    // Mostrar los datos de una tabla
    function displayTableData(data) {
        const dataTable = document.getElementById('data-table');

        // Crear encabezados
        let thead = '<thead><tr>';
        data.columns.forEach(column => {
            thead += `<th>${column}</th>`;
        });
        thead += '</tr></thead>';

        // Crear filas de datos
        let tbody = '<tbody>';

        if (data.rows.length === 0) {
            tbody += '<tr><td colspan="' + data.columns.length + '" class="empty-cell">No hay datos para mostrar</td></tr>';
        } else {
            data.rows.forEach(row => {
                tbody += '<tr>';
                data.columns.forEach(column => {
                    const value = row[column];
                    tbody += `<td>${value !== null ? value : '<em>NULL</em>'}</td>`;
                });
                tbody += '</tr>';
            });
        }

        tbody += '</tbody>';

        // Actualizar la tabla
        dataTable.innerHTML = thead + tbody;
    }

    // Actualizar la paginación
    function updatePagination(currentPage, totalPages) {
        document.getElementById('page-info').textContent = `Página ${currentPage} de ${totalPages}`;

        const prevButton = document.getElementById('prev-page');
        const nextButton = document.getElementById('next-page');

        prevButton.disabled = currentPage <= 1;
        nextButton.disabled = currentPage >= totalPages;

        // Eventos de paginación
        prevButton.onclick = function() {
            if (currentPage > 1) {
                const tableName = document.getElementById('preview-table-name').textContent;
                loadTableData(tableName, currentPage - 1);
            }
        };

        nextButton.onclick = function() {
            if (currentPage < totalPages) {
                const tableName = document.getElementById('preview-table-name').textContent;
                loadTableData(tableName, currentPage + 1);
            }
        };
    }

    // Botón para probar la conexión
    document.getElementById('test-connection-btn').addEventListener('click', function() {
        // Realizar solicitud AJAX para probar la conexión
        fetch('panel_api.php?action=test_connection')
            .then(response => response.json())
            .then(data => {
                updateConnectionStatus(data.success ? 'connected' : 'error');

                // Mostrar mensaje
                alert(data.success ? 'Conexión exitosa a la base de datos' : 'Error de conexión: ' + data.error);
            })
            .catch(error => {
                console.error('Error al probar la conexión:', error);
                updateConnectionStatus('error');
                alert('Error al probar la conexión');
            });
    });

    // Formulario de configuración de conexión
    document.getElementById('connection-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        // Realizar solicitud AJAX para actualizar la configuración
        fetch('api/panel_api.php?action=update_connection', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cerrar modal
                    document.getElementById('config-modal').classList.remove('modal-visible');

                    // Actualizar la UI
                    updateConnectionStatus('connected');
                    document.getElementById('db-host').textContent = formData.get('host');
                    document.getElementById('db-port').textContent = formData.get('port');
                    document.getElementById('db-name').textContent = formData.get('database');
                    document.getElementById('db-user').textContent = formData.get('username');

                    // Recargar datos
                    location.reload();
                } else {
                    alert('Error al actualizar la configuración: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error al actualizar la configuración:', error);
                alert('Error al actualizar la configuración');
            });
    });

    // Botón de actualización
    document.getElementById('refresh-btn').addEventListener('click', function() {
        location.reload();
    });

    // Botón de exportación
    document.getElementById('export-btn').addEventListener('click', function() {
        // Determinar qué datos exportar
        let dataToExport = '';
        let fileName = 'stripe_db_export.csv';

        if (!document.getElementById('table-details').classList.contains('hidden')) {
            // Exportar estructura de tabla
            const tableName = document.getElementById('current-table-name').textContent;
            const table = document.getElementById('fields-table');
            dataToExport = tableToCSV(table);
            fileName = `${tableName}_structure.csv`;
        } else if (!document.getElementById('data-preview').classList.contains('hidden')) {
            // Exportar datos de tabla
            const tableName = document.getElementById('preview-table-name').textContent;
            const table = document.getElementById('data-table');
            dataToExport = tableToCSV(table);
            fileName = `${tableName}_data.csv`;
        } else {
            // Exportar resumen general
            const statsData = [
                ['Tabla de estadísticas', 'Valor'],
                ['Tablas', document.getElementById('total-tables').textContent],
                ['Campos', document.getElementById('total-fields').textContent],
                ['Pagos', document.getElementById('total-payments').textContent],
                ['Suscripciones', document.getElementById('total-subscriptions').textContent]
            ];

            dataToExport = statsData.map(row => row.join(',')).join('\n');
            fileName = 'stripe_db_stats.csv';
        }

        // Crear y descargar el archivo CSV
        downloadCSV(dataToExport, fileName);
    });

    // Convertir tabla HTML a CSV
    function tableToCSV(table) {
        const rows = table.querySelectorAll('tr');
        const csvRows = [];

        rows.forEach(row => {
            const cells = row.querySelectorAll('th, td');
            const csvRow = [];

            cells.forEach(cell => {
                let text = cell.textContent.trim();
                // Escapar comas y comillas
                if (text.includes(',') || text.includes('"')) {
                    text = '"' + text.replace(/"/g, '""') + '"';
                }
                csvRow.push(text);
            });

            csvRows.push(csvRow.join(','));
        });

        return csvRows.join('\n');
    }

    // Descargar datos como archivo CSV
    function downloadCSV(csvContent, fileName) {
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', fileName);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
</body>
</html>