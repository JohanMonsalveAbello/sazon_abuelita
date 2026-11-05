<?php
declare(strict_types=1);

namespace App\Core;

final class ValidationException extends \RuntimeException
{
    public function __construct(public array $errores, public array $valores, public string $destino)
    {
        parent::__construct('Datos inválidos');
    }
}
