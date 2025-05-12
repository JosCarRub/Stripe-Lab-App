<?php
declare(strict_types=1);

namespace App\Repository\Impl;

use App\Repository\InvoiceRepositoryInterface;
use App\Commons\Exceptions\DatabaseException;
use App\Commons\Loggers\DatabaseLogger;
use App\Commons\Loggers\ErrorLogger;
use PDO;
use PDOException;

class InvoiceRepositoryImpl implements InvoiceRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    // Campos que queremos seleccionar para la lista de facturas/recibos
    private function getSelectFields(): string
    {
        return "transaction_id, stripe_invoice_id, stripe_payment_intent_id, stripe_customer_id, 
                customer_name, customer_email, transaction_type, amount, currency, status, 
                description, document_url, pdf_url, transaction_date_stripe, created_at_local";
    }

    public function findByStripeCustomerId(string $stripeCustomerId, int $limit = 25, int $offset = 0): array
    {
        $fields = $this->getSelectFields();
        $sql = "SELECT {$fields} FROM StripeTransactions 
                WHERE stripe_customer_id = :stripe_customer_id 
                ORDER BY transaction_date_stripe DESC
                LIMIT :limit OFFSET :offset";

        DatabaseLogger::query($sql, [
            'stripe_customer_id' => $stripeCustomerId,
            'limit' => $limit,
            'offset' => $offset
        ]);

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':stripe_customer_id', $stripeCustomerId);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            DatabaseLogger::error("Error al buscar facturas por Customer ID: " . $e->getMessage(), ['sql' => $sql]);
            throw new DatabaseException("Error al buscar facturas por Customer ID: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function findAll(int $limit = 25, int $offset = 0): array
    {
        $fields = $this->getSelectFields();
        $sql = "SELECT {$fields} FROM StripeTransactions 
                ORDER BY transaction_date_stripe DESC
                LIMIT :limit OFFSET :offset";

        DatabaseLogger::query($sql, ['limit' => $limit, 'offset' => $offset]);

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            DatabaseLogger::error("Error al buscar todas las facturas: " . $e->getMessage(), ['sql' => $sql]);
            throw new DatabaseException("Error al buscar todas las facturas: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function countByStripeCustomerId(string $stripeCustomerId): int
    {
        $sql = "SELECT COUNT(*) FROM StripeTransactions WHERE stripe_customer_id = :stripe_customer_id";
        DatabaseLogger::query($sql, ['stripe_customer_id' => $stripeCustomerId]);

        try {

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':stripe_customer_id', $stripeCustomerId);
            $stmt->execute();

            return (int)$stmt->fetchColumn();

        } catch (PDOException $e) {

            DatabaseLogger::error("Error al contar facturas por Customer ID: " . $e->getMessage(), ['sql' => $sql]);
            throw new DatabaseException("Error al contar facturas por Customer ID: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) FROM StripeTransactions";

        DatabaseLogger::query($sql);
        try {

            $stmt = $this->pdo->query($sql);
            return (int)$stmt->fetchColumn();

        } catch (PDOException $e) {

            DatabaseLogger::error("Error al contar todas las facturas: " . $e->getMessage(), ['sql' => $sql]);
            throw new DatabaseException("Error al contar todas las facturas: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }
}