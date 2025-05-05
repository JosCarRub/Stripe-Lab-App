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