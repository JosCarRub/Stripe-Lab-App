<?php
// 1. Definir la ruta raíz del proyecto
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', __DIR__ . '/..'); // Asume que api-invoices.php está en public/api/
}

require_once PROJECT_ROOT . '/vendor/autoload.php';


\config\Bootstrap::initialize(PROJECT_ROOT);

?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facturas - StripeLabApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/invoices.css">
</head>
<body>
<!-- Loading overlay (general para la página o para la tabla) -->
<div id="app-loading-overlay" style="display: none;"> <!-- Oculto inicialmente -->
    <div class="spinner"></div>
    <p class="loading-text">Cargando datos...</p>
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
                <span class="theme-slider"><i class="fas fa-sun sun-icon"></i><i class="fas fa-moon moon-icon"></i></span>
            </label>
        </div>
        <div class="nav-separator"><span>Navegación</span></div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item"><div class="nav-icon"><i class="fas fa-home"></i></div><span class="nav-label">Inicio</span></a>
            <a href="single-payment.php" class="nav-item"><div class="nav-icon"><i class="fas fa-credit-card"></i></div><span class="nav-label">Pago Único</span></a>
            <a href="subscriptions-payment.php" class="nav-item"><div class="nav-icon"><i class="fas fa-sync-alt"></i></div><span class="nav-label">Pagar Suscripción</span></a>
            <a href="invoices.php" class="nav-item active"><div class="nav-icon"><i class="fas fa-file-invoice-dollar"></i></div><span class="nav-label">Facturas</span></a>
            <a href="view-subscriptions.php" class="nav-item">
                <div class="nav-icon"><i class="fas fa-users"></i></div>
                <span class="nav-label">Gestionar Suscripciones</span>
            </a>            <div class="nav-separator"><span>Administración</span></div>
            <a href="admin/panel.php" class="nav-item"><div class="nav-icon"><i class="fas fa-tachometer-alt"></i></div><span class="nav-label">Panel de Control</span></a>
            <a href="#" class="nav-item" onclick="alert('Logs no implementados'); return false;"><div class="nav-icon"><i class="fas fa-list-alt"></i></div><span class="nav-label">Logs del Sistema</span></a>
            <a href="#" class="nav-item" onclick="alert('Docs no implementados'); return false;"><div class="nav-icon"><i class="fas fa-book"></i></div><span class="nav-label">Documentación</span></a>
        </nav>
        <div class="sidebar-footer">
            <div class="app-version">v0.1.0</div>
            <button class="sidebar-toggle" id="sidebar-toggle"><i class="fas fa-chevron-left"></i></button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="app-main">
        <div class="app-canvas"><div class="canvas-shape shape-1"></div><div class="canvas-shape shape-2"></div><div class="canvas-shape shape-3"></div></div>
        <div class="content-wrapper">
            <section class="invoices-header-section">
                <div class="section-header">
                    <h1 class="section-title"><span class="section-title-highlight">Gestión de Facturas</span></h1>
                    <p class="section-subtitle">Consulta y gestiona todas tus facturas y recibos desde un solo lugar.</p>
                </div>
                <div class="invoices-tabs">
                    <button class="invoices-tab active" data-tab="all-invoices"><i class="fas fa-list"></i><span>Todas las Facturas/Recibos</span></button>
                    <button class="invoices-tab" data-tab="customer-invoices"><i class="fas fa-user-tag"></i><span>Facturas por Cliente</span></button>
                </div>
            </section>

            <section class="invoices-content-section active" id="all-invoices-panel">
                <div class="search-container">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="Buscar por ID Factura, Email, Nombre..." id="search-all-invoices">
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" id="refresh-all-invoices-btn" title="Refrescar lista">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card">
                    <div id="all-invoices-loader" class="loader" style="display: none;"> <!-- Oculto inicialmente, se muestra al cargar -->
                        <div class="spinner"></div>
                    </div>
                    <div id="all-invoices-empty" class="empty-state" style="display: none;">
                        <div class="empty-icon"><i class="fas fa-file-invoice"></i></div>
                        <h3 class="empty-title">No hay facturas/recibos</h3>
                        <p class="empty-message">No se han encontrado documentos en el sistema.</p>
                    </div>
                    <div id="all-invoices-table-container" class="table-container" style="display: none;">
                        <table class="data-table" id="all-invoices-table">
                            <thead>
                            <tr>
                                <th>ID Documento</th>
                                <th>Cliente</th>
                                <th>Email</th>
                                <th>Fecha</th>
                                <th>Importe</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                            </thead>
                            <tbody id="all-invoices-body"></tbody>
                        </table>
                    </div>
                    <div class="pagination-controls mt-3" id="all-invoices-pagination" style="display: none;">
                        <!-- Controles de paginación se generarán aquí -->
                    </div>
                </div>
            </section>

            <section class="invoices-content-section" id="customer-invoices-panel">
                <div class="search-container">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="ID de Cliente de Stripe (cus_...)" id="search-customer-id-input">
                    </div>
                    <button class="btn btn-primary" id="search-customer-invoices-btn">Buscar Facturas</button>
                </div>
                <div class="card mt-3">
                    <div id="customer-invoices-loader" class="loader" style="display: none;">
                        <div class="spinner"></div>
                    </div>
                    <div id="customer-invoices-empty" class="empty-state" style="display: none;">
                        <div class="empty-icon"><i class="fas fa-user-slash"></i></div>
                        <h3 class="empty-title">Sin Resultados</h3>
                        <p class="empty-message">Introduce un ID de cliente válido o no hay facturas para este cliente.</p>
                    </div>
                    <div id="customer-invoices-table-container" class="table-container" style="display: none;">
                        <h4 id="customer-invoices-title" class="p-3">Facturas para Cliente: <span id="current-customer-id-display"></span></h4>
                        <table class="data-table" id="customer-invoices-table">
                            <thead>
                            <tr>
                                <th>ID Documento</th>
                                <th>Fecha</th>
                                <th>Importe</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                            </thead>
                            <tbody id="customer-invoices-body"></tbody>
                        </table>
                    </div>
                    <div class="pagination-controls mt-3" id="customer-invoices-pagination" style="display: none;">
                    </div>
                </div>
            </section>
        </div>
        <footer class="app-footer">
            <div class="footer-content">
                <div class="footer-copyright">© <span id="current-year"></span> StripeLabApp. Todos los derechos reservados.</div>
                <div class="footer-links"><a href="#">Documentación</a><a href="#">API</a><a href="#">Privacidad</a></div>
            </div>
        </footer>
    </main>
</div>

<script>
    // Hacer la clave pública de Stripe disponible (aunque no se usa directamente en esta página)
    // const STRIPE_PUBLISHABLE_KEY = '<?= defined('STRIPE_PUBLISHABLE_KEY') ? STRIPE_PUBLISHABLE_KEY : '' ?>';

    document.addEventListener('DOMContentLoaded', function() {
        // Set current year en footer
        const currentYearSpan = document.getElementById('current-year');
        if (currentYearSpan) {
            currentYearSpan.textContent = new Date().getFullYear();
        }

        // Theme Toggle
        const themeToggle = document.getElementById('theme-toggle-input');
        if (themeToggle) {
            const storedTheme = localStorage.getItem('theme');
            const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const currentTheme = storedTheme || preferredTheme;
            document.documentElement.setAttribute('data-theme', currentTheme);
            if (currentTheme === 'dark') themeToggle.checked = true;

            themeToggle.addEventListener('change', function() {
                const selectedTheme = this.checked ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', selectedTheme);
                localStorage.setItem('theme', selectedTheme);
            });
        }

        // Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebar-toggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                document.querySelector('.app-container').classList.toggle('sidebar-collapsed');
            });
        }

        // Tab switching
        const tabs = document.querySelectorAll('.invoices-tab');
        const contentPanels = document.querySelectorAll('.invoices-content-section');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                const tabId = this.getAttribute('data-tab');
                contentPanels.forEach(panel => {
                    if (panel.id === `${tabId}-panel`) {
                        panel.classList.add('active');
                        if (tabId === 'all-invoices' && !document.getElementById('all-invoices-body').hasChildNodes()) {
                            fetchAllInvoices(); // Cargar si la tabla está vacía
                        }
                    } else {
                        panel.classList.remove('active');
                    }
                });
            });
        });

        // Elementos del DOM para "Todas las Facturas"
        const allInvoicesLoader = document.getElementById('all-invoices-loader');
        const allInvoicesEmpty = document.getElementById('all-invoices-empty');
        const allInvoicesTableContainer = document.getElementById('all-invoices-table-container');
        const allInvoicesBody = document.getElementById('all-invoices-body');
        const allInvoicesPagination = document.getElementById('all-invoices-pagination');
        const refreshAllInvoicesBtn = document.getElementById('refresh-all-invoices-btn');

        // Elementos del DOM para "Facturas por Cliente"
        const customerInvoicesLoader = document.getElementById('customer-invoices-loader');
        const customerInvoicesEmpty = document.getElementById('customer-invoices-empty');
        const customerInvoicesTableContainer = document.getElementById('customer-invoices-table-container');
        const customerInvoicesBody = document.getElementById('customer-invoices-body');
        const customerInvoicesPagination = document.getElementById('customer-invoices-pagination');
        const searchCustomerBtn = document.getElementById('search-customer-invoices-btn');
        const customerIdInput = document.getElementById('search-customer-id-input');
        const currentCustomerIdDisplay = document.getElementById('current-customer-id-display');


        function showLoading(loaderElement, tableContainerElement, emptyStateElement) {
            if (loaderElement) loaderElement.style.display = 'flex';
            if (tableContainerElement) tableContainerElement.style.display = 'none';
            if (emptyStateElement) emptyStateElement.style.display = 'none';
            if (allInvoicesPagination) allInvoicesPagination.style.display = 'none';
            if (customerInvoicesPagination) customerInvoicesPagination.style.display = 'none';
        }

        function hideLoading(loaderElement) {
            if (loaderElement) loaderElement.style.display = 'none';
        }

        function displayInvoices(invoices, tableBodyElement, tableContainerElement, emptyStateElement, isCustomerSpecific = false) {
            if (tableBodyElement) tableBodyElement.innerHTML = ''; // Limpiar tabla

            if (invoices && invoices.length > 0) {
                invoices.forEach(invoice => {
                    const row = tableBodyElement.insertRow();
                    row.insertCell().textContent = invoice.stripe_invoice_id || invoice.stripe_payment_intent_id || invoice.transaction_id; // ID principal
                    if (!isCustomerSpecific) {
                        row.insertCell().textContent = invoice.customer_name || invoice.stripe_customer_id || 'N/A';
                        row.insertCell().textContent = invoice.customer_email || 'N/A';
                    }
                    row.insertCell().textContent = invoice.date_display || new Date(invoice.transaction_date_stripe).toLocaleDateString();
                    row.insertCell().textContent = invoice.amount_display || `${(invoice.amount / 100).toFixed(2)} ${invoice.currency.toUpperCase()}`;
                    row.insertCell().innerHTML = `<span class="status-badge status-${invoice.status}">${invoice.status}</span>`;

                    let actionsHtml = '';
                    if (invoice.view_document_url && invoice.view_document_url !== '#') {
                        actionsHtml += `<a href="${invoice.view_document_url}" target="_blank" class="btn btn-sm btn-outline-primary action-btn" title="Ver Documento"><i class="fas fa-eye"></i></a>`;
                    }

                    row.insertCell().innerHTML = actionsHtml || 'N/A';
                });
                if (tableContainerElement) tableContainerElement.style.display = 'block';
                if (emptyStateElement) emptyStateElement.style.display = 'none';
            } else {
                if (tableContainerElement) tableContainerElement.style.display = 'none';
                if (emptyStateElement) emptyStateElement.style.display = 'block';
            }
        }

        function setupPagination(paginationData, paginationContainer, fetchDataFunction) {
            if (!paginationData || paginationData.total_pages <= 1) {
                paginationContainer.style.display = 'none';
                paginationContainer.innerHTML = '';
                return;
            }
            paginationContainer.style.display = 'flex';
            paginationContainer.innerHTML = ''; // Clear previous pagination

            let paginationHtml = '<ul class="pagination justify-content-center">';

            // Previous button
            paginationHtml += `<li class="page-item ${paginationData.current_page <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${paginationData.current_page - 1}">«</a></li>`;

            // Page numbers
            for (let i = 1; i <= paginationData.total_pages; i++) {
                paginationHtml += `<li class="page-item ${i === paginationData.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            }

            // Next button
            paginationHtml += `<li class="page-item ${paginationData.current_page >= paginationData.total_pages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${paginationData.current_page + 1}">»</a></li>`;

            paginationHtml += '</ul>';
            paginationContainer.innerHTML = paginationHtml;

            // Add event listeners to pagination links
            paginationContainer.querySelectorAll('.page-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (link.parentElement.classList.contains('disabled') || link.parentElement.classList.contains('active')) {
                        return;
                    }
                    const page = parseInt(link.dataset.page);
                    fetchDataFunction(page);
                });
            });
        }


        async function fetchAllInvoices(page = 1, limit = 10) {
            showLoading(allInvoicesLoader, allInvoicesTableContainer, allInvoicesEmpty);
            try {
                console.log(`Fetching all invoices: page ${page}, limit ${limit}`);
                const response = await fetch(`api/api-invoices.php?action=list_all&page=${page}&limit=${limit}`);
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ error: 'Error de servidor desconocido al obtener facturas.' }));
                    throw new Error(errorData.error || `Error del servidor: ${response.status}`);
                }
                const result = await response.json();
                if (result.error) { throw new Error(result.error); }

                console.log("Todas las facturas recibidas:", result);
                displayInvoices(result.data, allInvoicesBody, allInvoicesTableContainer, allInvoicesEmpty, false);
                setupPagination(result.pagination, allInvoicesPagination, fetchAllInvoices);

            } catch (error) {
                console.error("Error fetching all invoices:", error);
                if (allInvoicesEmpty) {
                    allInvoicesEmpty.style.display = 'block';
                    allInvoicesEmpty.querySelector('.empty-message').textContent = 'Error al cargar facturas: ' + error.message;
                }
                if (allInvoicesTableContainer) allInvoicesTableContainer.style.display = 'none';
            } finally {
                hideLoading(allInvoicesLoader);
            }
        }

        async function fetchCustomerInvoices(customerId, page = 1, limit = 10) {
            if (!customerId) {
                alert("Por favor, ingrese un ID de cliente de Stripe.");
                if (customerInvoicesEmpty) customerInvoicesEmpty.style.display = 'block';
                return;
            }
            showLoading(customerInvoicesLoader, customerInvoicesTableContainer, customerInvoicesEmpty);
            if (currentCustomerIdDisplay) currentCustomerIdDisplay.textContent = customerId;

            try {
                console.log(`Fetching invoices for customer ${customerId}: page ${page}, limit ${limit}`);
                const response = await fetch(`./api/api-invoices.php?action=list_customer&customer_id=${encodeURIComponent(customerId)}&page=${page}&limit=${limit}`);
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ error: 'Error de servidor desconocido.' }));
                    throw new Error(errorData.error || `Error del servidor: ${response.status}`);
                }
                const result = await response.json();
                if (result.error) { throw new Error(result.error); }

                console.log(`Facturas para cliente ${customerId}:`, result);
                displayInvoices(result.data, customerInvoicesBody, customerInvoicesTableContainer, customerInvoicesEmpty, true);
                setupPagination(result.pagination, customerInvoicesPagination, (newPage) => fetchCustomerInvoices(customerId, newPage));


            } catch (error) {
                console.error(`Error fetching invoices for customer ${customerId}:`, error);
                if (customerInvoicesEmpty) {
                    customerInvoicesEmpty.style.display = 'block';
                    customerInvoicesEmpty.querySelector('.empty-message').textContent = 'Error al cargar facturas: ' + error.message;
                }
                if (customerInvoicesTableContainer) customerInvoicesTableContainer.style.display = 'none';
            } finally {
                hideLoading(customerInvoicesLoader);
            }
        }


        // Cargar todas las facturas al inicio
        fetchAllInvoices();

        if(refreshAllInvoicesBtn) {
            refreshAllInvoicesBtn.addEventListener('click', () => fetchAllInvoices());
        }

        if(searchCustomerBtn && customerIdInput) {
            searchCustomerBtn.addEventListener('click', () => {
                const customerId = customerIdInput.value.trim();
                fetchCustomerInvoices(customerId);
            });
            customerIdInput.addEventListener('keypress', (event) => {
                if (event.key === 'Enter') {
                    const customerId = customerIdInput.value.trim();
                    fetchCustomerInvoices(customerId);
                }
            });
        }
    });
</script>
</body>
</html>