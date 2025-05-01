// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos DOM
    const connectionStatus = document.getElementById('connectionStatus');
    const dbConnectionState = document.getElementById('dbConnectionState');
    const dbName = document.getElementById('dbName');
    const dbHost = document.getElementById('dbHost');
    const dbUser = document.getElementById('dbUser');
    const lastConnection = document.getElementById('lastConnection');
    const reconnectButton = document.getElementById('reconnectButton');

    // Elementos para explorador de tablas
    const tableSelector = document.getElementById('tableSelector');
    const loadTableButton = document.getElementById('loadTableButton');
    const exportTableButton = document.getElementById('exportTableButton');
    const tableData = document.getElementById('tableData');

    // Elementos para estadísticas de BD
    const tablesCount = document.getElementById('tablesCount');

    // Inicialización
    init();

    // Función de inicialización
    function init() {
        // Comprobar conexión a la base de datos
        checkDatabaseConnection();

        // Configurar eventos
        setupEventListeners();
    }

    // Configurar listeners de eventos
    function setupEventListeners() {
        // Evento para reconectar a la base de datos
        if (reconnectButton) {
            reconnectButton.addEventListener('click', function() {
                checkDatabaseConnection();
            });
        }

        // Evento para cargar una tabla
        if (loadTableButton) {
            loadTableButton.addEventListener('click', function() {
                const selectedTable = tableSelector ? tableSelector.value : '';

                if (selectedTable) {
                    loadTableData(selectedTable);
                } else {
                    alert('Por favor, seleccione una tabla para cargar');
                }
            });
        }

        // Evento para exportar una tabla
        if (exportTableButton) {
            exportTableButton.addEventListener('click', function() {
                const selectedTable = tableSelector ? tableSelector.value : '';

                if (selectedTable) {
                    exportTable(selectedTable);
                } else {
                    alert('Por favor, seleccione una tabla para exportar');
                }
            });
        }
    }

    // Verificar conexión a base de datos usando AJAX
    function checkDatabaseConnection() {
        // Mostrar indicador de carga
        if (connectionStatus) connectionStatus.classList.remove('connected');
        if (dbConnectionState) {
            dbConnectionState.textContent = 'Verificando...';
            dbConnectionState.style.color = 'var(--warning-color, orange)';
        }

        // Solicitar estado de la base de datos a PHP
        fetchDataFromPHP('checkDbConnection')
            .then(data => {
                // Actualizar indicador visual
                if (connectionStatus) {
                    if (data.connected) {
                        connectionStatus.classList.add('connected');
                    } else {
                        connectionStatus.classList.remove('connected');
                    }
                }

                // Actualizar texto de estado
                if (dbConnectionState) {
                    dbConnectionState.textContent = data.connected ? 'Conectado' : 'Desconectado';
                    dbConnectionState.style.color = data.connected ? 'var(--success-color, green)' : 'var(--danger-color, red)';
                }

                // Actualizar información de la base de datos
                if (dbName) dbName.textContent = data.dbName || 'N/A';
                if (dbHost) dbHost.textContent = data.dbHost || 'N/A';
                if (dbUser) dbUser.textContent = data.dbUser || 'N/A';
                if (lastConnection) lastConnection.textContent = data.lastConnection || getCurrentDateTime();

                // Si la conexión es exitosa, cargar las tablas
                if (data.connected) {
                    loadAvailableTables();
                }
            })
            .catch(error => {
                // Manejar errores
                if (connectionStatus) connectionStatus.classList.remove('connected');
                if (dbConnectionState) {
                    dbConnectionState.textContent = 'Error';
                    dbConnectionState.style.color = 'var(--danger-color, red)';
                }
                console.error('Error al verificar la conexión:', error);
            });
    }

    // Cargar tablas disponibles
    function loadAvailableTables() {
        // Solicitar tablas disponibles a PHP
        fetchDataFromPHP('getAvailableTables')
            .then(data => {
                // Actualizar la lista de tablas disponibles
                const availableTables = data.tables || [];

                // Limpiar y actualizar el selector de tablas
                if (tableSelector) {
                    // Mantener la opción por defecto
                    tableSelector.innerHTML = '<option value="">Seleccionar tabla</option>';

                    // Añadir opciones de tablas
                    availableTables.forEach(table => {
                        const option = document.createElement('option');
                        option.value = table;
                        option.textContent = table;
                        tableSelector.appendChild(option);
                    });

                    // Si solo hay una tabla, seleccionarla automáticamente
                    if (availableTables.length === 1) {
                        tableSelector.value = availableTables[0];
                        loadTableData(availableTables[0]);
                    }
                }

                // Actualizar estadísticas de la BD
                if (data.stats) {
                    if (tablesCount) tablesCount.textContent = data.stats.tablesCount || '0';
                }
            })
            .catch(error => {
                // Manejar errores
                console.error('Error al cargar las tablas disponibles:', error);
            });
    }

    // Cargar datos de una tabla
    function loadTableData(tableName) {
        // Mostrar un indicador de carga
        if (tableData) {
            tableData.innerHTML = '<div class="loading">Cargando datos...</div>';
        }

        // Solicitar datos de la tabla a PHP
        fetchDataFromPHP('getTableData', { tableName: tableName })
            .then(data => {
                if (!tableData) return;

                if (data.error) {
                    tableData.innerHTML = `<div class="error-message">${data.error}</div>`;
                    return;
                }

                // Crear tabla HTML con los datos recibidos
                let html = '<table>';

                // Cabecera de la tabla
                html += '<thead><tr>';
                data.columns.forEach(column => {
                    html += `<th>${column}</th>`;
                });
                html += '</tr></thead>';

                // Cuerpo de la tabla con los datos
                html += '<tbody>';
                data.rows.forEach(row => {
                    html += '<tr>';
                    Object.values(row).forEach(value => {
                        html += `<td>${value !== null ? value : ''}</td>`;
                    });
                    html += '</tr>';
                });
                html += '</tbody>';

                html += '</table>';

                // Añadir información sobre el total de filas
                html += `<div class="table-info">Mostrando ${data.shownRows} de ${data.totalRows} registros</div>`;

                // Actualizar el contenido
                tableData.innerHTML = html;
            })
            .catch(error => {
                // Manejar errores
                if (tableData) {
                    tableData.innerHTML = '<div class="error-message">Error al cargar los datos de la tabla</div>';
                }
                console.error('Error al cargar datos de la tabla:', error);
            });
    }

    // Exportar tabla actual
    function exportTable(tableName) {
        // Redirigir al script PHP con parámetros para exportar
        window.location.href = `/control.php?action=exportTable&tableName=${tableName}`;
    }

    // Función para obtener datos de PHP mediante AJAX
    function fetchDataFromPHP(action, params = {}) {
        // Crear un objeto FormData para enviar los parámetros
        const formData = new FormData();

        // Añadir la acción solicitada
        formData.append('action', action);

        // Añadir los parámetros adicionales
        for (const key in params) {
            formData.append(key, params[key]);
        }

        // Realizar la petición fetch con la ruta correcta a control.php
        return fetch('/control.php', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                // Verificar si la respuesta es correcta
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                console.error('Error en la petición AJAX:', error);
                throw error;
            });
    }

    // Función auxiliar para obtener la fecha y hora actual
    function getCurrentDateTime() {
        const now = new Date();

        // Formatear fecha: YYYY-MM-DD
        const date = now.toISOString().slice(0, 10);

        // Formatear hora: HH:MM:SS
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const time = `${hours}:${minutes}:${seconds}`;

        return `${date} ${time}`;
    }
});