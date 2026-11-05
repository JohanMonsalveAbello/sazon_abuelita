<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/** Contador de intentos fallidos por correo + IP (RS-07). */
final class IntentoLoginModel extends Model
{
    public function segundosDeBloqueo(string $clave): int
    {
        $s = $this->escalar(
            'SELECT TIMESTAMPDIFF(SECOND, NOW(), bloqueado_hasta) FROM intentos_login WHERE clave = ? AND bloqueado_hasta > NOW()',
            [$clave]
        );
        return $s === false ? 0 : max(1, (int) $s);
    }

    /** Suma un fallo. Una hora sin intentos reinicia el contador. Devuelve el total acumulado. */
    public function registrarFallo(string $clave): int
    {
        $this->ejecutar(
            'INSERT INTO intentos_login (clave, intentos) VALUES (?, 1)
             ON DUPLICATE KEY UPDATE
               intentos = IF(actualizado_en < NOW() - INTERVAL 1 HOUR, 1, intentos + 1),
               actualizado_en = NOW()',
            [$clave]
        );
        return (int) $this->escalar('SELECT intentos FROM intentos_login WHERE clave = ?', [$clave]);
    }

    public function bloquear(string $clave, int $segundos): void
    {
        $this->ejecutar('UPDATE intentos_login SET bloqueado_hasta = NOW() + INTERVAL ? SECOND WHERE clave = ?', [$segundos, $clave]);
    }

    public function limpiar(string $clave): void
    {
        $this->ejecutar('DELETE FROM intentos_login WHERE clave = ?', [$clave]);
    }
}
