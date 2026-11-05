<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Paginator;

final class ResenaModel extends Model
{
    public function listar(string $estado, int $pagina, ?int $clienteId = null): Paginator
    {
        $where = ['1 = 1'];
        $params = [];
        if ($clienteId !== null) {
            $where[] = 'r.cliente_id = ?';
            $params[] = $clienteId;
        }
        if (in_array($estado, ['pendiente', 'aprobada', 'rechazada'], true)) {
            $where[] = 'r.estado = ?';
            $params[] = $estado;
        }
        $desde = 'FROM resenas r JOIN usuarios u ON u.id = r.cliente_id JOIN ordenes o ON o.id = r.orden_id WHERE ' . implode(' AND ', $where);
        return $this->paginar('r.*, u.nombre AS cliente, o.codigo AS orden_codigo', $desde, $params, "FIELD(r.estado, 'pendiente', 'aprobada', 'rechazada'), r.creado_en DESC", $pagina);
    }

    public function buscar(int $id): ?array
    {
        return $this->uno('SELECT * FROM resenas WHERE id = ?', [$id]);
    }

    public function existeParaOrden(int $ordenId): bool
    {
        return (bool) $this->escalar('SELECT 1 FROM resenas WHERE orden_id = ?', [$ordenId]);
    }

    public function crear(int $ordenId, int $clienteId, int $calificacion, string $comentario, string $ciudad): int
    {
        return $this->insertar(
            'INSERT INTO resenas (orden_id, cliente_id, calificacion, comentario, ciudad) VALUES (?, ?, ?, ?, ?)',
            [$ordenId, $clienteId, $calificacion, $comentario, $ciudad]
        );
    }

    public function moderar(int $id, string $estado): void
    {
        $this->ejecutar('UPDATE resenas SET estado = ? WHERE id = ?', [$estado, $id]);
    }

    /** Testimonios publicados en la página principal. */
    public function publicas(int $limite = 2): array
    {
        return $this->todos(
            "SELECT r.calificacion, r.comentario, r.ciudad, u.nombre AS cliente
             FROM resenas r JOIN usuarios u ON u.id = r.cliente_id
             WHERE r.estado = 'aprobada' ORDER BY r.calificacion DESC, r.creado_en DESC, r.id DESC LIMIT ?",
            [$limite]
        );
    }

    /** Indicadores públicos calculados con las reseñas aprobadas (nunca cifras escritas a mano). */
    public function indicadores(): array
    {
        $fila = $this->uno(
            "SELECT COUNT(*) AS total, COALESCE(AVG(calificacion), 0) AS promedio,
                    COALESCE(SUM(calificacion >= 4), 0) AS satisfechos
             FROM resenas WHERE estado = 'aprobada'"
        );
        $total = (int) $fila['total'];
        return [
            'total'      => $total,
            'promedio'   => round((float) $fila['promedio'], 1),
            'satisfechos' => $total > 0 ? (int) round((int) $fila['satisfechos'] * 100 / $total) : 0,
        ];
    }
}
