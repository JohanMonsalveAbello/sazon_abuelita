<?php
declare(strict_types=1);

namespace App\Core;

/** Acceso controlado a la solicitud. Las entradas siempre llegan como texto recortado. */
final class Request
{
    private static ?self $actual = null;

    public static function current(): self
    {
        return self::$actual ??= new self();
    }

    /** Carpeta base de la aplicación, p. ej. /sazon_abuelita en XAMPP o '' en raíz. */
    public static function basePath(): string
    {
        $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $base = preg_replace('#/public$#', '', $base) ?? '';
        return rtrim($base, '/');
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $ruta = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
        $base = self::basePath();
        if ($base !== '' && str_starts_with($ruta, $base)) {
            $ruta = substr($ruta, strlen($base));
        }
        $ruta = preg_replace('#^/public(?=/|$)#', '', $ruta) ?? $ruta;
        return '/' . trim($ruta, '/');
    }

    /** Valor de la URL (?clave=). Si llega un arreglo inesperado se trata como vacío. */
    public function query(string $clave, string $porDefecto = ''): string
    {
        $v = $_GET[$clave] ?? $porDefecto;
        return is_string($v) ? trim($v) : $porDefecto;
    }

    /** Valor del formulario. Si llega un arreglo inesperado se trata como vacío. */
    public function input(string $clave, string $porDefecto = ''): string
    {
        $v = $_POST[$clave] ?? $porDefecto;
        return is_string($v) ? trim($v) : $porDefecto;
    }

    /** Todos los campos de texto del formulario. */
    public function all(): array
    {
        $datos = [];
        foreach ($_POST as $k => $v) {
            if (is_string($v)) {
                $datos[$k] = trim($v);
            }
        }
        return $datos;
    }

    public function page(): int
    {
        return max(1, (int) $this->query('page', '1'));
    }

    public function file(string $clave): ?array
    {
        $f = $_FILES[$clave] ?? null;
        return is_array($f) && is_string($f['tmp_name'] ?? null) ? $f : null;
    }

    public function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function esHttps(): bool
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }
}
