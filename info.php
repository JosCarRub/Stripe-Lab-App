<?php
// Obtener el directorio de extensiones
$extDir = ini_get('extension_dir');
echo "<h1>Verificación de archivos DLL</h1>";
echo "<p>Directorio de extensiones: $extDir</p>";

// Listar archivos en el directorio de extensiones
if (is_dir($extDir)) {
    $files = scandir($extDir);

    echo "<h2>Archivos en el directorio de extensiones:</h2>";
    echo "<ul>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'dll') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";

    // Buscar archivos específicos de PDO
    echo "<h2>Archivos PDO:</h2>";
    $pdoFiles = array_filter($files, function($file) {
        return strpos($file, 'pdo') !== false && pathinfo($file, PATHINFO_EXTENSION) === 'dll';
    });

    if (empty($pdoFiles)) {
        echo "<p style='color:red'>No se encontraron archivos DLL de PDO en el directorio de extensiones.</p>";
    } else {
        echo "<ul>";
        foreach ($pdoFiles as $file) {
            echo "<li>$file</li>";
        }
        echo "</ul>";
    }

    // Verificar la existencia de archivos específicos
    $requiredFiles = [
        'php_pdo.dll' => 'PDO base',
        'php_pdo_mysql.dll' => 'PDO MySQL driver'
    ];

    echo "<h2>Verificación de archivos requeridos:</h2>";
    echo "<ul>";
    foreach ($requiredFiles as $file => $description) {
        $exists = file_exists($extDir . DIRECTORY_SEPARATOR . $file);
        echo "<li>$file ($description): " .
            ($exists ? "<span style='color:green'>Encontrado</span>" : "<span style='color:red'>No encontrado</span>") .
            "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:red'>El directorio de extensiones no existe o no es accesible.</p>";
}

// Comprobar versión PHP
echo "<h2>Información de PHP:</h2>";
echo "<ul>";
echo "<li>Versión PHP: " . PHP_VERSION . "</li>";
echo "<li>Arquitectura: " . (PHP_INT_SIZE == 8 ? '64-bit' : '32-bit') . "</li>";
echo "<li>Thread Safety: " . (defined('PHP_ZTS') && PHP_ZTS ? 'Enabled' : 'Disabled') . "</li>";
echo "</ul>";

// Sugerencias para solucionar el problema
echo "<h2>Sugerencias de solución:</h2>";
if (!is_dir($extDir) || empty($pdoFiles)) {
    echo "<ol>";
    echo "<li><strong>Descarga las extensiones PDO</strong>:";
    echo "<ul>";
    echo "<li>Visita <a href='https://windows.php.net/downloads/pecl/releases/pdo/' target='_blank'>windows.php.net</a> para descargar las extensiones PDO para PHP 8.1</li>";
    echo "<li>Asegúrate de descargar la versión compatible con tu instalación (PHP 8.1.32, " . (PHP_INT_SIZE == 8 ? '64-bit' : '32-bit') . ", " . (defined('PHP_ZTS') && PHP_ZTS ? 'Thread Safe' : 'Non-Thread Safe') . ")</li>";
    echo "</ul></li>";
    echo "<li><strong>Copia los archivos DLL</strong>:";
    echo "<ul>";
    echo "<li>Descomprime los archivos descargados</li>";
    echo "<li>Copia <code>php_pdo.dll</code> y <code>php_pdo_mysql.dll</code> a tu directorio de extensiones: <code>$extDir</code></li>";
    echo "</ul></li>";
    echo "</ol>";
} else {
    echo "<ol>";
    echo "<li><strong>Verifica la configuración en php.ini</strong>:";
    echo "<ul>";
    echo "<li>Asegúrate de que las extensiones estén habilitadas correctamente en php.ini</li>";
    echo "<li>Usa los nombres exactos de los archivos DLL que encontraste</li>";
    echo "</ul></li>";
    echo "</ol>";
}

// Proporcionar configuración alternativa para php.ini
echo "<h2>Configuración alternativa para php.ini:</h2>";
echo "<pre>";
echo "extension_dir = \"" . $extDir . "\"\n";
echo "; Descomenta estas líneas y asegúrate de que los nombres de archivo coincidan con los que tienes\n";
$possiblePdoFiles = [];
foreach ($files as $file) {
    if (strpos($file, 'pdo') !== false && pathinfo($file, PATHINFO_EXTENSION) === 'dll') {
        $possiblePdoFiles[] = $file;
    }
}

if (!empty($possiblePdoFiles)) {
    foreach ($possiblePdoFiles as $file) {
        echo "extension=" . str_replace('php_', '', $file) . "\n";
    }
} else {
    echo "; extension=pdo\n";
    echo "; extension=pdo_mysql\n";
}
echo "</pre>";

// Alternativa: Usar MySQLi
echo "<h2>Alternativa: Usar MySQLi en lugar de PDO</h2>";
echo "<p>Si no puedes hacer funcionar PDO, puedes modificar tu código para usar MySQLi:</p>";
echo "<pre>";
echo "// Conexión usando MySQLi\n";
echo "\$mysqli = new mysqli(\n";
echo "    \$_ENV['DB_HOST'] ?? '127.0.0.1',\n";
echo "    \$_ENV['DB_USER'] ?? 'root',\n";
echo "    \$_ENV['DB_PASS'] ?? '',\n";
echo "    \$_ENV['DB_NAME'] ?? 'stripe_payments',\n";
echo "    \$_ENV['DB_PORT'] ?? 3306\n";
echo ");\n\n";
echo "if (\$mysqli->connect_error) {\n";
echo "    die('Error de conexión: ' . \$mysqli->connect_error);\n";
echo "}\n";
echo "</pre>";