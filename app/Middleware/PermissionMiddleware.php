<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

/** Autorización en servidor por módulo y acción: 'perm:reservas.aprobar' (RF-02, RS-04). */
final class PermissionMiddleware
{
    public function handle(Request $request, ?string $permiso = null): void
    {
        if (Auth::user() === null) {
            Response::redirect('/login');
        }
        if ($permiso === null || !Auth::can($permiso)) {
            throw new HttpException(403);
        }
    }
}
