<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BusinessRuleException;
use App\Core\Controller;
use App\Models\UsuarioModel;
use App\Services\AuditService;
use App\Services\AuthService;

/** Cada usuario edita sus datos y su contraseña. El rol no se puede cambiar desde aquí. */
final class PerfilController extends Controller
{
    public function edit(): never
    {
        $this->vista('perfil/edit', ['titulo' => 'Mi perfil', 'u' => Auth::user()]);
    }

    public function update(): never
    {
        $d = $this->validar([
            'nombre'   => ['Nombre', 'required|name|min:3|max:120'],
            'correo'   => ['Correo', 'required|email|max:120'],
            'telefono' => ['Teléfono', 'phone'],
        ], '/perfil');

        $id = (int) Auth::id();
        $usuarios = new UsuarioModel();
        $this->ejecutar(function () use ($d, $id, $usuarios) {
            if ($usuarios->correoEnUso($d['correo'], $id)) {
                throw new BusinessRuleException('Ese correo no está disponible.');
            }
            $usuarios->actualizarPerfil($id, $d['nombre'], $d['correo'], $d['telefono']);
            AuditService::registrar('perfil_editado', 'usuario', $id);
        }, 'Perfil actualizado.', '/perfil');
    }

    public function password(): never
    {
        $d = $this->validar([
            'password_actual'   => ['Contraseña actual', 'required|max:200'],
            'password'          => ['Nueva contraseña', 'required|password'],
            'password_confirma' => ['Confirmación', 'required|same:password'],
        ], '/perfil');

        $id = (int) Auth::id();
        $usuarios = new UsuarioModel();
        $this->ejecutar(function () use ($d, $id, $usuarios) {
            if (!password_verify($d['password_actual'], (string) $usuarios->hashDe($id))) {
                throw new BusinessRuleException('La contraseña actual no es correcta.');
            }
            $usuarios->cambiarPassword($id, AuthService::hash($d['password']));
            Auth::login($id); // Nuevo identificador de sesión tras cambiar la credencial.
            AuditService::registrar('password_cambiada', 'usuario', $id);
        }, 'Contraseña actualizada.', '/perfil');
    }
}
