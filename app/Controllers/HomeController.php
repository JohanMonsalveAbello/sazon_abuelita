<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CategoriaModel;
use App\Models\PlatoModel;
use App\Models\ResenaModel;

/** Página pública: conserva el diseño original, con contenido servido desde MySQL. */
final class HomeController extends Controller
{
    public function index(): never
    {
        $platos = new PlatoModel();
        $resenas = new ResenaModel();
        $this->vista('home/index', [
            'categorias'  => (new CategoriaModel())->todas(),
            'platos'      => $platos->carta(),
            'destacado'   => $platos->destacado(),
            'testimonios' => $resenas->publicas(2),
            'indicadores' => $resenas->indicadores(),
            'anios'       => (int) date('Y') - (int) config('app.fundacion'),
        ], 'publico');
    }
}
