<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Paginator;

final class PlatoModel extends Model
{
    private const ORDEN = ['nombre' => 'p.nombre', 'precio' => 'p.precio', 'categoria' => 'c.nombre'];

    public function listar(string $q, string $categoria, string $estado, string $sort, string $dir, int $pagina): Paginator
    {
        $where = ['p.eliminado_en IS NULL'];
        $params = [];
        if ($q !== '') {
            $where[] = '(p.nombre LIKE ? OR p.descripcion LIKE ?)';
            array_push($params, "%{$q}%", "%{$q}%");
        }
        if (ctype_digit($categoria)) {
            $where[] = 'p.categoria_id = ?';
            $params[] = (int) $categoria;
        }
        if ($estado === 'disponible' || $estado === 'agotado') {
            $where[] = 'p.disponible = ?';
            $params[] = $estado === 'disponible' ? 1 : 0;
        }
        $desde = 'FROM platos p JOIN categorias c ON c.id = p.categoria_id WHERE ' . implode(' AND ', $where);
        return $this->paginar('p.*, c.nombre AS categoria', $desde, $params, $this->ordenSql(self::ORDEN, $sort, $dir, 'c.orden ASC, p.nombre ASC'), $pagina);
    }

    /** Carta pública y selector de órdenes: solo lo que realmente se puede pedir. */
    public function carta(): array
    {
        return $this->todos(
            'SELECT p.*, c.slug AS categoria_slug, c.nombre AS categoria
             FROM platos p JOIN categorias c ON c.id = p.categoria_id
             WHERE p.eliminado_en IS NULL AND c.eliminado_en IS NULL AND p.disponible = 1
             ORDER BY p.id'
        );
    }

    public function destacado(): ?array
    {
        return $this->uno('SELECT * FROM platos WHERE destacado = 1 AND disponible = 1 AND eliminado_en IS NULL LIMIT 1');
    }

    public function buscar(int $id): ?array
    {
        return $this->uno('SELECT * FROM platos WHERE id = ? AND eliminado_en IS NULL', [$id]);
    }

    public function nombreEnUso(string $nombre, int $excluirId = 0): bool
    {
        return (bool) $this->escalar('SELECT 1 FROM platos WHERE nombre = ? AND id <> ?', [$nombre, $excluirId]);
    }

    public function crear(array $d): int
    {
        return $this->insertar(
            'INSERT INTO platos (categoria_id, nombre, descripcion, descripcion_larga, precio, imagen, etiqueta, tiempo_min, porciones, disponible, destacado)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$d['categoria_id'], $d['nombre'], $d['descripcion'], $d['descripcion_larga'] ?: null, $d['precio'], $d['imagen'],
             $d['etiqueta'] ?: null, $d['tiempo_min'], $d['porciones'], $d['disponible'], $d['destacado']]
        );
    }

    public function actualizar(int $id, array $d): void
    {
        $this->ejecutar(
            'UPDATE platos SET categoria_id = ?, nombre = ?, descripcion = ?, descripcion_larga = ?, precio = ?, imagen = ?,
             etiqueta = ?, tiempo_min = ?, porciones = ?, disponible = ?, destacado = ? WHERE id = ?',
            [$d['categoria_id'], $d['nombre'], $d['descripcion'], $d['descripcion_larga'] ?: null, $d['precio'], $d['imagen'],
             $d['etiqueta'] ?: null, $d['tiempo_min'], $d['porciones'], $d['disponible'], $d['destacado'], $id]
        );
    }

    /** Solo un plato puede ser "estrella" de la página principal. */
    public function quitarDestacado(int $excepto): void
    {
        $this->ejecutar('UPDATE platos SET destacado = 0 WHERE id <> ?', [$excepto]);
    }

    public function cambiarDisponible(int $id, bool $disponible): void
    {
        $this->ejecutar('UPDATE platos SET disponible = ? WHERE id = ?', [$disponible ? 1 : 0, $id]);
    }

    /** Eliminación lógica: las órdenes históricas conservan su plato. */
    public function eliminar(int $id): void
    {
        $this->ejecutar('UPDATE platos SET eliminado_en = NOW(), disponible = 0, destacado = 0 WHERE id = ?', [$id]);
    }
}
