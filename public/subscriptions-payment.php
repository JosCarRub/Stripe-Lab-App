<?php
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', __DIR__ . '/..');
}

require_once PROJECT_ROOT . '/vendor/autoload.php';

\config\Bootstrap::initialize(PROJECT_ROOT);

$allPlans = \config\Bootstrap::getDisplayPlans();


use App\Commons\Enums\StripeProductsTypeEnum;

$subscriptionPlans = array_filter($allPlans, function($key) {
    return $key !== StripeProductsTypeEnum::ONE_PAYMENT->value;
}, ARRAY_FILTER_USE_KEY);
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planes de Suscripción - StripeLabApp</title>
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <script src="https://js.stripe.com/v3/"></script>
    <link rel="stylesheet" href="assets/css/subscriptions-payment.css">
</head>
<body>
<div class="app-container">
    <!-- Sidebar -->
    <aside class="app-sidebar">
        <div class="sidebar-top">
            <div class="app-brand">
                <i class="fab fa-stripe app-logo"></i>
                <span class="app-name">StripeLabApp</span>
            </div>

            
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

        <div id="app-loading-overlay">
            <div class="spinner"></div>
            <p class="loading-text">Procesando tu suscripción...</p>
        </div>

        <div class="content-wrapper">
            <section class="subscription-header-section">
                <div class="section-header">
                    <h1 class="section-title">
                        <span class="section-title-highlight">Planes de Suscripción</span>
                    </h1>
                    <p class="section-subtitle">Accede a funcionalidades premium con nuestros planes flexibles y escalables</p>
                </div>
            </section>

            <section class="subscription-plans-section">
                <?php if (empty($subscriptionPlans)): ?>
                    <div class="empty-plans-message">
                        <div class="empty-icon"><i class="fas fa-calendar-times"></i></div>
                        <h3 class="empty-title">No hay planes disponibles</h3>
                        <p class="empty-text">No hay planes de suscripción disponibles en este momento. Por favor, inténtalo más tarde.</p>
                    </div>
                <?php else: ?>
                    <div class="plans-grid">
                        <?php
                        foreach ($subscriptionPlans as $type => $plan):
                            $planName = $plan['name'] ?? 'Plan Desconocido';
                            $planPrice = $plan['price'] ?? 'N/A';
                            $planPeriod = $plan['period'] ?? '';
                            $planDescription = $plan['description'] ?? 'Descripción no disponible.';
                            $planFeatures = $plan['features'] ?? [];
                            $planLookupKey = $plan['lookup_key'] ?? '';
                            $isHighlighted = $plan['highlight'] ?? false;
                            ?>
                            <div class="subscription-card <?= $isHighlighted ? 'subscription-card-highlighted' : '' ?>">
                                <?php if ($isHighlighted): ?>
                                <?php endif; ?>

                                <div class="subscription-card-header">
                                    <div class="subscription-icon">
                                        <i class="fas <?= $type === StripeProductsTypeEnum::YEARLY_SUBSCRIPTION->value ? 'fa-crown' : 'fa-star' ?>"></i>
                                    </div>
                                    <h2 class="subscription-name"><?= htmlspecialchars($planName) ?></h2>
                                    <div class="subscription-price">
                                        <span class="price-amount"><?= htmlspecialchars($planPrice) ?></span>
                                        <span class="price-period">por <?= htmlspecialchars($planPeriod) ?></span>
                                    </div>
                                </div>

                                <div class="subscription-card-body">
                                    <p class="subscription-description"><?= htmlspecialchars($planDescription) ?></p>

                                    <div class="subscription-features">
                                        <h3 class="features-title">Incluye:</h3>
                                        <ul class="features-list">
                                            <?php foreach ($planFeatures as $feature): ?>
                                                <li><i class="fas fa-check-circle"></i> <?= htmlspecialchars($feature) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>

                                <div class="subscription-card-footer">
                                    <button class="btn <?= $isHighlighted ? 'btn-primary' : 'btn-outline' ?> btn-block subscription-button"
                                            data-lookup="<?= htmlspecialchars($planLookupKey) ?>"
                                        <?= empty($planLookupKey) ? 'disabled' : '' ?>>
                                        <i class="fas <?= $isHighlighted ? 'fa-rocket' : 'fa-check' ?>"></i>
                                        <span><?= empty($planLookupKey) ? 'No Disponible' : 'Suscribirse Ahora' ?></span>
                                    </button>
                                    <!-- Contenedor para errores específicos -->
                                    <div class="payment-error-display-container"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="subscription-benefits-section">
                <div class="section-header">
                    <h2 class="subsection-title">Beneficios de la Suscripción</h2>
                    <p class="subsection-subtitle">Descubre las ventajas de ser un miembro premium</p>
                </div>

                <div class="benefits-grid">
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <h3 class="benefit-title">Ahorro Garantizado</h3>
                        <p class="benefit-text">Ahorra hasta un 40% con nuestros planes anuales en comparación con los pagos mensuales.</p>
                    </div>

                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <h3 class="benefit-title">Actualizaciones Prioritarias</h3>
                        <p class="benefit-text">Accede a las nuevas funcionalidades y mejoras antes que el resto de usuarios.</p>
                    </div>

                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3 class="benefit-title">Soporte Exclusivo</h3>
                        <p class="benefit-text">Soporte técnico prioritario 24/7 con tiempos de respuesta garantizados.</p>
                    </div>

                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-cloud-download-alt"></i>
                        </div>
                        <h3 class="benefit-title">Acceso Ilimitado</h3>
                        <p class="benefit-text">Sin restricciones de uso ni limitaciones en las funcionalidades principales.</p>
                    </div>
                </div>
            </section>

            <section class="subscription-comparison-section">
                <div class="section-header">
                    <h2 class="subsection-title">Comparativa de Planes</h2>
                    <p class="subsection-subtitle">Elige el plan que mejor se adapte a tus necesidades</p>
                </div>

                <div class="comparison-container">
                    <div class="comparison-table-wrapper">
                        <table class="comparison-table">
                            <thead>
                            <tr>
                                <th class="feature-cell">Características</th>
                                <th class="plan-cell">Mensual</th>
                                <th class="plan-cell highlighted">Anual</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>Acceso a todas las funciones</td>
                                <td><i class="fas fa-check text-success"></i></td>
                                <td><i class="fas fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td>Actualizaciones</td>
                                <td><i class="fas fa-check text-success"></i></td>
                                <td><i class="fas fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td>Soporte prioritario</td>
                                <td><i class="fas fa-times text-danger"></i></td>
                                <td><i class="fas fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td>Recursos adicionales</td>
                                <td>Básicos</td>
                                <td>Premium</td>
                            </tr>
                            <tr>
                                <td>Ahorro anual</td>
                                <td>0%</td>
                                <td>20%</td>
                            </tr>
                            </tbody>
                        </table>
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

<script>
    // Hacer la clave pública de Stripe disponible
    const STRIPE_PUBLISHABLE_KEY = '<?= STRIPE_PUBLISHABLE_KEY ?>';
</script>
<script>

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof STRIPE_PUBLISHABLE_KEY === 'undefined' || !STRIPE_PUBLISHABLE_KEY) {
            console.error('Stripe public key no está definida (STRIPE_PUBLISHABLE_KEY).');
            document.querySelectorAll('.subscription-button').forEach(button => button.disabled = true);
            return;
        }
        const stripe = Stripe(STRIPE_PUBLISHABLE_KEY);
        const loadingOverlay = document.getElementById('app-loading-overlay');



        function showLoading() {
            if (loadingOverlay) loadingOverlay.style.display = 'flex';
        }

        function hideLoading() {
            if (loadingOverlay) loadingOverlay.style.display = 'none';
        }


        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.app-container').classList.toggle('sidebar-collapsed');
        });

        const themeToggleInput = document.getElementById('theme-toggle-input');
        if (themeToggleInput) {
            const storedTheme = localStorage.getItem('theme');
            const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const currentTheme = storedTheme || preferredTheme;
            document.documentElement.setAttribute('data-theme', currentTheme);
            if (currentTheme === 'dark') {
                themeToggleInput.checked = true;
            }
            themeToggleInput.addEventListener('change', () => {
                const selectedTheme = themeToggleInput.checked ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', selectedTheme);
                localStorage.setItem('theme', selectedTheme);
            });
        }

        document.querySelectorAll('.subscription-button').forEach(button => {
            const lookupKey = button.dataset.lookup;

            if (!lookupKey) {
                console.warn("Botón de suscripción sin data-lookup:", button);
                button.disabled = true;
                const span = button.querySelector('span');
                if (span) span.textContent = 'No Configurado';
                return;
            }

            button.addEventListener('click', async (event) => {
                console.log("Frontend: Botón 'Suscribirse Ahora' clickeado.", { lookup_key: lookupKey });

                // Limpiar errores anteriores de este botón específico
                const planCardFooter = event.currentTarget.closest('.subscription-card-footer');
                const errorContainer = planCardFooter ? planCardFooter.querySelector('.payment-error-display-container') : null;
                if (errorContainer) {
                    errorContainer.innerHTML = ''; // Limpiar errores previos
                }

                try {
                    showLoading();

                    const response = await fetch(`./v1/create_subscription_session.php?lookup_key=${encodeURIComponent(lookupKey)}`);

                    if (!response.ok) {
                        let errorMsg = `Error del servidor: ${response.status}`;
                        try {
                            const errorData = await response.json();
                            errorMsg = errorData.error || errorMsg;
                        } catch (e) {
                            const textError = await response.text();
                            if(textError) errorMsg += ` - ${textError}`;
                        }
                        console.error("Frontend Error: Falló la creación de sesión de suscripción.", { status: response.status, lookup_key: lookupKey, details: errorMsg });
                        throw new Error(errorMsg);
                    }

                    const session = await response.json();

                    if (session.error) {
                        console.error("Frontend Error: Error devuelto por el servidor al crear sesión de suscripción.", { error: session.error, lookup_key: lookupKey });
                        throw new Error(session.error);
                    }

                    console.log("Frontend: Sesión de Stripe Checkout (suscripción) obtenida.", { session_id: session.id });

                    const result = await stripe.redirectToCheckout({ sessionId: session.id });

                    if (result.error) {
                        console.error("Frontend Error: redirectToCheckout (suscripción) falló.", { error_message: result.error.message, session_id: session.id });
                        hideLoading();
                        throw new Error(result.error.message);
                    }
                } catch (error) {
                    hideLoading();
                    console.error('Error en el proceso de suscripción:', error);
                    if (errorContainer) {
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'alert alert-danger mt-2 animate__animated animate__shakeX';
                        errorDiv.textContent = 'Error: ' + error.message;
                        errorContainer.appendChild(errorDiv);
                    } else {
                        alert('Ha ocurrido un error: ' + error.message);
                    }
                }
            });
        });
    });
</script>
</body>
</html>