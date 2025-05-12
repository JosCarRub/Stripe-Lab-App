<?php
declare(strict_types=1);

namespace App\Commons\Entities;

use App\Commons\Enums\SubscriptionStatusEnum;
use DateTimeImmutable;
use DateTimeZone;

class SubscriptionsModel
{
    private string $subscriptionId; // ID de Stripe `sub_...`
    private string $stripeCustomerId;
    private ?string $customerEmail;
    private SubscriptionStatusEnum $status;
    private string $stripePriceId;
    private ?string $interval;
    private ?DateTimeImmutable $currentPeriodStart;
    private ?DateTimeImmutable $currentPeriodEnd;
    private bool $cancelAtPeriodEnd;
    private ?DateTimeImmutable $canceledAt;
    private ?DateTimeImmutable $endedAt;
    private ?int $latestTransactionId = null; // CAMBIADO: ?int, FK a StripeTransactions.transaction_id
    private DateTimeImmutable $createdAtStripe;
    private DateTimeImmutable $createdAtLocal;

    public function __construct(
        string $subscriptionId,
        string $stripeCustomerId,
        SubscriptionStatusEnum $status,
        string $stripePriceId,
        DateTimeImmutable $createdAtStripe,
        ?string $customerEmail = null,
        ?string $interval = null,
        ?DateTimeImmutable $currentPeriodStart = null,
        ?DateTimeImmutable $currentPeriodEnd = null,
        bool $cancelAtPeriodEnd = false,
        ?DateTimeImmutable $canceledAt = null,
        ?DateTimeImmutable $endedAt = null,
        ?int $latestTransactionId = null, // CAMBIADO: ?int
        ?DateTimeImmutable $createdAtLocal = null
    ) {
        $this->subscriptionId = $subscriptionId;
        $this->stripeCustomerId = $stripeCustomerId;
        $this->customerEmail = $customerEmail;
        $this->status = $status;
        $this->stripePriceId = $stripePriceId;
        $this->interval = $interval;
        $this->currentPeriodStart = $currentPeriodStart ? $currentPeriodStart->setTimezone(new DateTimeZone('UTC')) : null;
        $this->currentPeriodEnd = $currentPeriodEnd ? $currentPeriodEnd->setTimezone(new DateTimeZone('UTC')) : null;
        $this->cancelAtPeriodEnd = $cancelAtPeriodEnd;
        $this->canceledAt = $canceledAt ? $canceledAt->setTimezone(new DateTimeZone('UTC')) : null;
        $this->endedAt = $endedAt ? $endedAt->setTimezone(new DateTimeZone('UTC')) : null;
        $this->latestTransactionId = $latestTransactionId; // Asignación directa
        $this->createdAtStripe = $createdAtStripe->setTimezone(new DateTimeZone('UTC'));
        $this->createdAtLocal = ($createdAtLocal ?: new DateTimeImmutable())->setTimezone(new DateTimeZone('UTC'));
    }

    // Getters
    public function getSubscriptionId(): string { return $this->subscriptionId; }
    public function getStripeCustomerId(): string { return $this->stripeCustomerId; }
    public function getCustomerEmail(): ?string { return $this->customerEmail; }
    public function getStatus(): SubscriptionStatusEnum { return $this->status; }
    public function getStripePriceId(): string { return $this->stripePriceId; }
    public function getInterval(): ?string { return $this->interval; }
    public function getCurrentPeriodStart(): ?DateTimeImmutable { return $this->currentPeriodStart; }
    public function getCurrentPeriodEnd(): ?DateTimeImmutable { return $this->currentPeriodEnd; }
    public function isCancelAtPeriodEnd(): bool { return $this->cancelAtPeriodEnd; }
    public function getCanceledAt(): ?DateTimeImmutable { return $this->canceledAt; }
    public function getEndedAt(): ?DateTimeImmutable { return $this->endedAt; }
    public function getLatestTransactionId(): ?int { return $this->latestTransactionId; } // CAMBIADO: Devuelve ?int
    public function getCreatedAtStripe(): DateTimeImmutable { return $this->createdAtStripe; }
    public function getCreatedAtLocal(): DateTimeImmutable { return $this->createdAtLocal; }

    // Setters
    public function setStripeCustomerId(string $stripeCustomerId): void { $this->stripeCustomerId = $stripeCustomerId; }
    public function setCustomerEmail(?string $customerEmail): void { $this->customerEmail = $customerEmail; }
    public function setStatus(SubscriptionStatusEnum $status): void { $this->status = $status; }
    public function setStripePriceId(string $stripePriceId): void { $this->stripePriceId = $stripePriceId; }
    public function setInterval(?string $interval): void { $this->interval = $interval; }
    public function setCurrentPeriodStart(?DateTimeImmutable $currentPeriodStart): void { $this->currentPeriodStart = $currentPeriodStart ? $currentPeriodStart->setTimezone(new DateTimeZone('UTC')) : null; }
    public function setCurrentPeriodEnd(?DateTimeImmutable $currentPeriodEnd): void { $this->currentPeriodEnd = $currentPeriodEnd ? $currentPeriodEnd->setTimezone(new DateTimeZone('UTC')) : null; }
    public function setCancelAtPeriodEnd(bool $cancelAtPeriodEnd): void { $this->cancelAtPeriodEnd = $cancelAtPeriodEnd; }
    public function setCanceledAt(?DateTimeImmutable $canceledAt): void { $this->canceledAt = $canceledAt ? $canceledAt->setTimezone(new DateTimeZone('UTC')) : null; }
    public function setEndedAt(?DateTimeImmutable $endedAt): void { $this->endedAt = $endedAt ? $endedAt->setTimezone(new DateTimeZone('UTC')) : null; }
    public function setLatestTransactionId(?int $latestTransactionId): void { $this->latestTransactionId = $latestTransactionId; } // CAMBIADO: Acepta ?int
    public function setCreatedAtStripe(DateTimeImmutable $createdAtStripe): void { $this->createdAtStripe = $createdAtStripe->setTimezone(new DateTimeZone('UTC')); }

    public static function createDateTimeFromStripeTimestamp(?int $timestamp): ?DateTimeImmutable
    {
        if ($timestamp === null) {
            return null;
        }
        return (new DateTimeImmutable('@' . $timestamp))->setTimezone(new DateTimeZone('UTC'));
    }
}