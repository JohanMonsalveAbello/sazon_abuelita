<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Request;

/** Todo POST debe traer el token CSRF de la sesión (RS-05). */
final class CsrfMiddleware
{
    public function handle(Request $request): void
    {
        if ($request->method() === 'POST' && !Csrf::valido($request->input('_csrf'))) {
            throw new HttpException(403, 'Tu formulario caducó o no es válido. Recarga la página e inténtalo de nuevo.');
        }
    }
}
