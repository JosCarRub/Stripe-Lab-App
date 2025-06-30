document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('current-year').textContent = new Date().getFullYear();

    document.getElementById('sidebar-toggle').addEventListener('click', function() {
        document.querySelector('.app-container').classList.toggle('sidebar-collapsed');
    });

    document.getElementById('menu-toggle').addEventListener('click', function() {
        document.querySelector('.app-container').classList.toggle('sidebar-visible');
    });

    document.getElementById('view-data-btn').addEventListener('click', function() {
        this.classList.add('active');
        document.getElementById('structure-btn').classList.remove('active');
        document.getElementById('data-preview').classList.remove('hidden');
        document.getElementById('table-details').classList.add('hidden');
        const tableName = document.getElementById('current-table-name').textContent;
        if (tableName !== '-') {
            loadTableData(tableName);
        }
    });

    document.getElementById('structure-btn').addEventListener('click', function() {
        this.classList.add('active');
        document.getElementById('view-data-btn').classList.remove('active');
        document.getElementById('table-details').classList.remove('hidden');
        document.getElementById('data-preview').classList.add('hidden');
    });

    document.getElementById('refresh-btn').addEventListener('click', function() {
        fetchInitialData();
    });

    document.getElementById('export-btn').addEventListener('click', function() {
        let dataToExport = '';
        let fileName = 'stripe_db_export.csv';
        if (!document.getElementById('table-details').classList.contains('hidden')) {
            const tableName = document.getElementById('current-table-name').textContent;
            const table = document.getElementById('fields-table');
            dataToExport = tableToCSV(table);
            fileName = `${tableName}_structure.csv`;
        } else if (!document.getElementById('data-preview').classList.contains('hidden')) {
            const tableName = document.getElementById('preview-table-name').textContent;
            const table = document.getElementById('data-table');
            dataToExport = tableToCSV(table);
            fileName = `${tableName}_data.csv`;
        } else {
            const statsData = [
                ['Statistic', 'Value'],
                ['Tables', document.getElementById('total-tables').textContent],
                ['Fields', document.getElementById('total-fields').textContent],
                ['Payments', document.getElementById('total-payments').textContent],
                ['Subscriptions', document.getElementById('total-subscriptions').textContent]
            ];
            dataToExport = statsData.map(row => row.join(',')).join('\n');
            fileName = 'stripe_db_stats.csv';
        }
        downloadCSV(dataToExport, fileName);
    });

    fetchInitialData();
});

function fetchInitialData() {
    fetch('api/panel_api.php?action=get_initial_data')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                initAppWithData(data);
            } else {
                console.error("Error fetching initial data:", data.error);
                updateConnectionStatus('error');
            }
        })
        .catch(error => {
            console.error("Fetch error:", error);
            updateConnectionStatus('error');
        });
}

function initAppWithData(data) {
    updateConnectionStatus(data.connectionStatus);
    document.getElementById('db-host').textContent = data.dbConfig.host;
    document.getElementById('db-port').textContent = data.dbConfig.port;
    document.getElementById('db-name').textContent = data.dbConfig.database;
    document.getElementById('db-user').textContent = data.dbConfig.username;
    document.getElementById('total-tables').textContent = data.stats.tables;
    document.getElementById('total-fields').textContent = data.stats.fields;
    document.getElementById('total-payments').textContent = data.stats.payments;
    document.getElementById('total-subscriptions').textContent = data.stats.subscriptions;
    loadTableList(data.tables);
}

function updateConnectionStatus(status) {
    const statusIndicator = document.getElementById('connection-status-indicator');
    const statusText = document.getElementById('connection-status-text');
    const sidebarStatusIndicator = document.querySelector('.sidebar-footer .status-indicator');
    const sidebarStatusText = document.querySelector('.sidebar-footer .status-text');

    if (status === 'connected') {
        statusIndicator.className = 'status-indicator-dot connected';
        statusText.textContent = 'Conectado';
        statusText.className = 'status-text connected';
        sidebarStatusIndicator.className = 'status-indicator connected';
        sidebarStatusText.textContent = 'Conectado';
    } else {
        statusIndicator.className = 'status-indicator-dot error';
        statusText.textContent = 'Error de conexión';
        statusText.className = 'status-text error';
        sidebarStatusIndicator.className = 'status-indicator error';
        sidebarStatusText.textContent = 'Error';
    }
}

function loadTableList(tables) {
    const tableList = document.getElementById('table-list');
    tableList.innerHTML = '';
    if (tables.length === 0) {
        tableList.innerHTML = '<li class="no-tables">No se encontraron tablas</li>';
        return;
    }
    tables.forEach(tableName => {
        const li = document.createElement('li');
        li.className = 'table-item';
        li.innerHTML = `<a href="#" class="table-link" data-table="${tableName}"><i class="fas fa-table table-icon"></i><span class="table-name">${tableName}</span></a>`;
        tableList.appendChild(li);
        li.querySelector('.table-link').addEventListener('click', function(e) {
            e.preventDefault();
            selectTable(tableName);
        });
    });
}

function selectTable(tableName) {
    document.querySelectorAll('.table-item').forEach(item => item.classList.remove('active'));
    document.querySelector(`.table-link[data-table="${tableName}"]`).closest('.table-item').classList.add('active');
    document.getElementById('current-table-name').textContent = tableName;
    document.getElementById('preview-table-name').textContent = tableName;
    if (window.innerWidth < 992) {
        document.querySelector('.app-container').classList.remove('sidebar-visible');
    }
    document.getElementById('dashboard-overview').classList.add('hidden');
    document.getElementById('connection-test').classList.add('hidden');
    document.getElementById('table-details').classList.remove('hidden');
    document.getElementById('data-preview').classList.add('hidden');
    document.getElementById('structure-btn').classList.add('active');
    document.getElementById('view-data-btn').classList.remove('active');
    loadTableStructure(tableName);
}

function loadTableStructure(tableName) {
    const fieldsTableBody = document.getElementById('fields-table').querySelector('tbody');
    fieldsTableBody.innerHTML = '<tr><td colspan="6" class="loading-cell"><div class="spinner"></div></td></tr>';
    fetch(`api/panel_api.php?action=get_table_structure&table=${encodeURIComponent(tableName)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTableStructure(data.structure);
            } else {
                fieldsTableBody.innerHTML = `<tr><td colspan="6" class="error-cell">${data.error}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error al cargar la estructura:', error);
            fieldsTableBody.innerHTML = '<tr><td colspan="6" class="error-cell">Error al cargar la estructura</td></tr>';
        });
}

function displayTableStructure(structure) {
    const tbody = document.getElementById('fields-table').querySelector('tbody');
    tbody.innerHTML = '';
    structure.forEach(field => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${field.Field}</td>
            <td>${field.Type}</td>
            <td>${field.Null}</td>
            <td>${field.Key}</td>
            <td>${field.Default !== null ? field.Default : '<em>NULL</em>'}</td>
            <td>${field.Extra}</td>
        `;
        tbody.appendChild(tr);
    });
}

function loadTableData(tableName, page = 1) {
    const dataTableBody = document.getElementById('data-table').querySelector('tbody');
    const dataTableHead = document.getElementById('data-table').querySelector('thead');
    dataTableBody.innerHTML = '<tr><td colspan="10" class="loading-cell"><div class="spinner"></div></td></tr>';
    dataTableHead.innerHTML = '';

    fetch(`api/panel_api.php?action=get_table_data&table=${encodeURIComponent(tableName)}&page=${page}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayTableData(data.data);
                updatePagination(page, data.data.pages, tableName);
            } else {
                dataTableBody.innerHTML = `<tr><td colspan="10" class="error-cell">${data.error}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error al cargar los datos:', error);
            dataTableBody.innerHTML = '<tr><td colspan="10" class="error-cell">Error al cargar los datos</td></tr>';
        });
}

function displayTableData(data) {
    const dataTableHead = document.getElementById('data-table').querySelector('thead');
    const dataTableBody = document.getElementById('data-table').querySelector('tbody');
    let headHTML = '<tr>';
    data.columns.forEach(column => headHTML += `<th>${column}</th>`);
    headHTML += '</tr>';
    dataTableHead.innerHTML = headHTML;

    let bodyHTML = '';
    if (data.rows.length === 0) {
        bodyHTML = `<tr><td colspan="${data.columns.length}" class="empty-cell">No hay datos para mostrar</td></tr>`;
    } else {
        data.rows.forEach(row => {
            bodyHTML += '<tr>';
            data.columns.forEach(column => {
                const value = row[column];
                bodyHTML += `<td>${value !== null ? value : '<em>NULL</em>'}</td>`;
            });
            bodyHTML += '</tr>';
        });
    }
    dataTableBody.innerHTML = bodyHTML;
}

function updatePagination(currentPage, totalPages, tableName) {
    document.getElementById('page-info').textContent = `Página ${currentPage} de ${totalPages}`;
    const prevButton = document.getElementById('prev-page');
    const nextButton = document.getElementById('next-page');
    prevButton.disabled = currentPage <= 1;
    nextButton.disabled = currentPage >= totalPages;

    prevButton.onclick = () => { if (currentPage > 1) loadTableData(tableName, currentPage - 1); };
    nextButton.onclick = () => { if (currentPage < totalPages) loadTableData(tableName, currentPage + 1); };
}

function tableToCSV(table) {
    const rows = table.querySelectorAll('tr');
    return Array.from(rows).map(row => {
        const cells = row.querySelectorAll('th, td');
        return Array.from(cells).map(cell => {
            let text = cell.textContent.trim();
            if (text.includes(',') || text.includes('"')) {
                text = `"${text.replace(/"/g, '""')}"`;
            }
            return text;
        }).join(',');
    }).join('\n');
}

function downloadCSV(csvContent, fileName) {
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = fileName;
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}