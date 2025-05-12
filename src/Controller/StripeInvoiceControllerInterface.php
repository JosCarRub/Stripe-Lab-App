<?php
declare(strict_types=1);

namespace App\Controller;

interface StripeInvoiceControllerInterface
{
    /**
     * Obtiene todas las facturas/recibos del sistema, paginadas.
     * Devuelve un array que podría incluir los datos y metadatos de paginación.
     *
     * @param int $page Número de página actual (ej. para calcular offset).
     * @param int $limit Número de items por página.
     * @return array Formato: ['data' => [], 'total' => 0, 'page' => 0, 'limit' => 0]
     */
    public function listAllInvoices(int $page = 1, int $limit = 10): array;

    /**
     * Obtiene todas las facturas/recibos para un cliente específico, paginadas.
     *
     * @param string $stripeCustomerId ID del cliente en Stripe.
     * @param int $page Número de página actual.
     * @param int $limit Número de items por página.
     * @return array Formato: ['data' => [], 'total' => 0, 'page' => 0, 'limit' => 0, 'customer_id' => '...']
     */
    public function listCustomerInvoices(string $stripeCustomerId, int $page = 1, int $limit = 10): array;
}