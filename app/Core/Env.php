<?php
declare(strict_types=1);

namespace App\Core;

/** Lector mínimo de archivos .env (CLAVE=valor). */
final class Env
{
    /** @var array<string,string> */
    private static array $valores = [];

    public static function load(string $ruta): void
    {
        if (!is_file($ruta)) {
            return; // Sin .env se usan los valores por defecto de config/.
        }
        foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) {
                continue;
            }
            [$clave, $valor] = explode('=', $linea, 2);
            self::$valores[trim($clave)] = trim(trim($valor), "\"'");
        }
    }

    public static function get(string $clave, ?string $porDefecto = null): ?string
    {
        return self::$valores[$clave] ?? $porDefecto;
    }
}
