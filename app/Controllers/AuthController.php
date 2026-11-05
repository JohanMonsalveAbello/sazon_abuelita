<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\BusinessRuleException;
use App\Core\Controller;
use App\Models\UsuarioModel;
use App\Services\AuditService;
use App\Services\AuthService;

final class AuthController extends Controller
{
    public function loginForm(): never
    {
        $this->vista('auth/login', ['titulo' => 'Iniciar sesión'], 'acceso');
    }

    public function login(): never
    {
        $d = $this->validar([
            'correo'   => ['Correo', 'required|email|max:120'],
            'password' => ['Contraseña', 'required|max:200'],
        ], '/login');

        try {
            (new AuthService())->iniciarSesion($d['correo'], $d['password'], $this->request->ip());
        } catch (BusinessRuleException $e) {
            \App\Core\Session::flash('old', ['correo' => $d['correo']]);
            $this->redirigirConError('/login', $e->getMessage());
        }
        $this->redirigir('/panel');
    }

    public function registroForm(): never
    {
        $this->vista('auth/registro', ['titulo' => 'Crear cuenta'], 'acceso');
    }

    /** Autorregistro: el rol siempre es Cliente, sin importar lo que envíe el formulario (RF-03). */
    public function registro(): never
    {
        $d = $this->validar([
            'nombre'            => ['Nombre', 'required|name|min:3|max:120'],
            'correo'            => ['Correo', 'required|email|max:120'],
            'telefono'          => ['Teléfono', 'phone'],
            'password'          => ['Contraseña', 'required|password'],
            'password_confirma' => ['Confirmación', 'required|same:password'],
        ], '/registro');

        $usuarios = new UsuarioModel();
        $this->ejecutar(function () use ($d, $usuarios) {
            if ($usuarios->correoEnUso($d['correo'])) {
                throw new BusinessRuleException('No fue posible crear la cuenta con esos datos. Si ya tienes una, inicia sesión.');
            }
            $id = $usuarios->crear([
                'rol_id' => $usuarios->idRol('cliente'), 'nombre' => $d['nombre'], 'correo' => $d['correo'],
                'telefono' => $d['telefono'], 'password_hash' => AuthService::hash($d['password']),
            ]);
            Auth::login($id);
            AuditService::registrar('usuario_registrado', 'usuario', $id, ['rol' => 'cliente'], $id);
        }, '¡Bienvenido! Tu cuenta quedó creada.', '/panel', '/registro');
    }

    public function logout(): never
    {
        AuditService::registrar('logout', 'sesion', Auth::id());
        Auth::logout();
        $this->redirigir('/');
    }
}
