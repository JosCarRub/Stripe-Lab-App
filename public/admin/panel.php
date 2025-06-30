<?php
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__, 2));
}
require_once PROJECT_ROOT . '/vendor/autoload.php';
\config\Bootstrap::initialize(PROJECT_ROOT);
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - StripeLabApp</title>
    <link rel="icon" type="image/svg+xml" href="../image/favicon.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/panel.css">
</head>
<body>
<div class="app-container">
    <aside class="app-sidebar">
        <div class="sidebar-header">
            <div class="app-brand">
                <i class="fab fa-stripe app-logo"></i>
                <a href="../index.php" class="app-link">
                    <span class="app-name">StripeLabApp</span>
                </a>
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
            <section id="dashboard-overview" class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">Resumen de Base de Datos</h2>
                    <p class="section-subtitle">Vista general del estado actual de la base de datos Stripe</p>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon table-icon"><i class="fas fa-table"></i></div>
                        <div class="stat-info">
                            <h3 id="total-tables" class="stat-value">0</h3>
                            <p class="stat-label">Tablas</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon field-icon"><i class="fas fa-columns"></i></div>
                        <div class="stat-info">
                            <h3 id="total-fields" class="stat-value">0</h3>
                            <p class="stat-label">Campos</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon payment-icon"><i class="fas fa-credit-card"></i></div>
                        <div class="stat-info">
                            <h3 id="total-payments" class="stat-value">0</h3>
                            <p class="stat-label">Pagos</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon subscription-icon"><i class="fas fa-sync"></i></div>
                        <div class="stat-info">
                            <h3 id="total-subscriptions" class="stat-value">0</h3>
                            <p class="stat-label">Suscripciones</p>
                        </div>
                    </div>
                </div>
            </section>
            <section id="table-details" class="dashboard-section hidden">
                <div class="section-header with-actions">
                    <div class="section-header-left">
                        <h2 class="section-title">Detalles de la Tabla: <span id="current-table-name" class="highlight-text">-</span></h2>
                        <p class="section-subtitle">Estructura y metadatos de la tabla seleccionada</p>
                    </div>
                    <div class="table-actions">
                        <button id="view-data-btn" class="btn btn-tab"><i class="fas fa-eye"></i><span>Ver Datos</span></button>
                        <button id="structure-btn" class="btn btn-tab active"><i class="fas fa-sitemap"></i><span>Estructura</span></button>
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
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </section>
            <section id="data-preview" class="dashboard-section hidden">
                <div class="section-header with-actions">
                    <div class="section-header-left">
                        <h2 class="section-title">Datos de: <span id="preview-table-name" class="highlight-text">-</span></h2>
                        <p class="section-subtitle">Vista previa de los registros almacenados</p>
                    </div>
                    <div class="table-pagination">
                        <button id="prev-page" class="pagination-btn"><i class="fas fa-chevron-left"></i></button>
                        <span id="page-info" class="page-info">Página 1 de 1</span>
                        <button id="next-page" class="pagination-btn"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="card table-card">
                    <div class="table-container">
                        <table id="data-table" class="data-table">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </section>
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
                    <a href="../index.php">Volver al Inicio</a>
                </div>
            </div>
        </footer>
    </main>
</div>
<script src="./assets/js/panel.js"></script>
</body>
</html>