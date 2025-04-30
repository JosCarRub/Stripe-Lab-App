<?php
/**
 * Script para validar el endpoint del webhook de Stripe y realizar una prueba
 * de recepción de eventos utilizando tu webhook secret
 */

// Configuración (ajusta según sea necesario)
$webhookUrl = 'http://localhost:8000/v1/stripe';
$webhookSecret = 'whsec_5fdf4bf55ca3bfa581d52883b03a15edaf5d2321e7a89381e80dd9e08cb17028'; // Tu webhook secret
$testMode = true; // Establecer en false para enviar un evento sin la cabecera X-Test-Request

// Imprimir encabezado
echo "=======================================\n";
echo "   VALIDADOR DE WEBHOOK STRIPE         \n";
echo "=======================================\n\n";

// Comprobar que stripe-php esté disponible (opcional)
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    $stripeLibraryAvailable = class_exists('\Stripe\Stripe');
    echo "Biblioteca Stripe-PHP: " . ($stripeLibraryAvailable ? "Disponible ✓" : "No disponible ✗") . "\n";
} else {
    $stripeLibraryAvailable = false;
    echo "Biblioteca Stripe-PHP: No disponible ✗ (vendor/autoload.php no encontrado)\n";
}

// Verificar si el servidor está en ejecución
echo "\nVerificando si el servidor está en ejecución...\n";
$serverCheck = @file_get_contents(str_replace('/v1/stripe', '/', $webhookUrl));
if ($serverCheck === false) {
    echo " ✗ SERVIDOR NO DISPONIBLE\n";
    echo " → Por favor, asegúrate de que el servidor PHP está en ejecución con: php -S localhost:8000\n";
    exit(1);
} else {
    echo " ✓ Servidor activo y respondiendo\n";
}

// Crear un evento de prueba
echo "\nCreando evento de prueba...\n";
$eventId = 'evt_' . bin2hex(random_bytes(16));
$timestamp = time();

// Payload del evento
$payload = json_encode([
    'id' => $eventId,
    'object' => 'event',
    'api_version' => '2023-10-16',
    'created' => $timestamp,
    'type' => 'payment_intent.succeeded',
    'data' => [
        'object' => [
            'id' => 'pi_' . bin2hex(random_bytes(16)),
            'object' => 'payment_intent',
            'amount' => 2000,
            'currency' => 'usd',
            'status' => 'succeeded',
            'customer' => 'cus_' . bin2hex(random_bytes(16)),
            'created' => $timestamp,
            'test_mode' => true
        ]
    ],
    'livemode' => false
]);

// Si tenemos la biblioteca Stripe disponible, generamos una firma real
$signatureHeader = null;
if ($stripeLibraryAvailable && !$testMode) {
    echo "Generando firma de Stripe con el webhook secret...\n";
    $signatureHeader = \Stripe\Webhook::generateTestHeaderString(
        json_decode($payload, true),
        $webhookSecret,
        $timestamp
    );
    echo " ✓ Firma generada: " . substr($signatureHeader, 0, 20) . "...\n";
} else {
    // Alternativa manual para crear una firma (simulada)
    echo "Creando una firma manual para el webhook...\n";
    $signedPayload = $timestamp . '.' . $payload;
    $signature = hash_hmac('sha256', $signedPayload, $webhookSecret);
    $signatureHeader = "t=$timestamp,v1=$signature";
    echo " ✓ Firma generada: " . substr($signature, 0, 20) . "...\n";
}

// Enviar la solicitud al endpoint
echo "\nEnviando evento al endpoint: $webhookUrl\n";

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

$headers = [
    'Content-Type: application/json',
];

// Añadir cabecera de firma si está disponible
if ($signatureHeader && !$testMode) {
    $headers[] = 'Stripe-Signature: ' . $signatureHeader;
}

// Si estamos en modo prueba, añadir la cabecera especial
if ($testMode) {
    $headers[] = 'X-Test-Request: true';
    echo "Modo de prueba activado: usando cabecera X-Test-Request\n";
} else {
    echo "Usando firma de Stripe-Signature para autenticación\n";
}

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Habilitar información detallada para depuración
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

// Ejecutar la solicitud
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "\nResultados:\n";
echo "----------------------------\n";

if ($response === false) {
    echo "✗ ERROR: " . curl_error($ch) . "\n";

    // Mostrar información detallada
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    echo "\nInformación detallada:\n";
    echo "----------------------------\n";
    echo "$verboseLog\n";
} else {
    echo "Código HTTP: " . $httpCode . " " . ($httpCode >= 200 && $httpCode < 300 ? "✓" : "✗") . "\n";
    echo "Respuesta del servidor:\n";
    echo "----------------------------\n";
    echo $response . "\n";
}

curl_close($ch);

// Sugerencias para solucionar problemas
echo "\nSugerencias si el evento no fue procesado:\n";
echo "----------------------------\n";
echo "1. Verifica que tu archivo index.php está configurado correctamente para manejar webhooks\n";
echo "2. Asegúrate de que la tabla 'payments' existe en tu base de datos\n";
echo "3. Confirma que el webhook secret en tu código coincide con: $webhookSecret\n";
echo "4. Prueba enviar un evento directamente desde la interfaz web\n";
echo "5. Revisa los registros de error de PHP para ver si hay algún problema\n";

// Comandos para enviar eventos desde la terminal
echo "\nComandos para enviar eventos desde la terminal:\n";
echo "----------------------------\n";
echo "1. Usando curl:\n";
echo "   curl -X POST \"$webhookUrl\" \\\n";
echo "     -H \"Content-Type: application/json\" \\\n";
echo "     -H \"X-Test-Request: true\" \\\n";
echo "     -d '{\"type\":\"payment_intent.succeeded\",\"data\":{\"object\":{\"id\":\"pi_test_123\",\"customer\":\"cus_test_123\"}}}'\n\n";

echo "2. Usando Stripe CLI (si la tienes instalada):\n";
echo "   stripe trigger payment_intent.succeeded --webhook-endpoint=$webhookUrl\n\n";

echo "3. Usando PowerShell en Windows:\n";
echo "   Invoke-RestMethod -Method Post -Uri \"$webhookUrl\" `\n";
echo "     -Headers @{\"Content-Type\"=\"application/json\"; \"X-Test-Request\"=\"true\"} `\n";
echo "     -Body '{\"type\":\"payment_intent.succeeded\",\"data\":{\"object\":{\"id\":\"pi_test_123\",\"customer\":\"cus_test_123\"}}}'\n";

echo "\n=======================================\n";
echo "Validación completada\n";
echo "=======================================\n";