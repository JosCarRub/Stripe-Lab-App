<?php
declare(strict_types=1);

namespace App\Factories;

use App\Commons\DTOs\ChargeDTO;
use App\Commons\DTOs\InvoiceDTO;
use App\Commons\DTOs\PaymentIntentDTO;
use App\Commons\DTOs\CheckoutSessionCompletedDTO;
use App\Commons\Entities\TransactionsModel;
use App\Commons\Enums\TransactionTypeEnum;
use App\Commons\Loggers\EventLogger;
use App\Commons\Loggers\ErrorLogger;

class TransactionModelFactory
{
    public function createFromInvoiceDTO(InvoiceDTO $invoiceDTO): TransactionsModel
    {
        EventLogger::log("TransactionModelFactory: Preparando TransactionsModel desde InvoiceDTO.", ['invoice_id' => $invoiceDTO->id]);

        $transactionDate = TransactionsModel::createDateTimeFromStripeTimestamp($invoiceDTO->createdTimestamp);
        if ($transactionDate === null) {
            ErrorLogger::log("TransactionModelFactory: Timestamp 'created' nulo para InvoiceDTO.", ['invoice_id' => $invoiceDTO->id], '[ERROR]');
            $transactionDate = new \DateTimeImmutable(); // Fallback
        }

        $periodStart = TransactionsModel::createDateTimeFromStripeTimestamp($invoiceDTO->periodStartTimestamp);
        $periodEnd = TransactionsModel::createDateTimeFromStripeTimestamp($invoiceDTO->periodEndTimestamp);
        $customerName = $invoiceDTO->customerName;

        $model = new TransactionsModel(
            transactionTypeEnum: TransactionTypeEnum::SUBSCRIPTION_INVOICE,
            amount: $invoiceDTO->amountPaid,
            currency: $invoiceDTO->currency,
            status: $invoiceDTO->status,
            transactionDateStripe: $transactionDate,
            stripeCustomerId: $invoiceDTO->customerId,
            customerEmail: $invoiceDTO->customerEmail,
            customerName: $customerName,
            stripePaymentIntentId: $invoiceDTO->paymentIntentId,
            stripeInvoiceId: $invoiceDTO->id,
            stripeSubscriptionId: $invoiceDTO->subscriptionId,
            stripeChargeId: $invoiceDTO->chargeId,
            description: "Factura de suscripción " . ($invoiceDTO->subscriptionId ?? $invoiceDTO->id),
            documentUrl: $invoiceDTO->hostedInvoiceUrl,
            pdfUrl: $invoiceDTO->invoicePdf,
            periodStart: $periodStart,
            periodEnd: $periodEnd
        );
        EventLogger::log("TransactionModelFactory: TransactionsModel (sin ID local aún) preparado desde InvoiceDTO.", ['invoice_id' => $invoiceDTO->id]);
        return $model;
    }

    public function createFromPaymentIntentDTO(PaymentIntentDTO $piDTO, ?ChargeDTO $chargeDTO = null): TransactionsModel
    {
        EventLogger::log("TransactionModelFactory: Preparando TransactionsModel desde PaymentIntentDTO.", ['pi_id' => $piDTO->id]);

        $transactionDate = TransactionsModel::createDateTimeFromStripeTimestamp($piDTO->createdTimestamp);
        if ($transactionDate === null) {
            ErrorLogger::log("TransactionModelFactory: Timestamp 'created' nulo para PaymentIntentDTO.", ['pi_id' => $piDTO->id], '[ERROR]');
            $transactionDate = new \DateTimeImmutable(); // Fallback
        }

        $receiptUrl = $chargeDTO?->receiptUrl ?? null;
        $customerName = $chargeDTO?->billingDetailsName ?? null;
        $customerEmail = $chargeDTO?->billingDetailsEmail ?? $piDTO->receiptEmail;

        $model = new TransactionsModel(
            transactionTypeEnum: TransactionTypeEnum::ONE_TIME_RECEIPT,
            amount: $piDTO->amountReceived,
            currency: $piDTO->currency,
            status: $piDTO->status,
            transactionDateStripe: $transactionDate,
            stripeCustomerId: $piDTO->customerId,
            customerEmail: $customerEmail,
            customerName: $customerName,
            stripePaymentIntentId: $piDTO->id,
            stripeInvoiceId: $piDTO->invoiceId,
            stripeSubscriptionId: null,
            stripeChargeId: $piDTO->latestChargeId ?? $chargeDTO?->id,
            description: $piDTO->description ?? "Pago único",
            documentUrl: $receiptUrl,
            pdfUrl: null,
            periodStart: null,
            periodEnd: null
        );
        EventLogger::log("TransactionModelFactory: TransactionsModel (sin ID local aún) preparado desde PaymentIntentDTO.", ['pi_id' => $piDTO->id]);
        return $model;
    }

    public function createFromCheckoutSessionDTO(CheckoutSessionCompletedDTO $csDTO): ?TransactionsModel
    {
        if ($csDTO->mode !== 'payment' || $csDTO->paymentStatus !== 'paid') {
            EventLogger::log("TransactionModelFactory: CS DTO no es 'payment' o 'paid'. No se crea transacción.", [
                'cs_id' => $csDTO->id, 'mode' => $csDTO->mode, 'payment_status' => $csDTO->paymentStatus
            ]);
            return null;
        }

        EventLogger::log("TransactionModelFactory: Preparando TransactionsModel desde CheckoutSessionCompletedDTO.", ['cs_id' => $csDTO->id]);
        $transactionDate = TransactionsModel::createDateTimeFromStripeTimestamp($csDTO->createdTimestamp);
        if ($transactionDate === null) {
            ErrorLogger::log("TransactionModelFactory: Timestamp 'created' nulo para CS DTO.", ['cs_id' => $csDTO->id], '[ERROR]');
            $transactionDate = new \DateTimeImmutable(); // Fallback
        }

        $model = new TransactionsModel(
            transactionTypeEnum: TransactionTypeEnum::ONE_TIME_RECEIPT,
            amount: $csDTO->amountTotal ?? 0,
            currency: $csDTO->currency ?? 'eur', // Deberían estar presentes
            status: $csDTO->paymentStatus, // "paid"
            transactionDateStripe: $transactionDate,
            stripeCustomerId: $csDTO->customerId,
            customerEmail: $csDTO->customerEmail,
            customerName: $csDTO->customerName,
            stripePaymentIntentId: $csDTO->paymentIntentId,
            stripeInvoiceId: null,
            stripeSubscriptionId: null,
            stripeChargeId: null, // Se necesita del evento de cargo
            description: "Pago vía Checkout Session " . $csDTO->id,
            documentUrl: null, // Se necesita de evento de cargo o del PaymentIntent
            pdfUrl: null,
            periodStart: null,
            periodEnd: null
        );
        EventLogger::log("TransactionModelFactory: TransactionsModel (sin ID local aún) preparado desde CS DTO.", ['cs_id' => $csDTO->id]);
        return $model;
    }
}