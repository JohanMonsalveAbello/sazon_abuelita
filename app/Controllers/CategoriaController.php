<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\BusinessRuleException;
use App\Core\Controller;
use App\Models\CategoriaModel;
use App\Services\AuditService;

final class CategoriaController extends Controller
{
    private const REGLAS = [
        'nombre' => ['Nombre', 'required|min:3|max:60'],
        'orden'  => ['Orden', 'required|between:0,999'],
    ];

    public function index(): never
    {
        $this->vista('categorias/index', ['titulo' => 'Categorías', 'lista' => (new CategoriaModel())->todas()]);
    }

    public function create(): never
    {
        $this->vista('categorias/form', ['titulo' => 'Nueva categoría', 'c' => null]);
    }

    public function store(): never
    {
        $d = $this->validar(self::REGLAS, '/categorias/crear');
        $modelo = new CategoriaModel();
        $this->ejecutar(function () use ($d, $modelo) {
            $slug = $this->slug($d['nombre']);
            if ($modelo->nombreEnUso($d['nombre'], $slug)) {
                throw new BusinessRuleException('Ya existe una categoría con ese nombre.');
            }
            $id = $modelo->crear($d['nombre'], $slug, (int) $d['orden']);
            AuditService::registrar('categoria_creada', 'categoria', $id, ['nombre' => $d['nombre']]);
        }, 'Categoría creada.', '/categorias', '/categorias/crear');
    }

    public function edit(int $id): never
    {
        $c = (new CategoriaModel())->buscar($id) ?? $this->noEncontrado();
        $this->vista('categorias/form', ['titulo' => 'Editar categoría', 'c' => $c]);
    }

    public function update(int $id): never
    {
        $modelo = new CategoriaModel();
        $modelo->buscar($id) ?? $this->noEncontrado();
        $destino = "/categorias/{$id}/editar";
        $d = $this->validar(self::REGLAS, $destino);
        $this->ejecutar(function () use ($d, $id, $modelo) {
            $slug = $this->slug($d['nombre']);
            if ($modelo->nombreEnUso($d['nombre'], $slug, $id)) {
                throw new BusinessRuleException('Ya existe una categoría con ese nombre.');
            }
            $modelo->actualizar($id, $d['nombre'], $slug, (int) $d['orden']);
            AuditService::registrar('categoria_editada', 'categoria', $id, ['nombre' => $d['nombre']]);
        }, 'Categoría actualizada.', '/categorias', $destino);
    }

    public function destroy(int $id): never
    {
        $modelo = new CategoriaModel();
        $c = $modelo->buscar($id) ?? $this->noEncontrado();
        $this->ejecutar(function () use ($id, $c, $modelo) {
            $conPlatos = array_filter($modelo->todas(), static fn ($x) => $x['id'] === $id && (int) $x['platos'] > 0);
            if ($conPlatos !== []) {
                throw new BusinessRuleException('No se puede eliminar una categoría que todavía tiene platos.');
            }
            $modelo->eliminar($id);
            AuditService::registrar('categoria_eliminada', 'categoria', $id, ['nombre' => $c['nombre']]);
        }, 'Categoría eliminada.', '/categorias');
    }

    private function slug(string $nombre): string
    {
        $texto = strtr(mb_strtolower($nombre), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $texto), '-');
        if ($slug === '') {
            throw new BusinessRuleException('El nombre debe incluir letras o números.');
        }
        return mb_substr($slug, 0, 40);
    }
}
