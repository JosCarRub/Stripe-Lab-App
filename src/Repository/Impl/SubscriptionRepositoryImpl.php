<?php

declare(strict_types=1);

namespace App\Repository\Impl;

use App\Commons\Entities\SubscriptionsModel;
use App\Commons\Enums\SubscriptionStatusEnum;
use App\Commons\Exceptions\DatabaseException;
use App\Commons\Loggers\DatabaseLogger;
use App\Commons\Loggers\ErrorLogger;
use App\Repository\SubscriptionRepositoryInterface;
use PDO;
use PDOException;
use DateTimeImmutable;

class SubscriptionRepositoryImpl implements SubscriptionRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function save(SubscriptionsModel $subscription): void
    {
        // Formatear fechas para la base de datos
        $currentPeriodStartDb = $subscription->getCurrentPeriodStart() ? $subscription->getCurrentPeriodStart()->format('Y-m-d H:i:s') : null;
        $currentPeriodEndDb = $subscription->getCurrentPeriodEnd() ? $subscription->getCurrentPeriodEnd()->format('Y-m-d H:i:s') : null;
        $canceledAtDb = $subscription->getCanceledAt() ? $subscription->getCanceledAt()->format('Y-m-d H:i:s') : null;
        $endedAtDb = $subscription->getEndedAt() ? $subscription->getEndedAt()->format('Y-m-d H:i:s') : null;
        $createdAtStripeDb = $subscription->getCreatedAtStripe()->format('Y-m-d H:i:s');
        $createdAtLocalDb = $subscription->getCreatedAtLocal()->format('Y-m-d H:i:s');


        $sql = "INSERT INTO StripeSubscriptions (
                    subscription_id, stripe_customer_id, customer_email, status, stripe_price_id,
                    `interval`, current_period_start, current_period_end, cancel_at_period_end,
                    canceled_at, ended_at, latest_transaction_id, created_at_stripe, created_at_local
                ) VALUES (
                    :subscription_id, :stripe_customer_id, :customer_email, :status, :stripe_price_id,
                    :interval, :current_period_start, :current_period_end, :cancel_at_period_end,
                    :canceled_at, :ended_at, :latest_transaction_id, :created_at_stripe, :created_at_local
                )
                ON DUPLICATE KEY UPDATE
                    stripe_customer_id = VALUES(stripe_customer_id),
                    customer_email = VALUES(customer_email),
                    status = VALUES(status),
                    stripe_price_id = VALUES(stripe_price_id),
                    `interval` = VALUES(`interval`),
                    current_period_start = VALUES(current_period_start),
                    current_period_end = VALUES(current_period_end),
                    cancel_at_period_end = VALUES(cancel_at_period_end),
                    canceled_at = VALUES(canceled_at),
                    ended_at = VALUES(ended_at),
                    latest_transaction_id = VALUES(latest_transaction_id)
                    -- created_at_stripe y created_at_local no se actualizan en un UPDATE
                ";

        DatabaseLogger::query($sql, [
            'subscription_id' => $subscription->getSubscriptionId(),
            'status' => $subscription->getStatus()->value
        ]);

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':subscription_id' => $subscription->getSubscriptionId(),
                ':stripe_customer_id' => $subscription->getStripeCustomerId(),
                ':customer_email' => $subscription->getCustomerEmail(),
                ':status' => $subscription->getStatus()->value,
                ':stripe_price_id' => $subscription->getStripePriceId(),
                ':interval' => $subscription->getInterval(),
                ':current_period_start' => $currentPeriodStartDb,
                ':current_period_end' => $currentPeriodEndDb,
                ':cancel_at_period_end' => $subscription->isCancelAtPeriodEnd() ? 1 : 0, // Convertir booleano a int para BD
                ':canceled_at' => $canceledAtDb,
                ':ended_at' => $endedAtDb,
                ':latest_transaction_id' => $subscription->getLatestTransactionId(), // Será int o null
                ':created_at_stripe' => $createdAtStripeDb,
                ':created_at_local' => $createdAtLocalDb
            ]);
        } catch (PDOException $e) {
            DatabaseLogger::error("Error al guardar (upsert) suscripción: " . $e->getMessage(), ['sql' => $sql]);
            throw new DatabaseException("Error al guardar la suscripción: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function findById(string $subscriptionId): ?SubscriptionsModel
    {
        $sql = "SELECT * FROM StripeSubscriptions WHERE subscription_id = :subscription_id";
        DatabaseLogger::query($sql, ['subscription_id' => $subscriptionId]);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':subscription_id', $subscriptionId);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $this->mapRowToSubscriptionModel($row) : null;
        } catch (PDOException $e) {
            DatabaseLogger::error("Error al buscar suscripción por ID: " . $e->getMessage(), ['sql' => $sql]);
            throw new DatabaseException("Error al buscar la suscripción por ID: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function findByStripeCustomerId(string $stripeCustomerId): array
    {
        $sql = "SELECT * FROM StripeSubscriptions WHERE stripe_customer_id = :stripe_customer_id ORDER BY created_at_stripe DESC";
        DatabaseLogger::query($sql, ['stripe_customer_id' => $stripeCustomerId]);
        $subscriptions = [];
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':stripe_customer_id', $stripeCustomerId);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $subscriptions[] = $this->mapRowToSubscriptionModel($row);
            }
        } catch (PDOException $e) {
            DatabaseLogger::error("Error al buscar suscripciones por Customer ID: " . $e->getMessage(), ['sql' => $sql]);
            throw new DatabaseException("Error al buscar suscripciones por Customer ID: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
        return $subscriptions;
    }

    /**
     * Helper para mapear una fila de la BD a un objeto SubscriptionsModel.
     */
    private function mapRowToSubscriptionModel(array $row): SubscriptionsModel
    {
        $statusEnum = SubscriptionStatusEnum::tryFromString($row['status']);
        if ($statusEnum === null) {
            ErrorLogger::log("Estado de suscripción desconocido desde BD.", ['value' => $row['status'], 'id' => $row['subscription_id']], '[WARNING]');
            // Decide un fallback o lanza una excepción. Para este ejemplo, un fallback.
            $statusEnum = SubscriptionStatusEnum::INCOMPLETE; // No ideal, indica un problema de datos
        }

        return new SubscriptionsModel(
            subscriptionId: $row['subscription_id'],
            stripeCustomerId: $row['stripe_customer_id'],
            status: $statusEnum,
            stripePriceId: $row['stripe_price_id'],
            createdAtStripe: new DateTimeImmutable($row['created_at_stripe']),
            customerEmail: $row['customer_email'],
            interval: $row['interval'],
            currentPeriodStart: $row['current_period_start'] ? new DateTimeImmutable($row['current_period_start']) : null,
            currentPeriodEnd: $row['current_period_end'] ? new DateTimeImmutable($row['current_period_end']) : null,
            cancelAtPeriodEnd: (bool)$row['cancel_at_period_end'],
            canceledAt: $row['canceled_at'] ? new DateTimeImmutable($row['canceled_at']) : null,
            endedAt: $row['ended_at'] ? new DateTimeImmutable($row['ended_at']) : null,
            latestTransactionId: $row['latest_transaction_id'] !== null ? (int)$row['latest_transaction_id'] : null,
            createdAtLocal: new DateTimeImmutable($row['created_at_local'])
        );
    }
}