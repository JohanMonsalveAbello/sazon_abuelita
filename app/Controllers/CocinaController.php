<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\View;
use App\Models\OrdenModel;
use App\Services\OrdenService;

/** Tablero de comandas: solo muestra lo necesario para cocinar (sin precios ni clientes). */
final class CocinaController extends Controller
{
    public function index(): never
    {
        $this->vista('cocina/index', ['titulo' => 'Tablero de cocina', 'comandas' => (new OrdenModel())->tableroCocina()]);
    }

    /** Fragmento HTML que el navegador recarga periódicamente sin refrescar la página. */
    public function tablero(): never
    {
        Response::html(View::fetch('cocina/_tablero', ['comandas' => (new OrdenModel())->tableroCocina()]));
    }

    public function iniciar(int $id): never
    {
        (new OrdenModel())->buscar($id) ?? $this->noEncontrado();
        $this->ejecutar(fn () => (new OrdenService())->iniciarPreparacion($id), 'Comanda en preparación.', '/cocina');
    }

    public function lista(int $id): never
    {
        (new OrdenModel())->buscar($id) ?? $this->noEncontrado();
        $this->ejecutar(fn () => (new OrdenService())->marcarLista($id), 'Comanda lista para servir.', '/cocina');
    }
}
