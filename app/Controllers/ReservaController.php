<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\MesaModel;
use App\Models\ReservaModel;
use App\Models\UsuarioModel;
use App\Services\ReservaService;

final class ReservaController extends Controller
{
    private ReservaModel $reservas;
    private ReservaService $servicio;

    public function __construct(\App\Core\Request $request)
    {
        parent::__construct($request);
        $this->reservas = new ReservaModel();
        $this->servicio = new ReservaService();
    }

    /** Formulario público de la página principal; también lo usa un visitante sin cuenta. */
    public function solicitar(): never
    {
        $d = $this->validar(ReservaService::REGLAS, '/#reservas');
        $clienteId = Auth::es('cliente') ? Auth::id() : null;
        try {
            $codigo = $this->servicio->solicitar($d, $clienteId);
        } catch (\App\Core\BusinessRuleException $e) {
            \App\Core\Session::flash('old', $d);
            $this->redirigirConError('/#reservas', $e->getMessage());
        }
        $this->redirigir('/#reservas', "¡Recibimos tu solicitud! Tu código es {$codigo}. Te confirmaremos la mesa por teléfono o correo.");
    }

    public function index(): never
    {
        $r = $this->request;
        $filtros = ['q' => $r->query('q'), 'estado' => $r->query('estado'), 'desde' => $this->fechaQuery('desde'), 'hasta' => $this->fechaQuery('hasta')];
        $this->vista('reservas/index', [
            'titulo' => Auth::es('cliente') ? 'Mis reservas' : 'Reservas',
            'lista' => $this->reservas->listar($filtros, $r->query('sort'), $r->query('dir'), $r->page(), Auth::es('cliente') ? Auth::id() : null),
            'f' => $filtros,
        ]);
    }

    public function create(): never
    {
        $this->vista('reservas/form', ['titulo' => 'Nueva reserva', 'reserva' => null, 'clientes' => $this->clientesParaPersonal()]);
    }

    public function store(): never
    {
        $reglas = ReservaService::REGLAS + ['cliente_id' => ['Cliente', 'int']];
        $d = $this->validar($reglas, '/reservas/crear');
        // Un cliente siempre reserva a su nombre. El personal puede asociar un cliente registrado.
        if (Auth::es('cliente')) {
            $clienteId = Auth::id();
        } else {
            $clienteId = isset($this->clientesParaPersonal()[(int) $d['cliente_id']]) ? (int) $d['cliente_id'] : null;
        }
        $this->ejecutar(function () use ($d, $clienteId) {
            $this->servicio->solicitar($d, $clienteId);
        }, 'Reserva registrada.', '/reservas', '/reservas/crear');
    }

    public function show(int $id): never
    {
        $reserva = $this->autorizada($id);
        $mesas = [];
        if (can('reservas.aprobar') && in_array($reserva['estado'], ['pendiente', 'confirmada'], true)) {
            $mesas = (new MesaModel())->disponibles($reserva['fecha'], $reserva['hora'], (int) $reserva['personas'], ReservaService::DURACION_MIN, $id);
        }
        $this->vista('reservas/show', ['titulo' => "Reserva {$reserva['codigo']}", 'reserva' => $reserva, 'mesas' => $mesas]);
    }

    public function edit(int $id): never
    {
        $reserva = $this->autorizada($id);
        $this->vista('reservas/form', ['titulo' => "Editar reserva {$reserva['codigo']}", 'reserva' => $reserva, 'clientes' => []]);
    }

    public function update(int $id): never
    {
        $reserva = $this->autorizada($id);
        $destino = "/reservas/{$id}/editar";
        $d = $this->validar(ReservaService::REGLAS, $destino);
        $this->ejecutar(fn () => $this->servicio->actualizar($reserva, $d), 'Reserva actualizada.', "/reservas/{$id}", $destino);
    }

    public function confirmar(int $id): never
    {
        $this->autorizada($id);
        $d = $this->validar(['mesa_id' => ['Mesa', 'required|int']], "/reservas/{$id}");
        $this->ejecutar(fn () => $this->servicio->confirmar($id, (int) $d['mesa_id']), 'Reserva confirmada y mesa asignada.', "/reservas/{$id}");
    }

    public function cerrar(int $id): never
    {
        $this->autorizada($id);
        $d = $this->validar(['estado' => ['Estado', 'required|in:cumplida,no_asistio']], "/reservas/{$id}");
        $this->ejecutar(fn () => $this->servicio->cerrar($id, $d['estado']), 'Reserva cerrada.', "/reservas/{$id}");
    }

    public function cancelar(int $id): never
    {
        $this->autorizada($id);
        $d = $this->validar(['motivo' => ['Motivo', 'max:255']], "/reservas/{$id}");
        $this->ejecutar(fn () => $this->servicio->cancelar($id, $d['motivo']), 'Reserva cancelada.', "/reservas/{$id}");
    }

    /** Control de propiedad (RS-04): un cliente solo accede a sus propias reservas. */
    private function autorizada(int $id): array
    {
        $reserva = $this->reservas->buscar($id) ?? $this->noEncontrado();
        if (Auth::es('cliente') && (int) $reserva['cliente_id'] !== Auth::id()) {
            $this->denegar();
        }
        return $reserva;
    }

    private function clientesParaPersonal(): array
    {
        return Auth::es('cliente') ? [] : (new UsuarioModel())->opcionesPorRol('cliente');
    }
}
