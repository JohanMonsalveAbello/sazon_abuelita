<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Paginator;

final class ReservaModel extends Model
{
    public const ESTADOS = ['pendiente' => 'Pendiente', 'confirmada' => 'Confirmada', 'cumplida' => 'Cumplida', 'no_asistio' => 'No asistió', 'cancelada' => 'Cancelada'];
    private const ORDEN = ['fecha' => 'r.fecha', 'nombre' => 'r.nombre_contacto', 'personas' => 'r.personas', 'estado' => 'r.estado', 'creado' => 'r.creado_en'];

    /** @param int|null $clienteId si se indica, solo devuelve reservas de ese cliente */
    public function listar(array $f, string $sort, string $dir, int $pagina, ?int $clienteId = null): Paginator
    {
        $where = ['1 = 1'];
        $params = [];
        if ($clienteId !== null) {
            $where[] = 'r.cliente_id = ?';
            $params[] = $clienteId;
        }
        if (($f['q'] ?? '') !== '') {
            $where[] = '(r.nombre_contacto LIKE ? OR r.codigo LIKE ? OR r.telefono LIKE ? OR r.correo LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (isset(self::ESTADOS[$f['estado'] ?? ''])) {
            $where[] = 'r.estado = ?';
            $params[] = $f['estado'];
        }
        if (($f['desde'] ?? '') !== '') {
            $where[] = 'r.fecha >= ?';
            $params[] = $f['desde'];
        }
        if (($f['hasta'] ?? '') !== '') {
            $where[] = 'r.fecha <= ?';
            $params[] = $f['hasta'];
        }
        $desde = 'FROM reservas r LEFT JOIN mesas m ON m.id = r.mesa_id WHERE ' . implode(' AND ', $where);
        $orden = $this->ordenSql(self::ORDEN, $sort, $dir, 'r.fecha DESC, r.hora DESC');
        if ($sort === 'fecha') {
            $orden .= strtolower($dir) === 'desc' ? ', r.hora DESC' : ', r.hora ASC';
        }
        return $this->paginar('r.*, m.numero AS mesa_numero', $desde, $params, $orden, $pagina);
    }

    public function buscar(int $id): ?array
    {
        return $this->uno(
            'SELECT r.*, m.numero AS mesa_numero, m.capacidad AS mesa_capacidad
             FROM reservas r LEFT JOIN mesas m ON m.id = r.mesa_id WHERE r.id = ?',
            [$id]
        );
    }

    public function bloquear(int $id): ?array
    {
        return $this->uno('SELECT * FROM reservas WHERE id = ? FOR UPDATE', [$id]);
    }

    public function crear(array $d, ?int $clienteId, string $codigo): int
    {
        return $this->insertar(
            'INSERT INTO reservas (codigo, cliente_id, nombre_contacto, telefono, correo, fecha, hora, personas, mensaje)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$codigo, $clienteId, $d['nombre'], $d['telefono'], $d['correo'], $d['fecha'], $d['hora'], (int) $d['personas'], $d['mensaje'] ?: null]
        );
    }

    public function actualizar(int $id, array $d): void
    {
        $this->ejecutar(
            'UPDATE reservas SET nombre_contacto = ?, telefono = ?, correo = ?, fecha = ?, hora = ?, personas = ?, mensaje = ? WHERE id = ?',
            [$d['nombre'], $d['telefono'], $d['correo'], $d['fecha'], $d['hora'], (int) $d['personas'], $d['mensaje'] ?: null, $id]
        );
    }

    public function confirmar(int $id, int $mesaId): void
    {
        $this->ejecutar("UPDATE reservas SET estado = 'confirmada', mesa_id = ? WHERE id = ?", [$mesaId, $id]);
    }

    public function cambiarEstado(int $id, string $estado, ?string $motivo = null): void
    {
        $this->ejecutar('UPDATE reservas SET estado = ?, motivo_cancelacion = ? WHERE id = ?', [$estado, $motivo, $id]);
    }

    /** Regla de duplicados: la misma persona no repite solicitud para la misma fecha y hora. */
    public function existeDuplicada(string $correo, string $fecha, string $hora, int $excluirId = 0): bool
    {
        return (bool) $this->escalar(
            "SELECT 1 FROM reservas WHERE correo = ? AND fecha = ? AND hora = ? AND id <> ? AND estado IN ('pendiente','confirmada')",
            [$correo, $fecha, $hora, $excluirId]
        );
    }

    /** Reservas confirmadas de hoy listas para sentar a los clientes y abrir su orden. */
    public function confirmadasDeHoy(): array
    {
        return $this->todos(
            "SELECT r.*, m.numero AS mesa_numero FROM reservas r JOIN mesas m ON m.id = r.mesa_id
             WHERE r.fecha = CURDATE() AND r.estado = 'confirmada' ORDER BY r.hora"
        );
    }

    /** Próximas reservas de un cliente. */
    public function proximasDeCliente(int $clienteId, int $limite = 5): array
    {
        return $this->todos(
            "SELECT r.*, m.numero AS mesa_numero FROM reservas r LEFT JOIN mesas m ON m.id = r.mesa_id
             WHERE r.cliente_id = ? AND r.fecha >= CURDATE() AND r.estado IN ('pendiente','confirmada')
             ORDER BY r.fecha, r.hora LIMIT ?",
            [$clienteId, $limite]
        );
    }

    /** ¿Otra reserva confirmada ocupa esta mesa en las próximas horas? (protege a quien reservó) */
    public function mesaReservadaProximamente(int $mesaId, int $duracionMin): bool
    {
        return (bool) $this->escalar(
            "SELECT 1 FROM reservas WHERE mesa_id = ? AND estado = 'confirmada' AND fecha = CURDATE()
               AND hora >= SUBTIME(CURTIME(), SEC_TO_TIME(1800))
               AND hora < ADDTIME(CURTIME(), SEC_TO_TIME(?)) LIMIT 1",
            [$mesaId, $duracionMin * 60]
        );
    }
}
