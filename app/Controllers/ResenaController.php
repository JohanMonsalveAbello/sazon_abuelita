<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BusinessRuleException;
use App\Core\Controller;
use App\Models\OrdenModel;
use App\Models\ResenaModel;
use App\Services\AuditService;

/**
 * RN-10: solo opina quien consumió (orden propia y pagada), una vez por orden.
 * Las reseñas se publican únicamente tras la aprobación del administrador.
 */
final class ResenaController extends Controller
{
    public function index(): never
    {
        $r = $this->request;
        $this->vista('resenas/index', [
            'titulo' => Auth::es('cliente') ? 'Mis reseñas' : 'Reseñas',
            'lista' => (new ResenaModel())->listar($r->query('estado'), $r->page(), Auth::es('cliente') ? Auth::id() : null),
        ]);
    }

    public function create(int $id): never
    {
        $orden = $this->ordenCalificable($id);
        $this->vista('resenas/crear', ['titulo' => "Calificar la orden {$orden['codigo']}", 'orden' => $orden]);
    }

    public function store(int $id): never
    {
        $destino = "/resenas/crear/{$id}";
        $this->ordenCalificable($id);
        $d = $this->validar([
            'calificacion' => ['Calificación', 'required|between:1,5'],
            'comentario'   => ['Comentario', 'required|min:10|max:400'],
            'ciudad'       => ['Ciudad', 'required|name|max:60'],
        ], $destino);
        $this->ejecutar(function () use ($id, $d) {
            $resenaId = (new ResenaModel())->crear($id, (int) Auth::id(), (int) $d['calificacion'], $d['comentario'], $d['ciudad']);
            AuditService::registrar('resena_creada', 'resena', $resenaId, ['orden_id' => $id, 'calificacion' => (int) $d['calificacion']]);
        }, 'Gracias por tu opinión. Se publicará cuando sea aprobada.', '/resenas', $destino);
    }

    public function moderar(int $id): never
    {
        $modelo = new ResenaModel();
        $modelo->buscar($id) ?? $this->noEncontrado();
        $d = $this->validar(['estado' => ['Estado', 'required|in:aprobada,rechazada']], '/resenas');
        $modelo->moderar($id, $d['estado']);
        AuditService::registrar('resena_moderada', 'resena', $id, ['estado' => $d['estado']]);
        $this->redirigir('/resenas', $d['estado'] === 'aprobada' ? 'Reseña publicada.' : 'Reseña rechazada.');
    }

    private function ordenCalificable(int $id): array
    {
        $orden = (new OrdenModel())->buscar($id) ?? $this->noEncontrado();
        if ($orden['cliente_id'] !== Auth::id()) {
            $this->denegar();
        }
        if ($orden['estado'] !== 'pagada') {
            $this->redirigirConError("/ordenes/{$id}", 'Podrás calificar cuando la cuenta esté pagada.');
        }
        if ((new ResenaModel())->existeParaOrden($id)) {
            $this->redirigirConError('/resenas', 'Ya calificaste esta orden.');
        }
        return $orden;
    }
}
