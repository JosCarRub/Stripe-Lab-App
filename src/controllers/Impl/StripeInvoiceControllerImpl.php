<?php

namespace App\controllers\Impl;

use App\commons\logger\ErrorLogger;
use App\controllers\StripeInvoiceController;
use App\repositories\InvoiceRepository;

class StripeInvoiceControllerImpl implements StripeInvoiceController
{

    private InvoiceRepository $invoiceRepository;

    public function __construct(InvoiceRepository $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
    }


    /**
     * Obtiene todas las facturas del sistema
     *
     * @param int $limit Límite de facturas a recuperar (opcional)
     * @param int $offset Desplazamiento para paginación (opcional)
     * @return array Lista de todas las facturas
     */
    public function getAllInvoices(): array
    {
        try {
            // Obtener todas las facturas desde el repositorio
            $invoices = $this->invoiceRepository->getAllInvoices();

            return $invoices;
        } catch (\Exception $e) {
            // Registrar el error
            ErrorLogger::errorLog('Error al obtener todas las facturas: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Devolver un array vacío en caso de error
            return [];
        }
    }

    /**
     * Obtiene todas las facturas asociadas a un cliente
     *
     * @param string $customerId ID del cliente en Stripe
     * @return array Array con las facturas del cliente
     */
    public function getCustomerInvoices(string $customerId): array
    {
        try {
            // Obtener las facturas del cliente desde el repositorio
            $invoices = $this->invoiceRepository->getCustomerInvoices($customerId);

            // Si no hay facturas, devolver un array vacío
            if (empty($invoices)) {
                return [];
            }

            return $invoices;
        } catch (\Exception $e) {
            // Registrar el error
            ErrorLogger::errorLog('Error al obtener facturas del cliente: ' . $e->getMessage(), [
                'customer_id' => $customerId,
                'trace' => $e->getTraceAsString()
            ]);

            // Devolver un array vacío en caso de error
            return [];
        }
    }
}