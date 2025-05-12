<?php
declare(strict_types=1);

namespace App\Controller;

use Stripe\Subscription; // Para el tipo de retorno de la cancelación

interface StripeSubscriptionControllerInterface
{
    /**
     * Lista todas las suscripciones del sistema, paginadas.
     * @param int $page
     * @param int $limit
     * @return array Formato: ['data' => [], 'pagination' => [...]]
     */
    public function listAllSubscriptions(int $page = 1, int $limit = 10): array;

    /**
     * Lista las suscripciones para un cliente específico, paginadas.
     * @param string $stripeCustomerId
     * @param int $page
     * @param int $limit
     * @return array Formato: ['data' => [], 'pagination' => [...], 'customer_id' => '...']
     */
    public function listCustomerSubscriptions(string $stripeCustomerId, int $page = 1, int $limit = 10): array;

    /**
     * Obtiene los detalles de una suscripción específica.
     * @param string $subscriptionId
     * @return array|null Null si no se encuentra, o array con datos de la suscripción.
     */
    public function getSubscriptionDetails(string $subscriptionId): ?array;

    /**
     * Gestiona una acción sobre una suscripción (ej. cancelar).
     *
     * @param string $action La acción a realizar ('cancel_now', 'cancel_at_period_end').
     * @param string $subscriptionId El ID de la suscripción de Stripe.
     * @return array Resultado de la operación. Formato: ['success' => bool, 'message' => string, 'subscription' => array (opcional)]
     */
    public function manageSubscriptionAction(string $action, string $subscriptionId): array;
}