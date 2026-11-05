<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PermisoModel;

/** Muestra la matriz real de permisos, leída de la misma tabla que usa el middleware. */
final class PermisoController extends Controller
{
    public function index(): never
    {
        $matriz = [];
        foreach ((new PermisoModel())->matriz() as $p) {
            $matriz[$p['modulo']][$p['accion']][$p['rol']] = true;
        }
        $this->vista('permisos/index', ['titulo' => 'Matriz de permisos', 'matriz' => $matriz]);
    }
}
