<?php
declare(strict_types=1);

namespace App\Controller\Impl;

use App\Controller\StripeSubscriptionControllerInterface;
use App\Repository\SubscriptionRepositoryInterface;
use App\Service\StripeSubscriptionManagementServiceInterface; // <--- NUEVO
use App\Commons\Entities\SubscriptionsModel;
use App\Commons\Loggers\ErrorLogger;
use App\Commons\Loggers\EventLogger;
use App\Commons\Exceptions\NotFoundException; // Para capturar del servicio
use Stripe\Exception\ApiErrorException as StripeApiErrorException; // Para capturar del servicio

class StripeSubscriptionControllerImpl implements StripeSubscriptionControllerInterface
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private ?StripeSubscriptionManagementServiceInterface $subscriptionManagementService = null
    ) {
    }

    public function listAllSubscriptions(int $page = 1, int $limit = 10): array
    {
        EventLogger::log(self::class . ": Solicitando todas las suscripciones.", ['page' => $page, 'limit' => $limit]);
        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 10;
        $offset = ($page - 1) * $limit;

        try {
            $subscriptions = $this->subscriptionRepository->findAll($limit, $offset);
            $totalSubscriptions = $this->subscriptionRepository->countAll();

            return [
                'data' => array_map([$this, 'formatSubscriptionForDisplay'], $subscriptions),
                'pagination' => [
                    'total_items' => $totalSubscriptions,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'total_pages' => (int)ceil($totalSubscriptions / $limit),
                ]
            ];
        } catch (\Throwable $e) {

            ErrorLogger::exception($e, ['page' => $page, 'limit' => $limit]);

            return ['error' => 'Error al obtener suscripciones.', 'data' => [], 'pagination' => []];
        }
    }

    public function listCustomerSubscriptions(string $stripeCustomerId, int $page = 1, int $limit = 10): array
    {
        EventLogger::log(self::class . ": Solicitando suscripciones para cliente.", [
            'stripe_customer_id' => $stripeCustomerId, 'page' => $page, 'limit' => $limit
        ]);

        if (empty($stripeCustomerId)) {

            return ['error' => 'ID de cliente no proporcionado.', 'data' => [], 'pagination' => []];
        }

        if ($page < 1) $page = 1;

        if ($limit < 1) $limit = 10;

        $offset = ($page - 1) * $limit;

        try {
            $subscriptions = $this->subscriptionRepository->findByStripeCustomerId($stripeCustomerId, $limit, $offset);
            $totalSubscriptions = $this->subscriptionRepository->countByStripeCustomerId($stripeCustomerId);

            return [
                'data' => array_map([$this, 'formatSubscriptionForDisplay'], $subscriptions),
                'customer_id' => $stripeCustomerId,
                'pagination' => [
                    'total_items' => $totalSubscriptions,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'total_pages' => (int)ceil($totalSubscriptions / $limit),
                ]
            ];
        } catch (\Throwable $e) {

            ErrorLogger::exception($e, ['stripe_customer_id' => $stripeCustomerId, 'page' => $page, 'limit' => $limit]);

            return ['error' => 'Error al obtener suscripciones del cliente.', 'data' => [], 'pagination' => []];
        }
    }

    public function getSubscriptionDetails(string $subscriptionId): ?array
    {
        EventLogger::log(self::class . ": Solicitando detalles de suscripción.", ['subscription_id' => $subscriptionId]);
        if (empty($subscriptionId)) {

            return null;
        }
        try {
            $subscription = $this->subscriptionRepository->findById($subscriptionId);

            return $subscription ? $this->formatSubscriptionForDisplay($subscription) : null;

        } catch (\Throwable $e) {

            ErrorLogger::exception($e, ['subscription_id' => $subscriptionId]);

            return null;
        }
    }


    /**
     * {@inheritdoc}
     */
    public function manageSubscriptionAction(string $action, string $subscriptionId): array
    {
        EventLogger::log(self::class . ": Solicitando acción de gestión de suscripción.", [
            'action' => $action, 'subscription_id' => $subscriptionId
        ]);

        if (!$this->subscriptionManagementService) {
            ErrorLogger::log(self::class . ": StripeSubscriptionManagementService no está disponible/inyectado.", [], '[ERROR_CONFIG]');
            return ['success' => false, 'message' => 'Servicio de gestión de suscripciones no configurado.'];
        }

        try {

            $stripeSubscriptionObject = null;
            $message = '';

            switch ($action) {
                case 'cancel_now':
                    $stripeSubscriptionObject = $this->subscriptionManagementService->cancelSubscriptionNow($subscriptionId);
                    $message = "Suscripción {$subscriptionId} cancelada inmediatamente con éxito.";
                    EventLogger::log(self::class . ": " . $message, ['subscription_id' => $subscriptionId]);
                    break;
                case 'cancel_at_period_end':
                    $stripeSubscriptionObject = $this->subscriptionManagementService->cancelSubscriptionAtPeriodEnd($subscriptionId);
                    $message = "Suscripción {$subscriptionId} programada para cancelación al final del periodo.";
                    EventLogger::log(self::class . ": " . $message, ['subscription_id' => $subscriptionId]);
                    break;
                // case 'reactivate': // para implementar reactivación
                //     $stripeSubscriptionObject = $this->subscriptionManagementService->reactivateSubscription($subscriptionId);
                //     $message = "Solicitud de reactivación para {$subscriptionId} enviada.";
                //     break;
                default:
                    ErrorLogger::log(self::class . ": Acción de gestión de suscripción no válida.", ['action' => $action], '[BAD_REQUEST]');
                    return ['success' => false, 'message' => "Acción '{$action}' no reconocida."];
            }

            // El estado en nuestra BD se actualiza vía webhook.
            // se devuelve el estado actual de Stripe como referencia.
            return [
                'success' => true,
                'message' => $message,
                'stripe_subscription_details' => [ // datos del objeto devuelto por la API de Stripe
                    'id' => $stripeSubscriptionObject->id,
                    'status' => $stripeSubscriptionObject->status,
                    'cancel_at_period_end' => $stripeSubscriptionObject->cancel_at_period_end,
                    'canceled_at' => $stripeSubscriptionObject->canceled_at,
                    'current_period_end' => $stripeSubscriptionObject->current_period_end,
                ]
            ];

        } catch (NotFoundException $e) {
            ErrorLogger::exception($e, ['action' => $action, 'subscription_id' => $subscriptionId]);
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (StripeApiErrorException $e) {
            ErrorLogger::exception($e, ['action' => $action, 'subscription_id' => $subscriptionId]);
            return ['success' => false, 'message' => "Error de Stripe: " . $e->getMessage()];
        } catch (\Throwable $e) {
            ErrorLogger::exception($e, ['action' => $action, 'subscription_id' => $subscriptionId]);
            return ['success' => false, 'message' => "Error inesperado al gestionar la suscripción."];
        }
    }

    private function formatSubscriptionForDisplay(SubscriptionsModel $subscription): array
    {

        $dateFormat = 'd/m/Y H:i';
        return [
            'subscription_id' => $subscription->getSubscriptionId(),
            'stripe_customer_id' => $subscription->getStripeCustomerId(),
            'customer_email' => $subscription->getCustomerEmail(),
            'status' => $subscription->getStatus()->value,
            'status_display' => ucfirst(str_replace('_', ' ', $subscription->getStatus()->value)),
            'stripe_price_id' => $subscription->getStripePriceId(),
            'interval' => $subscription->getInterval(),
            'current_period_start_display' => $subscription->getCurrentPeriodStart() ? $subscription->getCurrentPeriodStart()->format($dateFormat) : 'N/A',
            'current_period_end_display' => $subscription->getCurrentPeriodEnd() ? $subscription->getCurrentPeriodEnd()->format($dateFormat) : 'N/A',
            'cancel_at_period_end' => $subscription->isCancelAtPeriodEnd(),
            'canceled_at_display' => $subscription->getCanceledAt() ? $subscription->getCanceledAt()->format($dateFormat) : null,
            'ended_at_display' => $subscription->getEndedAt() ? $subscription->getEndedAt()->format($dateFormat) : null,
            'latest_transaction_id' => $subscription->getLatestTransactionId(),
            'created_at_stripe_display' => $subscription->getCreatedAtStripe()->format($dateFormat),
            'plan_name_placeholder' => 'Plan ' . ucfirst($subscription->getInterval() ?? 'Desconocido'),
        ];
    }
}