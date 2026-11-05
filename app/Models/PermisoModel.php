<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class PermisoModel extends Model
{
    /** @return string[] p. ej. ['reservas.ver', 'reservas.crear'] */
    public function deRol(int $rolId): array
    {
        $filas = $this->todos("SELECT CONCAT(modulo, '.', accion) AS permiso FROM rol_permisos WHERE rol_id = ?", [$rolId]);
        return array_column($filas, 'permiso');
    }

    public function matriz(): array
    {
        return $this->todos(
            'SELECT r.codigo AS rol, p.modulo, p.accion FROM rol_permisos p JOIN roles r ON r.id = p.rol_id ORDER BY p.modulo'
        );
    }
}
