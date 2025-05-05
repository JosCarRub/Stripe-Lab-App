<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\commons\enums\StripeProductsTypeEnum;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Clave pública de Stripe
$stripePublicKey = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? 'pk_test_51RHJ61P71JLI6sb9n5j8nuXdad0jWnTz03XP7QaWF09jKsZnMsGEXURA8rMCd23unCphMA88UBNfKkxwB7YREaVy00AkXqDYSx';

// Planes disponibles basados en tu enumeración
$plans = [
    StripeProductsTypeEnum::MONTHLY_SUBSCRIPTION->value => [
        'name' => 'Suscripción Mensual',
        'description' => 'Acceso completo con pago mensual',
        'price' => '9,99€',
        'period' => 'mes',
        'highlight' => false,
        'lookup_key' => 'monthly_subscriptions',
        'features' => [
            'Acceso a todas las funcionalidades',
            'Soporte técnico estándar',
            'Actualizaciones mensuales',
            'Hasta 3 proyectos'
        ]
    ],
    StripeProductsTypeEnum::YEARLY_SUBSCRIPTION->value => [
        'name' => 'Suscripción Anual',
        'description' => 'Acceso completo con pago anual (ahorro del 20%)',
        'price' => '95,88€',
        'period' => 'año',
        'highlight' => true,
        'lookup_key' => 'annual_payment',
        'features' => [
            'Acceso a todas las funcionalidades',
            'Soporte técnico prioritario',
            'Actualizaciones en primicia',
            'Proyectos ilimitados',
            '2 meses gratis'
        ]
    ],
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StripeLabApp - Sistema de Pagos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Stripe.js -->
    <script src="https://js.stripe.com/v3/"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<!-- Loading overlay -->
<div id="index-loading-overlay">
    <div id="index-spinner"></div>
    <p id="index-loading-text">Procesando tu pago...</p>
</div>

<div id="index-wrapper">
    <!-- Sidebar -->
    <nav id="index-sidebar">
        <div id="index-sidebar-header">
            <i class="fab fa-stripe" id="index-app-logo"></i>
            <h1 id="index-app-title">StripeLabApp</h1>
        </div>

        <ul id="index-sidebar-menu">
            <li class="index-menu-item active">
                <a href="index.php" class="index-menu-link">
                    <i class="fas fa-home"></i>
                    <span>Inicio</span>
                </a>
            </li>
            <li class="index-menu-item">
                <a href="#" class="index-menu-link">
                    <i class="fas fa-credit-card"></i>
                    <span>Pagos</span>
                </a>
            </li>
            <li class="index-menu-item">
                <a href="#" class="index-menu-link">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Mis Facturas</span>
                </a>
            </li>
            <li class="index-menu-item">
                <a href="#" class="index-menu-link">
                    <i class="fas fa-users"></i>
                    <span>Facturas de Clientes</span>
                </a>
            </li>
            <li class="index-menu-item">
                <a href="#" class="index-menu-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Panel de Control</span>
                </a>
            </li>
            <li class="index-menu-item">
                <a href="#index-contact" class="index-menu-link">
                    <i class="fas fa-question-circle"></i>
                    <span>Ayuda</span>
                </a>
            </li>
        </ul>

        <div id="index-sidebar-footer">
            <a href="#" class="index-menu-link">
                <i class="fas fa-cog"></i>
                <span>Configuración</span>
            </a>
            <a href="#" class="index-menu-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div id="index-content">
        <div id="index-top-bar">
            <button id="index-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <div id="index-user-menu">
                <img src="https://ui-avatars.com/api/?name=User&background=6772e5&color=fff" alt="User" id="index-user-avatar">
                <span id="index-username">Usuario</span>
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>

        <!-- Header Section -->
        <header id="index-header-section">
            <div class="container">
                <h1 id="index-header-title">Sistema de Pagos Integrado con Stripe</h1>
                <p id="index-header-subtitle">Plataforma de prueba para integración con Stripe. Experimenta con pagos únicos y suscripciones en un entorno de prueba seguro.</p>
            </div>
        </header>

        <main id="index-main-container" class="container">
            <!-- One-time Payment Section -->
            <section id="index-one-time" class="index-section">
                <h2 id="index-one-time-title" class="index-section-title">Pago Único</h2>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div id="index-one-time-payment">
                            <div class="mb-4">
                                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/stripe/stripe-original.svg" alt="Stripe" width="60">
                            </div>
                            <h3 id="index-one-time-heading">Acceso Estándar</h3>
                            <div id="index-one-time-price" class="index-card-price">10,00€</div>
                            <p id="index-one-time-description" class="mb-4">Acceso completo a todas las funcionalidades con un pago único. Ideal para probar nuestra plataforma.</p>
                            <button id="single-payment-btn" class="index-btn-primary" data-type="<?= StripeProductsTypeEnum::ONE_PAYMENT->value ?>">
                                <i class="fas fa-credit-card me-2"></i>Realizar Pago
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Subscription Section -->
            <section id="index-subscriptions" class="index-section">
                <h2 id="index-subscriptions-title" class="index-section-title">Planes de Suscripción</h2>
                <div class="row">
                    <?php foreach ($plans as $type => $plan): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="index-card <?= $plan['highlight'] ? 'index-highlight' : '' ?>">
                                <div class="index-card-header">
                                    <?php if ($plan['highlight']): ?>

                                    <?php endif; ?>
                                    <h3 id="index-plan-title-<?= $type ?>"><?= htmlspecialchars($plan['name']) ?></h3>
                                    <div id="index-plan-price-<?= $type ?>" class="index-card-price"><?= htmlspecialchars($plan['price']) ?></div>
                                    <div id="index-plan-period-<?= $type ?>" class="index-card-period">por <?= htmlspecialchars($plan['period']) ?></div>
                                </div>
                                <div class="index-card-body">
                                    <p id="index-plan-description-<?= $type ?>"><?= htmlspecialchars($plan['description']) ?></p>
                                    <ul id="index-plan-features-<?= $type ?>" class="index-feature-list">
                                        <?php foreach ($plan['features'] as $index => $feature): ?>
                                            <li id="index-plan-feature-<?= $type ?>-<?= $index ?>"><?= htmlspecialchars($feature) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div class="index-card-footer">
                                    <button id="index-subscription-btn-<?= $type ?>"
                                            class="<?= $plan['highlight'] ? 'index-btn-primary' : 'index-btn-outline' ?> subscription-btn w-100"
                                            data-type="<?= htmlspecialchars($type) ?>"
                                            data-lookup="<?= htmlspecialchars($plan['lookup_key']) ?>">
                                        <i class="fas fa-check-circle me-2"></i>Suscribirse
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Contact Section -->
            <section id="index-contact" class="index-section">
                <h2 id="index-contact-title" class="index-section-title">¿Necesitas Ayuda?</h2>
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <div class="index-card">
                            <div class="index-card-body p-4">
                                <div class="row">
                                    <div class="col-md-6 mb-4 mb-md-0">
                                        <h4 id="index-contact-heading">Contacto</h4>
                                        <p id="index-contact-text">Si tienes alguna pregunta sobre nuestros planes de pago o necesitas asistencia, no dudes en contactarnos.</p>
                                        <ul id="index-contact-list" class="list-unstyled">
                                            <li id="index-contact-email" class="mb-2"><i class="fas fa-envelope me-2 text-primary"></i> soporte@stripelab.com</li>
                                            <li id="index-contact-phone" class="mb-2"><i class="fas fa-phone me-2 text-primary"></i> +34 912 345 678</li>
                                            <li id="index-contact-address"><i class="fas fa-map-marker-alt me-2 text-primary"></i> Calle Principal 123, Madrid</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h4 id="index-faq-heading">Preguntas Frecuentes</h4>
                                        <div class="mb-3">
                                            <h6 id="index-faq-question-1" class="fw-bold">¿Cómo funciona el sistema de prueba?</h6>
                                            <p id="index-faq-answer-1" class="small">Esta es una plataforma de prueba para integración con Stripe. Los pagos son simulados.</p>
                                        </div>
                                        <div>
                                            <h6 id="index-faq-question-2" class="fw-bold">¿Puedo cancelar mi suscripción?</h6>
                                            <p id="index-faq-answer-2" class="small">Sí, puedes cancelar tu suscripción en cualquier momento desde tu panel de usuario.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer id="index-footer">
            <div class="container">
                <div id="index-copyright" class="text-center">
                    <p>&copy; <?= date('Y') ?> StripeLabApp. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Inicializar Stripe (con la clave pública)
    const stripe = Stripe('<?= $stripePublicKey ?>');
    const loadingOverlay = document.getElementById('index-loading-overlay');

    // Toggle sidebar
    document.getElementById('index-menu-toggle').addEventListener('click', function() {
        document.getElementById('index-wrapper').classList.toggle('collapsed');
    });

    // Configurar el botón de pago único
    document.getElementById('single-payment-btn').addEventListener('click', async () => {
        try {
            showLoading();
            // Corregir la ruta del fetch para que coincida con tu estructura
            const response = await fetch('./create_payment_session.php');

            if (!response.ok) {
                throw new Error('Error al conectar con el servidor: ' + response.status);
            }

            const session = await response.json();

            if (session.error) {
                throw new Error(session.error);
            }

            // Redirigir a la página de checkout de Stripe
            const result = await stripe.redirectToCheckout({ sessionId: session.id });

            if (result.error) {
                throw new Error(result.error.message);
            }
        } catch (error) {
            hideLoading();
            console.error('Error:', error);
            alert('Ha ocurrido un error: ' + error.message);
        }
    });

    // Configurar los botones de suscripción
    document.querySelectorAll('.subscription-btn').forEach(button => {
        button.addEventListener('click', async (event) => {
            try {
                showLoading();
                const lookupKey = event.target.dataset.lookup;
                // Corregir la ruta del fetch para que coincida con tu estructura
                const response = await fetch(`./create_subscription_session.php?lookup_key=${encodeURIComponent(lookupKey)}`);

                if (!response.ok) {
                    throw new Error('Error al conectar con el servidor: ' + response.status);
                }

                const session = await response.json();

                if (session.error) {
                    throw new Error(session.error);
                }

                // Redirigir a la página de checkout de Stripe
                const result = await stripe.redirectToCheckout({ sessionId: session.id });

                if (result.error) {
                    throw new Error(result.error.message);
                }
            } catch (error) {
                hideLoading();
                console.error('Error:', error);
                alert('Ha ocurrido un error: ' + error.message);
            }
        });
    });

    function showLoading() {
        loadingOverlay.style.display = 'flex';
    }

    function hideLoading() {
        loadingOverlay.style.display = 'none';
    }
</script>
</body>
</html>