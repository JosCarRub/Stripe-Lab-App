<?php

namespace App\controllers\Impl;

use App\commons\logger\ErrorLogger;
use App\commons\logger\EventLogger;
use App\controllers\StripeWebhookController;
use App\services\StripeWebhookService;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookControllerImpl implements StripeWebhookController
{
    private StripeWebhookService $stripeWebhookService;

    public function __construct(StripeWebhookService $stripeWebhookService)
    {

        $this->stripeWebhookService = $stripeWebhookService;
    }

    public function handleStripeWebhook(string $payload, string $signatureHeader): void
    {
        try {
            $event = $this->stripeWebhookService->constructEvent($payload, $signatureHeader);
            $this->stripeWebhookService->manageWebhook($event);

            http_response_code(200);

            $eventMessage = 'Webhook event processed successfully: ';
            $eventContext = ( [
                'event_id' => $event->id,
                'event_type' => $event->type,
            ]);

            EventLogger::eventLog($eventMessage, $eventContext);

        } catch (SignatureVerificationException $e) {

            http_response_code(400);

            $errorMessage = 'Webhook signature verification failed: ';
            $errorDetails = $e->getMessage();

            echo "$errorMessage" . $errorDetails;
            ErrorLogger::errorLog($errorMessage . $errorDetails);

        } catch (\Stripe\Exception\ApiConnectionException $e) {

            http_response_code(500);

            $errorMessage = 'Error connecting with Stripe: ';
            $errorDetails = $e->getMessage();

            echo "$errorMessage" . $errorDetails;
            ErrorLogger::errorLog($errorMessage);

        } catch (\Stripe\Exception\InvalidRequestException $e) {

            http_response_code(400);

            $errorMessage = 'Invalid request to Stripe: ';
            $errorDetails = $e->getMessage();

            echo "$errorMessage" . $errorDetails;
            ErrorLogger::errorLog($errorMessage . $errorDetails);

        } catch (\Exception $e) {

            http_response_code(500);

            $errorMessage = 'An unexpected error occurred: ';
            $errorDetails = $e->getMessage();

            echo "$errorMessage" . $errorDetails;
            ErrorLogger::errorLog($errorMessage . $errorDetails);
        }


    }
}