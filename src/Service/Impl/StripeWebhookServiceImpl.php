<?php

declare(strict_types=1);

namespace App\Service\Impl;

use App\Commons\Loggers\UnhandledStripeEventLogger;
use App\Service\StripeWebhookServiceInterface;
use App\Strategy\StripeWebhookStrategyInterface;
use App\Commons\Loggers\EventLogger;
use App\Commons\Loggers\ErrorLogger;
use App\Commons\Loggers\StripePayloadLogger;
use App\Commons\Exceptions\ConfigurationException;
use App\Commons\Exceptions\WebhookProcessingException;
use Stripe\Event as StripeEvent;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookServiceImpl implements StripeWebhookServiceInterface
{
    private string $stripeWebhookSecret;

    /**
     * @var StripeWebhookStrategyInterface[]
     */
    private array $strategies = [];

    /**
     * @param string $stripeWebhookSecret El endpoint secret del webhook de Stripe.
     * @param iterable<StripeWebhookStrategyInterface> $strategies Colección de estrategias disponibles.
     */
    public function __construct(string $stripeWebhookSecret, iterable $strategies)
    {
        if (empty($stripeWebhookSecret)) {
            throw new ConfigurationException("Stripe webhook secret no está configurado.");
        }
        $this->stripeWebhookSecret = $stripeWebhookSecret;

        foreach ($strategies as $strategy) {
            if ($strategy instanceof StripeWebhookStrategyInterface) {
                $this->strategies[] = $strategy;
            } else {
                ErrorLogger::log("StripeWebhookServiceImpl: Se intentó registrar un objeto que no es StripeWebhookStrategyInterface.", [
                    'object_class' => get_class($strategy)
                ], '[CONFIG_ERROR]');
            }
        }
        EventLogger::log("StripeWebhookServiceImpl inicializado con " . count($this->strategies) . " estrategias.");
    }

    public function constructEvent(string $rawPayload, string $signatureHeader): StripeEvent
    {
        EventLogger::log("StripeWebhookService: Intentando construir evento de Stripe.");
        try {
            $event = Webhook::constructEvent(
                $rawPayload,
                $signatureHeader,
                $this->stripeWebhookSecret
            );

            StripePayloadLogger::log($event->type, $event->id, $event->data->object);
            return $event;

        } catch (SignatureVerificationException $e) {

            throw $e;

        } catch (\UnexpectedValueException $e) { // Payload JSON inválido

            throw $e;
        }
    }

    public function processWebhookEvent(StripeEvent $event): void
    {
        EventLogger::log("StripeWebhookService: Buscando estrategia para procesar evento.", [
            'event_id' => $event->id,
            'event_type' => $event->type
        ]);

        foreach ($this->strategies as $strategy) {

            if ($strategy->isApplicable($event)) {

                EventLogger::log("StripeWebhookService: Estrategia encontrada, procesando...", [
                    'event_id' => $event->id,
                    'event_type' => $event->type,
                    'strategy_class' => get_class($strategy)
                ]);

                try {
                    $strategy->process($event);
                    EventLogger::log("StripeWebhookService: Estrategia procesada exitosamente.", [
                        'event_id' => $event->id,
                        'strategy_class' => get_class($strategy)
                    ]);
                    return;

                } catch (\Exception $e) { // Captura cualquier excepción de la estrategia
                    ErrorLogger::exception($e, [
                        'event_id' => $event->id,
                        'event_type' => $event->type,
                        'strategy_class' => get_class($strategy)
                    ]);

                    throw new WebhookProcessingException(
                        "Error durante la ejecución de la estrategia " . get_class($strategy) . ": " . $e->getMessage(),
                        $event->type,
                        $event->id,
                        0,
                        $e
                    );
                }
            }
        }
        UnhandledStripeEventLogger::log($event->type, $event->id, $event->data->object);
        EventLogger::log("StripeWebhookService: No se encontró estrategia aplicable (payload logueado en unhandled_stripe_events.log).", [
            'event_id' => $event->id, 'event_type' => $event->type
        ], '[INFO]');


        throw new WebhookProcessingException(
            "No applicable strategy found for event type: " . $event->type,
            $event->type,
            $event->id

        );
    }
}