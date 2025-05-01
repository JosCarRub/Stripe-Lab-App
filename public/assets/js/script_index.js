// Esperar a que se cargue el DOM
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos DOM
    const themeSwitcher = document.getElementById('themeSwitcher');
    const themeIcon = document.getElementById('themeIcon');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.querySelector('.app-sidebar');
    const singlePayment = document.getElementById('single-payment');
    const subscription = document.getElementById('subscription');

    // Función para verificar el tema actual
    function checkTheme() {
        if (document.documentElement.getAttribute('data-bs-theme') === 'dark') {
            themeIcon.classList.remove('bi-moon-fill');
            themeIcon.classList.add('bi-sun-fill');
        } else {
            themeIcon.classList.remove('bi-sun-fill');
            themeIcon.classList.add('bi-moon-fill');
        }
    }

    // Cambiar tema
    themeSwitcher.addEventListener('click', function() {
        if (document.documentElement.getAttribute('data-bs-theme') === 'dark') {
            document.documentElement.setAttribute('data-bs-theme', 'light');
            localStorage.setItem('theme', 'light');
        } else {
            document.documentElement.setAttribute('data-bs-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        }
        checkTheme();
    });

    // Cargar tema desde localStorage
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
        checkTheme();
    }

    // Toggle sidebar en móvil
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }

    // Cerrar sidebar al hacer clic fuera de ella en móvil
    document.addEventListener('click', function(event) {
        const isClickInsideSidebar = sidebar.contains(event.target);
        const isClickOnToggle = sidebarToggle.contains(event.target);

        if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
        }
    });

    // Animación al pasar el ratón por encima de las opciones de pago
    if (singlePayment && subscription) {
        // Efecto de elevación y sombra en hover
        singlePayment.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 20px rgba(0, 0, 0, 0.1)';
        });

        singlePayment.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });

        subscription.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 20px rgba(0, 0, 0, 0.1)';
        });

        subscription.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    }

    // Animación para los elementos que se muestran al cargar la página
    const elementsToAnimate = document.querySelectorAll('.payment-option-box, .recent-transactions-card');

    elementsToAnimate.forEach((element, index) => {
        // Agregar clase para fade-in con delay incremental
        setTimeout(() => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'all 0.5s ease-in-out';

            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, 100);
        }, index * 150);
    });

    // Simular estado activo al hacer clic en opciones de pago
    const paymentOptions = document.querySelectorAll('.payment-option-box');

    paymentOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            // Si el clic no fue en el botón, simular clic en el botón
            if (!e.target.closest('.btn-option')) {
                const btn = this.querySelector('.btn-option');
                if (btn) {
                    btn.click();
                }
            }
        });
    });

    // Usuario desplegable
    const userMenu = document.querySelector('.user-menu');

    if (userMenu) {
        userMenu.addEventListener('click', function() {
            // Aquí se podría implementar un menú desplegable para el usuario
            console.log('User menu clicked');
        });
    }

    // Cerrar alerta si existe
    const closeAlerts = document.querySelectorAll('.alert .btn-close');

    closeAlerts.forEach(btn => {
        btn.addEventListener('click', function() {
            const alert = this.closest('.alert');
            if (alert) {
                alert.remove();
            }
        });
    });
});