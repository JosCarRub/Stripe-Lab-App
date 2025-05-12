<?php
declare(strict_types=1);

namespace App\Controller\Impl;

use App\Controller\StripeInvoiceControllerInterface;
use App\Repository\InvoiceRepositoryInterface;
use App\Commons\Loggers\ErrorLogger;
use App\Commons\Loggers\EventLogger;

class StripeInvoiceControllerImpl implements StripeInvoiceControllerInterface
{
    public function __construct(private InvoiceRepositoryInterface $invoiceRepository)
    {
    }

    public function listAllInvoices(int $page = 1, int $limit = 10): array
    {
        EventLogger::log(self::class . ": Solicitando todas las facturas.", ['page' => $page, 'limit' => $limit]);
        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 10;
        $offset = ($page - 1) * $limit;

        try {
            $invoicesData = $this->invoiceRepository->findAll($limit, $offset);
            $totalInvoices = $this->invoiceRepository->countAll();

            return [
                'data' => $this->formatInvoicesForDisplay($invoicesData),
                'pagination' => [
                    'total_items' => $totalInvoices,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'total_pages' => ceil($totalInvoices / $limit),
                ]
            ];
        } catch (\App\Commons\Exceptions\DatabaseException $e) {
            ErrorLogger::exception($e, ['page' => $page, 'limit' => $limit]);
            // En un API real, devolverías un error JSON con código 500.
            // Por ahora, devolvemos un array vacío con error.
            return ['error' => 'Error al obtener facturas.', 'data' => [], 'pagination' => []];
        } catch (\Throwable $e) { // Captura genérica
            ErrorLogger::exception($e, ['page' => $page, 'limit' => $limit]);
            return ['error' => 'Error inesperado.', 'data' => [], 'pagination' => []];
        }
    }

    public function listCustomerInvoices(string $stripeCustomerId, int $page = 1, int $limit = 10): array
    {
        EventLogger::log(self::class . ": Solicitando facturas para cliente.", [
            'stripe_customer_id' => $stripeCustomerId, 'page' => $page, 'limit' => $limit
        ]);
        if (empty($stripeCustomerId)) {
            return ['error' => 'ID de cliente no proporcionado.', 'data' => [], 'pagination' => []];
        }
        if ($page < 1) $page = 1;
        if ($limit < 1) $limit = 10;
        $offset = ($page - 1) * $limit;

        try {
            $invoicesData = $this->invoiceRepository->findByStripeCustomerId($stripeCustomerId, $limit, $offset);
            $totalInvoices = $this->invoiceRepository->countByStripeCustomerId($stripeCustomerId);

            return [
                'data' => $this->formatInvoicesForDisplay($invoicesData),
                'customer_id' => $stripeCustomerId,
                'pagination' => [
                    'total_items' => $totalInvoices,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'total_pages' => ceil($totalInvoices / $limit),
                ]
            ];
        } catch (\App\Commons\Exceptions\DatabaseException $e) {

            ErrorLogger::exception($e, ['stripe_customer_id' => $stripeCustomerId, 'page' => $page, 'limit' => $limit]);
            return ['error' => 'Error al obtener facturas del cliente.', 'data' => [], 'pagination' => []];

        } catch (\Throwable $e) {

            ErrorLogger::exception($e, ['stripe_customer_id' => $stripeCustomerId, 'page' => $page, 'limit' => $limit]);
            return ['error' => 'Error inesperado.', 'data' => [], 'pagination' => []];
        }
    }

    /**
     * Formatea los datos crudos de las facturas para una mejor visualización.
     * (Ej. formatear fechas, montos, etc.)
     * @param array<int, array<string, mixed>> $invoicesData
     * @return array<int, array<string, mixed>>
     */
    private function formatInvoicesForDisplay(array $invoicesData): array
    {
        $formatted = [];
        foreach ($invoicesData as $invoice) {
            $formattedInvoice = $invoice; // Copiar todos los campos
            // Formatear monto (de centavos a formato legible)
            if (isset($invoice['amount']) && isset($invoice['currency'])) {
                $formattedInvoice['amount_display'] = number_format($invoice['amount'] / 100, 2, ',', '.') . ' ' . strtoupper($invoice['currency']);
            }
            // Formatear fechas
            if (isset($invoice['transaction_date_stripe'])) {
                try {
                    $formattedInvoice['date_display'] = (new \DateTimeImmutable($invoice['transaction_date_stripe']))->format('d/m/Y H:i');
                } catch (\Exception $e) {
                    $formattedInvoice['date_display'] = 'Fecha inválida';
                }
            }

            $formattedInvoice['view_document_url'] = $invoice['pdf_url'] ?? $invoice['document_url'] ?? '#';

            $formatted[] = $formattedInvoice;
        }
        return $formatted;
    }
}