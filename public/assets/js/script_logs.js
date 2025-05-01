// Variables para gestión de logs
let logs = [];
const logTypes = ['info', 'warning', 'error'];

// Referencias a elementos DOM para logs
const logsContainer = document.getElementById('logsContainer');
const logTypeFilter = document.getElementById('logTypeFilter');
const refreshLogsButton = document.getElementById('refreshLogsButton');
const clearLogsButton = document.getElementById('clearLogsButton');

// Función para inicializar la funcionalidad de logs
function initLogs() {
    console.log("Inicializando sistema de logs...");

    // Configurar listeners de eventos para los controles de logs
    if (logTypeFilter) {
        logTypeFilter.addEventListener('change', function() {
            filterLogs();
        });
    }

    if (refreshLogsButton) {
        refreshLogsButton.addEventListener('click', function() {
            fetchLogs();
        });
    }

    if (clearLogsButton) {
        clearLogsButton.addEventListener('click', function() {
            clearLogs();
        });
    }

    // Cargar logs iniciales
    fetchLogs();
}

// Función para obtener logs del servidor usando logger.php
function fetchLogs() {
    // Mostrar indicador de carga en la terminal
    if (logsContainer) {
        logsContainer.innerHTML = '<div class="loading">Cargando logs...</div>';
    }

    // Log para depuración
    console.log("Solicitando logs al servidor...");

    // Obtener filtros actuales
    const filterType = logTypeFilter ? logTypeFilter.value : 'all';

    // URL del endpoint (ajustar según la ubicación real del archivo)
    const loggerUrl = '/logger.php';

    // Crear objeto FormData para la solicitud
    const formData = new FormData();
    formData.append('action', 'getLogs');
    formData.append('limit', '100'); // Limitar a 100 entradas

    // Si hay un filtro por tipo que no sea 'all', añadirlo
    if (filterType !== 'all') {
        formData.append('filter', filterType);
    }

    // Realizar petición a logger.php
    fetch(loggerUrl, {
        method: 'POST',
        body: formData
    })
        .then(response => {
            console.log("Respuesta recibida:", response.status, response.statusText);

            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
            }

            // Verificar el tipo de contenido para ayudar a diagnosticar problemas
            const contentType = response.headers.get('content-type');
            console.log("Tipo de contenido:", contentType);

            if (!contentType || !contentType.includes('application/json')) {
                // Si la respuesta no es JSON, intentar obtener el texto
                return response.text().then(text => {
                    // Mostrar los primeros 100 caracteres para diagnóstico
                    const preview = text.substring(0, 100);
                    throw new Error(`Respuesta no válida: El servidor no devolvió JSON. Recibido: ${preview}...`);
                });
            }

            return response.json();
        })
        .then(data => {
            console.log("Datos recibidos:", data);

            if (data.success) {
                // Guardar los logs obtenidos
                logs = data.entries || [];

                // Mostrar los logs en la terminal
                displayLogs();

                // Si no hay logs, mostrar un mensaje
                if (logs.length === 0) {
                    addLocalLog('info', 'No se encontraron logs en el archivo error.log');
                }
            } else {
                console.error('Error en la respuesta:', data);
                // Añadir mensaje de error local
                addLocalLog('error', 'Error al cargar logs: ' + (data.error || 'Respuesta inesperada'));
            }
        })
        .catch(error => {
            console.error('Error en la petición de logs:', error);

            // Añadir mensaje de error local con información detallada
            addLocalLog('error', 'Error en la petición de logs: ' + error.message);

            // Sugerir posibles soluciones basadas en el error
            if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                addLocalLog('warning', 'Comprueba que la ruta a logger.php sea correcta y que el servidor esté funcionando.');
            } else if (error.message.includes('JSON')) {
                addLocalLog('warning', 'El servidor no está devolviendo JSON válido. Verifica logger.php y los logs de PHP para errores.');
            }
        });
}

// Función para añadir un log local (para seguimiento del cliente)
function addLocalLog(type, message) {
    if (!logTypes.includes(type)) {
        type = 'info'; // Tipo por defecto
    }

    const newLog = {
        type: type,
        message: message,
        timestamp: getCurrentDateTime(),
        source: 'local' // Indica que fue generado localmente
    };

    logs.unshift(newLog); // Añadir al principio para mostrar los más recientes primero
    displayLogs();
}

// Función para mostrar los logs en la terminal
function displayLogs() {
    if (!logsContainer) return;

    // Obtener el filtro actual
    const filterType = logTypeFilter ? logTypeFilter.value : 'all';

    // Si no hay logs, mostrar mensaje
    if (logs.length === 0) {
        logsContainer.innerHTML = `
            <div class="terminal-placeholder">
                No hay logs disponibles. Las actividades del sistema se mostrarán aquí.
            </div>
        `;
        return;
    }

    // Filtrar logs según el tipo seleccionado
    const filteredLogs = filterType === 'all'
        ? logs
        : logs.filter(log => log.type === filterType);

    // Si no hay logs después del filtrado
    if (filteredLogs.length === 0) {
        logsContainer.innerHTML = `
            <div class="terminal-placeholder">
                No hay logs de tipo "${filterType}" disponibles.
            </div>
        `;
        return;
    }

    // Generar HTML para los logs
    let html = '';

    filteredLogs.forEach(log => {
        // Determinar la clase de estilo según el tipo de log
        const typeClass = logTypes.includes(log.type) ? log.type : 'info';

        // Formatear la fecha/hora si existe
        const timestamp = log.timestamp || 'Sin fecha';

        // Determinar una etiqueta para la fuente del log
        let sourceTag = '';
        if (log.source === 'local') {
            sourceTag = '<span class="log-source local">LOCAL</span>';
        } else {
            sourceTag = '<span class="log-source error">ERROR.LOG</span>';
        }

        // Si hay una propiedad raw, mostrarla en un tooltip
        const rawData = log.raw ? `title="${log.raw.replace(/"/g, '&quot;')}"` : '';

        html += `
            <div class="log-entry" ${rawData}>
                <span class="log-timestamp">[${timestamp}]</span>
                <span class="log-type log-type-${typeClass}">${log.type.toUpperCase()}</span>
                ${sourceTag}
                <span class="log-message">${log.message}</span>
            </div>
        `;
    });

    logsContainer.innerHTML = html;

    // Desplazar al inicio para ver los logs más recientes
    logsContainer.scrollTop = 0;
}

// Función para filtrar logs según el tipo seleccionado
function filterLogs() {
    displayLogs();
}

// Función para limpiar logs
function clearLogs() {
    if (confirm('¿Está seguro de que desea limpiar todos los logs?')) {
        console.log("Solicitando limpiar logs...");

        // Crear objeto FormData para la solicitud
        const formData = new FormData();
        formData.append('action', 'clearLogs');

        // URL del endpoint (ajustar según la ubicación real del archivo)
        const loggerUrl = '/logger.php';

        // Realizar petición a logger.php
        fetch(loggerUrl, {
            method: 'POST',
            body: formData
        })
            .then(response => {
                console.log("Respuesta de clearLogs:", response.status, response.statusText);

                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
                }

                // Verificar el tipo de contenido
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        const preview = text.substring(0, 100);
                        throw new Error(`Respuesta no válida: ${preview}...`);
                    });
                }

                return response.json();
            })
            .then(data => {
                console.log("Resultado de clearLogs:", data);

                if (data.success) {
                    // Limpiar los logs locales
                    logs = [];
                    displayLogs();

                    // Añadir un log local confirmando la limpieza
                    addLocalLog('info', 'Logs limpiados: ' + (data.message || 'Operación exitosa'));
                } else {
                    console.error('Error al limpiar logs:', data);
                    // Añadir mensaje de error local
                    addLocalLog('error', 'Error al limpiar logs: ' + (data.error || 'Respuesta inesperada'));
                }
            })
            .catch(error => {
                console.error('Error en la petición para limpiar logs:', error);
                // Añadir mensaje de error local
                addLocalLog('error', 'Error al limpiar logs: ' + error.message);
            });
    }
}

// Función auxiliar para obtener la fecha y hora actual
function getCurrentDateTime() {
    const now = new Date();

    // Formatear fecha: YYYY-MM-DD
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    // Formatear hora: HH:MM:SS
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');

    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

// Inicializar si el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM cargado, iniciando sistema de logs...");
    initLogs();
});