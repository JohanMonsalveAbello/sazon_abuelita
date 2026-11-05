<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\MesaModel;
use App\Models\OrdenModel;
use App\Models\PlatoModel;
use App\Models\ReporteModel;
use App\Models\ReservaModel;
use App\Models\ResenaModel;
use App\Models\UsuarioModel;
use App\Services\OrdenService;

final class OrdenController extends Controller
{
    private OrdenModel $ordenes;
    private OrdenService $servicio;

    public function __construct(\App\Core\Request $request)
    {
        parent::__construct($request);
        $this->ordenes = new OrdenModel();
        $this->servicio = new OrdenService();
    }

    public function index(): never
    {
        $r = $this->request;
        $filtros = ['q' => $r->query('q'), 'estado' => $r->query('estado'), 'desde' => $this->fechaQuery('desde'), 'hasta' => $this->fechaQuery('hasta')];
        $alcance = match (Auth::rol()) {
            'mesero'  => ['mesero_id' => Auth::id()],
            'cliente' => ['cliente_id' => Auth::id()],
            default   => [],
        };
        $this->vista('ordenes/index', [
            'titulo' => Auth::es('cliente') ? 'Mis consumos' : 'Órdenes',
            'lista' => $this->ordenes->listar($filtros, $r->query('sort'), $r->query('dir'), $r->page(), $alcance),
            'f' => $filtros,
        ]);
    }

    public function create(): never
    {
        $reservaId = (int) $this->request->query('reserva', '0');
        $reserva = $reservaId > 0 ? (new ReservaModel())->buscar($reservaId) : null;
        $usuarios = new UsuarioModel();
        $this->vista('ordenes/crear', [
            'titulo' => 'Abrir orden',
            'reserva' => $reserva,
            'mesas' => (new MesaModel())->opcionesLibres(),
            'clientes' => $usuarios->opcionesPorRol('cliente'),
            'meseros' => Auth::es('admin') ? $usuarios->opcionesPorRol('mesero') : [],
        ]);
    }

    public function store(): never
    {
        $d = $this->validar([
            'mesa_id'    => ['Mesa', 'int'],
            'mesero_id'  => ['Mesero', 'int'],
            'cliente_id' => ['Cliente', 'int'],
            'reserva_id' => ['Reserva', 'int'],
        ], '/ordenes/crear');

        $usuarios = new UsuarioModel();
        // Un mesero solo abre órdenes a su nombre; el administrador asigna a un mesero activo.
        $meseroId = (int) Auth::id();
        if (Auth::es('admin')) {
            $meseroId = (int) $d['mesero_id'];
            if (!isset($usuarios->opcionesPorRol('mesero')[$meseroId])) {
                $this->redirigirConError('/ordenes/crear', 'Selecciona el mesero que atenderá la orden.');
            }
        }
        $reservaId = $d['reserva_id'] !== '' ? (int) $d['reserva_id'] : null;
        if ($reservaId === null && $d['mesa_id'] === '') {
            $this->redirigirConError('/ordenes/crear', 'Selecciona una mesa.');
        }
        $clienteId = isset($usuarios->opcionesPorRol('cliente')[(int) $d['cliente_id']]) ? (int) $d['cliente_id'] : null;

        $nueva = 0;
        try {
            $nueva = $this->servicio->abrir((int) $d['mesa_id'], $meseroId, $clienteId, $reservaId);
        } catch (\App\Core\BusinessRuleException $e) {
            $this->redirigirConError('/ordenes/crear', $e->getMessage());
        }
        $this->redirigir("/ordenes/{$nueva}", 'Orden abierta. Agrega los platos.');
    }

    public function show(int $id): never
    {
        $orden = $this->autorizada($id);
        $editable = can('ordenes.editar') && $orden['estado'] === 'abierta';
        $this->vista('ordenes/show', [
            'titulo' => "Orden {$orden['codigo']}",
            'orden' => $orden,
            'items' => $this->ordenes->items($id),
            'historial' => $this->ordenes->historial($id),
            'carta' => $editable ? (new PlatoModel())->carta() : [],
            'editable' => $editable,
            'meseros' => can('ordenes.aprobar') ? (new UsuarioModel())->opcionesPorRol('mesero') : [],
            'tieneResena' => (new ResenaModel())->existeParaOrden($id),
        ]);
    }

    public function agregarItem(int $id): never
    {
        $this->autorizada($id);
        $d = $this->validar([
            'plato_id' => ['Plato', 'required|int'],
            'cantidad' => ['Cantidad', 'required|between:1,50'],
            'nota'     => ['Nota', 'max:160'],
        ], "/ordenes/{$id}");
        $this->ejecutar(fn () => $this->servicio->agregarItem($id, (int) $d['plato_id'], (int) $d['cantidad'], $d['nota']), 'Plato agregado.', "/ordenes/{$id}");
    }

    public function cantidadItem(int $id, int $item): never
    {
        $this->autorizada($id);
        $d = $this->validar(['cantidad' => ['Cantidad', 'required|between:1,50']], "/ordenes/{$id}");
        $this->ejecutar(fn () => $this->servicio->cambiarCantidadItem($id, $item, (int) $d['cantidad']), 'Cantidad actualizada.', "/ordenes/{$id}");
    }

    public function quitarItem(int $id, int $item): never
    {
        $this->autorizada($id);
        $this->ejecutar(fn () => $this->servicio->quitarItem($id, $item), 'Plato retirado de la orden.', "/ordenes/{$id}");
    }

    public function enviar(int $id): never
    {
        $this->autorizada($id);
        $this->ejecutar(fn () => $this->servicio->enviarACocina($id), 'Orden enviada a cocina.', "/ordenes/{$id}");
    }

    public function servir(int $id): never
    {
        $this->autorizada($id);
        $this->ejecutar(fn () => $this->servicio->servir($id), 'Orden marcada como servida.', "/ordenes/{$id}");
    }

    public function nuevaRonda(int $id): never
    {
        $this->autorizada($id);
        $this->ejecutar(fn () => $this->servicio->nuevaRonda($id), 'Nueva ronda abierta.', "/ordenes/{$id}");
    }

    public function cancelar(int $id): never
    {
        $this->autorizada($id);
        $d = $this->validar(['motivo' => ['Motivo', 'required|min:3|max:255']], "/ordenes/{$id}");
        $this->ejecutar(fn () => $this->servicio->cancelar($id, $d['motivo']), 'Cancelación registrada.', "/ordenes/{$id}");
    }

    public function reasignar(int $id): never
    {
        $this->autorizada($id);
        $d = $this->validar(['mesero_id' => ['Mesero', 'required|int']], "/ordenes/{$id}");
        $this->ejecutar(fn () => $this->servicio->reasignar($id, (int) $d['mesero_id']), 'Orden reasignada.', "/ordenes/{$id}");
    }

    public function cuenta(int $id): never
    {
        $orden = $this->autorizada($id);
        if (!in_array($orden['estado'], ['servida', 'pagada'], true)) {
            $this->redirigirConError("/ordenes/{$id}", 'La cuenta está disponible cuando la orden ya fue servida.');
        }
        $this->vista('ordenes/cuenta', [
            'titulo' => "Cuenta de la orden {$orden['codigo']}",
            'orden' => $orden,
            'items' => $this->ordenes->items($id),
            'cuenta' => $this->ordenes->buscarCuenta($id),
            'previa' => $this->servicio->calcularCuenta($this->ordenes->subtotal($id), 0),
            'metodos' => ReporteModel::METODOS,
        ]);
    }

    public function cobrar(int $id): never
    {
        $this->autorizada($id);
        $destino = "/ordenes/{$id}/cuenta";
        $d = $this->validar([
            'propina' => ['Propina', 'required|between:0,' . OrdenService::PROPINA_MAXIMA],
            'metodo'  => ['Método de pago', 'required|in:' . implode(',', array_keys(ReporteModel::METODOS))],
        ], $destino);
        $this->ejecutar(fn () => $this->servicio->cobrar($id, (int) $d['propina'], $d['metodo']), 'Pago simulado registrado. Mesa liberada.', $destino, "/ordenes/{$id}");
    }

    /**
     * Control de propiedad (RS-04): el mesero solo opera sus órdenes y el cliente
     * solo consulta las suyas. El administrador supervisa todas.
     */
    private function autorizada(int $id): array
    {
        $orden = $this->ordenes->buscar($id) ?? $this->noEncontrado();
        $esPropia = match (Auth::rol()) {
            'admin'   => true,
            'mesero'  => $orden['mesero_id'] === Auth::id(),
            'cliente' => $orden['cliente_id'] === Auth::id(),
            default   => false,
        };
        if (!$esPropia) {
            $this->denegar();
        }
        return $orden;
    }
}
