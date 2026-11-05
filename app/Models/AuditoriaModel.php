<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Paginator;

final class AuditoriaModel extends Model
{
    private const ORDEN = ['fecha' => 'a.creado_en', 'accion' => 'a.accion', 'entidad' => 'a.entidad', 'usuario' => 'u.nombre'];

    public function registrar(?int $usuarioId, string $accion, string $entidad, ?int $entidadId, ?string $detalle, string $ip): void
    {
        $this->ejecutar(
            'INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalle, ip) VALUES (?, ?, ?, ?, ?, ?)',
            [$usuarioId, $accion, $entidad, $entidadId, $detalle, $ip]
        );
    }

    public function listar(array $f, string $sort, string $dir, int $pagina): Paginator
    {
        $where = ['1 = 1'];
        $params = [];
        if (($f['q'] ?? '') !== '') {
            $where[] = '(a.accion LIKE ? OR a.detalle LIKE ? OR u.nombre LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (($f['entidad'] ?? '') !== '') {
            $where[] = 'a.entidad = ?';
            $params[] = $f['entidad'];
        }
        if (($f['desde'] ?? '') !== '') {
            $where[] = 'a.creado_en >= ?';
            $params[] = $f['desde'] . ' 00:00:00';
        }
        if (($f['hasta'] ?? '') !== '') {
            $where[] = 'a.creado_en <= ?';
            $params[] = $f['hasta'] . ' 23:59:59';
        }
        $desde = 'FROM auditoria a LEFT JOIN usuarios u ON u.id = a.usuario_id WHERE ' . implode(' AND ', $where);
        return $this->paginar('a.*, u.nombre AS usuario', $desde, $params, $this->ordenSql(self::ORDEN, $sort, $dir, 'a.creado_en DESC, a.id DESC'), $pagina);
    }

    public function entidades(): array
    {
        return array_column($this->todos('SELECT DISTINCT entidad FROM auditoria ORDER BY entidad'), 'entidad');
    }
}
