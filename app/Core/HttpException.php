<?php
declare(strict_types=1);

namespace App\Core;

/** Termina la solicitud con una pantalla de error HTTP (403, 404, 405). */
final class HttpException extends \RuntimeException
{
    public function __construct(public int $estado, string $mensaje = '')
    {
        parent::__construct($mensaje);
    }
}
