<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

/** Quien ya inició sesión no necesita login ni registro. */
final class GuestMiddleware
{
    public function handle(Request $request, ?string $parametro = null): void
    {
        if (Auth::user() !== null) {
            Response::redirect('/panel');
        }
    }
}
