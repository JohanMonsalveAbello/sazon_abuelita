<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BusinessRuleException;
use App\Core\Controller;
use App\Models\MesaModel;
use App\Services\AuditService;

final class MesaController extends Controller
{
    private const REGLAS = [
        'numero'    => ['Número', 'required|between:1,999'],
        'capacidad' => ['Capacidad', 'required|between:1,20'],
        'zona'      => ['Zona', 'required|in:salon,terraza,privado'],
        'activa'    => ['Activa', 'in:0,1'],
    ];

    public function index(): never
    {
        $this->vista('mesas/index', ['titulo' => 'Mesas', 'lista' => (new MesaModel())->todas()]);
    }

    public function create(): never
    {
        $this->vista('mesas/form', ['titulo' => 'Nueva mesa', 'm' => null]);
    }

    public function store(): never
    {
        $d = $this->normalizar($this->validar(self::REGLAS, '/mesas/crear'));
        $modelo = new MesaModel();
        $this->ejecutar(function () use ($d, $modelo) {
            if ($modelo->numeroEnUso($d['numero'])) {
                throw new BusinessRuleException('Ya existe una mesa con ese número (puede estar eliminada).');
            }
            $id = $modelo->crear($d);
            AuditService::registrar('mesa_creada', 'mesa', $id, $d);
        }, 'Mesa creada.', '/mesas', '/mesas/crear');
    }

    public function edit(int $id): never
    {
        $m = (new MesaModel())->buscar($id) ?? $this->noEncontrado();
        $this->vista('mesas/form', ['titulo' => 'Editar mesa', 'm' => $m]);
    }

    public function update(int $id): never
    {
        $modelo = new MesaModel();
        $modelo->buscar($id) ?? $this->noEncontrado();
        $destino = "/mesas/{$id}/editar";
        $d = $this->normalizar($this->validar(self::REGLAS, $destino));
        $this->ejecutar(function () use ($d, $id, $modelo) {
            if ($modelo->numeroEnUso($d['numero'], $id)) {
                throw new BusinessRuleException('Ya existe una mesa con ese número (puede estar eliminada).');
            }
            if ($d['activa'] === 0 && $modelo->tieneCompromisos($id)) {
                throw new BusinessRuleException('No se puede desactivar: la mesa tiene una orden activa o reservas confirmadas.');
            }
            $modelo->actualizar($id, $d);
            AuditService::registrar('mesa_editada', 'mesa', $id, $d);
        }, 'Mesa actualizada.', '/mesas', $destino);
    }

    public function destroy(int $id): never
    {
        $modelo = new MesaModel();
        $m = $modelo->buscar($id) ?? $this->noEncontrado();
        $this->ejecutar(function () use ($id, $m, $modelo) {
            if ($modelo->tieneCompromisos($id)) {
                throw new BusinessRuleException('No se puede eliminar: la mesa tiene una orden activa o reservas confirmadas.');
            }
            $modelo->eliminar($id);
            AuditService::registrar('mesa_eliminada', 'mesa', $id, ['numero' => $m['numero']]);
        }, 'Mesa eliminada.', '/mesas');
    }

    private function normalizar(array $d): array
    {
        return ['numero' => (int) $d['numero'], 'capacidad' => (int) $d['capacidad'], 'zona' => $d['zona'], 'activa' => $d['activa'] === '1' ? 1 : 0];
    }
}
