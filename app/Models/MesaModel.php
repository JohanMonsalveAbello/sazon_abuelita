<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class MesaModel extends Model
{
    public const ZONAS = ['salon' => 'Salón', 'terraza' => 'Terraza', 'privado' => 'Privado'];

    /** Mesas con su orden activa (si existe), para ver la ocupación del salón. */
    public function todas(): array
    {
        return $this->todos(
            'SELECT m.*, o.id AS orden_id, o.codigo AS orden_codigo
             FROM mesas m LEFT JOIN ordenes o ON o.mesa_activa = m.id
             WHERE m.eliminado_en IS NULL ORDER BY m.numero'
        );
    }

    public function buscar(int $id): ?array
    {
        return $this->uno('SELECT * FROM mesas WHERE id = ? AND eliminado_en IS NULL', [$id]);
    }

    /** Bloquea la fila de la mesa hasta el fin de la transacción: serializa reservas simultáneas. */
    public function bloquear(int $id): ?array
    {
        return $this->uno('SELECT * FROM mesas WHERE id = ? AND eliminado_en IS NULL FOR UPDATE', [$id]);
    }

    public function numeroEnUso(int $numero, int $excluirId = 0): bool
    {
        return (bool) $this->escalar('SELECT 1 FROM mesas WHERE numero = ? AND id <> ?', [$numero, $excluirId]);
    }

    /**
     * Mesas libres para una franja: activas, con capacidad suficiente y sin otra reserva
     * confirmada a menos de la duración estándar.
     */
    public function disponibles(string $fecha, string $hora, int $personas, int $duracionMin, int $excluirReserva = 0): array
    {
        return $this->todos(
            'SELECT m.* FROM mesas m
             WHERE m.activa = 1 AND m.eliminado_en IS NULL AND m.capacidad >= ?
               AND NOT EXISTS (
                 SELECT 1 FROM reservas r
                 WHERE r.mesa_id = m.id AND r.fecha = ? AND r.estado = \'confirmada\' AND r.id <> ?
                   AND ABS(TIME_TO_SEC(TIMEDIFF(r.hora, CAST(? AS TIME)))) < ?
               )
             ORDER BY m.capacidad, m.numero',
            [$personas, $fecha, $excluirReserva, $hora, $duracionMin * 60]
        );
    }

    public function hayConflicto(int $mesaId, string $fecha, string $hora, int $duracionMin, int $excluirReserva = 0): bool
    {
        return (bool) $this->escalar(
            'SELECT 1 FROM reservas r
             WHERE r.mesa_id = ? AND r.fecha = ? AND r.estado = \'confirmada\' AND r.id <> ?
               AND ABS(TIME_TO_SEC(TIMEDIFF(r.hora, CAST(? AS TIME)))) < ? LIMIT 1',
            [$mesaId, $fecha, $excluirReserva, $hora, $duracionMin * 60]
        );
    }

    public function tieneCompromisos(int $id): bool
    {
        return (bool) $this->escalar(
            "SELECT 1 FROM mesas m WHERE m.id = ? AND (
               EXISTS (SELECT 1 FROM ordenes o WHERE o.mesa_activa = m.id)
               OR EXISTS (SELECT 1 FROM reservas r WHERE r.mesa_id = m.id AND r.estado = 'confirmada' AND r.fecha >= CURDATE()))",
            [$id]
        );
    }

    public function crear(array $d): int
    {
        return $this->insertar('INSERT INTO mesas (numero, capacidad, zona, activa) VALUES (?, ?, ?, ?)', [$d['numero'], $d['capacidad'], $d['zona'], $d['activa']]);
    }

    public function actualizar(int $id, array $d): void
    {
        $this->ejecutar('UPDATE mesas SET numero = ?, capacidad = ?, zona = ?, activa = ? WHERE id = ?', [$d['numero'], $d['capacidad'], $d['zona'], $d['activa'], $id]);
    }

    public function eliminar(int $id): void
    {
        $this->ejecutar('UPDATE mesas SET eliminado_en = NOW(), activa = 0 WHERE id = ?', [$id]);
    }

    /** Mesas sin orden activa, para abrir una orden nueva. @return array<int,string> */
    public function opcionesLibres(): array
    {
        $filas = $this->todos(
            'SELECT m.id, m.numero, m.capacidad, m.zona FROM mesas m
             WHERE m.activa = 1 AND m.eliminado_en IS NULL
               AND NOT EXISTS (SELECT 1 FROM ordenes o WHERE o.mesa_activa = m.id)
             ORDER BY m.numero'
        );
        $opciones = [];
        foreach ($filas as $f) {
            $opciones[$f['id']] = "Mesa {$f['numero']} · {$f['capacidad']} personas · " . self::ZONAS[$f['zona']];
        }
        return $opciones;
    }
}
