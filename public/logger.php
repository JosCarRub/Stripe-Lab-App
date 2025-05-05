<?php
// Configuración
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: application/json');

// Ruta al archivo de script_logs (relativa a la ubicación de este script)
$errorLogPath = __DIR__ . '/../logs/errors.log';

// Variable para almacenar respuesta
$response = ['success' => false];

// Verificar que se ha solicitado una acción
if (!isset($_POST['action']) && !isset($_GET['action'])) {
    $response['error'] = 'No se ha especificado ninguna acción';
    echo json_encode($response);
    exit;
}

// Obtener la acción solicitada
$action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'];

// Realizar la acción correspondiente
switch ($action) {
    case 'getLogs':
        getLogs();
        break;
    case 'clearLogs':
        clearLogs();
        break;
    default:
        $response['error'] = 'Acción no reconocida';
        echo json_encode($response);
        break;
}

/**
 * Obtiene las entradas del archivo error.log
 */
function getLogs() {
    global $errorLogPath, $response;

    try {
        // Verificar si el archivo existe
        if (!file_exists($errorLogPath)) {
            // Si el archivo no existe, devolver un array vacío
            $response = [
                'success' => true,
                'entries' => [],
                'message' => "El archivo de log no existe o no se puede acceder"
            ];
            echo json_encode($response);
            return;
        }

        // Obtener parámetros de filtrado opcionales
        $limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 100;
        $filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : '';

        // Leer el contenido del archivo
        $content = file_get_contents($errorLogPath);

        if (empty($content)) {
            $response = [
                'success' => true,
                'entries' => [],
                'message' => "El archivo de log está vacío"
            ];
            echo json_encode($response);
            return;
        }

        // Dividir el contenido en líneas
        $lines = explode("\n", $content);

        // Filtrar líneas vacías
        $lines = array_filter($lines, function($line) {
            return !empty(trim($line));
        });

        // Procesar cada línea para extraer información relevante
        $entries = [];

        foreach ($lines as $line) {
            // Intentar extraer timestamp, tipo y mensaje
            $entry = parseLogLine($line);

            // Aplicar filtro de texto si está definido
            if ($filter && stripos($line, $filter) === false) {
                continue;
            }

            $entries[] = $entry;

            // Limitar la cantidad de entradas
            if (count($entries) >= $limit) {
                break;
            }
        }

        // Invertir el array para mostrar las entradas más recientes primero
        $entries = array_reverse($entries);

        // Construir respuesta
        $response = [
            'success' => true,
            'entries' => $entries,
            'totalEntries' => count($entries),
            'logExists' => true
        ];
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }

    echo json_encode($response);
}

/**
 * Limpia el archivo error.log
 */
function clearLogs() {
    global $errorLogPath, $response;

    try {
        // Verificar si el archivo existe
        if (!file_exists($errorLogPath)) {
            $response = [
                'success' => true,
                'message' => "El archivo de log no existe"
            ];
            echo json_encode($response);
            return;
        }

        // Vaciar el archivo pero mantenerlo
        file_put_contents($errorLogPath, '');

        $response = [
            'success' => true,
            'message' => "Archivo de log limpiado con éxito"
        ];
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }

    echo json_encode($response);
}

/**
 * Parsea una línea de log para extraer información relevante
 *
 * @param string $line Línea de log a parsear
 * @return array Información extraída
 */
function parseLogLine($line) {
    // Resultado por defecto con la línea completa
    $result = [
        'timestamp' => '',
        'type' => 'info',
        'message' => trim($line),
        'raw' => trim($line)
    ];

    // Intentar extraer timestamp con diferentes formatos comunes en archivos de log
    $timestampPatterns = [
        // Formato ISO: [2023-04-01 12:34:56]
        '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/',
        // Formato: 2023-04-01 12:34:56
        '/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/',
        // Formato: [01/Apr/2023:12:34:56 +0000]
        '/\[(\d{2}\/\w{3}\/\d{4}:\d{2}:\d{2}:\d{2} [\+\-]\d{4})\]/',
        // Formato: Apr 01 12:34:56
        '/(\w{3} \d{2} \d{2}:\d{2}:\d{2})/'
    ];

    foreach ($timestampPatterns as $pattern) {
        if (preg_match($pattern, $line, $matches)) {
            $result['timestamp'] = $matches[1];
            // Eliminar el timestamp de la línea para facilitar la extracción del resto
            $line = str_replace($matches[0], '', $line);
            break;
        }
    }

    // Intentar extraer el tipo de log
    $typePatterns = [
        // Formato: [ERROR]
        '/\[(ERROR|WARNING|INFO|DEBUG|NOTICE|CRITICAL)\]/i',
        // Formato: ERROR:
        '/(ERROR|WARNING|INFO|DEBUG|NOTICE|CRITICAL):/i',
        // Formato: Error -
        '/(Error|Warning|Info|Debug|Notice|Critical) -/i'
    ];

    foreach ($typePatterns as $pattern) {
        if (preg_match($pattern, $line, $matches)) {
            $result['type'] = strtolower($matches[1]);
            // Eliminar el tipo de la línea
            $line = str_replace($matches[0], '', $line);
            break;
        }
    }

    // Mapear tipos comunes a categorías simplificadas
    $typeMap = [
        'error' => 'error',
        'err' => 'error',
        'critical' => 'error',
        'alert' => 'error',
        'emergency' => 'error',
        'warning' => 'warning',
        'warn' => 'warning',
        'info' => 'info',
        'information' => 'info',
        'notice' => 'info',
        'debug' => 'info'
    ];

    if (array_key_exists(strtolower($result['type']), $typeMap)) {
        $result['type'] = $typeMap[strtolower($result['type'])];
    }

    // El resto de la línea es el mensaje
    $result['message'] = trim($line);

    // Si no se extrajo un mensaje o quedó vacío, usar la línea completa
    if (empty($result['message'])) {
        $result['message'] = $result['raw'];
    }

    return $result;
}