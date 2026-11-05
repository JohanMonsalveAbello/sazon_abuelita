<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\BusinessRuleException;
use App\Models\IntentoLoginModel;
use App\Models\UsuarioModel;

/** Inicio de sesión con verificación de hash y limitación progresiva de intentos. */
final class AuthService
{
    private const INTENTOS_LIBRES = 5;
    private const BLOQUEO_BASE = 30;     // segundos tras el 5.º fallo; se duplica en cada fallo adicional
    private const BLOQUEO_MAXIMO = 900;  // 15 minutos
    public const MENSAJE_GENERICO = 'Correo o contraseña incorrectos.';

    public function __construct(
        private UsuarioModel $usuarios = new UsuarioModel(),
        private IntentoLoginModel $intentos = new IntentoLoginModel()
    ) {
    }

    public static function algoritmo(): string|int
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, self::algoritmo());
    }

    /** @throws BusinessRuleException con un mensaje que no revela si el correo existe */
    public function iniciarSesion(string $correo, string $password, string $ip): void
    {
        $clave = hash('sha256', mb_strtolower($correo) . '|' . $ip);

        if ($this->intentos->segundosDeBloqueo($clave) > 0) {
            throw new BusinessRuleException('Demasiados intentos fallidos. Espera unos minutos e inténtalo de nuevo.');
        }

        $cuenta = $this->usuarios->credencialesPorCorreo($correo);
        // Se verifica también sin usuario para no delatar por tiempo de respuesta si el correo existe.
        $hash = $cuenta['password_hash'] ?? '$2y$11$abcdefghijklmnopqrstuuJ6v3a9Y3yq4VnNnUe5m7q8m2Wq1cK9S';
        $correcta = password_verify($password, $hash);

        if ($cuenta === null || !$correcta || (int) $cuenta['activo'] !== 1) {
            $fallos = $this->intentos->registrarFallo($clave);
            if ($fallos >= self::INTENTOS_LIBRES) {
                $segundos = min(self::BLOQUEO_MAXIMO, self::BLOQUEO_BASE * (2 ** ($fallos - self::INTENTOS_LIBRES)));
                $this->intentos->bloquear($clave, $segundos);
            }
            AuditService::registrar('login_fallido', 'sesion', null, ['correo' => $correo]);
            throw new BusinessRuleException(self::MENSAJE_GENERICO);
        }

        $this->intentos->limpiar($clave);
        if (password_needs_rehash($hash, self::algoritmo())) {
            $this->usuarios->cambiarPassword((int) $cuenta['id'], self::hash($password));
        }
        Auth::login((int) $cuenta['id']);
        AuditService::registrar('login_exitoso', 'sesion', (int) $cuenta['id'], [], (int) $cuenta['id']);
    }
}
