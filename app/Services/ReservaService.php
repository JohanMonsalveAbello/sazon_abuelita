<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\BusinessRuleException;
use App\Core\Database;
use App\Models\MesaModel;
use App\Models\ReservaModel;
use DateTimeImmutable;

/**
 * Reglas de negocio de reservas:
 *  RN-01 Una mesa no puede tener dos reservas confirmadas a menos de 2 horas entre sí.
 *  RN-02 Solo dentro del horario de atención, con 1 h de anticipación y hasta 60 días.
 *  RN-03 La mesa asignada debe tener capacidad para el grupo.
 *  RN-04 El cliente solo cancela con al menos 2 h de anticipación.
 *  RN-05 No se aceptan solicitudes duplicadas (mismo correo, fecha y hora).
 *
 * Estados: pendiente → confirmada → cumplida | no_asistio; pendiente|confirmada → cancelada.
 */
final class ReservaService
{
    public const DURACION_MIN = 120;
    private const CANCELACION_CLIENTE_HORAS = 2;
    private const ANTICIPACION_MIN = 60;
    private const DIAS_MAXIMOS = 60;
    private const ULTIMA_ENTRADA_ANTES_CIERRE_MIN = 60;

    /** Día ISO (1 = lunes) => [apertura, cierre]. */
    private const HORARIO = [
        1 => ['11:30', '22:00'], 2 => ['11:30', '22:00'], 3 => ['11:30', '22:00'], 4 => ['11:30', '22:00'], 5 => ['11:30', '22:00'],
        6 => ['12:00', '23:00'],
        7 => ['12:00', '20:00'],
    ];

    /** Reglas de formato compartidas por el formulario público y el del panel. */
    public const REGLAS = [
        'nombre'   => ['Nombre', 'required|name|min:3|max:120'],
        'telefono' => ['Teléfono', 'required|phone'],
        'correo'   => ['Correo', 'required|email|max:120'],
        'fecha'    => ['Fecha', 'required|date'],
        'hora'     => ['Hora', 'required|time'],
        'personas' => ['Personas', 'required|between:1,20'],
        'mensaje'  => ['Mensaje', 'max:500'],
    ];

    public function __construct(
        private ReservaModel $reservas = new ReservaModel(),
        private MesaModel $mesas = new MesaModel()
    ) {
    }

    /** Devuelve el código de la nueva reserva. */
    public function solicitar(array $d, ?int $clienteId): string
    {
        $this->validarFranja($d['fecha'], $d['hora']);
        return Database::transaction(function () use ($d, $clienteId) {
            if ($this->reservas->existeDuplicada($d['correo'], $d['fecha'], $d['hora'])) {
                throw new BusinessRuleException('Ya existe una solicitud con este correo para esa fecha y hora.');
            }
            if ($this->mesas->disponibles($d['fecha'], $d['hora'], (int) $d['personas'], self::DURACION_MIN) === []) {
                throw new BusinessRuleException('No tenemos mesas disponibles para ese grupo en ese horario. Prueba otra hora.');
            }
            $codigo = 'R-' . strtoupper(bin2hex(random_bytes(3)));
            $id = $this->reservas->crear($d, $clienteId, $codigo);
            AuditService::registrar('reserva_creada', 'reserva', $id, ['codigo' => $codigo, 'fecha' => $d['fecha'], 'hora' => $d['hora'], 'personas' => (int) $d['personas']]);
            return $codigo;
        });
    }

    public function actualizar(array $reserva, array $d): void
    {
        $id = (int) $reserva['id'];
        Database::transaction(function () use ($id, $d) {
            $actual = $this->reservas->bloquear($id);
            if (!in_array($actual['estado'], ['pendiente', 'confirmada'], true)) {
                throw new BusinessRuleException('Esta reserva ya no se puede modificar.');
            }
            if (Auth::es('cliente') && $actual['estado'] !== 'pendiente') {
                throw new BusinessRuleException('Tu reserva ya fue confirmada. Para cambiarla, cancélala y crea otra o llámanos.');
            }
            $this->validarFranja($d['fecha'], $d['hora']);
            if ($this->reservas->existeDuplicada($d['correo'], $d['fecha'], $d['hora'], $id)) {
                throw new BusinessRuleException('Ya existe una solicitud con este correo para esa fecha y hora.');
            }
            if ($actual['estado'] === 'confirmada') {
                $mesa = $this->mesas->bloquear((int) $actual['mesa_id']);
                $this->verificarMesa($mesa, $d['fecha'], $d['hora'], (int) $d['personas'], $id);
            }
            $this->reservas->actualizar($id, $d);
            AuditService::registrar('reserva_editada', 'reserva', $id, ['fecha' => $d['fecha'], 'hora' => $d['hora'], 'personas' => (int) $d['personas']]);
        });
    }

    public function confirmar(int $id, int $mesaId): void
    {
        Database::transaction(function () use ($id, $mesaId) {
            $reserva = $this->reservas->bloquear($id);
            if (!in_array($reserva['estado'], ['pendiente', 'confirmada'], true)) {
                throw new BusinessRuleException('Solo se pueden asignar mesas a reservas pendientes o confirmadas.');
            }
            // El bloqueo de la fila hace que dos confirmaciones simultáneas se atiendan en fila.
            $mesa = $this->mesas->bloquear($mesaId);
            $this->verificarMesa($mesa, $reserva['fecha'], $reserva['hora'], (int) $reserva['personas'], $id);
            $this->reservas->confirmar($id, $mesaId);
            AuditService::registrar('reserva_confirmada', 'reserva', $id, ['de' => $reserva['estado'], 'mesa_id' => $mesaId]);
        });
    }

    /** Cierra una reserva confirmada cuando ya llegó su hora: cumplida o no_asistio. */
    public function cerrar(int $id, string $estado): void
    {
        Database::transaction(function () use ($id, $estado) {
            $reserva = $this->reservas->bloquear($id);
            if ($reserva['estado'] !== 'confirmada') {
                throw new BusinessRuleException('Solo se pueden cerrar reservas confirmadas.');
            }
            if (new DateTimeImmutable($reserva['fecha'] . ' ' . $reserva['hora']) > new DateTimeImmutable()) {
                throw new BusinessRuleException('Aún no llega la hora de esta reserva.');
            }
            $this->reservas->cambiarEstado($id, $estado);
            AuditService::registrar('reserva_cerrada', 'reserva', $id, ['estado' => $estado]);
        });
    }

    public function cancelar(int $id, string $motivo): void
    {
        Database::transaction(function () use ($id, $motivo) {
            $reserva = $this->reservas->bloquear($id);
            if (!in_array($reserva['estado'], ['pendiente', 'confirmada'], true)) {
                throw new BusinessRuleException('Esta reserva ya no se puede cancelar.');
            }
            if (Auth::es('cliente')) {
                $limite = (new DateTimeImmutable($reserva['fecha'] . ' ' . $reserva['hora']))->modify('-' . self::CANCELACION_CLIENTE_HORAS . ' hours');
                if (new DateTimeImmutable() > $limite) {
                    throw new BusinessRuleException('Solo puedes cancelar hasta 2 horas antes. Comunícate con el restaurante.');
                }
            }
            $this->reservas->cambiarEstado($id, 'cancelada', $motivo ?: null);
            AuditService::registrar('reserva_cancelada', 'reserva', $id, ['de' => $reserva['estado'], 'motivo' => $motivo]);
        });
    }

    private function validarFranja(string $fecha, string $hora): void
    {
        $momento = new DateTimeImmutable("{$fecha} {$hora}");
        $ahora = new DateTimeImmutable();
        if ($momento < $ahora->modify('+' . self::ANTICIPACION_MIN . ' minutes')) {
            throw new BusinessRuleException('La reserva debe hacerse con al menos 1 hora de anticipación.');
        }
        if ($momento > $ahora->modify('+' . self::DIAS_MAXIMOS . ' days')) {
            throw new BusinessRuleException('Solo aceptamos reservas hasta con ' . self::DIAS_MAXIMOS . ' días de anticipación.');
        }
        [$abre, $cierra] = self::HORARIO[(int) $momento->format('N')];
        $apertura = new DateTimeImmutable("{$fecha} {$abre}");
        $ultimaEntrada = (new DateTimeImmutable("{$fecha} {$cierra}"))->modify('-' . self::ULTIMA_ENTRADA_ANTES_CIERRE_MIN . ' minutes');
        if ($momento < $apertura || $momento > $ultimaEntrada) {
            throw new BusinessRuleException('Ese día atendemos reservas entre ' . hora_12($apertura->format('H:i')) . ' y ' . hora_12($ultimaEntrada->format('H:i')) . '.');
        }
    }

    private function verificarMesa(?array $mesa, string $fecha, string $hora, int $personas, int $reservaId): void
    {
        if ($mesa === null || (int) $mesa['activa'] !== 1) {
            throw new BusinessRuleException('La mesa seleccionada no está disponible.');
        }
        if ((int) $mesa['capacidad'] < $personas) {
            throw new BusinessRuleException("La mesa {$mesa['numero']} solo tiene capacidad para {$mesa['capacidad']} personas.");
        }
        if ($this->mesas->hayConflicto((int) $mesa['id'], $fecha, $hora, self::DURACION_MIN, $reservaId)) {
            throw new BusinessRuleException("La mesa {$mesa['numero']} ya tiene otra reserva confirmada a menos de 2 horas de ese horario.");
        }
    }
}
