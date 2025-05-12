// public/assets/js/subscription-page.js

document.addEventListener('DOMContentLoaded', () => {
    // STRIPE_PUBLISHABLE_KEY es una constante PHP definida por config/Bootstrap.php
    // y debe ser accesible globalmente en el scope de este script (ej. a través de una variable global de JS)
    // o pasada como un atributo de datos en algún elemento del DOM.
    // Para este ejemplo, asumiremos que está disponible como una variable global de JS
    // que se establece en el <script> tag en el HTML.
    // En el HTML: <script> const STRIPE_KEY = '<?= STRIPE_PUBLISHABLE_KEY ?>'; </script>
    // O directamente: const stripe = Stripe('<?= STRIPE_PUBLISHABLE_KEY ?>');

    if (typeof STRIPE_PUBLISHABLE_KEY === 'undefined') {
        console.error('Stripe public key no está definida. Asegúrate de que la constante STRIPE_PUBLISHABLE_KEY esté disponible para JavaScript.');
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

    // Theme Toggle Logic (igual que en single-payment)
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

    // Subscription Buttons
    document.querySelectorAll('.subscription-button').forEach(button => {
        const lookupKey = button.dataset.lookup;

        if (!lookupKey) {
            console.warn("Botón de suscripción deshabilitado: falta data-lookup.", button);
            button.disabled = true;
            // Podrías querer cambiar el texto del botón también
            const span = button.querySelector('span');
            if (span) span.textContent = 'No Disponible';
            return; // Saltar este botón
        }

        button.addEventListener('click', async (event) => {
            console.log("Frontend: Botón 'Suscribirse Ahora' clickeado.", { lookup_key: lookupKey });
            try {
                showLoading();

                // Endpoint en public/v1/
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

                const planCardFooter = event.currentTarget.closest('.plan-footer');

                let errorDisplay = planCardFooter.querySelector('.payment-error-display');
                if (!errorDisplay && planCardFooter) {
                    errorDisplay = document.createElement('div');
                    errorDisplay.className = 'alert alert-danger mt-2 payment-error-display'; // Clase para identificarlo
                    planCardFooter.appendChild(errorDisplay);
                }
                if (errorDisplay) {
                    errorDisplay.textContent = 'Error: ' + error.message;
                    errorDisplay.style.display = 'block';
                } else {
                    alert('Ha ocurrido un error: ' + error.message);
                }
            }
        });
    });
});