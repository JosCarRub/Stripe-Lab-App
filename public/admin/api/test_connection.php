<?php
// test-connection.php - Coloca este archivo en /public/admin/api/
// Accede a: http://tu-servidor/admin/api/test-connection.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Prueba de Conexión a MySQL</h1>";

// Intentar cargar el archivo .env
$dotenv = __DIR__ . '/../../../.env';
echo "<p>Buscando archivo .env en: " . $dotenv . "</p>";

if (file_exists($dotenv)) {
    echo "<p style='color:green'>✓ Archivo .env encontrado</p>";

    $envContent = file_get_contents($dotenv);
    echo "<h2>Contenido del archivo .env (sólo claves de DB):</h2>";
    echo "<pre>";

    // Extraer y mostrar sólo las claves DB_*
    $lines = file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'DB_') === 0) {
            // No mostrar la contraseña completa
            if (strpos($line, 'DB_PASSWORD') === 0) {
                $parts = explode('=', $line, 2);
                if (count($parts) > 1) {
                    echo htmlspecialchars($parts[0] . "=[OCULTA]") . "\n";
                } else {
                    echo htmlspecialchars($line) . "\n";
                }
            } else {
                echo htmlspecialchars($line) . "\n";
            }
        }
    }
    echo "</pre>";

    // Cargar variables .env
    $env = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;

        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
                $value = substr($value, 1, -1);
            } elseif (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1) {
                $value = substr($value, 1, -1);
            }

            $env[$name] = $value;
        }
    }

    // Configuración extraída
    echo "<h2>Configuración de Base de Datos:</h2>";
    echo "<ul>";
    echo "<li><strong>Driver:</strong> " . ($env['DB_CONNECTION'] ?? 'mysql (predeterminado)') . "</li>";
    echo "<li><strong>Host:</strong> " . ($env['DB_HOST'] ?? 'No definido') . "</li>";
    echo "<li><strong>Puerto:</strong> " . ($env['DB_PORT'] ?? 'No definido') . "</li>";
    echo "<li><strong>Base de Datos:</strong> " . ($env['DB_DATABASE'] ?? 'No definido') . "</li>";
    echo "<li><strong>Usuario:</strong> " . ($env['DB_USERNAME'] ?? 'No definido') . "</li>";
    echo "<li><strong>Contraseña:</strong> " . (empty($env['DB_PASSWORD']) ? 'No definida' : '[Definida]') . "</li>";
    echo "</ul>";

    // Probar la conexión con valores predeterminados de .env
    echo "<h2>Prueba 1: Conexión con valores del .env</h2>";
    try {
        $driver = $env['DB_CONNECTION'] ?? 'mysql';
        $host = $env['DB_HOST'] ?? '127.0.0.1';
        $port = $env['DB_PORT'] ?? '3306';
        $database = $env['DB_DATABASE'] ?? '';
        $username = $env['DB_USERNAME'] ?? '';
        $password = $env['DB_PASSWORD'] ?? '';

        $dsn = sprintf('%s:host=%s;port=%s;dbname=%s', $driver, $host, $port, $database);
        echo "<p>DSN: " . $dsn . "</p>";

        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        echo "<p style='color:green'>✓ Conexión exitosa a la base de datos</p>";

        // Mostrar tablas
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo "<p>Tablas encontradas: " . count($tables) . "</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>";
        }
        echo "</ul>";

    } catch (PDOException $e) {
        echo "<p style='color:red'>✗ Error de conexión: " . $e->getMessage() . "</p>";
    }

    // Probar con valores fijos conocidos
    echo "<h2>Prueba 2: Conexión con valores fijos</h2>";
    try {
        $dsn = 'mysql:host=127.0.0.1;port=3307;dbname=stripe_lab';
        echo "<p>DSN: " . $dsn . "</p>";

        $pdo = new PDO($dsn, 'test_user', $env['DB_PASSWORD'] ?? '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        echo "<p style='color:green'>✓ Conexión exitosa con 'test_user'</p>";

        // Mostrar tablas
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo "<p>Tablas encontradas: " . count($tables) . "</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>" . htmlspecialchars($table) . "</li>";
        }
        echo "</ul>";

    } catch (PDOException $e) {
        echo "<p style='color:red'>✗ Error con 'test_user': " . $e->getMessage() . "</p>";

        // Intentar con user_test como alternativa
        try {
            $dsn = 'mysql:host=127.0.0.1;port=3307;dbname=stripe_lab';
            $pdo = new PDO($dsn, 'user_test', $env['DB_PASSWORD'] ?? '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            echo "<p style='color:green'>✓ Conexión exitosa con 'user_test'</p>";

            // Mostrar tablas
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

            echo "<p>Tablas encontradas: " . count($tables) . "</p>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>" . htmlspecialchars($table) . "</li>";
            }
            echo "</ul>";

        } catch (PDOException $e2) {
            echo "<p style='color:red'>✗ Error con 'user_test': " . $e2->getMessage() . "</p>";
        }
    }

} else {
    echo "<p style='color:red'>✗ Archivo .env no encontrado</p>";
}

echo "<h2>Extensiones PHP disponibles</h2>";
echo "<pre>";
print_r(get_loaded_extensions());
echo "</pre>";

echo "<h2>Drivers PDO disponibles</h2>";
echo "<pre>";
print_r(PDO::getAvailableDrivers());
echo "</pre>";

echo "<hr>";
echo "<p><strong>Sugerencia:</strong> Si ambas pruebas fallan, verifica:</p>";
echo "<ol>";
echo "<li>Que MySQL esté ejecutándose en el puerto 3307</li>";
echo "<li>Que el usuario y contraseña sean correctos</li>";
echo "<li>Que la base de datos 'stripe_lab' exista</li>";
echo "<li>Que el usuario tenga permisos sobre esa base de datos</li>";
echo "</ol>";