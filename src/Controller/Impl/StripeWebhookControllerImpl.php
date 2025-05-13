<?php

declare(strict_types=1);

namespace App\Controller\Impl;

use App\Controller\StripeWebhookControllerInterface;
use App\Service\StripeWebhookServiceInterface;
use App\Commons\Loggers\ErrorLogger;
use App\Commons\Loggers\EventLogger;
use App\Commons\Exceptions\WebhookProcessingException;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookControllerImpl implements StripeWebhookControllerInterface
{
    public function __construct(private StripeWebhookServiceInterface $stripeWebhookService)
    {
    }

    public function handleStripeWebhook(string $rawPayload, ?string $signatureHeader): void
    {
        EventLogger::log("StripeWebhookController: Webhook recibido.");

        if ($signatureHeader === null) {

            ErrorLogger::log("StripeWebhookController: Falta la cabecera Stripe-Signature.", [], '[BAD_REQUEST]');
            http_response_code(400);

            echo "Missing Stripe-Signature header.";
            return;
        }

        try {

            $event = $this->stripeWebhookService->constructEvent($rawPayload, $signatureHeader);
            EventLogger::log("StripeWebhookController: Evento de Stripe construido y verificado.", [
                'event_id' => $event->id,
                'event_type' => $event->type
            ]);

            $this->stripeWebhookService->processWebhookEvent($event);

            EventLogger::log("StripeWebhookController: Webhook procesado exitosamente.", ['event_id' => $event->id]);
            http_response_code(200);

            echo "Webhook received successfully.";

        } catch (SignatureVerificationException $e) {

            ErrorLogger::exception($e, ['header' => $signatureHeader], '[SIGNATURE_VERIFICATION_FAILED]');
            http_response_code(400);

            echo "Webhook signature verification failed.";

        } catch (\UnexpectedValueException $e) {

            ErrorLogger::exception($e, [], '[INVALID_JSON_PAYLOAD]');
            http_response_code(400);

            echo "Invalid JSON payload.";

        } catch (WebhookProcessingException $e) {

            ErrorLogger::exception($e);

            if (str_contains($e->getMessage(), "No applicable strategy found")) {

                EventLogger::log("StripeWebhookController: No se encontró estrategia aplicable.", ['event_type' => $e->webhookEventType, 'event_id' => $e->webhookEventId], '[INFO]');
                http_response_code(200);

                echo "Webhook event type not handled by any strategy.";

            } else {
                http_response_code(500);

                echo "Error processing webhook event.";
            }
        } catch (\Throwable $e) {

            ErrorLogger::exception($e, [], '[UNEXPECTED_WEBHOOK_ERROR]');
            http_response_code(500);

            echo "An unexpected error occurred.";
        }
    }
}