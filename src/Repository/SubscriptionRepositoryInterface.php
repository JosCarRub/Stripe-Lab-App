<?php

declare(strict_types=1);

namespace App\Repository;

use App\Commons\Entities\SubscriptionsModel;
use App\Commons\Exceptions\DatabaseException;

interface SubscriptionRepositoryInterface
{
    /**
     * Guarda una nueva suscripción o actualiza una existente.
     *
     * @param SubscriptionsModel $subscription
     * @return void
     * @throws DatabaseException Si ocurre un error en la base de datos.
     */
    public function save(SubscriptionsModel $subscription): void;

    /**
     * Busca una suscripción por su ID de Stripe (sub_...).
     *
     * @param string $subscriptionId
     * @return SubscriptionsModel|null Null si no se encuentra.
     */
    public function findById(string $subscriptionId): ?SubscriptionsModel;

    /**
     * Encuentra todas las suscripciones para un cliente de Stripe.
     *
     * @param string $stripeCustomerId
     * @return SubscriptionsModel[]
     */
    public function findByStripeCustomerId(string $stripeCustomerId): array;

    public function countByStripeCustomerId(string $stripeCustomerId): int;

    /**
     * Encuentra todas las suscripciones del sistema, con paginación.
     * @param int $limit
     * @param int $offset
     * @return SubscriptionsModel[]
     * @throws DatabaseException
     */
    public function findAll(int $limit = 25, int $offset = 0): array;
    public function countAll(): int;

}