<?php
declare(strict_types=1);

namespace App\Repository;


use App\Commons\Exceptions\DatabaseException;

interface InvoiceRepositoryInterface
{
    /**
     * Obtiene todas las facturas (o transacciones que actúan como facturas/recibos) para un cliente específico.
     *
     * @param string $stripeCustomerId ID del cliente en Stripe.
     * @param int $limit Límite de resultados.
     * @param int $offset Desplazamiento para paginación.
     * @return array<int, array<string, mixed>> Lista de facturas/recibos, cada una como array asociativo.
     *                                           Ej: [['invoice_id_stripe' => 'in_...', 'date' => '...', ...], ...]
     * @throws DatabaseException
     */
    public function findByStripeCustomerId(string $stripeCustomerId, int $limit = 25, int $offset = 0): array;

    /**
     * Obtiene todas las facturas/recibos del sistema, con paginación.
     *
     * @param int $limit Límite de resultados.
     * @param int $offset Desplazamiento para paginación.
     * @return array<int, array<string, mixed>> Lista de todas las facturas/recibos.
     * @throws DatabaseException
     */
    public function findAll(int $limit = 25, int $offset = 0): array;

    /**
     * (Opcional) Cuenta el total de facturas/recibos para un cliente. Útil para paginación.
     * @param string $stripeCustomerId
     * @return int
     * @throws DatabaseException
     */
    public function countByStripeCustomerId(string $stripeCustomerId): int;

    /**
     * (Opcional) Cuenta el total de todas las facturas/recibos. Útil para paginación.
     * @return int
     * @throws DatabaseException
     */
    public function countAll(): int;
}