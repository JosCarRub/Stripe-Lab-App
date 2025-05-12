/**
 * Panel de Administración - Stripe DB
 * Este script maneja la funcionalidad del panel para visualizar
 * tablas de base de datos, verificar conexiones y mostrar datos.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Configuración inicial
    let currentTable = null;
    let currentPage = 1;
    let itemsPerPage = 10;
    let dbConfig = {
        host: localStorage.getItem('db_host') || '127.0.0.1',
        port: localStorage.getItem('db_port') || '3307',
        database: localStorage.getItem('db_name') || 'stripe_lab',
        username: localStorage.getItem('db_user') || 'test_user', // ¡Nombre correcto!
        password: localStorage.getItem('db_password') || ''
    };

    // Elementos DOM
    const elements = {
        // Secciones principales
        dashboardOverview: document.getElementById('dashboard-overview'),
        tableDetails: document.getElementById('table-details'),
        dataPreview: document.getElementById('data-preview'),
        connectionTest: document.getElementById('connection-test'),
        relationships: document.getElementById('relationships'),

        // Elementos de la barra lateral
        tableList: document.getElementById('table-list'),
        dbStatus: document.getElementById('db-status'),

        // Elementos de encabezado
        searchInput: document.getElementById('search-input'),
        searchBtn: document.getElementById('search-btn'),
        refreshBtn: document.getElementById('refresh-btn'),
        exportBtn: document.getElementById('export-btn'),

        // Elementos del resumen
        totalTables: document.getElementById('total-tables'),
        totalFields: document.getElementById('total-fields'),
        totalPayments: document.getElementById('total-payments'),
        totalSubscriptions: document.getElementById('total-subscriptions'),

        // Elementos de detalles de la tabla
        currentTableName: document.getElementById('current-table-name'),
        fieldsTable: document.getElementById('fields-table'),
        viewDataBtn: document.getElementById('view-data-btn'),
        structureBtn: document.getElementById('structure-btn'),

        // Elementos de vista previa de datos
        previewTableName: document.getElementById('preview-table-name'),
        dataTable: document.getElementById('data-table'),
        prevPageBtn: document.getElementById('prev-page'),
        nextPageBtn: document.getElementById('next-page'),
        pageInfo: document.getElementById('page-info'),

        // Elementos de prueba de conexión
        dbHost: document.getElementById('db-host'),
        dbPort: document.getElementById('db-port'),
        dbName: document.getElementById('db-name'),
        dbUser: document.getElementById('db-user'),
        connectionStatusIndicator: document.getElementById('connection-status-indicator'),
        connectionStatusText: document.getElementById('connection-status-text'),
        testConnectionBtn: document.getElementById('test-connection-btn'),
        configConnectionBtn: document.getElementById('config-connection-btn'),

        // Elementos del modal
        configModal: document.getElementById('config-modal'),
        closeModalBtn: document.getElementById('close-modal'),
        connectionForm: document.getElementById('connection-form'),
        cancelConfigBtn: document.getElementById('cancel-config'),

        // Campos del formulario
        hostInput: document.getElementById('host'),
        portInput: document.getElementById('port'),
        databaseInput: document.getElementById('database'),
        usernameInput: document.getElementById('username'),
        passwordInput: document.getElementById('password'),
    };

    // Inicialización del panel
    function initPanel() {
        // Cargar configuración de la base de datos
        loadDbConfig();

        // Verificar estado de la conexión
        testConnection();

        // Configurar listeners de eventos
        setupEventListeners();
    }

    // Cargar configuración de la base de datos
    function loadDbConfig() {
        elements.dbHost.textContent = dbConfig.host;
        elements.dbPort.textContent = dbConfig.port;
        elements.dbName.textContent = dbConfig.database;
        elements.dbUser.textContent = dbConfig.username;

        // Configurar campos del formulario
        elements.hostInput.value = dbConfig.host;
        elements.portInput.value = dbConfig.port;
        elements.databaseInput.value = dbConfig.database;
        elements.usernameInput.value = dbConfig.username;
        elements.passwordInput.value = dbConfig.password;
    }

    // Verificar conexión a la base de datos
    function testConnection() {
        // Mostrar indicadores de carga
        elements.connectionStatusIndicator.className = 'status-dot';
        elements.connectionStatusText.textContent = 'Verificando...';
        document.querySelector('.status-indicator').className = 'status-indicator';
        document.querySelector('.status-text').textContent = 'Verificando conexión...';

        // Realizar petición al backend
        fetch('/admin/api/db-connection.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dbConfig)
        })
            .then(response => response.json())
            .then(data => {
                if (data.connected) {
                    // Conexión exitosa
                    elements.connectionStatusIndicator.className = 'status-dot connected';
                    elements.connectionStatusText.textContent = 'Conectado';
                    document.querySelector('.status-indicator').classList.add('connected');
                    document.querySelector('.status-text').textContent = 'Conectado';

                    // Actualizar lista de tablas si están disponibles
                    if (data.tables && data.tables.length > 0) {
                        updateTablesList(data.tables);
                    } else {
                        // Si no hay tablas en la respuesta, cargar tablas de forma separada
                        loadTables();
                    }

                    // Cargar estadísticas reales
                    loadStats();
                } else {
                    // Error de conexión
                    elements.connectionStatusIndicator.className = 'status-dot error';
                    elements.connectionStatusText.textContent = data.error ? 'Error: ' + data.error : 'Error de conexión';
                    document.querySelector('.status-indicator').classList.add('error');
                    document.querySelector('.status-text').textContent = 'Error de conexión';

                    console.error('Error de conexión:', data.error);
                }
            })
            .catch(error => {
                // Error de petición
                elements.connectionStatusIndicator.className = 'status-dot error';
                elements.connectionStatusText.textContent = 'Error: No se pudo conectar al servidor';
                document.querySelector('.status-indicator').classList.add('error');
                document.querySelector('.status-text').textContent = 'Error de conexión';
                console.error('Error:', error);
            });
    }

    // Cargar tablas de la base de datos
    function loadTables() {
        // Mostrar indicador de carga
        elements.tableList.innerHTML = '<li class="sidebar-loader"><div class="spinner"></div> Cargando tablas...</li>';

        fetch('/admin/api/db-connection.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dbConfig)
        })
            .then(response => response.json())
            .then(data => {
                if (data.connected && data.tables) {
                    updateTablesList(data.tables);
                } else {
                    // Si hay un error, mostrar mensaje
                    elements.tableList.innerHTML = '<li>No se pudieron cargar las tablas</li>';
                    console.error('Error al cargar tablas:', data.error);
                }
            })
            .catch(error => {
                console.error('Error al cargar tablas:', error);
                elements.tableList.innerHTML = '<li>Error al cargar tablas</li>';
            });
    }

    // Actualizar la lista de tablas en la interfaz
    function updateTablesList(tables) {
        const fragment = document.createDocumentFragment();

        // Limpiar lista de tablas
        elements.tableList.innerHTML = '';

        // Agregar tablas a la lista
        tables.forEach(table => {
            const li = document.createElement('li');
            li.dataset.table = table;

            // Asignar icono según el nombre de la tabla
            let icon = 'fa-table';
            if (table.toLowerCase().includes('transaction')) {
                icon = 'fa-credit-card';
            } else if (table.toLowerCase().includes('subscription')) {
                icon = 'fa-sync';
            } else if (table.toLowerCase().includes('customer')) {
                icon = 'fa-users';
            } else if (table.toLowerCase().includes('invoice')) {
                icon = 'fa-file-invoice-dollar';
            }

            li.innerHTML = `<i class="fas ${icon}"></i> <span>${table}</span>`;

            li.addEventListener('click', () => {
                // Quitar clase activa de todos los elementos
                document.querySelectorAll('#table-list li').forEach(el => el.classList.remove('active'));

                // Agregar clase activa a este elemento
                li.classList.add('active');

                // Cargar detalles de la tabla
                loadTableDetails(table);
            });

            fragment.appendChild(li);
        });

        elements.tableList.appendChild(fragment);
    }

    // Cargar detalles de la tabla seleccionada
    function loadTableDetails(tableName) {
        currentTable = tableName;

        // Actualizar nombre de la tabla
        elements.currentTableName.textContent = tableName;
        elements.previewTableName.textContent = tableName;

        // Mostrar sección de detalles y ocultar otras
        elements.tableDetails.classList.remove('hidden');
        elements.dataPreview.classList.add('hidden');
        elements.relationships.classList.add('hidden');

        // Mostrar indicador de carga
        const tableBody = elements.fieldsTable.querySelector('tbody');
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Cargando estructura de la tabla...</td></tr>';

        // Cargar campos de la tabla desde el backend
        fetch(`/admin/api/table-structure.php?table=${tableName}`)
            .then(response => response.json())
            .then(data => {
                if (data.fields) {
                    loadTableFieldsFromData(data.fields);
                } else if (data.error) {
                    tableBody.innerHTML = `<tr><td colspan="6" class="text-center">Error al cargar la estructura: ${data.error}</td></tr>`;
                    console.error('Error al cargar estructura:', data.error);
                } else {
                    // Error desconocido
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No se pudo cargar la estructura de la tabla</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error al cargar estructura de tabla:', error);
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Error al cargar la estructura</td></tr>';
            });

        // Activar botón de estructura y desactivar botón de datos
        elements.structureBtn.classList.add('btn-active');
        elements.viewDataBtn.classList.remove('btn-active');
    }

    // Cargar campos de la tabla desde datos recibidos
    function loadTableFieldsFromData(fields) {
        const tableBody = elements.fieldsTable.querySelector('tbody');
        tableBody.innerHTML = '';

        if (fields && fields.length > 0) {
            const fragment = document.createDocumentFragment();

            fields.forEach(field => {
                const row = document.createElement('tr');

                row.innerHTML = `
                    <td>${field.name}</td>
                    <td>${field.type}</td>
                    <td>${field.nullable}</td>
                    <td>${field.key}</td>
                    <td>${field.default !== null ? field.default : '<em>NULL</em>'}</td>
                    <td>${field.extra}</td>
                `;

                fragment.appendChild(row);
            });

            tableBody.appendChild(fragment);
        } else {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No hay campos disponibles para esta tabla</td></tr>';
        }
    }

    // Cargar datos de la tabla para vista previa
    function loadTableData(tableName, page = 1) {
        currentPage = page;

        // Actualizar nombre de la tabla
        elements.previewTableName.textContent = tableName;

        // Mostrar sección de datos y ocultar otras
        elements.dataPreview.classList.remove('hidden');
        elements.tableDetails.classList.add('hidden');

        // Mostrar indicador de carga
        const tableHead = elements.dataTable.querySelector('thead');
        const tableBody = elements.dataTable.querySelector('tbody');
        tableHead.innerHTML = '';
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Cargando datos...</td></tr>';

        // Para depuración
        console.log('Cargando datos de tabla:', tableName);
        console.log('URL:', `/admin/api/table-data.php?table=${tableName}&page=${page}&limit=${itemsPerPage}`);

        // Cargar datos de la tabla desde el backend
        fetch(`/admin/api/table-data.php?table=${tableName}&page=${page}&limit=${itemsPerPage}`)
            .then(response => {
                // Para depuración
                console.log('Status:', response.status);
                return response.json();
            })
            .then(data => {
                // Para depuración
                console.log('Datos recibidos:', data);

                if (data.data && Array.isArray(data.data)) {
                    // Actualizar información de la página
                    const totalPages = data.total_pages || 1;
                    elements.pageInfo.textContent = `Página ${page} de ${totalPages}`;

                    // Habilitar/deshabilitar botones de paginación
                    elements.prevPageBtn.disabled = page <= 1;
                    elements.nextPageBtn.disabled = page >= totalPages;

                    // Renderizar tabla con datos reales
                    renderDataTableFromData(data.data);
                } else if (data.error) {
                    tableBody.innerHTML = `<tr><td colspan="6" class="text-center">Error al cargar los datos: ${data.error}</td></tr>`;
                    console.error('Error al cargar datos:', data.error);
                } else {
                    // Error desconocido
                    tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No se pudieron cargar los datos</td></tr>';
                }
            })
            .catch(error => {
                // Para depuración
                console.error('Error detallado:', error);

                tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Error al cargar los datos</td></tr>';
            });
    }

    // Renderizar tabla de datos desde datos recibidos
    function renderDataTableFromData(data) {
        const tableHead = elements.dataTable.querySelector('thead');
        const tableBody = elements.dataTable.querySelector('tbody');

        // Limpiar tabla
        tableHead.innerHTML = '';
        tableBody.innerHTML = '';

        if (data && data.length > 0) {
            // Crear encabezados
            const headerRow = document.createElement('tr');

            Object.keys(data[0]).forEach(key => {
                const th = document.createElement('th');
                th.textContent = key;
                headerRow.appendChild(th);
            });

            tableHead.appendChild(headerRow);

            // Crear filas de datos
            const fragment = document.createDocumentFragment();

            data.forEach(row => {
                const tr = document.createElement('tr');

                Object.values(row).forEach(value => {
                    const td = document.createElement('td');
                    // Formatear los valores correctamente
                    if (value === null) {
                        td.innerHTML = '<em>NULL</em>';
                    } else if (typeof value === 'object') {
                        // Para objetos JSON, mostrar como cadena formateada
                        td.textContent = JSON.stringify(value);
                    } else {
                        td.textContent = value;
                    }
                    tr.appendChild(td);
                });

                fragment.appendChild(tr);
            });

            tableBody.appendChild(fragment);
        } else {
            // Mostrar mensaje si no hay datos
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center">No hay datos disponibles para esta tabla</td></tr>';
        }
    }

    // Cargar estadísticas del dashboard
    function loadStats() {
        fetch('/admin/api/db-stats.php')
            .then(response => response.json())
            .then(data => {
                if (!data.error) {
                    // Actualizar estadísticas con datos reales
                    elements.totalTables.textContent = data.total_tables || 0;
                    elements.totalFields.textContent = data.total_fields || 0;
                    elements.totalPayments.textContent = data.total_transactions || 0;
                    elements.totalSubscriptions.textContent = data.total_subscriptions || 0;
                } else {
                    console.error('Error al cargar estadísticas:', data.error);
                }
            })
            .catch(error => {
                console.error('Error al cargar estadísticas:', error);
            });
    }

    // Exportar datos de la tabla a CSV
    function exportTableData(tableName) {
        fetch(`/admin/api/table-data.php?table=${tableName}&limit=1000`)
            .then(response => response.json())
            .then(data => {
                if (data.data && Array.isArray(data.data) && data.data.length > 0) {
                    // Convertir datos a CSV
                    const headers = Object.keys(data.data[0]);
                    let csvContent = headers.join(',') + '\n';

                    data.data.forEach(row => {
                        let rowData = headers.map(header => {
                            let cell = row[header] === null ? '' : row[header];
                            // Escapar comas y comillas
                            if (typeof cell === 'string') {
                                cell = cell.replace(/"/g, '""');
                                if (cell.includes(',') || cell.includes('"') || cell.includes('\n')) {
                                    cell = `"${cell}"`;
                                }
                            }
                            return cell;
                        });
                        csvContent += rowData.join(',') + '\n';
                    });

                    // Crear blob y descargar
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');

                    link.setAttribute('href', url);
                    link.setAttribute('download', `${tableName}_export_${new Date().toISOString().slice(0, 10)}.csv`);
                    link.style.visibility = 'hidden';

                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                } else if (data.error) {
                    alert(`Error al exportar datos: ${data.error}`);
                } else {
                    alert('No hay datos disponibles para exportar');
                }
            })
            .catch(error => {
                console.error('Error al exportar datos:', error);
                alert('Error al exportar datos. Por favor, intente de nuevo.');
            });
    }

    // Configurar listeners de eventos
    function setupEventListeners() {
        // Botones de navegación
        elements.viewDataBtn.addEventListener('click', () => {
            elements.viewDataBtn.classList.add('btn-active');
            elements.structureBtn.classList.remove('btn-active');
            loadTableData(currentTable);
        });

        elements.structureBtn.addEventListener('click', () => {
            elements.structureBtn.classList.add('btn-active');
            elements.viewDataBtn.classList.remove('btn-active');
            elements.dataPreview.classList.add('hidden');
            elements.tableDetails.classList.remove('hidden');
        });

        // Botones de paginación
        elements.prevPageBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                loadTableData(currentTable, currentPage - 1);
            }
        });

        elements.nextPageBtn.addEventListener('click', () => {
            if (!elements.nextPageBtn.disabled) {
                loadTableData(currentTable, currentPage + 1);
            }
        });

        // Botón de actualizar
        elements.refreshBtn.addEventListener('click', () => {
            // Recargar todo desde el backend
            testConnection();
            loadStats();

            if (currentTable) {
                loadTableDetails(currentTable);
            }
        });

        // Botón de prueba de conexión
        elements.testConnectionBtn.addEventListener('click', () => {
            testConnection();
        });

        // Botón de configuración
        elements.configConnectionBtn.addEventListener('click', () => {
            elements.configModal.style.display = 'block';
        });

        // Botón de cerrar modal
        elements.closeModalBtn.addEventListener('click', () => {
            elements.configModal.style.display = 'none';
        });

        elements.cancelConfigBtn.addEventListener('click', () => {
            elements.configModal.style.display = 'none';
        });

        // Clic fuera del modal para cerrar
        window.addEventListener('click', (event) => {
            if (event.target === elements.configModal) {
                elements.configModal.style.display = 'none';
            }
        });

        // Envío del formulario de configuración
        elements.connectionForm.addEventListener('submit', (event) => {
            event.preventDefault();

            // Actualizar configuración
            dbConfig = {
                host: elements.hostInput.value,
                port: elements.portInput.value,
                database: elements.databaseInput.value,
                username: elements.usernameInput.value,
                password: elements.passwordInput.value
            };

            // Guardar en localStorage
            localStorage.setItem('db_host', dbConfig.host);
            localStorage.setItem('db_port', dbConfig.port);
            localStorage.setItem('db_name', dbConfig.database);
            localStorage.setItem('db_user', dbConfig.username);
            localStorage.setItem('db_password', dbConfig.password);

            // Actualizar pantalla
            loadDbConfig();
            testConnection();

            // Cerrar modal
            elements.configModal.style.display = 'none';
        });

        // Búsqueda de tablas
        elements.searchBtn.addEventListener('click', () => {
            const searchTerm = elements.searchInput.value.toLowerCase();

            if (searchTerm) {
                // Filtrar tablas
                document.querySelectorAll('#table-list li').forEach(li => {
                    const tableName = li.dataset.table;

                    if (tableName && tableName.toLowerCase().includes(searchTerm)) {
                        li.style.display = '';
                    } else {
                        li.style.display = 'none';
                    }
                });
            } else {
                // Mostrar todas las tablas si no hay término de búsqueda
                document.querySelectorAll('#table-list li').forEach(li => {
                    li.style.display = '';
                });
            }
        });

        // Evento de tecla Enter en búsqueda
        elements.searchInput.addEventListener('keypress', (event) => {
            if (event.key === 'Enter') {
                elements.searchBtn.click();
            }
        });

        // Botón de exportar
        elements.exportBtn.addEventListener('click', () => {
            if (currentTable) {
                exportTableData(currentTable);
            } else {
                alert('Por favor, seleccione una tabla para exportar');
            }
        });
    }

    // Iniciar el panel
    initPanel();
});