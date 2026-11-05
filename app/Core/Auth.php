<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\PermisoModel;
use App\Models\UsuarioModel;

/**
 * Usuario actual y permisos. Se consultan en MySQL en cada solicitud:
 * desactivar un usuario o cambiarle el rol surte efecto de inmediato.
 */
final class Auth
{
    private static ?array $usuario = null;
    private static bool $cargado = false;
    /** @var array<string,bool>|null */
    private static ?array $permisos = null;

    public static function user(): ?array
    {
        if (!self::$cargado) {
            self::$cargado = true;
            $id = Session::get('usuario_id');
            self::$usuario = is_int($id) ? (new UsuarioModel())->buscarActivo($id) : null;
            if ($id !== null && self::$usuario === null) {
                Session::forget('usuario_id');
            }
        }
        return self::$usuario;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function rol(): ?string
    {
        return self::user()['rol_codigo'] ?? null;
    }

    public static function es(string ...$roles): bool
    {
        return in_array(self::rol(), $roles, true);
    }

    public static function can(string $permiso): bool
    {
        $usuario = self::user();
        if ($usuario === null) {
            return false;
        }
        self::$permisos ??= array_fill_keys((new PermisoModel())->deRol((int) $usuario['rol_id']), true);
        return isset(self::$permisos[$permiso]);
    }

    public static function login(int $usuarioId): void
    {
        Session::regenerate();
        Csrf::rotar();
        Session::set('usuario_id', $usuarioId);
        self::$cargado = false;
        self::$permisos = null;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$usuario = null;
        self::$cargado = true;
    }
}
