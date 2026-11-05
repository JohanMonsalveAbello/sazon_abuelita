<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class CategoriaModel extends Model
{
    public function todas(): array
    {
        return $this->todos(
            'SELECT c.*, (SELECT COUNT(*) FROM platos p WHERE p.categoria_id = c.id AND p.eliminado_en IS NULL) AS platos
             FROM categorias c WHERE c.eliminado_en IS NULL ORDER BY c.orden, c.nombre'
        );
    }

    /** @return array<int,string> */
    public function opciones(): array
    {
        return array_column($this->todas(), 'nombre', 'id');
    }

    public function buscar(int $id): ?array
    {
        return $this->uno('SELECT * FROM categorias WHERE id = ? AND eliminado_en IS NULL', [$id]);
    }

    public function nombreEnUso(string $nombre, string $slug, int $excluirId = 0): bool
    {
        return (bool) $this->escalar('SELECT 1 FROM categorias WHERE (nombre = ? OR slug = ?) AND id <> ?', [$nombre, $slug, $excluirId]);
    }

    public function crear(string $nombre, string $slug, int $orden): int
    {
        return $this->insertar('INSERT INTO categorias (nombre, slug, orden) VALUES (?, ?, ?)', [$nombre, $slug, $orden]);
    }

    public function actualizar(int $id, string $nombre, string $slug, int $orden): void
    {
        $this->ejecutar('UPDATE categorias SET nombre = ?, slug = ?, orden = ? WHERE id = ?', [$nombre, $slug, $orden, $id]);
    }

    public function eliminar(int $id): void
    {
        $this->ejecutar('UPDATE categorias SET eliminado_en = NOW() WHERE id = ?', [$id]);
    }
}
