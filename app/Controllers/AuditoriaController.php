<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\AuditoriaModel;

final class AuditoriaController extends Controller
{
    public function index(): never
    {
        $r = $this->request;
        $modelo = new AuditoriaModel();
        $filtros = ['q' => $r->query('q'), 'entidad' => $r->query('entidad'), 'desde' => $this->fechaQuery('desde'), 'hasta' => $this->fechaQuery('hasta')];
        $this->vista('auditoria/index', [
            'titulo' => 'Auditoría',
            'lista' => $modelo->listar($filtros, $r->query('sort'), $r->query('dir'), $r->page()),
            'entidades' => $modelo->entidades(),
            'f' => $filtros,
        ]);
    }
}
