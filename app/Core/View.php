<?php
declare(strict_types=1);

namespace App\Core;

/** Motor de vistas: PHP puro con layouts. */
final class View
{
    public static function render(string $vista, array $datos = [], ?string $layout = 'panel'): string
    {
        // Consume errores y valores previos una sola vez: no deben reaparecer en otra pantalla.
        errores();
        old('');
        $contenido = self::fetch($vista, $datos);
        if ($layout === null) {
            return $contenido;
        }
        return self::fetch('layouts/' . $layout, $datos + ['contenido' => $contenido]);
    }

    public static function fetch(string $__vista, array $__datos = []): string
    {
        $__ruta = BASE_PATH . '/app/Views/' . $__vista . '.php';
        if (!is_file($__ruta)) {
            throw new \RuntimeException("Vista no encontrada: {$__vista}");
        }
        extract($__datos, EXTR_SKIP);
        ob_start();
        try {
            require $__ruta;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
