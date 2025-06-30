<?php
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', __DIR__ . '/..'); 
}

// carga el autoloader de Composer
require_once PROJECT_ROOT . '/vendor/autoload.php';

// inicializa la aplicación a través de Bootstrap
\config\Bootstrap::initialize(PROJECT_ROOT);


$plans = \config\Bootstrap::getDisplayPlans();

use App\Commons\Enums\StripeProductsTypeEnum;
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StripeLabApp - Sistema de Pagos</title>
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://js.stripe.com/v3/"></script>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<!-- Loading overlay -->
<div id="app-loading-overlay">
    <div class="spinner"></div>
    <p class="loading-text">Procesando el pago...</p>
</div>

<div class="app-container">
    <!-- Sidebar -->
    <aside class="app-sidebar">
        <div class="sidebar-top">
            <div class="app-brand">
                <i class="fab fa-stripe app-logo"></i>
                <span class="app-name">StripeLabApp</span>
            </div>

            <label class="theme-toggle" aria-label="Cambiar modo oscuro">
                <input type="checkbox" id="theme-toggle-input">
                <span class="theme-slider">
                    <i class="fas fa-sun sun-icon"></i>
                    <i class="fas fa-moon moon-icon"></i>
                </span>
            </label>
        </div>

        <div class="nav-separator">
            <span>Navegación</span>
        </div>

        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item active">
                <div class="nav-icon"><i class="fas fa-home"></i></div>
                <span class="nav-label">Inicio</span>
            </a>

            <a href="single-payment.php" class="nav-item">
                <div class="nav-icon"><i class="fas fa-credit-card"></i></div>
                <span class="nav-label">Pago Único</span>
            </a>

            <a href="subscriptions-payment.php" class="nav-item">
                <div class="nav-icon"><i class="fas fa-sync-alt"></i></div>
                <span class="nav-label">Pagar Suscripción</span>
            </a>

            <a href="invoices.php" class="nav-item">
                <div class="nav-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <span class="nav-label">Facturas</span>
            </a>

            <a href="view-subscriptions.php" class="nav-item">
                <div class="nav-icon"><i class="fas fa-users"></i></div>
                <span class="nav-label">Gestionar Suscripciones</span>
            </a>

            <div class="nav-separator">
                <span>Administración</span>
            </div>

            <a href="admin/panel.php" class="nav-item">
                <div class="nav-icon"><i class="fas fa-tachometer-alt"></i></div>
                <span class="nav-label">Panel de Control</span>
            </a>


            <a href="doc/documentation-index.html" class="nav-item">
                <div class="nav-icon"><i class="fas fa-book"></i></div>
                <span class="nav-label">Documentación</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="https://github.com/JosCarRub" target="_blank" rel="noopener noreferrer" class="github-link">
                <i class="bi bi-github"></i>
            </a>
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

        <div class="content-wrapper">
            <section class="hero-section">
                <div class="hero-content">
                    <div class="hero-label">Plataforma de Pagos</div>
                    <h1 class="hero-title">
                        <span class="hero-title-highlight">StripeLabApp</span>
                        Sistema de Gestión de Pagos
                    </h1>
                    <p class="hero-subtitle">Plataforma integral para la gestión de pagos únicos y suscripciones con Stripe, optimizada para máxima seguridad y facilidad de uso.</p>
                    <div class="hero-cta">
                        <a href="single-payment.php" class="btn btn-primary">
                            <i class="fas fa-credit-card"></i>
                            <span>Realizar Pago</span>
                        </a>
                        <a href="view-subscriptions.php" class="btn btn-secondary">
                            <i class="fas fa-sync-alt"></i>
                            <span>Gestionar Suscripciones</span>
                        </a>
                        <a href="doc/documentation-index.html" class="btn btn-outline">
                            <i class="fas fa-book"></i>
                            <span>Documentación</span>
                        </a>
                    </div>

                    <div class="hero-cards">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value">Orientado a eventos</div>
                                <div class="stat-label">Uptime</div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value">Weebhok Security</div>
                                <div class="stat-label">Verificación de signature</div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="hero-image">
                    <div class="card-visual">
                        <div class="card-chip"></div>
                        <div class="card-logo">
                            <i class="fab fa-stripe"></i>
                        </div>
                        <div class="card-number">•••• •••• •••• 4242</div>
                        <div class="card-details">
                            <div class="card-holder">STRIPELAB USER</div>
                            <div class="card-expiry">08/28</div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="features-section">
                <div class="section-header">
                    <h2 class="section-title">Características Principales</h2>
                    <p class="section-subtitle">Descubre todas las herramientas que StripeLabApp te ofrece para gestionar tus pagos</p>
                </div>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon payment-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h3 class="feature-title">Pagos Únicos</h3>
                        <p class="feature-desc">Procesa pagos de una sola vez con total seguridad y verifica en tiempo real el estado de cada transacción.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon subscription-icon">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <h3 class="feature-title">Suscripciones</h3>
                        <p class="feature-desc">Gestiona suscripciones recurrentes con planes personalizables y ciclos de facturación flexibles.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon invoice-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h3 class="feature-title">Facturación</h3>
                        <p class="feature-desc">Sistema completo de facturas con histórico, exportación a PDF y notificaciones automáticas.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon analytics-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <h3 class="feature-title">Panel de Control</h3>
                        <p class="feature-desc">Visualiza y gestiona todas las tablas alojadas en el contenedor de forma intuitiva y completa desde un único panel.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon security-icon">
                            <i class="fas fa-list-alt"></i>
                        </div>
                        <h3 class="feature-title">Sistema de Logs</h3>
                        <p class="feature-desc">Monitoriza en tiempo real todas las operaciones y eventos que ocurren en el servidor con registro detallado y personalizable.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon integration-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h3 class="feature-title">Documentación</h3>
                        <p class="feature-desc">Accede a guías detalladas, tutoriales y referencia técnica completa de la API directamente desde la aplicación.</p>
                    </div>
                </div>
            </section>


            <section class="integration-section">
                <div class="section-header">
                    <h2 class="section-title">Integración sin Complicaciones</h2>
                    <p class="section-subtitle">Conecta StripeLabApp con una base de datos utilizando este docker compose</p>
                </div>

                <div class="code-snippet">
                    <div class="code-header">
                        <span class="code-language">docker-compose.yml</span>
                        <button class="copy-button" data-clipboard-target="#integration-code">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <pre class="code-content"><code id="integration-code">version: '3.8'

services:
  mysql:
    image: mysql:8.0
    container_name: stripe_mysql
    restart: always
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: stripe_lab
      MYSQL_USER: test_user
      MYSQL_PASSWORD: password
    ports:
      - "3307:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    command: --default-authentication-plugin=mysql_native_password

volumes:
  mysql_data:</code></pre>
                </div>

                <div class="integration-platforms">
                    <div class="platform-icon">
                        <i class="fab fa-php"></i>
                    </div>
                    <div class="platform-icon">
                        <i class="fab fa-js"></i>
                    </div>
                    <div class="platform-icon">
                        <i class="fab fa-stripe"></i>
                    </div>
                    <div class="platform-icon">
                        <i class="fab fa-docker"></i>
                    </div>
                </div>
            </section>
        </div>

        <footer class="app-footer">
            <div class="footer-content">
                <div class="footer-copyright">
                    © <?= date('Y') ?> StripeLabApp. JosCarRub.
                </div>
                <div class="footer-links">
                <a href="doc/documentation-index.html">Documentación</a>
                </div>
            </div>
        </footer>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const stripe = Stripe('<?= STRIPE_PUBLISHABLE_KEY ?>');
    const loadingOverlay = document.getElementById('app-loading-overlay');

    // Toggle sidebar
    document.getElementById('sidebar-toggle').addEventListener('click', function() {
        document.querySelector('.app-container').classList.toggle('sidebar-collapsed');
    });

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

    // Pricing tabs
    document.querySelectorAll('.pricing-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');

            // Remove active class from all tabs
            document.querySelectorAll('.pricing-tab').forEach(t => {
                t.classList.remove('active');
            });

            // Hide all panels
            document.querySelectorAll('.pricing-panel').forEach(panel => {
                panel.classList.remove('active');
            });

            // Add active class to clicked tab
            this.classList.add('active');

            // Show corresponding panel
            document.getElementById(`${tabId}-panel`).classList.add('active');
        });
    });

    // Copy code button
    document.querySelector('.copy-button').addEventListener('click', function() {
        const code = document.getElementById('integration-code').textContent;
        navigator.clipboard.writeText(code).then(() => {
            const icon = this.querySelector('i');
            icon.className = 'fas fa-check';

            setTimeout(() => {
                icon.className = 'fas fa-copy';
            }, 2000);
        });
    });

    // Single Payment Button
    const singlePaymentButton = document.getElementById('single-payment-btn');
    if (singlePaymentButton) {
        singlePaymentButton.addEventListener('click', async (event) => {
            try {
                showLoading();
                const lookupKey = event.currentTarget.dataset.lookup;
                if (!lookupKey) {
                    throw new Error('Lookup key no configurada para el pago único.');
                }
                // Endpoint en public/v1/
                const response = await fetch('./v1/create_payment_session.php?lookup_key=' + encodeURIComponent(lookupKey));
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ error: 'Error de servidor desconocido.' }));
                    throw new Error(errorData.error || `Error del servidor: ${response.status}`);
                }
                const session = await response.json();
                if (session.error) { throw new Error(session.error); }
                const result = await stripe.redirectToCheckout({ sessionId: session.id });
                if (result.error) {
                    hideLoading(); // Ocultar si redirectToCheckout falla antes de redirigir
                    throw new Error(result.error.message);
                }
            } catch (error) {
                hideLoading();
                console.error('Error en pago único:', error);
                alert('Ha ocurrido un error: ' + error.message);
            }
        });
    }

    // Subscription Buttons
    document.querySelectorAll('.subscription-btn').forEach(button => {
        button.addEventListener('click', async (event) => {
            try {
                showLoading();
                const lookupKey = event.currentTarget.dataset.lookup;
                if (!lookupKey) {
                    throw new Error('Lookup key no configurada para la suscripción.');
                }
                // Endpoint en public/v1/
                const response = await fetch(`./v1/create_subscription_session.php?lookup_key=${encodeURIComponent(lookupKey)}`);
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ error: 'Error de servidor desconocido.' }));
                    throw new Error(errorData.error || `Error del servidor: ${response.status}`);
                }
                const session = await response.json();
                if (session.error) { throw new Error(session.error); }
                const result = await stripe.redirectToCheckout({ sessionId: session.id });
                if (result.error) {
                    hideLoading();
                    throw new Error(result.error.message);
                }
            } catch (error) {
                hideLoading();
                console.error('Error en suscripción:', error);
                alert('Ha ocurrido un error: ' + error.message);
            }
        });
    });

    function showLoading() {
        if (loadingOverlay) loadingOverlay.style.display = 'flex';
    }
    function hideLoading() {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
    }
</script>
</body>
</html>