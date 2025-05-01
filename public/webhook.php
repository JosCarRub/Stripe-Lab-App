<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();


$stripeWebhookController = config\Bootstrap::getStripeWebhookController();


$payload = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';


$stripeWebhookController->handleStripeWebhook($payload, $signatureHeader);