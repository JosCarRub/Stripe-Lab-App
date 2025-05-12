// Función para cargar la estructura de una tabla
function loadTableStructure(tableName) {
    // Mostrar indicador de carga
    const fieldsTable = document.getElementById('fields-table');
    fieldsTable.querySelector('tbody').innerHTML = '<tr><td colspan="6" class="loading-cell"><div class="spinner"></div></td></tr>';

    // Realizar solicitud AJAX para obtener la estructura
    fetch(`panel_api.php?action=get_table_structure&table=${encodeURIComponent(tableName)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayTableStructure(data.structure);
            } else {
                console.error('Error en la respuesta:', data.error);
                fieldsTable.querySelector('tbody').innerHTML = `<tr><td colspan="6" class="error-cell">${data.error || 'Error desconocido'}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error al cargar la estructura:', error);
            fieldsTable.querySelector('tbody').innerHTML = '<tr><td colspan="6" class="error-cell">Error al cargar la estructura</td></tr>';
        });
}

// Mostrar la estructura de una tabla
function displayTableStructure(structure) {
    const tbody = document.getElementById('fields-table').querySelector('tbody');
    tbody.innerHTML = '';

    structure.forEach(field => {
        const tr = document.createElement('tr');

        // Normalizar nombres de campo para compatibilidad con diferentes versiones de MySQL/PDO
        const fieldName = field.Field || field.COLUMN_NAME || field.field || field.column_name;
        const fieldType = field.Type || field.COLUMN_TYPE || field.type || field.column_type || field.DATA_TYPE || field.data_type;
        const fieldNull = field.Null || field.IS_NULLABLE || field.null || field['is_nullable'];
        const fieldKey = field.Key || field.COLUMN_KEY || field.key || field.column_key;
        const fieldDefault = field.Default !== undefined ? field.Default : (
            field.COLUMN_DEFAULT !== undefined ? field.COLUMN_DEFAULT : (
                field.default !== undefined ? field.default : (
                    field.column_default !== undefined ? field.column_default : null)));
        const fieldExtra = field.Extra || field.EXTRA || field.extra || '';

        tr.innerHTML = `
            <td>${fieldName || ''}</td>
            <td>${fieldType || ''}</td>
            <td>${fieldNull || ''}</td>
            <td>${fieldKey || ''}</td>
            <td>${fieldDefault !== null && fieldDefault !== undefined ? fieldDefault : '<em>NULL</em>'}</td>
            <td>${fieldExtra || ''}</td>
        `;
        tbody.appendChild(tr);
    });
}

// Cargar los datos de una tabla
function loadTableData(tableName, page = 1) {
    // Mostrar indicador de carga
    const dataTable = document.getElementById('data-table');
    dataTable.innerHTML = '<tbody><tr><td colspan="10" class="loading-cell"><div class="spinner"></div></td></tr></tbody>';

    // Realizar solicitud AJAX para obtener los datos
    fetch(`panel_api.php?action=get_table_data&table=${encodeURIComponent(tableName)}&page=${page}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayTableData(data.data);
                updatePagination(page, data.data.pages);
            } else {
                console.error('Error en la respuesta:', data.error);
                dataTable.innerHTML = `<tbody><tr><td colspan="10" class="error-cell">${data.error || 'Error desconocido'}</td></tr></tbody>`;
            }
        })
        .catch(error => {
            console.error('Error al cargar los datos:', error);
            dataTable.innerHTML = '<tbody><tr><td colspan="10" class="error-cell">Error al cargar los datos</td></tr></tbody>';
        });
}

// Función de depuración
function debugResponse(response) {
    console.log('Respuesta completa:', response);
    return response;
}

// Inicializar la aplicación con los datos
function initAppWithData(data) {
    console.log('Inicializando con datos:', data);

    // Actualizar indicador de conexión
    updateConnectionStatus(data.connectionStatus);

    // Actualizar detalles de conexión
    document.getElementById('db-host').textContent = data.dbConfig.host;
    document.getElementById('db-port').textContent = data.dbConfig.port;
    document.getElementById('db-name').textContent = data.dbConfig.database;
    document.getElementById('db-user').textContent = data.dbConfig.username;

    // Actualizar estadísticas
    document.getElementById('total-tables').textContent = data.stats.tables;
    document.getElementById('total-fields').textContent = data.stats.fields;
    document.getElementById('total-payments').textContent = data.stats.payments;
    document.getElementById('total-subscriptions').textContent = data.stats.subscriptions;

    // Cargar lista de tablas
    loadTableList(data.tables);

    // Inicializar diagrama de relaciones si hay relaciones
    if (data.relationships && data.relationships.length > 0) {
        initRelationshipsDiagram(data.relationships);
    } else {
        document.getElementById('relationships').classList.add('hidden');
    }
}