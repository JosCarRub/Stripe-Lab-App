<?php

declare(strict_types=1);

namespace App\Repository\Impl;

use App\Commons\Entities\TransactionsModel;
use App\Commons\Enums\TransactionTypeEnum;
use App\Commons\Exceptions\DatabaseException;
use App\Commons\Loggers\DatabaseLogger;
use App\Commons\Loggers\ErrorLogger;
use App\Repository\TransactionRepositoryInterface;
use PDO;
use PDOException;
use DateTimeImmutable;
class TransactionRepositoryImpl implements TransactionRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function save(TransactionsModel $transaction): void
    {
        // formateo de fechas para la base de datos
        $periodStartDb = $transaction->getPeriodStart() ? $transaction->getPeriodStart()->format('Y-m-d H:i:s') : null;
        $periodEndDb = $transaction->getPeriodEnd() ? $transaction->getPeriodEnd()->format('Y-m-d H:i:s') : null;
        $transactionDateStripeDb = $transaction->getTransactionDateStripe()->format('Y-m-d H:i:s');
        // createdAtLocal tiene un DEFAULT CURRENT_TIMESTAMP en la BDD

        $createdAtLocalDb = $transaction->getCreatedAtLocal()->format('Y-m-d H:i:s');


        if ($transaction->getTransactionId() === null) { // Nueva transacción -> INSERT
            $sql = "INSERT INTO StripeTransactions (
                        stripe_customer_id, customer_email, customer_name, transaction_type,
                        stripe_payment_intent_id, stripe_invoice_id, stripe_subscription_id, stripe_charge_id,
                        amount, currency, status, description, document_url, pdf_url,
                        period_start, period_end, transaction_date_stripe, created_at_local
                    ) VALUES (
                        :stripe_customer_id, :customer_email, :customer_name, :transaction_type,
                        :stripe_payment_intent_id, :stripe_invoice_id, :stripe_subscription_id, :stripe_charge_id,
                        :amount, :currency, :status, :description, :document_url, :pdf_url,
                        :period_start, :period_end, :transaction_date_stripe, :created_at_local
                    )";

            DatabaseLogger::query($sql, [
                'stripe_customer_id' => $transaction->getStripeCustomerId(),

            ]);

            try {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':stripe_customer_id' => $transaction->getStripeCustomerId(),
                    ':customer_email' => $transaction->getCustomerEmail(),
                    ':customer_name' => $transaction->getCustomerName(),
                    ':transaction_type' => $transaction->getTransactionTypeEnum()->value,
                    ':stripe_payment_intent_id' => $transaction->getStripePaymentIntentId(),
                    ':stripe_invoice_id' => $transaction->getStripeInvoiceId(),
                    ':stripe_subscription_id' => $transaction->getStripeSubscriptionId(),
                    ':stripe_charge_id' => $transaction->getStripeChargeId(),
                    ':amount' => $transaction->getAmount(),
                    ':currency' => $transaction->getCurrency(),
                    ':status' => $transaction->getStatus(),
                    ':description' => $transaction->getDescription(),
                    ':document_url' => $transaction->getDocumentUrl(),
                    ':pdf_url' => $transaction->getPdfUrl(),
                    ':period_start' => $periodStartDb,
                    ':period_end' => $periodEndDb,
                    ':transaction_date_stripe' => $transactionDateStripeDb,
                    ':created_at_local' => $createdAtLocalDb
                ]);
                $lastId = $this->pdo->lastInsertId();
                if ($lastId) {
                    $transaction->setTransactionId((int)$lastId);
                } else {

                    ErrorLogger::log("PdoTransactionRepository: lastInsertId() no devolvió un ID después del INSERT.", [], '[CRITICAL]');
                    throw new DatabaseException("No se pudo obtener el ID de la transacción después de la inserción.");
                }

            } catch (PDOException $e) {

                DatabaseLogger::error("Error al insertar transacción: " . $e->getMessage(), ['sql' => $sql]);
                throw new DatabaseException("Error al guardar la transacción: " . $e->getMessage(), (int)$e->getCode(), $e);
            }

        } else { //  UPDATE

            $sql = "UPDATE StripeTransactions SET
                        stripe_customer_id = :stripe_customer_id, customer_email = :customer_email, customer_name = :customer_name,
                        transaction_type = :transaction_type, stripe_payment_intent_id = :stripe_payment_intent_id,
                        stripe_invoice_id = :stripe_invoice_id, stripe_subscription_id = :stripe_subscription_id,
                        stripe_charge_id = :stripe_charge_id, amount = :amount, currency = :currency, status = :status,
                        description = :description, document_url = :document_url, pdf_url = :pdf_url,
                        period_start = :period_start, period_end = :period_end,
                        transaction_date_stripe = :transaction_date_stripe
                        -- created_at_local no se actualiza usualmente
                    WHERE transaction_id = :transaction_id";
            DatabaseLogger::query($sql, ['transaction_id' => $transaction->getTransactionId() /* ... */]);

            try {

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    ':stripe_customer_id' => $transaction->getStripeCustomerId(),
                    ':customer_email' => $transaction->getCustomerEmail(),
                    ':customer_name' => $transaction->getCustomerName(),
                    ':transaction_type' => $transaction->getTransactionTypeEnum()->value,
                    ':stripe_payment_intent_id' => $transaction->getStripePaymentIntentId(),
                    ':stripe_invoice_id' => $transaction->getStripeInvoiceId(),
                    ':stripe_subscription_id' => $transaction->getStripeSubscriptionId(),
                    ':stripe_charge_id' => $transaction->getStripeChargeId(),
                    ':amount' => $transaction->getAmount(),
                    ':currency' => $transaction->getCurrency(),
                    ':status' => $transaction->getStatus(),
                    ':description' => $transaction->getDescription(),
                    ':document_url' => $transaction->getDocumentUrl(),
                    ':pdf_url' => $transaction->getPdfUrl(),
                    ':period_start' => $periodStartDb,
                    ':period_end' => $periodEndDb,
                    ':transaction_date_stripe' => $transactionDateStripeDb,
                    ':transaction_id' => $transaction->getTransactionId()
                ]);

            } catch (PDOException $e) {

                DatabaseLogger::error("Error al actualizar transacción: " . $e->getMessage(), ['sql' => $sql]);
                throw new DatabaseException("Error al actualizar la transacción: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }
    }

    public function findById(int $transactionId): ?TransactionsModel
    {
        $sql = "SELECT * FROM StripeTransactions WHERE transaction_id = :transaction_id";
        DatabaseLogger::query($sql, ['transaction_id' => $transactionId]);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':transaction_id', $transactionId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $this->mapRowToTransactionModel($row) : null;
        } catch (PDOException $e) {
            DatabaseLogger::error("Error al buscar transacción por ID: " . $e->getMessage(), ['sql' => $sql]);
            throw new DatabaseException("Error al buscar la transacción por ID: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function findByPaymentIntentId(string $paymentIntentId): ?TransactionsModel
    {
        $sql = "SELECT * FROM StripeTransactions WHERE stripe_payment_intent_id = :payment_intent_id";
        DatabaseLogger::query($sql, ['payment_intent_id' => $paymentIntentId]);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':payment_intent_id', $paymentIntentId);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $this->mapRowToTransactionModel($row) : null;
        } catch (PDOException $e) {
            DatabaseLogger::error("Error al buscar transacción por PI ID: " . $e->getMessage(), ['sql' => $sql]);
            throw new DatabaseException("Error al buscar la transacción por PI ID: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function findByInvoiceId(string $invoiceId): ?TransactionsModel
    {
        $sql = "SELECT * FROM StripeTransactions WHERE stripe_invoice_id = :invoice_id";
        DatabaseLogger::query($sql, ['invoice_id' => $invoiceId]);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':invoice_id', $invoiceId);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $this->mapRowToTransactionModel($row) : null;
        } catch (PDOException $e) {
            DatabaseLogger::error("Error al buscar transacción por Invoice ID: " . $e->getMessage(), ['sql' => $sql]);
            throw new DatabaseException("Error al buscar la transacción por Invoice ID: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function findByStripeCustomerId(string $stripeCustomerId): array
    {
        $sql = "SELECT * FROM StripeTransactions WHERE stripe_customer_id = :stripe_customer_id ORDER BY transaction_date_stripe DESC";
        DatabaseLogger::query($sql, ['stripe_customer_id' => $stripeCustomerId]);
        $transactions = [];
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':stripe_customer_id', $stripeCustomerId);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $transactions[] = $this->mapRowToTransactionModel($row);
            }
        } catch (PDOException $e) {
            DatabaseLogger::error("Error al buscar transacciones por Customer ID: " . $e->getMessage(), ['sql' => $sql]);
            throw new DatabaseException("Error al buscar transacciones por Customer ID: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
        return $transactions;
    }

    /**
     * Helper para mapear una fila de la BD a un objeto TransactionsModel.
     */
    private function mapRowToTransactionModel(array $row): TransactionsModel
    {
        $transactionType = TransactionTypeEnum::tryFromString($row['transaction_type']);
        if ($transactionType === null) {
            ErrorLogger::log("Tipo de transacción desconocido desde BD.", ['value' => $row['transaction_type'], 'id' => $row['transaction_id']], '[WARNING]');
            // Decide un fallback o lanza una excepción más específica si es un estado inválido que no debería estar en la BD
            $transactionType = TransactionTypeEnum::ONE_TIME_RECEIPT; // Ejemplo de fallback,
        }

        return new TransactionsModel(
            transactionTypeEnum: $transactionType,
            amount: (int)$row['amount'],
            currency: $row['currency'],
            status: $row['status'],
            transactionDateStripe: new DateTimeImmutable($row['transaction_date_stripe']),
            stripeCustomerId: $row['stripe_customer_id'],
            customerEmail: $row['customer_email'],
            customerName: $row['customer_name'],
            stripePaymentIntentId: $row['stripe_payment_intent_id'],
            stripeInvoiceId: $row['stripe_invoice_id'],
            stripeSubscriptionId: $row['stripe_subscription_id'],
            stripeChargeId: $row['stripe_charge_id'],
            description: $row['description'],
            documentUrl: $row['document_url'],
            pdfUrl: $row['pdf_url'],
            periodStart: $row['period_start'] ? new DateTimeImmutable($row['period_start']) : null,
            periodEnd: $row['period_end'] ? new DateTimeImmutable($row['period_end']) : null,
            createdAtLocal: new DateTimeImmutable($row['created_at_local']),
            transactionId: (int)$row['transaction_id'] // El ID se pasa aquí al reconstruir
        );
    }
}