<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

/** Exige una sesión activa de un usuario activo. */
final class AuthMiddleware
{
    public function handle(Request $request, ?string $parametro = null): void
    {
        if (Auth::user() === null) {
            Response::redirect('/login');
        }
    }
}
