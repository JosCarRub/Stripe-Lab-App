<?php

declare(strict_types=1);

namespace App\Commons\Exceptions;

/**
 * Lanzada por errores durante operaciones de base de datos que no son
 * manejados por la extensión PDO directamente o cuando se quiere añadir
 * un contexto específico de la aplicación a un error de base de datos.
 */
class DatabaseException extends ApplicationException
{
}