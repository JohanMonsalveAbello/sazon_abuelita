<?php
declare(strict_types=1);

namespace App\Core;

/** Sesión segura (RS-03): cookies HttpOnly + SameSite, Secure bajo HTTPS y expiración por inactividad. */
final class Session
{
    public static function start(): void
    {
        $inactividad = (int) config('app.session_idle_seconds', 1800);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) max($inactividad, 1440));
        session_name('SAZONSESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => Request::basePath() ?: '/',
            'secure'   => Request::current()->esHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        $ultimo = $_SESSION['_ultima_actividad'] ?? null;
        if (isset($_SESSION['usuario_id']) && $ultimo !== null && time() - (int) $ultimo > $inactividad) {
            self::destroy();
            session_start();
            self::flash('error', 'Tu sesión expiró por inactividad. Ingresa de nuevo.');
        }
        $_SESSION['_ultima_actividad'] = time();
    }

    public static function get(string $clave, mixed $porDefecto = null): mixed
    {
        return $_SESSION[$clave] ?? $porDefecto;
    }

    public static function set(string $clave, mixed $valor): void
    {
        $_SESSION[$clave] = $valor;
    }

    public static function forget(string $clave): void
    {
        unset($_SESSION[$clave]);
    }

    /** Mensaje de un solo uso: sobrevive hasta que una vista lo consume. */
    public static function flash(string $clave, mixed $valor): void
    {
        $_SESSION['_flash'][$clave] = $valor;
    }

    public static function pull(string $clave, mixed $porDefecto = null): mixed
    {
        $valor = $_SESSION['_flash'][$clave] ?? $porDefecto;
        unset($_SESSION['_flash'][$clave]);
        return $valor;
    }

    /** Evita fijación de sesión: nuevo identificador al cambiar de privilegios. */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'],
            'httponly' => true, 'samesite' => 'Lax',
        ]);
        session_destroy();
    }
}
