<?php
declare(strict_types=1);

namespace App\Core;

use App\Middleware\CsrfMiddleware;

/** Orquesta el ciclo de una solicitud: sesión → CSRF → enrutador → respuesta. */
final class Application
{
    public function run(): void
    {
        $this->cabecerasDeSeguridad();
        Session::start();

        $request = Request::current();
        $router = new Router();
        $r = $router;
        require BASE_PATH . '/config/routes.php';

        try {
            (new CsrfMiddleware())->handle($request);
            $router->dispatch($request);
        } catch (ValidationException $e) {
            Session::flash('errors', $e->errores);
            Session::flash('old', $e->valores);
            Session::flash('error', 'Revisa los campos marcados.');
            Response::redirect($e->destino);
        }
    }

    private function cabecerasDeSeguridad(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; "
            . "style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; "
            . "img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
    }
}
