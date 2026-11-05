<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Paginator;

/** Consultas de indicadores y reportes: siempre calculadas con datos reales de MySQL (RF-09). */
final class ReporteModel extends Model
{
    public const METODOS = ['efectivo' => 'Efectivo', 'tarjeta_simulada' => 'Tarjeta (simulada)', 'transferencia_simulada' => 'Transferencia (simulada)'];

    public function indicadoresAdmin(): array
    {
        $ventas = $this->uno(
            "SELECT COALESCE(SUM(CASE WHEN c.creado_en >= CURDATE() THEN c.total END), 0) AS hoy,
                    COALESCE(SUM(c.total), 0) AS mes,
                    COUNT(*) AS cuentas_mes,
                    COALESCE(AVG(c.total), 0) AS ticket
             FROM cuentas c WHERE c.creado_en >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
        );
        return $ventas + [
            'ordenes_activas' => (int) $this->escalar("SELECT COUNT(*) FROM ordenes WHERE estado NOT IN ('pagada','cancelada')"),
            'reservas_pendientes' => (int) $this->escalar("SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente' AND fecha >= CURDATE()"),
            'resenas_pendientes' => (int) $this->escalar("SELECT COUNT(*) FROM resenas WHERE estado = 'pendiente'"),
        ];
    }

    /** Ventas diarias de los últimos $dias, con ceros en los días sin ventas. */
    public function ventasPorDia(int $dias = 14): array
    {
        $filas = $this->todos(
            'SELECT DATE(creado_en) AS dia, SUM(total) AS total, COUNT(*) AS cuentas FROM cuentas
             WHERE creado_en >= CURDATE() - INTERVAL ? DAY GROUP BY DATE(creado_en)',
            [$dias - 1]
        );
        $porDia = array_column($filas, null, 'dia');
        $serie = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $dia = date('Y-m-d', strtotime("-{$i} days"));
            $serie[] = ['dia' => $dia, 'total' => (int) ($porDia[$dia]['total'] ?? 0), 'cuentas' => (int) ($porDia[$dia]['cuentas'] ?? 0)];
        }
        return $serie;
    }

    public function platosMasVendidos(string $desde, string $hasta, int $limite = 5): array
    {
        return $this->todos(
            "SELECT p.nombre, SUM(i.cantidad) AS unidades, SUM(i.cantidad * i.precio_unitario) AS ingresos
             FROM orden_items i
             JOIN ordenes o ON o.id = i.orden_id AND o.estado = 'pagada'
             JOIN cuentas c ON c.orden_id = o.id
             JOIN platos p ON p.id = i.plato_id
             WHERE c.creado_en >= ? AND c.creado_en <= ?
             GROUP BY p.id, p.nombre ORDER BY unidades DESC, p.nombre LIMIT ?",
            [$desde . ' 00:00:00', $hasta . ' 23:59:59', $limite]
        );
    }

    public function indicadoresMesero(int $meseroId): array
    {
        return [
            'activas' => (int) $this->escalar("SELECT COUNT(*) FROM ordenes WHERE mesero_id = ? AND estado NOT IN ('pagada','cancelada')", [$meseroId]),
            'listas'  => (int) $this->escalar("SELECT COUNT(*) FROM ordenes WHERE mesero_id = ? AND estado = 'lista'", [$meseroId]),
            'ventas_hoy' => (int) $this->escalar(
                'SELECT COALESCE(SUM(c.total), 0) FROM cuentas c JOIN ordenes o ON o.id = c.orden_id WHERE o.mesero_id = ? AND c.creado_en >= CURDATE()',
                [$meseroId]
            ),
            'reservas_pendientes' => (int) $this->escalar("SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente' AND fecha >= CURDATE()"),
        ];
    }

    public function indicadoresCocina(): array
    {
        $promedio = $this->escalar(
            "SELECT AVG(TIMESTAMPDIFF(MINUTE,
                (SELECT MAX(e.creado_en) FROM orden_historial e
                 WHERE e.orden_id = l.orden_id AND e.estado_nuevo = 'enviada' AND e.creado_en <= l.creado_en),
                l.creado_en))
             FROM orden_historial l
             WHERE l.estado_nuevo = 'lista' AND l.creado_en >= CURDATE() - INTERVAL 30 DAY"
        );
        return [
            'en_cola'        => (int) $this->escalar("SELECT COUNT(*) FROM ordenes WHERE estado = 'enviada'"),
            'en_preparacion' => (int) $this->escalar("SELECT COUNT(*) FROM ordenes WHERE estado = 'en_preparacion'"),
            'listas_hoy'     => (int) $this->escalar("SELECT COUNT(*) FROM orden_historial WHERE estado_nuevo = 'lista' AND creado_en >= CURDATE()"),
            'minutos_promedio' => $promedio === null || $promedio === false ? null : (int) round((float) $promedio),
            'agotados'       => (int) $this->escalar('SELECT COUNT(*) FROM platos WHERE disponible = 0 AND eliminado_en IS NULL'),
        ];
    }

    public function indicadoresCliente(int $clienteId): array
    {
        return [
            'proximas' => (int) $this->escalar("SELECT COUNT(*) FROM reservas WHERE cliente_id = ? AND fecha >= CURDATE() AND estado IN ('pendiente','confirmada')", [$clienteId]),
            'visitas'  => (int) $this->escalar("SELECT COUNT(*) FROM ordenes WHERE cliente_id = ? AND estado = 'pagada'", [$clienteId]),
            'gastado'  => (int) $this->escalar('SELECT COALESCE(SUM(c.total), 0) FROM cuentas c JOIN ordenes o ON o.id = c.orden_id WHERE o.cliente_id = ?', [$clienteId]),
        ];
    }

    // ---------- Reporte de ventas filtrable ----------

    /** @return array{0:string,1:array} fragmento FROM...WHERE y sus parámetros */
    private function filtrosVentas(array $f): array
    {
        $where = ['c.creado_en >= ?', 'c.creado_en <= ?'];
        $params = [$f['desde'] . ' 00:00:00', $f['hasta'] . ' 23:59:59'];
        if (ctype_digit($f['mesero_id'] ?? '')) {
            $where[] = 'o.mesero_id = ?';
            $params[] = (int) $f['mesero_id'];
        }
        if (isset(self::METODOS[$f['metodo'] ?? ''])) {
            $where[] = 'c.metodo = ?';
            $params[] = $f['metodo'];
        }
        $desde = 'FROM cuentas c
                  JOIN ordenes o ON o.id = c.orden_id
                  JOIN mesas m ON m.id = o.mesa_id
                  JOIN usuarios me ON me.id = o.mesero_id
                  WHERE ' . implode(' AND ', $where);
        return [$desde, $params];
    }

    private const COLUMNAS_VENTAS = 'c.creado_en, o.id AS orden_id, o.codigo, m.numero AS mesa, me.nombre AS mesero, c.metodo, c.subtotal, c.impoconsumo, c.propina, c.total';

    public function ventas(array $f, int $pagina): Paginator
    {
        [$desde, $params] = $this->filtrosVentas($f);
        return $this->paginar(self::COLUMNAS_VENTAS, $desde, $params, 'c.creado_en DESC', $pagina);
    }

    public function resumenVentas(array $f): array
    {
        [$desde, $params] = $this->filtrosVentas($f);
        return $this->uno(
            "SELECT COUNT(*) AS cuentas, COALESCE(SUM(c.subtotal), 0) AS subtotal, COALESCE(SUM(c.impoconsumo), 0) AS impoconsumo,
                    COALESCE(SUM(c.propina), 0) AS propina, COALESCE(SUM(c.total), 0) AS total, COALESCE(AVG(c.total), 0) AS ticket {$desde}",
            $params
        );
    }

    public function ventasParaExportar(array $f): array
    {
        [$desde, $params] = $this->filtrosVentas($f);
        return $this->todos('SELECT ' . self::COLUMNAS_VENTAS . " {$desde} ORDER BY c.creado_en DESC LIMIT 20000", $params);
    }
}
