<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Paginator;

final class OrdenModel extends Model
{
    public const ESTADOS = [
        'abierta' => 'Abierta', 'enviada' => 'En cola de cocina', 'en_preparacion' => 'En preparación',
        'lista' => 'Lista para servir', 'servida' => 'Servida', 'pagada' => 'Pagada', 'cancelada' => 'Cancelada',
    ];
    private const ORDEN = ['codigo' => 'o.codigo', 'fecha' => 'o.creado_en', 'mesa' => 'm.numero', 'estado' => 'o.estado', 'mesero' => 'me.nombre'];

    /** @param array{mesero_id?:int,cliente_id?:int} $alcance restringe el listado según el rol */
    public function listar(array $f, string $sort, string $dir, int $pagina, array $alcance = []): Paginator
    {
        $where = ['1 = 1'];
        $params = [];
        foreach (['mesero_id', 'cliente_id'] as $col) {
            if (isset($alcance[$col])) {
                $where[] = "o.{$col} = ?";
                $params[] = $alcance[$col];
            }
        }
        if (($f['q'] ?? '') !== '') {
            $where[] = '(o.codigo LIKE ? OR c.nombre LIKE ? OR me.nombre LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (isset(self::ESTADOS[$f['estado'] ?? ''])) {
            $where[] = 'o.estado = ?';
            $params[] = $f['estado'];
        }
        if (($f['desde'] ?? '') !== '') {
            $where[] = 'o.creado_en >= ?';
            $params[] = $f['desde'] . ' 00:00:00';
        }
        if (($f['hasta'] ?? '') !== '') {
            $where[] = 'o.creado_en <= ?';
            $params[] = $f['hasta'] . ' 23:59:59';
        }
        $desde = 'FROM ordenes o
                  JOIN mesas m ON m.id = o.mesa_id
                  JOIN usuarios me ON me.id = o.mesero_id
                  LEFT JOIN usuarios c ON c.id = o.cliente_id
                  WHERE ' . implode(' AND ', $where);
        $select = 'o.id, o.codigo, o.estado, o.creado_en, m.numero AS mesa_numero, me.nombre AS mesero, c.nombre AS cliente,
                   (SELECT COALESCE(SUM(i.cantidad * i.precio_unitario), 0) FROM orden_items i WHERE i.orden_id = o.id) AS subtotal';
        return $this->paginar($select, $desde, $params, $this->ordenSql(self::ORDEN, $sort, $dir, 'o.creado_en DESC'), $pagina);
    }

    public function buscar(int $id): ?array
    {
        return $this->uno(
            'SELECT o.*, m.numero AS mesa_numero, me.nombre AS mesero, c.nombre AS cliente, r.codigo AS reserva_codigo
             FROM ordenes o
             JOIN mesas m ON m.id = o.mesa_id
             JOIN usuarios me ON me.id = o.mesero_id
             LEFT JOIN usuarios c ON c.id = o.cliente_id
             LEFT JOIN reservas r ON r.id = o.reserva_id
             WHERE o.id = ?',
            [$id]
        );
    }

    public function bloquear(int $id): ?array
    {
        return $this->uno('SELECT * FROM ordenes WHERE id = ? FOR UPDATE', [$id]);
    }

    public function crear(string $codigo, int $mesaId, int $meseroId, ?int $clienteId, ?int $reservaId): int
    {
        return $this->insertar(
            'INSERT INTO ordenes (codigo, mesa_id, mesero_id, cliente_id, reserva_id) VALUES (?, ?, ?, ?, ?)',
            [$codigo, $mesaId, $meseroId, $clienteId, $reservaId]
        );
    }

    public function mesaTieneOrdenActiva(int $mesaId): bool
    {
        return (bool) $this->escalar('SELECT 1 FROM ordenes WHERE mesa_activa = ?', [$mesaId]);
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $this->ejecutar('UPDATE ordenes SET estado = ? WHERE id = ?', [$estado, $id]);
    }

    public function cambiarRonda(int $id, string $estado, int $ronda): void
    {
        $this->ejecutar('UPDATE ordenes SET estado = ?, ronda_actual = ? WHERE id = ?', [$estado, $ronda, $id]);
    }

    public function reasignar(int $id, int $meseroId): void
    {
        $this->ejecutar('UPDATE ordenes SET mesero_id = ? WHERE id = ?', [$meseroId, $id]);
    }

    public function registrarHistorial(int $ordenId, ?string $anterior, string $nuevo, int $usuarioId, ?string $nota = null): void
    {
        $this->ejecutar(
            'INSERT INTO orden_historial (orden_id, estado_anterior, estado_nuevo, usuario_id, nota) VALUES (?, ?, ?, ?, ?)',
            [$ordenId, $anterior, $nuevo, $usuarioId, $nota]
        );
    }

    public function historial(int $ordenId): array
    {
        return $this->todos(
            'SELECT h.*, u.nombre AS usuario, r.nombre AS rol FROM orden_historial h
             JOIN usuarios u ON u.id = h.usuario_id JOIN roles r ON r.id = u.rol_id
             WHERE h.orden_id = ? ORDER BY h.creado_en, h.id',
            [$ordenId]
        );
    }

    // ---------- Ítems ----------

    public function items(int $ordenId): array
    {
        return $this->todos(
            'SELECT i.*, p.nombre AS plato, (i.cantidad * i.precio_unitario) AS importe
             FROM orden_items i JOIN platos p ON p.id = i.plato_id
             WHERE i.orden_id = ? ORDER BY i.ronda, i.id',
            [$ordenId]
        );
    }

    public function buscarItem(int $ordenId, int $itemId): ?array
    {
        return $this->uno('SELECT * FROM orden_items WHERE id = ? AND orden_id = ?', [$itemId, $ordenId]);
    }

    public function contarItemsRonda(int $ordenId, int $ronda): int
    {
        return (int) $this->escalar('SELECT COUNT(*) FROM orden_items WHERE orden_id = ? AND ronda = ?', [$ordenId, $ronda]);
    }

    /** El precio se congela en el ítem; subir el precio del plato no altera órdenes ya tomadas. */
    public function agregarItem(int $ordenId, int $platoId, int $ronda, int $cantidad, int $precio, string $nota): void
    {
        $this->ejecutar(
            'INSERT INTO orden_items (orden_id, plato_id, ronda, cantidad, precio_unitario, nota) VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE cantidad = LEAST(cantidad + VALUES(cantidad), 50)',
            [$ordenId, $platoId, $ronda, $cantidad, $precio, $nota]
        );
    }

    public function cantidadItem(int $itemId, int $cantidad): void
    {
        $this->ejecutar('UPDATE orden_items SET cantidad = ? WHERE id = ?', [$cantidad, $itemId]);
    }

    public function eliminarItem(int $itemId): void
    {
        $this->ejecutar('DELETE FROM orden_items WHERE id = ?', [$itemId]);
    }

    public function eliminarItemsRonda(int $ordenId, int $ronda): void
    {
        $this->ejecutar('DELETE FROM orden_items WHERE orden_id = ? AND ronda = ?', [$ordenId, $ronda]);
    }

    public function subtotal(int $ordenId): int
    {
        return (int) $this->escalar('SELECT COALESCE(SUM(cantidad * precio_unitario), 0) FROM orden_items WHERE orden_id = ?', [$ordenId]);
    }

    // ---------- Cocina ----------

    /** Comandas activas con los ítems de su ronda vigente, de la más antigua a la más reciente. */
    public function tableroCocina(): array
    {
        $ordenes = $this->todos(
            "SELECT o.id, o.codigo, o.estado, o.ronda_actual, m.numero AS mesa_numero, me.nombre AS mesero,
                    (SELECT MAX(h.creado_en) FROM orden_historial h WHERE h.orden_id = o.id AND h.estado_nuevo = 'enviada') AS enviada_en
             FROM ordenes o JOIN mesas m ON m.id = o.mesa_id JOIN usuarios me ON me.id = o.mesero_id
             WHERE o.estado IN ('enviada','en_preparacion','lista')
             ORDER BY enviada_en, o.id"
        );
        if ($ordenes === []) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($ordenes), '?'));
        $items = $this->todos(
            "SELECT i.orden_id, i.ronda, i.cantidad, i.nota, p.nombre AS plato
             FROM orden_items i JOIN platos p ON p.id = i.plato_id
             WHERE i.orden_id IN ({$marcas}) ORDER BY i.id",
            array_column($ordenes, 'id')
        );
        foreach ($ordenes as &$orden) {
            $orden['items'] = array_values(array_filter(
                $items,
                static fn (array $i) => $i['orden_id'] === $orden['id'] && $i['ronda'] === $orden['ronda_actual']
            ));
        }
        return $ordenes;
    }

    // ---------- Cuenta simulada ----------

    public function buscarCuenta(int $ordenId): ?array
    {
        return $this->uno(
            'SELECT c.*, u.nombre AS cerrada_por_nombre FROM cuentas c JOIN usuarios u ON u.id = c.cerrada_por WHERE c.orden_id = ?',
            [$ordenId]
        );
    }

    public function crearCuenta(int $ordenId, int $subtotal, int $impoconsumo, int $propina, int $total, string $metodo, int $usuarioId): int
    {
        return $this->insertar(
            'INSERT INTO cuentas (orden_id, subtotal, impoconsumo, propina, total, metodo, cerrada_por) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$ordenId, $subtotal, $impoconsumo, $propina, $total, $metodo, $usuarioId]
        );
    }

    /** Órdenes activas de un mesero, las listas para servir primero. */
    public function activasDeMesero(int $meseroId): array
    {
        return $this->todos(
            "SELECT o.id, o.codigo, o.estado, m.numero AS mesa_numero
             FROM ordenes o JOIN mesas m ON m.id = o.mesa_id
             WHERE o.mesero_id = ? AND o.estado NOT IN ('pagada','cancelada')
             ORDER BY FIELD(o.estado, 'lista', 'servida', 'abierta', 'enviada', 'en_preparacion'), o.creado_en",
            [$meseroId]
        );
    }

    /** Órdenes pagadas del cliente que aún no tienen reseña. */
    public function pagadasSinResena(int $clienteId): array
    {
        return $this->todos(
            "SELECT o.id, o.codigo, o.creado_en FROM ordenes o
             LEFT JOIN resenas r ON r.orden_id = o.id
             WHERE o.cliente_id = ? AND o.estado = 'pagada' AND r.id IS NULL
             ORDER BY o.creado_en DESC LIMIT 5",
            [$clienteId]
        );
    }
}
