<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BusinessRuleException;
use App\Core\Controller;
use App\Models\UsuarioModel;
use App\Services\AuditService;
use App\Services\AuthService;

/** Gestión de usuarios (RF-03). Solo llega aquí quien tenga el permiso usuarios.*. */
final class UsuarioController extends Controller
{
    private UsuarioModel $usuarios;

    public function __construct(\App\Core\Request $request)
    {
        parent::__construct($request);
        $this->usuarios = new UsuarioModel();
    }

    public function index(): never
    {
        $r = $this->request;
        $this->vista('usuarios/index', [
            'titulo' => 'Usuarios',
            'lista' => $this->usuarios->listar($r->query('q'), $r->query('rol'), $r->query('estado'), $r->query('sort'), $r->query('dir'), $r->page()),
            'roles' => $this->opcionesRol('codigo'),
        ]);
    }

    public function create(): never
    {
        $this->vista('usuarios/form', ['titulo' => 'Nuevo usuario', 'u' => null, 'roles' => $this->opcionesRol('id')]);
    }

    public function store(): never
    {
        $d = $this->validar($this->reglas(true), '/usuarios/crear');
        $this->ejecutar(function () use ($d) {
            if ($this->usuarios->correoEnUso($d['correo'])) {
                throw new BusinessRuleException('Ya existe un usuario con ese correo.');
            }
            $d['rol_id'] = (int) $d['rol_id'];
            $d['password_hash'] = AuthService::hash($d['password']);
            $id = $this->usuarios->crear($d);
            AuditService::registrar('usuario_creado', 'usuario', $id, ['correo' => $d['correo'], 'rol_id' => $d['rol_id']]);
        }, 'Usuario creado.', '/usuarios', '/usuarios/crear');
    }

    public function edit(int $id): never
    {
        $u = $this->usuarios->buscar($id) ?? $this->noEncontrado();
        $this->vista('usuarios/form', ['titulo' => 'Editar usuario', 'u' => $u, 'roles' => $this->opcionesRol('id')]);
    }

    public function update(int $id): never
    {
        $u = $this->usuarios->buscar($id) ?? $this->noEncontrado();
        $destino = "/usuarios/{$id}/editar";
        $d = $this->validar($this->reglas(false), $destino);
        $this->ejecutar(function () use ($d, $id, $u) {
            $d['rol_id'] = (int) $d['rol_id'];
            if ($id === Auth::id() && $d['rol_id'] !== (int) $u['rol_id']) {
                throw new BusinessRuleException('No puedes cambiar tu propio rol.');
            }
            if ($this->usuarios->correoEnUso($d['correo'], $id)) {
                throw new BusinessRuleException('Ya existe un usuario con ese correo.');
            }
            $this->usuarios->actualizar($id, $d);
            if ($d['password'] !== '') {
                $this->usuarios->cambiarPassword($id, AuthService::hash($d['password']));
            }
            AuditService::registrar('usuario_editado', 'usuario', $id, [
                'rol_anterior' => $u['rol_id'], 'rol_nuevo' => $d['rol_id'], 'clave_restablecida' => $d['password'] !== '',
            ]);
        }, 'Usuario actualizado.', '/usuarios', $destino);
    }

    /** Eliminación lógica: los usuarios se desactivan para conservar su historial. */
    public function toggle(int $id): never
    {
        $u = $this->usuarios->buscar($id) ?? $this->noEncontrado();
        $activar = (int) $u['activo'] !== 1;
        $this->ejecutar(function () use ($id, $u, $activar) {
            if ($id === Auth::id()) {
                throw new BusinessRuleException('No puedes desactivar tu propia cuenta.');
            }
            if (!$activar && $u['rol_codigo'] === 'admin' && $this->usuarios->contarAdminsActivos() <= 1) {
                throw new BusinessRuleException('Debe quedar al menos un administrador activo.');
            }
            $this->usuarios->cambiarActivo($id, $activar);
            AuditService::registrar($activar ? 'usuario_activado' : 'usuario_desactivado', 'usuario', $id);
        }, $activar ? 'Usuario activado.' : 'Usuario desactivado.', '/usuarios');
    }

    private function reglas(bool $nuevo): array
    {
        $ids = implode(',', array_column($this->usuarios->roles(), 'id'));
        return [
            'nombre'   => ['Nombre', 'required|name|min:3|max:120'],
            'correo'   => ['Correo', 'required|email|max:120'],
            'telefono' => ['Teléfono', 'phone'],
            'rol_id'   => ['Rol', "required|in:{$ids}"],
            'password' => ['Contraseña', ($nuevo ? 'required|' : '') . 'password'],
        ];
    }

    private function opcionesRol(string $clave): array
    {
        return array_column($this->usuarios->roles(), 'nombre', $clave);
    }
}
