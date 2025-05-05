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
        'lookup_key' => 'price_monthly_key'
    ],
    StripeProductsTypeEnum::YEARLY_SUBSCRIPTION->value => [
        'name' => 'Suscripción Anual',
        'description' => 'Acceso completo con pago anual (ahorro del 20%)',
        'price' => '95,88€',
        'period' => 'año',
        'highlight' => true,
        'lookup_key' => 'price_annual_key'
    ],
];
?>

    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sistema de Pagos</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Asegurarse de que Stripe.js se carga correctamente -->
        <script src="https://js.stripe.com/v3/"></script>
        <style>
            :root {
                --primary-color: #4f46e5;
                --secondary-color: #4338ca;
                --light-color: #f3f4f6;
                --dark-color: #1f2937;
                --success-color: #047857;
                --warning-color: #b91c1c;
            }

            body {
                font-family: 'Arial', sans-serif;
                line-height: 1.6;
                color: var(--dark-color);
                background-color: #f4f5f7;
                padding-top: 2rem;
                padding-bottom: 2rem;
            }

            .header {
                text-align: center;
                margin-bottom: 3rem;
            }

            .payment-section {
                margin-bottom: 4rem;
            }

            .payment-options {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 1.5rem;
            }

            .payment-card {
                background-color: white;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                padding: 2rem;
                width: 100%;
                max-width: 350px;
                text-align: center;
                transition: transform 0.3s, box-shadow 0.3s;
            }

            .payment-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
            }

            .payment-card.highlight {
                border: 2px solid var(--primary-color);
                position: relative;
            }

            .highlight-badge {
                position: absolute;
                top: -15px;
                right: -15px;
                background-color: var(--primary-color);
                color: white;
                padding: 0.5rem 1rem;
                border-radius: 30px;
                font-size: 0.8rem;
                font-weight: bold;
            }

            .card-title {
                font-size: 1.5rem;
                font-weight: bold;
                margin-bottom: 1rem;
                color: var(--dark-color);
            }

            .card-price {
                font-size: 2rem;
                font-weight: bold;
                color: var(--primary-color);
                margin-bottom: 0.5rem;
            }

            .card-period {
                font-size: 1rem;
                color: #6b7280;
                margin-bottom: 1.5rem;
            }

            .card-description {
                margin-bottom: 1.5rem;
                color: #4b5563;
            }

            .btn-payment {
                background-color: var(--primary-color);
                color: white;
                border: none;
                padding: 0.75rem 1.5rem;
                border-radius: 6px;
                cursor: pointer;
                font-size: 1rem;
                font-weight: 600;
                transition: background-color 0.3s;
                width: 100%;
            }

            .btn-payment:hover {
                background-color: var(--secondary-color);
            }

            .one-time-payment {
                text-align: center;
                background-color: white;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                padding: 2rem;
                max-width: 500px;
                margin: 0 auto;
            }

            .section-title {
                text-align: center;
                margin-bottom: 2rem;
                color: var(--dark-color);
                font-weight: bold;
            }

            .footer {
                text-align: center;
                margin-top: 3rem;
                color: #6b7280;
                font-size: 0.9rem;
            }

            .payment-options-container {
                display: flex;
                justify-content: center;
            }

            #loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
                display: none;
            }

            .spinner {
                border: 4px solid rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                border-top: 4px solid white;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            @media (max-width: 768px) {
                .payment-options {
                    flex-direction: column;
                    align-items: center;
                }

                .payment-card {
                    max-width: 100%;
                }
            }
        </style>
    </head>
<body>
    <div id="loading-overlay">
        <div class="spinner"></div>
    </div>

<div class="container">
    <header class="header">
        <h1>Sistema de Pagos</h1>
        <p class="lead">Elige la opción de pago que mejor se adapte a tus necesidades</p>
    </header>

    <main>
    <section class="payment-section">
        <h2 class="section-title">Pago Único</h2>
        <div class="one-time-payment">
            <div class="card-title">Acceso Estándar</div>
            <div class="card-price">10,00€</div>
            <div class="card-description">
                Acceso completo a todas las funcionalidades con un pago único.
            </div>
            <button id="single-payment-btn" class="btn-payment" data-type="<?= StripeProductsTypeEnum::ONE_PAYMENT->value ?>">
                Realizar Pago
            </button>
        </div>
    </section>

    <section class="payment-section">
    <h2 class="section-title">Suscripciones</h2>
    <div class="payment-options-container">
    <div class="payment-options">
<?php foreach ($plans as $type => $plan): ?>
    <div class="payment-card <?= $plan['highlight