<?php
// 1. Definir la ruta raíz del proyecto
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', __DIR__ . '/..');
}

// 2. Cargar el autoloader de Composer
require_once PROJECT_ROOT . '/vendor/autoload.php';

// 3. Inicializar la aplicación a través de Bootstrap
\config\Bootstrap::initialize(PROJECT_ROOT);

?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Suscripciones - StripeLabApp</title>
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/view-subscriptions.css">
</head>
<body>
<div id="app-loading-overlay" style="display: none;">
    <div class="spinner"></div>
    <p class="loading-text">Procesando...</p>
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

    <main class="app-main">
        <div class="app-canvas"><div class="canvas-shape shape-1"></div><div class="canvas-shape shape-2"></div><div class="canvas-shape shape-3"></div></div>
        <div class="content-wrapper">
            <section class="subscriptions-header-section">
                <div class="section-header">
                    <h1 class="section-title"><span class="section-title-highlight">Gestión de Suscripciones</span></h1>
                    <p class="section-subtitle">Consulta y gestiona todas las suscripciones del sistema.</p>
                </div>
                <div class="subscriptions-tabs">
                    <button class="subscriptions-tab active" data-tab="all-system-subscriptions"><i class="fas fa-list-ul"></i><span>Todas las Suscripciones</span></button>
                    <button class="subscriptions-tab" data-tab="customer-subscriptions"><i class="fas fa-user-cog"></i><span>Buscar por Cliente</span></button>
                </div>
            </section>

            <!-- Pestaña: Todas las Suscripciones del Sistema -->
            <section class="subscriptions-content-section active" id="all-system-subscriptions-panel">
                <div class="search-container">
                    <p>Mostrando todas las suscripciones registradas en el sistema.</p>
                    <button class="btn btn-sm btn-outline-secondary" id="refresh-all-system-subscriptions-btn" title="Refrescar lista">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                <div class="card">
                    <div id="all-system-subscriptions-loader" class="loader" style="display: none;"><div class="spinner"></div></div>
                    <div id="all-system-subscriptions-empty" class="empty-state" style="display: none;">
                        <div class="empty-icon"><i class="fas fa-box-open"></i></div>
                        <h3 class="empty-title">No hay suscripciones</h3>
                        <p class="empty-message">No se han encontrado suscripciones en el sistema.</p>
                    </div>
                    <div id="all-system-subscriptions-table-container" class="table-container" style="display: none;">
                        <table class="data-table" id="all-system-subscriptions-table">
                            <thead>
                            <tr>
                                <th>ID Suscripción</th>
                                <th>ID Cliente Stripe</th> <!-- Añadido para la vista de admin -->
                                <th>Email Cliente</th>
                                <th>Plan (ID Precio)</th>
                                <th>Estado</th>
                                <th>Periodo Fin</th>
                                <th>Acciones</th>
                            </tr>
                            </thead>
                            <tbody id="all-system-subscriptions-body"></tbody>
                        </table>
                    </div>
                    <div class="pagination-controls mt-3" id="all-system-subscriptions-pagination" style="display: none;"></div>
                </div>
            </section>

            <!-- Pestaña: Suscripciones por Cliente (Admin) -->
            <section class="subscriptions-content-section" id="customer-subscriptions-panel">
                <div class="search-container">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" placeholder="ID de Cliente de Stripe (cus_...)" id="search-customer-id-subs-input">
                    </div>
                    <button class="btn btn-primary" id="search-customer-subscriptions-btn">Buscar Suscripciones</button>
                </div>
                <div class="card mt-3">
                    <div id="customer-subscriptions-loader" class="loader" style="display: none;"><div class="spinner"></div></div>
                    <div id="customer-subscriptions-empty" class="empty-state" style="display: none;">
                        <div class="empty-icon"><i class="fas fa-user-slash"></i></div>
                        <h3 class="empty-title">Sin Resultados</h3>
                        <p class="empty-message">Introduce un ID de cliente válido o no hay suscripciones para este cliente.</p>
                    </div>
                    <div id="customer-subscriptions-table-container" class="table-container" style="display: none;">
                        <h4 id="customer-subscriptions-title" class="p-3">Suscripciones para Cliente: <span id="current-customer-id-subs-display"></span></h4>
                        <table class="data-table" id="customer-subscriptions-table">
                            <thead>
                            <tr>
                                <th>ID Suscripción</th>
                                <th>Plan (ID Precio)</th>
                                <th>Estado</th>
                                <th>Periodo Fin</th>
                                <th>Acciones</th>
                            </tr>
                            </thead>
                            <tbody id="customer-subscriptions-body"></tbody>
                        </table>
                    </div>
                    <div class="pagination-controls mt-3" id="customer-subscriptions-pagination" style="display: none;"></div>
                </div>
            </section>
        </div>
        <footer class="app-footer">
            <div class="footer-content">
                <div class="footer-copyright">© <span id="current-year-footer"></span> StripeLabApp. Todos los derechos reservados.</div>
                <div class="footer-links"><a href="#">Documentación</a><a href="#">API</a><a href="#">Privacidad</a></div>
            </div>
        </footer>
    </main>
</div>

<script>
    // No necesitamos STRIPE_PUBLISHABLE_KEY aquí para listar suscripciones
    // const STRIPE_PUBLISHABLE_KEY = '<?= defined('STRIPE_PUBLISHABLE_KEY') ? STRIPE_PUBLISHABLE_KEY : '' ?>';
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const currentYearSpan = document.getElementById('current-year-footer');
        if (currentYearSpan) currentYearSpan.textContent = new Date().getFullYear();

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

        const sidebarToggle = document.getElementById('sidebar-toggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                document.querySelector('.app-container').classList.toggle('sidebar-collapsed');
            });
        }

        const tabs = document.querySelectorAll('.subscriptions-tab');
        const contentPanels = document.querySelectorAll('.subscriptions-content-section');

        // Elementos DOM para "Todas las Suscripciones del Sistema"
        const allSystemSubsLoader = document.getElementById('all-system-subscriptions-loader');
        const allSystemSubsEmpty = document.getElementById('all-system-subscriptions-empty');
        const allSystemSubsTableContainer = document.getElementById('all-system-subscriptions-table-container');
        const allSystemSubsBody = document.getElementById('all-system-subscriptions-body');
        const allSystemSubsPagination = document.getElementById('all-system-subscriptions-pagination');
        const refreshAllSystemSubsBtn = document.getElementById('refresh-all-system-subscriptions-btn');

        // Elementos DOM para "Suscripciones por Cliente (Admin)"
        const customerSubsLoader = document.getElementById('customer-subscriptions-loader');
        const customerSubsEmpty = document.getElementById('customer-subscriptions-empty');
        const customerSubsTableContainer = document.getElementById('customer-subscriptions-table-container');
        const customerSubsBody = document.getElementById('customer-subscriptions-body');
        const customerSubsPagination = document.getElementById('customer-subscriptions-pagination');
        const searchCustomerSubsBtn = document.getElementById('search-customer-subscriptions-btn');
        const customerIdSubsInput = document.getElementById('search-customer-id-subs-input');
        const currentCustomerIdSubsDisplay = document.getElementById('current-customer-id-subs-display');

        const globalLoadingOverlay = document.getElementById('app-loading-overlay');

        function showGlobalLoading() { if (globalLoadingOverlay) globalLoadingOverlay.style.display = 'flex'; }
        function hideGlobalLoading() { if (globalLoadingOverlay) globalLoadingOverlay.style.display = 'none'; }

        function showTableLoading(loaderElem, tableContainerElem, emptyStateElem, paginationElem) {
            if(loaderElem) loaderElem.style.display = 'flex';
            if(tableContainerElem) tableContainerElem.style.display = 'none';
            if(emptyStateElem) emptyStateElem.style.display = 'none';
            if(paginationElem) paginationElem.style.display = 'none';
        }
        function hideTableLoading(loaderElem) {
            if(loaderElem) loaderElem.style.display = 'none';
        }

        function displaySubscriptions(subscriptions, tableBodyElement, tableContainerElement, emptyStateElement, isCustomerSpecificAdminView = false) {
            if (tableBodyElement) tableBodyElement.innerHTML = '';
            if (!subscriptions || subscriptions.length === 0) {
                if (tableContainerElement) tableContainerElement.style.display = 'none';
                if (emptyStateElement) emptyStateElement.style.display = 'block';
                return;
            }

            subscriptions.forEach(sub => {
                const row = tableBodyElement.insertRow();
                row.insertCell().textContent = sub.subscription_id;

                if (!isCustomerSpecificAdminView) {
                    row.insertCell().textContent = sub.stripe_customer_id || 'N/A'; // Mostrar ID Cliente Stripe
                    row.insertCell().textContent = sub.customer_email || 'N/A';
                }
                row.insertCell().textContent = sub.plan_name_placeholder || sub.stripe_price_id; // Usar el placeholder o el ID del precio
                row.insertCell().innerHTML = `<span class="status-badge status-${sub.status.toLowerCase()}">${sub.status_display}</span> ${sub.cancel_at_period_end ? '<span class="badge bg-warning text-dark ms-1">Cancela al final</span>' : ''}`;
                row.insertCell().textContent = sub.current_period_end_display || 'N/A';

                const actionsCell = row.insertCell();
                actionsCell.classList.add('actions-cell');
                let canBeCancelledNow = ['active', 'trialing', 'past_due'].includes(sub.status);
                let canBeSetToCancelAtEnd = ['active', 'trialing'].includes(sub.status) && !sub.cancel_at_period_end;

                if (canBeCancelledNow) {
                    const btnCancelNow = document.createElement('button');
                    btnCancelNow.className = 'btn btn-sm btn-danger action-btn cancel-subscription-btn';
                    btnCancelNow.dataset.id = sub.subscription_id;
                    btnCancelNow.dataset.action = 'cancel_now';
                    btnCancelNow.title = 'Cancelar Inmediatamente';
                    btnCancelNow.innerHTML = '<i class="fas fa-times-circle"></i>';
                    actionsCell.appendChild(btnCancelNow);
                }
                if (canBeSetToCancelAtEnd) {
                    const btnCancelAtEnd = document.createElement('button');
                    btnCancelAtEnd.className = 'btn btn-sm btn-warning action-btn cancel-subscription-btn ms-1';
                    btnCancelAtEnd.dataset.id = sub.subscription_id;
                    btnCancelAtEnd.dataset.action = 'cancel_at_period_end';
                    btnCancelAtEnd.title = 'Cancelar al Final del Periodo';
                    btnCancelAtEnd.innerHTML = '<i class="far fa-calendar-times"></i>';
                    actionsCell.appendChild(btnCancelAtEnd);
                }
                if (!actionsCell.hasChildNodes()) {
                    actionsCell.textContent = (sub.status === 'canceled' || sub.status === 'ended' || sub.status === 'unpaid') ? 'Finalizada' : 'Gestionada';
                }
            });
            if (tableContainerElement) tableContainerElement.style.display = 'block';
            if (emptyStateElement) emptyStateElement.style.display = 'none';
        }

        function setupGenericPagination(paginationData, paginationContainer, fetchDataFunction, customerId = null) {
            if (!paginationData || !paginationContainer || paginationData.total_pages <= 1) {
                if(paginationContainer) {
                    paginationContainer.style.display = 'none';
                    paginationContainer.innerHTML = '';
                }
                return;
            }
            paginationContainer.style.display = 'flex';
            paginationContainer.innerHTML = '';

            let paginationHtml = '<ul class="pagination justify-content-center">';
            paginationHtml += `<li class="page-item ${paginationData.current_page <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${paginationData.current_page - 1}">«</a></li>`;
            for (let i = 1; i <= paginationData.total_pages; i++) {
                paginationHtml += `<li class="page-item ${i === paginationData.current_page ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            }
            paginationHtml += `<li class="page-item ${paginationData.current_page >= paginationData.total_pages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${paginationData.current_page + 1}">»</a></li>`;
            paginationHtml += '</ul>';
            paginationContainer.innerHTML = paginationHtml;

            paginationContainer.querySelectorAll('.page-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (link.parentElement.classList.contains('disabled') || link.parentElement.classList.contains('active')) return;
                    const page = parseInt(link.dataset.page);
                    if (customerId) {
                        fetchDataFunction(customerId, page); // Para fetchCustomerSubscriptionsAdmin
                    } else {
                        fetchDataFunction(page); // Para fetchAllSystemSubscriptions
                    }
                });
            });
        }

        async function fetchAllSystemSubscriptions(page = 1, limit = 5) {
            showTableLoading(allSystemSubsLoader, allSystemSubsTableContainer, allSystemSubsEmpty, allSystemSubsPagination);
            try {
                console.log(`Fetching ALL system subscriptions: page ${page}, limit ${limit}`);
                const response = await fetch(`./api/api-subscriptions.php?action=list_all_system&page=${page}&limit=${limit}`);
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ error: 'Error de servidor al obtener todas las suscripciones.' }));
                    throw new Error(errorData.error || `Error del servidor: ${response.status}`);
                }
                const result = await response.json();

                if (result.error) {

                    throw new Error(result.error);
                }

                console.log("Todas las suscripciones del sistema recibidas:", result);
                displaySubscriptions(result.data, allSystemSubsBody, allSystemSubsTableContainer, allSystemSubsEmpty, false);
                setupGenericPagination(result.pagination, allSystemSubsPagination, fetchAllSystemSubscriptions);
            } catch (error) {
                console.error("Error fetching all system subscriptions:", error);
                if (allSystemSubsEmpty) { allSystemSubsEmpty.style.display = 'block'; allSystemSubsEmpty.querySelector('.empty-message').textContent = 'Error: ' + error.message; }
            } finally {
                hideTableLoading(allSystemSubsLoader);
            }
        }

        async function fetchCustomerSubscriptionsAdmin(customerId, page = 1, limit = 5) {
            if (!customerId || !customerId.startsWith('cus_')) {
                alert("Por favor, ingrese un ID de cliente de Stripe válido (ej. cus_...).");
                if (customerSubsEmpty) { customerSubsEmpty.style.display = 'block'; customerSubsEmpty.querySelector('.empty-message').textContent = 'ID de cliente inválido.'; }
                if (customerSubsTableContainer) customerSubsTableContainer.style.display = 'none';
                if (customerSubsPagination) customerSubsPagination.style.display = 'none';
                return;
            }
            showTableLoading(customerSubsLoader, customerSubsTableContainer, customerSubsEmpty, customerSubsPagination);
            if (currentCustomerIdSubsDisplay) currentCustomerIdSubsDisplay.textContent = customerId;
            try {
                console.log(`Fetching subscriptions for customer (admin) ${customerId}: page ${page}, limit ${limit}`);
                const response = await fetch(`./api/api-subscriptions.php?action=list_customer&customer_id=${encodeURIComponent(customerId)}&page=${page}&limit=${limit}`);
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ error: 'Error de servidor al obtener suscripciones del cliente.' }));
                    throw new Error(errorData.error || `Error del servidor: ${response.status}`);
                }
                const result = await response.json();
                if (result.error) { throw new Error(result.error); }
                console.log(`Suscripciones para cliente ${customerId}:`, result);
                displaySubscriptions(result.data, customerSubsBody, customerSubsTableContainer, customerSubsEmpty, true);
                setupGenericPagination(result.pagination, customerSubsPagination, (newPage) => fetchCustomerSubscriptionsAdmin(customerId, newPage), customerId);
            } catch (error) {
                console.error(`Error fetching subscriptions for customer ${customerId}:`, error);
                if (customerSubsEmpty) { customerSubsEmpty.style.display = 'block'; customerSubsEmpty.querySelector('.empty-message').textContent = 'Error: ' + error.message; }
            } finally {
                hideTableLoading(customerSubsLoader);
            }
        }

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                const tabId = this.getAttribute('data-tab');
                contentPanels.forEach(panel => {
                    panel.classList.toggle('active', panel.id === `${tabId}-panel`);
                });

                if (tabId === 'all-system-subscriptions') { // Cambiado de 'all-subscriptions'
                    // Cargar si la tabla está vacía o si queremos refrescar siempre
                    if (!allSystemSubsBody.hasChildNodes() || allSystemSubsBody.innerHTML.trim() === '') {
                        fetchAllSystemSubscriptions();
                    }
                } else if (tabId === 'customer-subscriptions') {
                    if (!customerSubsBody.hasChildNodes() && !customerIdSubsInput.value) {
                        if(customerSubsEmpty) customerSubsEmpty.style.display = 'block';
                        if(customerSubsTableContainer) customerSubsTableContainer.style.display = 'none';
                        if(customerSubsLoader) customerSubsLoader.style.display = 'none';
                        if(customerSubsPagination) customerSubsPagination.style.display = 'none';
                    }
                }
            });
        });

        // Carga inicial de la pestaña por defecto
        const activeTabOnInit = document.querySelector('.subscriptions-tab.active');
        if (activeTabOnInit && activeTabOnInit.dataset.tab === 'all-system-subscriptions') {
            fetchAllSystemSubscriptions();
        } else if (activeTabOnInit && activeTabOnInit.dataset.tab === 'customer-subscriptions') {
            if(customerSubsEmpty) customerSubsEmpty.style.display = 'block';
            if(customerSubsTableContainer) customerSubsTableContainer.style.display = 'none';
        }

        if(refreshAllSystemSubsBtn) {
            refreshAllSystemSubsBtn.addEventListener('click', () => fetchAllSystemSubscriptions());
        }

        if(searchCustomerSubsBtn && customerIdSubsInput) {
            searchCustomerSubsBtn.addEventListener('click', () => {
                const customerId = customerIdSubsInput.value.trim();
                fetchCustomerSubscriptionsAdmin(customerId);
            });
            customerIdSubsInput.addEventListener('keypress', (event) => {
                if (event.key === 'Enter') {
                    const customerId = customerIdSubsInput.value.trim();
                    fetchCustomerSubscriptionsAdmin(customerId);
                }
            });
        }

        document.querySelector('.app-main').addEventListener('click', async function(event) {
            const button = event.target.closest('.cancel-subscription-btn');
            if (!button) return;

            const subscriptionId = button.dataset.id;
            const action = button.dataset.action;
            const actionText = action === 'cancel_at_period_end' ? 'programar la cancelación al final del periodo para' : 'cancelar inmediatamente';

            if (!subscriptionId || !action) return;
            if (!confirm(`¿Estás seguro de que quieres ${actionText} la suscripción ${subscriptionId}?`)) return;

            showGlobalLoading();
            const currentActiveTabElement = document.querySelector('.subscriptions-tab.active');
            const currentActiveTab = currentActiveTabElement ? currentActiveTabElement.dataset.tab : 'all-system-subscriptions';

            try {
                console.log(`Frontend: Solicitando ${action} para suscripción ID: ${subscriptionId}`);
                const response = await fetch('./api/api-manage-subscription.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', },
                    body: JSON.stringify({ action: action, subscription_id: subscriptionId })
                });
                const result = await response.json();
                if (!response.ok || result.error) { throw new Error(result.error || `Error del servidor: ${response.status}`); }

                console.log(`Frontend: Solicitud de ${action} procesada. Respuesta:`, result);
                alert(result.message || 'La solicitud ha sido procesada. El estado se actualizará en breve vía webhook.');

                if (currentActiveTab === 'all-system-subscriptions') {
                    fetchAllSystemSubscriptions(); // Recargar la lista actual
                } else if (currentActiveTab === 'customer-subscriptions') {
                    const searchCustId = customerIdSubsInput.value.trim();
                    if (searchCustId) fetchCustomerSubscriptionsAdmin(searchCustId);
                }
            } catch (error) {
                console.error('Error al gestionar suscripción:', error);
                alert('Error al procesar la acción: ' + error.message);
            } finally {
                hideGlobalLoading();
            }
        });
    });
</script>
</body>
</html>