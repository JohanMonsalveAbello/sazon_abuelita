<?php
declare(strict_types=1);

namespace App\Core;

/** Token CSRF por sesión (RS-05). */
final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf');
        if (!is_string($token)) {
            $token = bin2hex(random_bytes(32));
            Session::set('_csrf', $token);
        }
        return $token;
    }

    public static function valido(string $recibido): bool
    {
        $token = Session::get('_csrf');
        return is_string($token) && $recibido !== '' && hash_equals($token, $recibido);
    }

    public static function rotar(): void
    {
        Session::forget('_csrf');
    }
}
