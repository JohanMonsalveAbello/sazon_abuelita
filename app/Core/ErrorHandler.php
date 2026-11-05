<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;

/** Toda excepción termina en una pantalla amable; el detalle técnico va solo al log (RS-08). */
final class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler(static function (int $nivel, string $mensaje, string $archivo, int $linea): bool {
            if (!(error_reporting() & $nivel)) {
                return false;
            }
            throw new \ErrorException($mensaje, 0, $nivel, $archivo, $linea);
        });
        set_exception_handler([self::class, 'manejar']);
    }

    public static function manejar(Throwable $e): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $estado = $e instanceof HttpException ? $e->estado : 500;
        if ($estado >= 500) {
            self::log($e);
        }
        $detalle = $estado >= 500 && config('app.debug') ? $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine() : null;
        $mensaje = $e instanceof HttpException ? $e->getMessage() : '';
        $vista = match ($estado) {
            403      => 'errors/403',
            404, 405 => 'errors/404',
            default  => 'errors/500',
        };
        http_response_code($estado);
        header('Content-Type: text/html; charset=UTF-8');
        try {
            echo View::render($vista, ['estado' => $estado, 'mensaje' => $mensaje, 'detalle' => $detalle], 'error');
        } catch (Throwable $interno) {
            self::log($interno);
            echo '<!doctype html><meta charset="utf-8"><title>Error</title><h1>Ocurrió un error</h1><p>Intenta de nuevo en unos minutos.</p>';
        }
    }

    private static function log(Throwable $e): void
    {
        $linea = sprintf(
            "[%s] %s: %s en %s:%d\n",
            date('c'),
            get_class($e),
            str_replace(["\r", "\n"], ' ', $e->getMessage()),
            $e->getFile(),
            $e->getLine()
        );
        @file_put_contents(BASE_PATH . '/storage/logs/app.log', $linea, FILE_APPEND | LOCK_EX);
    }
}
