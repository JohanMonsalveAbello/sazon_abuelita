<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Validación reutilizable. Uso:
 *   Validator::check($datos, ['correo' => ['Correo', 'required|email|max:120']]);
 * Devuelve [campo => mensaje] con el primer error de cada campo.
 */
final class Validator
{
    public static function check(array $datos, array $reglas): array
    {
        $errores = [];
        foreach ($reglas as $campo => [$etiqueta, $cadena]) {
            $valor = is_string($datos[$campo] ?? null) ? $datos[$campo] : '';
            foreach (explode('|', $cadena) as $regla) {
                [$nombre, $parametro] = array_pad(explode(':', $regla, 2), 2, null);
                if ($nombre !== 'required' && $valor === '') {
                    continue; // Los campos opcionales vacíos no se validan más.
                }
                $mensaje = self::aplicar($nombre, $valor, $parametro, $etiqueta, $datos);
                if ($mensaje !== null) {
                    $errores[$campo] = $mensaje;
                    break;
                }
            }
        }
        return $errores;
    }

    private static function aplicar(string $regla, string $v, ?string $p, string $et, array $datos): ?string
    {
        switch ($regla) {
            case 'required':
                return $v === '' ? "{$et} es obligatorio." : null;
            case 'email':
                return filter_var($v, FILTER_VALIDATE_EMAIL) ? null : "{$et} no tiene un formato válido.";
            case 'min':
                return mb_strlen($v) < (int) $p ? "{$et} debe tener al menos {$p} caracteres." : null;
            case 'max':
                return mb_strlen($v) > (int) $p ? "{$et} admite máximo {$p} caracteres." : null;
            case 'int':
                return preg_match('/^\d{1,9}$/', $v) ? null : "{$et} debe ser un número entero.";
            case 'between':
                [$a, $b] = explode(',', (string) $p);
                return preg_match('/^\d{1,9}$/', $v) && (int) $v >= (int) $a && (int) $v <= (int) $b
                    ? null : "{$et} debe estar entre {$a} y {$b}.";
            case 'in':
                return in_array($v, explode(',', (string) $p), true) ? null : "{$et} no es una opción válida.";
            case 'date':
                $f = \DateTimeImmutable::createFromFormat('!Y-m-d', $v);
                return $f && $f->format('Y-m-d') === $v ? null : "{$et} no es una fecha válida.";
            case 'time':
                return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v) ? null : "{$et} no es una hora válida.";
            case 'phone':
                return preg_match('/^\+?[0-9 ()-]{7,20}$/', $v) ? null : "{$et} solo admite números, espacios y + ( ) -.";
            case 'name':
                return preg_match('/^[\p{L}\p{M} .\'-]+$/u', $v) ? null : "{$et} solo admite letras, espacios y . ' -.";
            case 'password':
                return mb_strlen($v) >= 8 && mb_strlen($v) <= 72 && preg_match('/[a-z]/', $v) && preg_match('/[A-Z]/', $v) && preg_match('/\d/', $v)
                    ? null : "{$et} debe tener de 8 a 72 caracteres, con mayúscula, minúscula y número.";
            case 'same':
                return $v === (string) ($datos[(string) $p] ?? '') ? null : "{$et} no coincide.";
            default:
                throw new \InvalidArgumentException("Regla desconocida: {$regla}");
        }
    }
}
