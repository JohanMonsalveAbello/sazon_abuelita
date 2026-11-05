<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BusinessRuleException;
use App\Core\Controller;
use App\Core\Database;
use App\Models\CategoriaModel;
use App\Models\PlatoModel;
use App\Services\AuditService;
use App\Services\ImagenService;

final class PlatoController extends Controller
{
    public function index(): never
    {
        $r = $this->request;
        $this->vista('platos/index', [
            'titulo' => 'Platos',
            'lista' => (new PlatoModel())->listar($r->query('q'), $r->query('categoria'), $r->query('estado'), $r->query('sort'), $r->query('dir'), $r->page()),
            'categorias' => (new CategoriaModel())->opciones(),
        ]);
    }

    public function create(): never
    {
        $this->vista('platos/form', ['titulo' => 'Nuevo plato', 'p' => null, 'categorias' => (new CategoriaModel())->opciones()]);
    }

    public function store(): never
    {
        $d = $this->validar($this->reglas(), '/platos/crear');
        $this->ejecutar(function () use ($d) {
            $imagen = (new ImagenService())->guardar($this->request->file('imagen'));
            if ($imagen === null) {
                throw new BusinessRuleException('Debes subir una foto del plato.');
            }
            $this->guardar(0, $d, $imagen);
        }, 'Plato creado.', '/platos', '/platos/crear');
    }

    public function edit(int $id): never
    {
        $p = (new PlatoModel())->buscar($id) ?? $this->noEncontrado();
        $this->vista('platos/form', ['titulo' => 'Editar plato', 'p' => $p, 'categorias' => (new CategoriaModel())->opciones()]);
    }

    public function update(int $id): never
    {
        $p = (new PlatoModel())->buscar($id) ?? $this->noEncontrado();
        $destino = "/platos/{$id}/editar";
        $d = $this->validar($this->reglas(), $destino);
        $this->ejecutar(function () use ($d, $id, $p) {
            $imagen = (new ImagenService())->guardar($this->request->file('imagen')) ?? $p['imagen'];
            $this->guardar($id, $d, $imagen);
        }, 'Plato actualizado.', '/platos', $destino);
    }

    /** Cocina marca un plato como agotado o disponible; deja de ofrecerse de inmediato. */
    public function disponibilidad(int $id): never
    {
        $modelo = new PlatoModel();
        $p = $modelo->buscar($id) ?? $this->noEncontrado();
        $disponible = (int) $p['disponible'] !== 1;
        $modelo->cambiarDisponible($id, $disponible);
        AuditService::registrar('plato_disponibilidad', 'plato', $id, ['plato' => $p['nombre'], 'disponible' => $disponible]);
        $destino = '/platos' . ($this->request->input('volver') === 'cocina' ? '?estado=agotado' : '');
        $this->redirigir($destino, $disponible ? "«{$p['nombre']}» disponible de nuevo." : "«{$p['nombre']}» marcado como agotado.");
    }

    public function destroy(int $id): never
    {
        $modelo = new PlatoModel();
        $p = $modelo->buscar($id) ?? $this->noEncontrado();
        $modelo->eliminar($id);
        AuditService::registrar('plato_eliminado', 'plato', $id, ['plato' => $p['nombre']]);
        $this->redirigir('/platos', 'Plato eliminado. Las órdenes anteriores lo conservan en su historial.');
    }

    private function guardar(int $id, array $d, string $imagen): void
    {
        $modelo = new PlatoModel();
        if ($modelo->nombreEnUso($d['nombre'], $id)) {
            throw new BusinessRuleException('Ya existe un plato con ese nombre (puede estar eliminado).');
        }
        if (!isset((new CategoriaModel())->opciones()[(int) $d['categoria_id']])) {
            throw new BusinessRuleException('Selecciona una categoría válida.');
        }
        $d['categoria_id'] = (int) $d['categoria_id'];
        $d['precio'] = (int) $d['precio'];
        $d['tiempo_min'] = (int) $d['tiempo_min'];
        $d['disponible'] = $d['disponible'] === '1' ? 1 : 0;
        $d['destacado'] = $d['destacado'] === '1' ? 1 : 0;
        $d['imagen'] = $imagen;
        Database::transaction(function () use ($modelo, &$id, $d) {
            $nuevo = $id === 0;
            if ($nuevo) {
                $id = $modelo->crear($d);
            } else {
                $modelo->actualizar($id, $d);
            }
            if ($d['destacado'] === 1) {
                $modelo->quitarDestacado($id);
            }
            AuditService::registrar($nuevo ? 'plato_creado' : 'plato_editado', 'plato', $id, ['nombre' => $d['nombre'], 'precio' => $d['precio']]);
        });
    }

    private function reglas(): array
    {
        return [
            'nombre'       => ['Nombre', 'required|min:3|max:80'],
            'categoria_id' => ['Categoría', 'required|int'],
            'descripcion'  => ['Descripción', 'required|min:10|max:255'],
            'descripcion_larga' => ['Descripción destacada', 'max:500'],
            'precio'       => ['Precio', 'required|between:500,2000000'],
            'etiqueta'     => ['Etiqueta', 'max:20'],
            'tiempo_min'   => ['Tiempo', 'required|between:1,240'],
            'porciones'    => ['Porciones', 'required|max:20'],
            'disponible'   => ['Disponible', 'in:0,1'],
            'destacado'    => ['Destacado', 'in:0,1'],
        ];
    }
}
