<?php
require_once __DIR__ . '/../vendor/autoload.php';

use config\Bootstrap;

header('Content-Type: application/json');

$lookup_key = $_GET['lookup_key'] ?? null;
if (!$lookup_key) {
    http_response_code(400);

    echo json_encode(['error' => 'Missing lookup_key']);

    exit;
}

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    $stripePaymentService = Bootstrap::getStripePaymentService();
    $session = $stripePaymentService->createSubscriptionSession($lookup_key);

    echo json_encode(['id' => $session->id]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}