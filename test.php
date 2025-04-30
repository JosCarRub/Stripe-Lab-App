<?php
// Script de prueba con tu configuración específica

// Configuración específica para tu entorno Docker
$host = '127.0.0.1';
$port = 3306;
$database = 'stripe_lab';
$username = 'test_user';
$password = 'password';

echo "<h1>Prueba de conexión a tu MySQL en Docker</h1>";
echo "<h2>Configuración:</h2>";
echo "<ul>";
echo "<li>Host: $host</li>";
echo "<li>Puerto: $port</li>";
echo "<li>Base de datos: $database</li>";
echo "<li>Usuario: $username</li>";
echo "<li>Contraseña: $password</li>";
echo "</ul>";

// Mostrar drivers disponibles
echo "<h2>Drivers PDO disponibles:</h2>";
$drivers = PDO::getAvailableDrivers();
echo "<pre>";
var_dump($drivers);
echo "</pre>";

// Intentar conexión con PDO
echo "<h2>Intentando conexión con PDO:</h2>";
try {
    $dsn = "mysql:host=$host;port=$port";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<p style='color:green'>✅ Conexión exitosa al servidor MySQL</p>";

    // Verificar existencia de la base de datos
    $stmt = $pdo->query("SHOW DATABASES LIKE '$database'");
    $databaseExists = $stmt->rowCount() > 0;

    if ($databaseExists) {
        echo "<p style='color:green'>✅ Base de datos '$database' encontrada</p>";

        // Conectar a la base de datos específica
        $dsn = "mysql:host=$host;port=$port;dbname=$database";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verificar si existe la tabla payments
        $stmt = $pdo->query("SHOW TABLES LIKE 'payments'");
        $tableExists = $stmt->rowCount() > 0;

        if ($tableExists) {
            echo "<p style='color:green'>✅ Tabla 'payments' encontrada</p>";

            // Contar registros
            $stmt = $pdo->query("SELECT COUNT(*) FROM payments");
            $count = $stmt->fetchColumn();
            echo "<p>La tabla contiene $count registros</p>";

            // Mostrar estructura de la tabla
            $stmt = $pdo->query("DESCRIBE payments");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<h3>Estructura de la tabla:</h3>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Predeterminado</th><th>Extra</th></tr>";

            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>" . $column['Field'] . "</td>";
                echo "<td>" . $column['Type'] . "</td>";
                echo "<td>" . $column['Null'] . "</td>";
                echo "<td>" . $column['Key'] . "</td>";
                echo "<td>" . $column['Default'] . "</td>";
                echo "<td>" . $column['Extra'] . "</td>";
                echo "</tr>";
            }

            echo "</table>";
        } else {
            echo "<p style='color:orange'>⚠️ La tabla 'payments' no existe</p>";
            echo "<p>¿Quieres crear la tabla 'payments'? <a href='?create_table=yes'>Crear tabla</a></p>";

            if (isset($_GET['create_table']) && $_GET['create_table'] === 'yes') {
                try {
                    $createTableSQL = "CREATE TABLE payments (
                        id_payment VARCHAR(36) PRIMARY KEY,
                        event_id VARCHAR(255) NOT NULL,
                        customer_id VARCHAR(255) NOT NULL,
                        payment_intent_id VARCHAR(255) NOT NULL,
                        event_type VARCHAR(100) NOT NULL,
                        payload TEXT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )";

                    $pdo->exec($createTableSQL);
                    echo "<p style='color:green'>✅ Tabla 'payments' creada exitosamente</p>";
                    echo "<p>Recarga la página para ver la estructura</p>";
                } catch (PDOException $e) {
                    echo "<p style='color:red'>❌ Error al crear la tabla: " . $e->getMessage() . "</p>";
                }
            }
        }
    } else {
        echo "<p style='color:red'>❌ Base de datos '$database' no encontrada</p>";
        echo "<p>Esto es extraño, ya que tu configuración de Docker especifica que esta base de datos debería ser creada automáticamente.</p>";
        echo "<p>¿Quieres intentar crearla? <a href='?create_db=yes'>Crear base de datos</a></p>";

        if (isset($_GET['create_db']) && $_GET['create_db'] === 'yes') {
            try {
                $pdo->exec("CREATE DATABASE `$database`");
                echo "<p style='color:green'>✅ Base de datos '$database' creada exitosamente</p>";
                echo "<p>Recarga la página para continuar la verificación</p>";
            } catch (PDOException $e) {
                echo "<p style='color:red'>❌ Error al crear la base de datos: " . $e->getMessage() . "</p>";
            }
        }
    }

    // Proporcionar la configuración para index.php
    echo "<h2>Tu configuración está correcta</h2>";
    echo "<p>Puedes usar la siguiente configuración en tu archivo .env:</p>";
    echo "<pre>
DB_HOST=$host
DB_PORT=$port
DB_NAME=$database
DB_USER=$username
DB_PASS=$password
</pre>";

    echo "<p>Para probar tu proyecto, sigue estos pasos:</p>";
    echo "<ol>";
    echo "<li>Asegúrate de que tu archivo .env tiene las credenciales correctas (mostradas arriba)</li>";
    echo "<li>Copia el archivo index.php (tolerante a advertencias) que te proporcioné</li>";
    echo "<li>Inicia el servidor PHP con: <code>php -S localhost:8000</code></li>";
    echo "<li>Visita <a href='http://localhost:8000'>http://localhost:8000</a> en tu navegador</li>";
    echo "</ol>";

} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error de conexión: " . $e->getMessage() . "</p>";

    // Sugerencias específicas basadas en el error
    if (strpos($e->getMessage(), "Access denied") !== false) {
        echo "<h3>Solución de problemas para 'Access denied':</h3>";
        echo "<p>El error indica que las credenciales no son correctas. Verifica:</p>";
        echo "<ol>";
        echo "<li>Usuario: Asegúrate de que '$username' es correcto</li>";
        echo "<li>Contraseña: Verifica que '$password' es la contraseña correcta</li>";
        echo "<li>Host: Prueba también con 'localhost' o 'host.docker.internal' en lugar de '$host'</li>";
        echo "</ol>";

        echo "<p>Puedes verificar la configuración de tu contenedor con:</p>";
        echo "<pre>docker exec -it stripe_mysql mysql -u root -p</pre>";
        echo "<p>Luego, dentro de MySQL, verifica los usuarios:</p>";
        echo "<pre>SELECT user, host, plugin FROM mysql.user;</pre>";
    }

    if (strpos($e->getMessage(), "Connection refused") !== false) {
        echo "<h3>Solución de problemas para 'Connection refused':</h3>";
        echo "<p>El error indica que no se puede establecer conexión con MySQL. Verifica:</p>";
        echo "<ol>";
        echo "<li>Estado del contenedor: <code>docker ps | grep stripe_mysql</code></li>";
        echo "<li>Puerto mapeado: <code>docker port stripe_mysql</code></li>";
        echo "<li>Logs del contenedor: <code>docker logs stripe_mysql</code></li>";
        echo "</ol>";
    }

    if (strpos($e->getMessage(), "Unknown database") !== false) {
        echo "<h3>Solución de problemas para 'Unknown database':</h3>";
        echo "<p>La base de datos '$database' no existe. Verifica:</p>";
        echo "<ol>";
        echo "<li>La configuración MYSQL_DATABASE en tu docker-compose.yml</li>";
        echo "<li>Si el contenedor se inició correctamente: <code>docker logs stripe_mysql</code></li>";
        echo "</ol>";
    }
}