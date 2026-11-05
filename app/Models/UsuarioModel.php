<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Paginator;

final class UsuarioModel extends Model
{
    private const COLUMNAS = 'u.id, u.rol_id, u.nombre, u.correo, u.telefono, u.activo, u.creado_en, r.codigo AS rol_codigo, r.nombre AS rol_nombre';
    private const ORDEN = ['nombre' => 'u.nombre', 'correo' => 'u.correo', 'rol' => 'r.nombre', 'creado' => 'u.creado_en'];

    public function buscar(int $id): ?array
    {
        return $this->uno('SELECT ' . self::COLUMNAS . ' FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.id = ?', [$id]);
    }

    public function buscarActivo(int $id): ?array
    {
        return $this->uno('SELECT ' . self::COLUMNAS . ' FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE u.id = ? AND u.activo = 1', [$id]);
    }

    /** Único método que devuelve el hash; solo lo usan autenticación y cambio de contraseña. */
    public function credencialesPorCorreo(string $correo): ?array
    {
        return $this->uno('SELECT id, password_hash, activo FROM usuarios WHERE correo = ?', [$correo]);
    }

    public function hashDe(int $id): ?string
    {
        $hash = $this->escalar('SELECT password_hash FROM usuarios WHERE id = ?', [$id]);
        return is_string($hash) ? $hash : null;
    }

    public function correoEnUso(string $correo, int $excluirId = 0): bool
    {
        return (bool) $this->escalar('SELECT 1 FROM usuarios WHERE correo = ? AND id <> ?', [$correo, $excluirId]);
    }

    public function listar(string $q, string $rol, string $estado, string $sort, string $dir, int $pagina): Paginator
    {
        $where = ['1 = 1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(u.nombre LIKE ? OR u.correo LIKE ?)';
            array_push($params, "%{$q}%", "%{$q}%");
        }
        if ($rol !== '') {
            $where[] = 'r.codigo = ?';
            $params[] = $rol;
        }
        if ($estado === 'activo' || $estado === 'inactivo') {
            $where[] = 'u.activo = ?';
            $params[] = $estado === 'activo' ? 1 : 0;
        }
        $desde = 'FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE ' . implode(' AND ', $where);
        return $this->paginar(self::COLUMNAS, $desde, $params, $this->ordenSql(self::ORDEN, $sort, $dir, 'u.nombre ASC'), $pagina);
    }

    public function crear(array $d): int
    {
        return $this->insertar(
            'INSERT INTO usuarios (rol_id, nombre, correo, telefono, password_hash) VALUES (?, ?, ?, ?, ?)',
            [$d['rol_id'], $d['nombre'], $d['correo'], $d['telefono'] ?: null, $d['password_hash']]
        );
    }

    public function actualizar(int $id, array $d): void
    {
        $this->ejecutar(
            'UPDATE usuarios SET rol_id = ?, nombre = ?, correo = ?, telefono = ? WHERE id = ?',
            [$d['rol_id'], $d['nombre'], $d['correo'], $d['telefono'] ?: null, $id]
        );
    }

    public function actualizarPerfil(int $id, string $nombre, string $correo, string $telefono): void
    {
        $this->ejecutar('UPDATE usuarios SET nombre = ?, correo = ?, telefono = ? WHERE id = ?', [$nombre, $correo, $telefono ?: null, $id]);
    }

    public function cambiarPassword(int $id, string $hash): void
    {
        $this->ejecutar('UPDATE usuarios SET password_hash = ? WHERE id = ?', [$hash, $id]);
    }

    public function cambiarActivo(int $id, bool $activo): void
    {
        $this->ejecutar('UPDATE usuarios SET activo = ? WHERE id = ?', [$activo ? 1 : 0, $id]);
    }

    public function contarAdminsActivos(): int
    {
        return (int) $this->escalar("SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE r.codigo = 'admin' AND u.activo = 1");
    }

    public function roles(): array
    {
        return $this->todos('SELECT id, codigo, nombre FROM roles ORDER BY id');
    }

    public function idRol(string $codigo): int
    {
        return (int) $this->escalar('SELECT id FROM roles WHERE codigo = ?', [$codigo]);
    }

    /** @return array<int,string> id => nombre */
    public function opcionesPorRol(string $codigo): array
    {
        $filas = $this->todos(
            'SELECT u.id, u.nombre FROM usuarios u JOIN roles r ON r.id = u.rol_id WHERE r.codigo = ? AND u.activo = 1 ORDER BY u.nombre',
            [$codigo]
        );
        return array_column($filas, 'nombre', 'id');
    }
}
