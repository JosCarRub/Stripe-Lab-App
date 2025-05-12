<?php

declare(strict_types=1);

namespace App\Commons\Exceptions;

/**
 * Lanzada cuando hay un problema con la configuración de la aplicación
 * (ej. falta una clave API, un directorio no es escribible, etc.).
 */
class ConfigurationException extends ApplicationException
{
}