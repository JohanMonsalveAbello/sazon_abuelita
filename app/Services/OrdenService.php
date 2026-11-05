<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\BusinessRuleException;
use App\Core\Database;
use App\Models\MesaModel;
use App\Models\OrdenModel;
use App\Models\PlatoModel;
use App\Models\ReservaModel;
use App\Models\UsuarioModel;

/**
 * Proceso principal (RF-04): Mesero registra → Cocina prepara → Mesero sirve y cobra → Cliente consulta.
 *
 *   abierta → enviada → en_preparacion → lista → servida → pagada
 *                                                   ↳ abierta (nueva ronda)
 *   abierta | enviada → cancelada (o se descarta la ronda, si ya hubo una servida)
 *
 * Reglas:
 *  RN-06 Una mesa no puede tener dos órdenes activas ni abrirse si se aproxima una reserva ajena.
 *  RN-07 Una orden sin platos no va a cocina; solo se modifica la ronda aún no enviada.
 *  RN-08 No se piden platos agotados y su precio se congela al tomarlos.
 *  RN-09 Solo se cobra lo ya servido; el total se calcula siempre en el servidor.
 */
final class OrdenService
{
    public const PROPINA_MAXIMA = 20;
    private const TRANSICIONES = [
        'abierta'        => ['enviada', 'cancelada'],
        'enviada'        => ['en_preparacion', 'cancelada'],
        'en_preparacion' => ['lista'],
        'lista'          => ['servida'],
        'servida'        => ['abierta', 'pagada'],
        'pagada'         => [],
        'cancelada'      => [],
    ];

    public function __construct(
        private OrdenModel $ordenes = new OrdenModel(),
        private MesaModel $mesas = new MesaModel(),
        private PlatoModel $platos = new PlatoModel(),
        private ReservaModel $reservas = new ReservaModel(),
        private UsuarioModel $usuarios = new UsuarioModel()
    ) {
    }

    /** Abre una orden. Si viene de una reserva hereda mesa y cliente y la marca como cumplida. */
    public function abrir(int $mesaId, int $meseroId, ?int $clienteId, ?int $reservaId): int
    {
        return Database::transaction(function () use ($mesaId, $meseroId, $clienteId, $reservaId) {
            if ($reservaId !== null) {
                $reserva = $this->reservas->bloquear($reservaId);
                if ($reserva === null || $reserva['estado'] !== 'confirmada' || $reserva['fecha'] !== date('Y-m-d')) {
                    throw new BusinessRuleException('Solo se puede abrir una orden desde una reserva confirmada de hoy.');
                }
                $mesaId = (int) $reserva['mesa_id'];
                $clienteId = $reserva['cliente_id'] === null ? $clienteId : (int) $reserva['cliente_id'];
            }
            $mesa = $this->mesas->bloquear($mesaId);
            if ($mesa === null || (int) $mesa['activa'] !== 1) {
                throw new BusinessRuleException('La mesa seleccionada no está disponible.');
            }
            if ($this->ordenes->mesaTieneOrdenActiva($mesaId)) {
                throw new BusinessRuleException("La mesa {$mesa['numero']} ya tiene una orden activa.");
            }
            if ($reservaId === null && $this->reservas->mesaReservadaProximamente($mesaId, ReservaService::DURACION_MIN)) {
                throw new BusinessRuleException("La mesa {$mesa['numero']} está reservada para las próximas 2 horas. Elige otra o abre la orden desde la reserva.");
            }
            $codigo = 'O-' . strtoupper(bin2hex(random_bytes(3)));
            $id = $this->ordenes->crear($codigo, $mesaId, $meseroId, $clienteId, $reservaId);
            $this->ordenes->registrarHistorial($id, null, 'abierta', (int) Auth::id());
            if ($reservaId !== null) {
                $this->reservas->cambiarEstado($reservaId, 'cumplida');
            }
            AuditService::registrar('orden_abierta', 'orden', $id, ['codigo' => $codigo, 'mesa' => $mesa['numero'], 'reserva_id' => $reservaId]);
            return $id;
        });
    }

    public function agregarItem(int $ordenId, int $platoId, int $cantidad, string $nota): void
    {
        Database::transaction(function () use ($ordenId, $platoId, $cantidad, $nota) {
            $orden = $this->ordenEditable($ordenId);
            $plato = $this->platos->buscar($platoId);
            if ($plato === null || (int) $plato['disponible'] !== 1) {
                throw new BusinessRuleException('Ese plato no está disponible en este momento.');
            }
            $this->ordenes->agregarItem($ordenId, $platoId, (int) $orden['ronda_actual'], $cantidad, (int) $plato['precio'], $nota);
            AuditService::registrar('orden_item_agregado', 'orden', $ordenId, ['plato' => $plato['nombre'], 'cantidad' => $cantidad]);
        });
    }

    public function cambiarCantidadItem(int $ordenId, int $itemId, int $cantidad): void
    {
        Database::transaction(function () use ($ordenId, $itemId, $cantidad) {
            $orden = $this->ordenEditable($ordenId);
            $item = $this->itemDeRondaVigente($orden, $itemId);
            $this->ordenes->cantidadItem((int) $item['id'], $cantidad);
            AuditService::registrar('orden_item_editado', 'orden', $ordenId, ['item_id' => $itemId, 'de' => $item['cantidad'], 'a' => $cantidad]);
        });
    }

    public function quitarItem(int $ordenId, int $itemId): void
    {
        Database::transaction(function () use ($ordenId, $itemId) {
            $orden = $this->ordenEditable($ordenId);
            $item = $this->itemDeRondaVigente($orden, $itemId);
            $this->ordenes->eliminarItem((int) $item['id']);
            AuditService::registrar('orden_item_eliminado', 'orden', $ordenId, ['item_id' => $itemId, 'plato_id' => $item['plato_id']]);
        });
    }

    public function enviarACocina(int $id): void
    {
        Database::transaction(function () use ($id) {
            $orden = $this->ordenes->bloquear($id);
            if ($this->ordenes->contarItemsRonda($id, (int) $orden['ronda_actual']) === 0) {
                throw new BusinessRuleException('Agrega al menos un plato antes de enviar a cocina.');
            }
            $this->transicion($orden, 'enviada');
        });
    }

    public function iniciarPreparacion(int $id): void
    {
        Database::transaction(fn () => $this->transicion($this->ordenes->bloquear($id), 'en_preparacion'));
    }

    public function marcarLista(int $id): void
    {
        Database::transaction(fn () => $this->transicion($this->ordenes->bloquear($id), 'lista'));
    }

    public function servir(int $id): void
    {
        Database::transaction(fn () => $this->transicion($this->ordenes->bloquear($id), 'servida'));
    }

    /** La mesa pide algo más: la orden vuelve a edición con una ronda nueva. */
    public function nuevaRonda(int $id): void
    {
        Database::transaction(function () use ($id) {
            $orden = $this->ordenes->bloquear($id);
            $this->exigirTransicion($orden['estado'], 'abierta');
            $ronda = (int) $orden['ronda_actual'] + 1;
            $this->ordenes->cambiarRonda($id, 'abierta', $ronda);
            $this->ordenes->registrarHistorial($id, $orden['estado'], 'abierta', (int) Auth::id(), "Ronda {$ronda}");
            AuditService::registrar('orden_nueva_ronda', 'orden', $id, ['ronda' => $ronda]);
        });
    }

    /**
     * Cancela la orden. Si ya hubo rondas servidas no se pierden: se descarta solo la ronda
     * vigente y la orden vuelve a "servida" para poder cobrar lo consumido.
     */
    public function cancelar(int $id, string $motivo): void
    {
        Database::transaction(function () use ($id, $motivo) {
            $orden = $this->ordenes->bloquear($id);
            $this->exigirTransicion($orden['estado'], 'cancelada');
            $ronda = (int) $orden['ronda_actual'];
            if ($ronda > 1) {
                $this->ordenes->eliminarItemsRonda($id, $ronda);
                $this->ordenes->cambiarRonda($id, 'servida', $ronda - 1);
                $this->ordenes->registrarHistorial($id, $orden['estado'], 'servida', (int) Auth::id(), "Ronda {$ronda} descartada: {$motivo}");
                AuditService::registrar('orden_ronda_descartada', 'orden', $id, ['ronda' => $ronda, 'motivo' => $motivo]);
                return;
            }
            $this->ordenes->cambiarEstado($id, 'cancelada');
            $this->ordenes->registrarHistorial($id, $orden['estado'], 'cancelada', (int) Auth::id(), $motivo);
            AuditService::registrar('orden_cancelada', 'orden', $id, ['de' => $orden['estado'], 'motivo' => $motivo]);
        });
    }

    public function reasignar(int $id, int $meseroId): void
    {
        Database::transaction(function () use ($id, $meseroId) {
            $orden = $this->ordenes->bloquear($id);
            if (in_array($orden['estado'], ['pagada', 'cancelada'], true)) {
                throw new BusinessRuleException('No se puede reasignar una orden cerrada.');
            }
            if (!isset($this->usuarios->opcionesPorRol('mesero')[$meseroId])) {
                throw new BusinessRuleException('Selecciona un mesero activo.');
            }
            $this->ordenes->reasignar($id, $meseroId);
            AuditService::registrar('orden_reasignada', 'orden', $id, ['de' => $orden['mesero_id'], 'a' => $meseroId]);
        });
    }

    /** Valores de la cuenta calculados en el servidor; el navegador solo indica el % de propina. */
    public function calcularCuenta(int $subtotal, int $propinaPct): array
    {
        $impoconsumo = (int) round($subtotal * (float) config('app.impoconsumo'));
        $propina = (int) round($subtotal * $propinaPct / 100);
        return [
            'subtotal' => $subtotal,
            'impoconsumo' => $impoconsumo,
            'propina' => $propina,
            'total' => $subtotal + $impoconsumo + $propina,
        ];
    }

    /** Pago SIMULADO: no se conecta a ninguna pasarela ni guarda datos financieros. */
    public function cobrar(int $id, int $propinaPct, string $metodo): void
    {
        Database::transaction(function () use ($id, $propinaPct, $metodo) {
            $orden = $this->ordenes->bloquear($id);
            $this->exigirTransicion($orden['estado'], 'pagada');
            $c = $this->calcularCuenta($this->ordenes->subtotal($id), $propinaPct);
            $this->ordenes->crearCuenta($id, $c['subtotal'], $c['impoconsumo'], $c['propina'], $c['total'], $metodo, (int) Auth::id());
            $this->transicion($orden, 'pagada');
            AuditService::registrar('cuenta_cerrada', 'orden', $id, ['total' => $c['total'], 'metodo' => $metodo]);
        });
    }

    // ---------- Internos ----------

    private function ordenEditable(int $id): array
    {
        $orden = $this->ordenes->bloquear($id);
        if ($orden['estado'] !== 'abierta') {
            throw new BusinessRuleException('Esta orden ya salió hacia cocina y no se puede modificar. Espera a servirla y abre una nueva ronda.');
        }
        return $orden;
    }

    private function itemDeRondaVigente(array $orden, int $itemId): array
    {
        $item = $this->ordenes->buscarItem((int) $orden['id'], $itemId);
        if ($item === null || (int) $item['ronda'] !== (int) $orden['ronda_actual']) {
            throw new BusinessRuleException('Ese plato ya no se puede modificar.');
        }
        return $item;
    }

    private function exigirTransicion(string $de, string $a): void
    {
        if (!in_array($a, self::TRANSICIONES[$de] ?? [], true)) {
            $origen = OrdenModel::ESTADOS[$de] ?? $de;
            $destino = OrdenModel::ESTADOS[$a] ?? $a;
            throw new BusinessRuleException("Una orden en estado «{$origen}» no puede pasar a «{$destino}».");
        }
    }

    private function transicion(array $orden, string $nuevo): void
    {
        $this->exigirTransicion($orden['estado'], $nuevo);
        $this->ordenes->cambiarEstado((int) $orden['id'], $nuevo);
        $this->ordenes->registrarHistorial((int) $orden['id'], $orden['estado'], $nuevo, (int) Auth::id());
        AuditService::registrar('orden_estado', 'orden', (int) $orden['id'], ['de' => $orden['estado'], 'a' => $nuevo]);
    }
}
