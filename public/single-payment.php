<?php

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', __DIR__ . '/..');
}
require_once PROJECT_ROOT . '/vendor/autoload.php';


\config\Bootstrap::initialize(PROJECT_ROOT);


$plans = \config\Bootstrap::getDisplayPlans();


use App\Commons\Enums\StripeProductsTypeEnum;

$oneTimePlanKey = StripeProductsTypeEnum::ONE_PAYMENT->value;
$oneTimePlanData = $plans[$oneTimePlanKey] ?? [
    'name' => 'Pago Único (Error Config)',
    'price' => 'N/A',
    'description' => 'Plan no configurado correctamente.',
    'lookup_key' => ''
];
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Único - StripeLabApp</title>
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <script src="https://js.stripe.com/v3/"></script>
    <link rel="stylesheet" href="assets/css/single-payment.css">
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
            <p class="loading-text">Procesando el pago...</p>
        </div>

        <div class="content-wrapper">
            <section class="payment-header-section">
                <div class="section-header">
                    <h1 class="section-title">
                        <span class="section-title-highlight">Pago Único</span>
                    </h1>
                    <p class="section-subtitle">Completa tu compra con un solo pago para acceder a todas las funcionalidades</p>
                </div>
            </section>

            <section class="payment-content-section">
                <!-- Plan details on the left -->
                <div class="payment-card-container">
                    <div class="plan-card">
                        <div class="plan-header">
                            <div class="plan-icon"><i class="fas fa-gem"></i></div>
                            <h2 class="plan-name"><?= htmlspecialchars($oneTimePlanData['name']) ?></h2>
                            <div class="plan-price"><?= htmlspecialchars($oneTimePlanData['price']) ?></div>
                        </div>
                        <div class="plan-body">
                            <p class="plan-description"><?= htmlspecialchars($oneTimePlanData['description']) ?></p>
                            <ul class="plan-features">
                                <li><i class="fas fa-check"></i> Acceso completo a la plataforma</li>
                                <li><i class="fas fa-check"></i> Sin cargos mensuales</li>
                                <li><i class="fas fa-check"></i> Actualizaciones gratuitas</li>
                                <li><i class="fas fa-check"></i> Soporte prioritario</li>
                            </ul>
                        </div>
                        <div class="plan-footer">
                            <button id="payment-button" class="btn btn-primary btn-block"
                                    data-lookup="<?= htmlspecialchars($oneTimePlanData['lookup_key']) ?>">
                                <i class="fas fa-credit-card"></i>
                                <span>Pagar Ahora</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Test cards showcased like real cards -->
                <div class="stripe-cards-showcase">
                    <div class="section-header">
                        <h2 class="subsection-title">Tarjetas de Prueba de Stripe</h2>
                        <p class="subsection-subtitle">Usa estas tarjetas para simular diferentes escenarios de pago</p>
                    </div>

                    <div class="cards-gallery">
                        <div class="cards-category">
                            <h3 class="cards-category-title">Pagos Exitosos</h3>
                            <div class="cards-wrapper">
                                <div class="credit-card success-card" data-card="4242424242424242">
                                    <div class="card-inner">
                                        <div class="card-front">
                                            <div class="card-bg"></div>
                                            <div class="card-chip"></div>
                                            <div class="card-logo"><i class="fab fa-cc-visa"></i></div>
                                            <div class="card-number">4242 4242 4242 4242</div>
                                            <div class="card-details">
                                                <div class="card-holder">CLIENTE EJEMPLO</div>
                                                <div class="card-exp">CUALQUIER FECHA</div>
                                            </div>
                                            <div class="card-brand">Transacción Exitosa</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="cards-category">
                            <h3 class="cards-category-title">Pruebas de Error</h3>
                            <div class="cards-wrapper">
                                <div class="credit-card decline-card" data-card="4000000000000002">
                                    <div class="card-inner">
                                        <div class="card-front">
                                            <div class="card-bg"></div>
                                            <div class="card-chip"></div>
                                            <div class="card-logo"><i class="fab fa-cc-visa"></i></div>
                                            <div class="card-number">4000 0000 0000 0002</div>
                                            <div class="card-details">
                                                <div class="card-holder">CLIENTE EJEMPLO</div>
                                                <div class="card-exp">CUALQUIER FECHA</div>
                                            </div>
                                            <div class="card-brand">Pago Rechazado</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="credit-card insufficient-card" data-card="4000000000009995">
                                    <div class="card-inner">
                                        <div class="card-front">
                                            <div class="card-bg"></div>
                                            <div class="card-chip"></div>
                                            <div class="card-logo"><i class="fab fa-cc-visa"></i></div>
                                            <div class="card-number">4000 0000 0000 9995</div>
                                            <div class="card-details">
                                                <div class="card-holder">CLIENTE EJEMPLO</div>
                                                <div class="card-exp">CUALQUIER FECHA</div>
                                            </div>
                                            <div class="card-brand">Fondos Insuficientes</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="cards-category">
                            <h3 class="cards-category-title">Autenticación</h3>
                            <div class="cards-wrapper">
                                <div class="credit-card auth-card" data-card="4000002760003184">
                                    <div class="card-inner">
                                        <div class="card-front">
                                            <div class="card-bg"></div>
                                            <div class="card-chip"></div>
                                            <div class="card-logo"><i class="fab fa-cc-visa"></i></div>
                                            <div class="card-number">4000 0027 6000 3184</div>
                                            <div class="card-details">
                                                <div class="card-holder">CLIENTE EJEMPLO</div>
                                                <div class="card-exp">CUALQUIER FECHA</div>
                                            </div>
                                            <div class="card-brand">3D Secure</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="cards-note">
                        <div class="note-icon"><i class="fas fa-info-circle"></i></div>
                        <p>Para todas las tarjetas de prueba, puedes usar <strong>cualquier fecha futura</strong> como fecha de expiración (MM/AA) y <strong>cualquier código CVC</strong> de 3 dígitos.</p>
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
    // STRIPE_PUBLISHABLE_KEY es una constante PHP definida por config/Bootstrap.php
    const stripe = Stripe('<?= STRIPE_PUBLISHABLE_KEY ?>');
    const paymentButton = document.getElementById('payment-button');
    const loadingOverlay = document.getElementById('app-loading-overlay');

    function showLoading() {
        if (loadingOverlay) loadingOverlay.style.display = 'flex';
    }

    function hideLoading() {
        if (loadingOverlay) loadingOverlay.style.display = 'none';
    }

    // Toggle para el tema
    const themeToggleInput = document.getElementById('theme-toggle-input');
    if (themeToggleInput) {
        // Cargar tema guardado o preferido por el sistema
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
            localStorage.setItem('theme', selectedTheme); // Guardar preferencia
        });
    }

    // Establecer año actual
    document.getElementById('current-year').textContent = new Date().getFullYear();

    // Toggle sidebar
    document.getElementById('sidebar-toggle').addEventListener('click', function() {
        document.querySelector('.app-container').classList.toggle('sidebar-collapsed');
    });

    // Añadir clases para efectos hover en las tarjetas de crédito
    document.querySelectorAll('.credit-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.classList.add('card-hover');
        });

        card.addEventListener('mouseleave', function() {
            this.classList.remove('card-hover');
        });

        // Permitir hacer clic para copiar números de tarjeta
        const cardNumber = card.querySelector('.card-number');
        if (cardNumber) {
            cardNumber.addEventListener('click', function() {
                const number = this.textContent.replace(/\s/g, '');
                navigator.clipboard.writeText(number).then(() => {
                    // Feedback visual
                    const originalText = this.textContent;
                    this.textContent = '¡Copiado!';
                    this.classList.add('copied');

                    setTimeout(() => {
                        this.textContent = originalText;
                        this.classList.remove('copied');
                    }, 2000);
                });
            });
        }
    });

    // Lógica para los acordeones de FAQ
    document.querySelectorAll('.faq-item').forEach(item => {
        const question = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');
        const toggleButton = item.querySelector('.faq-toggle i');

        if (question && answer && toggleButton) {
            question.addEventListener('click', () => {
                const isOpen = answer.classList.contains('open');
                // Cerrar todos los demás
                document.querySelectorAll('.faq-answer.open').forEach(openAnswer => {
                    if (openAnswer !== answer) {
                        openAnswer.classList.remove('open');
                        const otherButton = openAnswer.closest('.faq-item').querySelector('.faq-toggle i');
                        if (otherButton) {
                            otherButton.classList.remove('fa-chevron-up');
                            otherButton.classList.add('fa-chevron-down');
                        }
                    }
                });
                // Abrir/cerrar el actual
                answer.classList.toggle('open', !isOpen);
                toggleButton.classList.toggle('fa-chevron-down', isOpen);
                toggleButton.classList.toggle('fa-chevron-up', !isOpen);
            });
        }
    });


    if (paymentButton) {
        const lookupKey = paymentButton.dataset.lookup;

        if (!lookupKey) {
            console.error("Error de Configuración: El botón de pago no tiene un 'data-lookup' definido.");
            paymentButton.disabled = true;
            paymentButton.innerHTML = '<i class="fas fa-exclamation-circle"></i> <span>Error Config</span>';
        } else {
            paymentButton.addEventListener('click', async (event) => {
                console.log("Frontend: Botón 'Pagar Ahora' clickeado.", { lookup_key: lookupKey });
                try {
                    showLoading();

                    // La URL del endpoint ahora es relativa a la carpeta 'v1'
                    // Asumiendo que este index.php está en public/ y los endpoints en public/v1/
                    const response = await fetch('./v1/create_payment_session.php?lookup_key=' + encodeURIComponent(lookupKey));

                    if (!response.ok) {
                        let errorMsg = `Error del servidor: ${response.status}`;
                        try {
                            const errorData = await response.json(); // Intenta parsear como JSON
                            errorMsg = errorData.error || errorMsg; // Usa el error del JSON si existe
                        } catch (e) {
                            // Si no es JSON, usa el texto de la respuesta si está disponible
                            const textError = await response.text();
                            if(textError) errorMsg += ` - ${textError}`;
                        }
                        console.error("Frontend Error: Falló la creación de sesión de pago.", { status: response.status, lookup_key: lookupKey, details: errorMsg });
                        throw new Error(errorMsg);
                    }

                    const session = await response.json();

                    if (session.error) {
                        console.error("Frontend Error: Error devuelto por el servidor al crear sesión.", { error: session.error, lookup_key: lookupKey });
                        throw new Error(session.error);
                    }

                    console.log("Frontend: Sesión de Stripe Checkout obtenida.", { session_id: session.id });

                    const result = await stripe.redirectToCheckout({ sessionId: session.id });

                    if (result.error) {
                        console.error("Frontend Error: redirectToCheckout falló.", { error_message: result.error.message, session_id: session.id });
                        hideLoading();
                        throw new Error(result.error.message);
                    }
                    // Si redirectToCheckout tiene éxito, el usuario es redirigido.
                    // hideLoading() no se llamará aquí a menos que haya un error *antes* de la redirección.

                } catch (error) {
                    hideLoading();
                    console.error('Error en el proceso de pago:', error);
                    // Mostrar error en la UI de forma más amigable
                    const paymentDetailsDiv = document.querySelector('.payment-card-container');
                    let errorDisplay = document.getElementById('payment-error-display');
                    if (!errorDisplay && paymentDetailsDiv) {
                        errorDisplay = document.createElement('div');
                        errorDisplay.id = 'payment-error-display';
                        errorDisplay.className = 'alert alert-danger mt-3 animate__animated animate__shakeX';
                        paymentDetailsDiv.appendChild(errorDisplay);
                    }
                    if (errorDisplay) {
                        errorDisplay.textContent = 'Error: ' + error.message;
                        errorDisplay.style.display = 'block';
                    } else {
                        alert('Ha ocurrido un error: ' + error.message);
                    }
                }
            });
        }
    } else {
        console.error("Error: Botón de pago con ID 'payment-button' no encontrado en el DOM.");
    }
</script>
</body>
</html>