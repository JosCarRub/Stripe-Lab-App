<?php
declare(strict_types=1);

error_reporting(E_ALL & ~E_WARNING);

use App\commons\routes\StripeApiPath;
use config\DatabaseConnection;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';


$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$dotenv->required(['DB_HOST']);

echo "<h2>Conexión a la base de datos:</h2>";

try {
    $pdo = DatabaseConnection::getInstance();
    echo "<p style='color: green;'>✅ ¡Conexión exitosa a la base de datos!</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error al conectar a la base de datos: " . $e->getMessage() . "</p>";
}

echo "<h2>Ruta de la API de Stripe:</h2>";
echo "<p>" . $_ENV['DB_HOST'] . StripeApiPath::V1_ROUTE . StripeApiPath::STRIPE_ROUTE . "</p>";





