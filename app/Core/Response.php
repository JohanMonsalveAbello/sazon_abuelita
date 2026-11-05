<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    /** Redirección interna (la ruta siempre se construye dentro de la aplicación). */
    public static function redirect(string $ruta): never
    {
        header('Location: ' . url($ruta), true, 303);
        exit;
    }

    public static function html(string $contenido, int $estado = 200): never
    {
        http_response_code($estado);
        header('Content-Type: text/html; charset=UTF-8');
        echo $contenido;
        exit;
    }
}
