<?php

declare(strict_types=1);

namespace App\Repository;

use App\Commons\Entities\TransactionsModel;
use App\Commons\Exceptions\DatabaseException;

interface TransactionRepositoryInterface
{
    /**
     * Guarda una nueva transacción o actualiza una existente.
     * Si es una nueva transacción (sin ID), se le asignará un ID después de la inserción.
     *
     * @param TransactionsModel $transaction
     * @return void
     * @throws DatabaseException Si ocurre un error en la base de datos.
     */
    public function save(TransactionsModel $transaction): void;

    /**
     * Busca una transacción por su ID interno.
     *
     * @param int $transactionId
     * @return TransactionsModel|null Null si no se encuentra.
     */
    public function findById(int $transactionId): ?TransactionsModel;

    /**
     * Busca una transacción por su Stripe Payment Intent ID.
     *
     * @param string $paymentIntentId
     * @return TransactionsModel|null
     */
    public function findByPaymentIntentId(string $paymentIntentId): ?TransactionsModel;

    /**
     * Busca una transacción por su Stripe Invoice ID.
     *
     * @param string $invoiceId
     * @return TransactionsModel|null
     */
    public function findByInvoiceId(string $invoiceId): ?TransactionsModel;

    /**
     *  Encuentra todas las transacciones para un cliente de Stripe.
     *
     * @param string $stripeCustomerId
     * @return TransactionsModel[]
     */
    public function findByStripeCustomerId(string $stripeCustomerId): array;

}