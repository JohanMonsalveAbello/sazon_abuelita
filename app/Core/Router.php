<?php
declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\PermissionMiddleware;

/** Enrutador: asocia método + ruta con middleware y un método de controlador. */
final class Router
{
    /** @var array<int,array{0:string,1:string,2:array,3:array}> */
    private array $rutas = [];

    private const MIDDLEWARE = [
        'auth'  => AuthMiddleware::class,
        'guest' => GuestMiddleware::class,
        'perm'  => PermissionMiddleware::class,
    ];

    public function get(string $ruta, array $accion, array $middleware = []): void
    {
        $this->rutas[] = ['GET', $this->patron($ruta), $accion, $middleware];
    }

    public function post(string $ruta, array $accion, array $middleware = []): void
    {
        $this->rutas[] = ['POST', $this->patron($ruta), $accion, $middleware];
    }

    /** /reservas/{id} => regex donde {x} solo acepta dígitos. */
    private function patron(string $ruta): string
    {
        return '#^' . preg_replace('#\{\w+\}#', '(\d{1,10})', $ruta) . '$#';
    }

    public function dispatch(Request $request): void
    {
        $metodoEncontrado = false;
        foreach ($this->rutas as [$metodo, $patron, $accion, $middleware]) {
            if (!preg_match($patron, $request->path(), $coincidencias)) {
                continue;
            }
            if ($metodo !== $request->method()) {
                $metodoEncontrado = true;
                continue;
            }
            foreach ($middleware as $m) {
                [$nombre, $parametro] = array_pad(explode(':', $m, 2), 2, null);
                $clase = self::MIDDLEWARE[$nombre];
                (new $clase())->handle($request, $parametro);
            }
            [$claseControlador, $metodoControlador] = $accion;
            $parametros = array_map('intval', array_slice($coincidencias, 1));
            (new $claseControlador($request))->{$metodoControlador}(...$parametros);
            return;
        }
        throw new HttpException($metodoEncontrado ? 405 : 404);
    }
}
