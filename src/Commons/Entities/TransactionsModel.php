<?php

declare(strict_types=1);

namespace App\Commons\Entities;

use App\Commons\Enums\TransactionTypeEnum;
use App\Commons\Loggers\ErrorLogger;
use DateTimeImmutable;
use DateTimeZone;

class TransactionsModel
{
    private ?int $transactionId = null; // ID local, autoincremental de la BD
    private ?string $stripeCustomerId;
    private ?string $customerEmail;
    private ?string $customerName;
    private TransactionTypeEnum $transactionTypeEnum;
    private ?string $stripePaymentIntentId;
    private ?string $stripeInvoiceId;
    private ?string $stripeSubscriptionId;
    private ?string $stripeChargeId;
    private int $amount; // En centavos
    private string $currency; // ej. "usd"
    private string $status; // ej. "succeeded", "paid"
    private ?string $description;
    private ?string $documentUrl; // URL del recibo o factura hosteada
    private ?string $pdfUrl; // URL del PDF de la factura
    private ?DateTimeImmutable $periodStart;
    private ?DateTimeImmutable $periodEnd;
    private DateTimeImmutable $transactionDateStripe;
    private DateTimeImmutable $createdAtLocal;

    /**
     * Constructor para TransactionsModel.
     * El $transactionId es opcional y usualmente se establece después de la inserción en BD.
     */
    public function __construct(
        TransactionTypeEnum $transactionTypeEnum,
        int $amount,
        string $currency,
        string $status,
        DateTimeImmutable $transactionDateStripe,
        ?string $stripeCustomerId = null,
        ?string $customerEmail = null,
        ?string $customerName = null,
        ?string $stripePaymentIntentId = null,
        ?string $stripeInvoiceId = null,
        ?string $stripeSubscriptionId = null,
        ?string $stripeChargeId = null,
        ?string $description = null,
        ?string $documentUrl = null,
        ?string $pdfUrl = null,
        ?DateTimeImmutable $periodStart = null,
        ?DateTimeImmutable $periodEnd = null,
        ?DateTimeImmutable $createdAtLocal = null,
        ?int $transactionId = null // Para reconstruir desde BD o establecer después de inserción
    ) {
        $this->transactionTypeEnum = $transactionTypeEnum;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->status = $status;
        $this->transactionDateStripe = $transactionDateStripe->setTimezone(new DateTimeZone('UTC'));
        $this->stripeCustomerId = $stripeCustomerId;
        $this->customerEmail = $customerEmail;
        $this->customerName = $customerName;
        $this->stripePaymentIntentId = $stripePaymentIntentId;
        $this->stripeInvoiceId = $stripeInvoiceId;
        $this->stripeSubscriptionId = $stripeSubscriptionId;
        $this->stripeChargeId = $stripeChargeId;
        $this->description = $description;
        $this->documentUrl = $documentUrl;
        $this->pdfUrl = $pdfUrl;
        $this->periodStart = $periodStart ? $periodStart->setTimezone(new DateTimeZone('UTC')) : null;
        $this->periodEnd = $periodEnd ? $periodEnd->setTimezone(new DateTimeZone('UTC')) : null;
        $this->createdAtLocal = ($createdAtLocal ?: new DateTimeImmutable())->setTimezone(new DateTimeZone('UTC'));

        if ($transactionId !== null) {
            $this->transactionId = $transactionId;
        }
    }

    // Getters
    public function getTransactionId(): ?int { return $this->transactionId; }
    public function getStripeCustomerId(): ?string { return $this->stripeCustomerId; }
    public function getCustomerEmail(): ?string { return $this->customerEmail; }
    public function getCustomerName(): ?string { return $this->customerName; }
    public function getTransactionTypeEnum(): TransactionTypeEnum { return $this->transactionTypeEnum; }
    public function getStripePaymentIntentId(): ?string { return $this->stripePaymentIntentId; }
    public function getStripeInvoiceId(): ?string { return $this->stripeInvoiceId; }
    public function getStripeSubscriptionId(): ?string { return $this->stripeSubscriptionId; }
    public function getStripeChargeId(): ?string { return $this->stripeChargeId; }
    public function getAmount(): int { return $this->amount; }
    public function getCurrency(): string { return $this->currency; }
    public function getStatus(): string { return $this->status; }
    public function getDescription(): ?string { return $this->description; }
    public function getDocumentUrl(): ?string { return $this->documentUrl; }
    public function getPdfUrl(): ?string { return $this->pdfUrl; }
    public function getPeriodStart(): ?DateTimeImmutable { return $this->periodStart; }
    public function getPeriodEnd(): ?DateTimeImmutable { return $this->periodEnd; }
    public function getTransactionDateStripe(): DateTimeImmutable { return $this->transactionDateStripe; }
    public function getCreatedAtLocal(): DateTimeImmutable { return $this->createdAtLocal; }

    // Setters
    /**
     * Establece el ID de la transacción, usualmente después de ser insertado en la base de datos.
     */
    public function setTransactionId(int $transactionId): void
    {

        if ($this->transactionId !== null && $this->transactionId !== $transactionId) {

             ErrorLogger::log("Intento de cambiar un transactionId ya establecido.", ['actual' => $this->transactionId, 'nuevo' => $transactionId], '[WARNING]');
        }
        $this->transactionId = $transactionId;
    }
    public function setStripeCustomerId(?string $stripeCustomerId): void { $this->stripeCustomerId = $stripeCustomerId; }
    public function setCustomerEmail(?string $customerEmail): void { $this->customerEmail = $customerEmail; }
    public function setCustomerName(?string $customerName): void { $this->customerName = $customerName; }
    public function setTransactionTypeEnum(TransactionTypeEnum $transactionTypeEnum): void { $this->transactionTypeEnum = $transactionTypeEnum; }
    public function setStripePaymentIntentId(?string $stripePaymentIntentId): void { $this->stripePaymentIntentId = $stripePaymentIntentId; }
    public function setStripeInvoiceId(?string $stripeInvoiceId): void { $this->stripeInvoiceId = $stripeInvoiceId; }
    public function setStripeSubscriptionId(?string $stripeSubscriptionId): void { $this->stripeSubscriptionId = $stripeSubscriptionId; }
    public function setStripeChargeId(?string $stripeChargeId): void { $this->stripeChargeId = $stripeChargeId; }
    public function setAmount(int $amount): void { $this->amount = $amount; }
    public function setCurrency(string $currency): void { $this->currency = $currency; }
    public function setStatus(string $status): void { $this->status = $status; }
    public function setDescription(?string $description): void { $this->description = $description; }
    public function setDocumentUrl(?string $documentUrl): void { $this->documentUrl = $documentUrl; }
    public function setPdfUrl(?string $pdfUrl): void { $this->pdfUrl = $pdfUrl; }
    public function setPeriodStart(?DateTimeImmutable $periodStart): void { $this->periodStart = $periodStart ? $periodStart->setTimezone(new DateTimeZone('UTC')) : null; }
    public function setPeriodEnd(?DateTimeImmutable $periodEnd): void { $this->periodEnd = $periodEnd ? $periodEnd->setTimezone(new DateTimeZone('UTC')) : null; }
    public function setTransactionDateStripe(DateTimeImmutable $transactionDateStripe): void { $this->transactionDateStripe = $transactionDateStripe->setTimezone(new DateTimeZone('UTC'));}

    public static function createDateTimeFromStripeTimestamp(?int $timestamp): ?DateTimeImmutable
    {
        if ($timestamp === null) {
            return null;
        }
        return (new DateTimeImmutable('@' . $timestamp))->setTimezone(new DateTimeZone('UTC'));
    }
}