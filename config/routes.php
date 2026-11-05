<?php
/**
 * Mapa de rutas. Formato: $r->get|post(ruta, [Controlador, método], [middleware...]).
 * - 'guest': solo visitantes sin sesión. - 'auth': requiere sesión activa.
 * - 'perm:modulo.accion': requiere permiso (implica sesión).
 * El token CSRF se verifica de forma global en todo POST (ver Application).
 * La propiedad de cada registro se valida además dentro de los controladores.
 *
 * @var App\Core\Router $r
 */

use App\Controllers\AuditoriaController;
use App\Controllers\AuthController;
use App\Controllers\CategoriaController;
use App\Controllers\CocinaController;
use App\Controllers\HomeController;
use App\Controllers\MesaController;
use App\Controllers\OrdenController;
use App\Controllers\PanelController;
use App\Controllers\PerfilController;
use App\Controllers\PermisoController;
use App\Controllers\PlatoController;
use App\Controllers\ReporteController;
use App\Controllers\ResenaController;
use App\Controllers\ReservaController;
use App\Controllers\UsuarioController;

// Sitio público
$r->get('/', [HomeController::class, 'index']);
$r->post('/reservas/solicitar', [ReservaController::class, 'solicitar']);

// Autenticación
$r->get('/login', [AuthController::class, 'loginForm'], ['guest']);
$r->post('/login', [AuthController::class, 'login'], ['guest']);
$r->get('/registro', [AuthController::class, 'registroForm'], ['guest']);
$r->post('/registro', [AuthController::class, 'registro'], ['guest']);
$r->post('/logout', [AuthController::class, 'logout'], ['auth']);

// Panel y perfil (cualquier usuario autenticado)
$r->get('/panel', [PanelController::class, 'index'], ['auth']);
$r->get('/perfil', [PerfilController::class, 'edit'], ['auth']);
$r->post('/perfil', [PerfilController::class, 'update'], ['auth']);
$r->post('/perfil/password', [PerfilController::class, 'password'], ['auth']);

// Usuarios
$r->get('/usuarios', [UsuarioController::class, 'index'], ['perm:usuarios.ver']);
$r->get('/usuarios/crear', [UsuarioController::class, 'create'], ['perm:usuarios.crear']);
$r->post('/usuarios', [UsuarioController::class, 'store'], ['perm:usuarios.crear']);
$r->get('/usuarios/{id}/editar', [UsuarioController::class, 'edit'], ['perm:usuarios.editar']);
$r->post('/usuarios/{id}', [UsuarioController::class, 'update'], ['perm:usuarios.editar']);
$r->post('/usuarios/{id}/estado', [UsuarioController::class, 'toggle'], ['perm:usuarios.eliminar']);

// Matriz de permisos y auditoría
$r->get('/permisos', [PermisoController::class, 'index'], ['perm:permisos.ver']);
$r->get('/auditoria', [AuditoriaController::class, 'index'], ['perm:auditoria.ver']);

// Categorías
$r->get('/categorias', [CategoriaController::class, 'index'], ['perm:categorias.ver']);
$r->get('/categorias/crear', [CategoriaController::class, 'create'], ['perm:categorias.crear']);
$r->post('/categorias', [CategoriaController::class, 'store'], ['perm:categorias.crear']);
$r->get('/categorias/{id}/editar', [CategoriaController::class, 'edit'], ['perm:categorias.editar']);
$r->post('/categorias/{id}', [CategoriaController::class, 'update'], ['perm:categorias.editar']);
$r->post('/categorias/{id}/eliminar', [CategoriaController::class, 'destroy'], ['perm:categorias.eliminar']);

// Platos
$r->get('/platos', [PlatoController::class, 'index'], ['perm:platos.ver']);
$r->get('/platos/crear', [PlatoController::class, 'create'], ['perm:platos.crear']);
$r->post('/platos', [PlatoController::class, 'store'], ['perm:platos.crear']);
$r->get('/platos/{id}/editar', [PlatoController::class, 'edit'], ['perm:platos.editar']);
$r->post('/platos/{id}', [PlatoController::class, 'update'], ['perm:platos.editar']);
$r->post('/platos/{id}/disponibilidad', [PlatoController::class, 'disponibilidad'], ['perm:platos.aprobar']);
$r->post('/platos/{id}/eliminar', [PlatoController::class, 'destroy'], ['perm:platos.eliminar']);

// Mesas
$r->get('/mesas', [MesaController::class, 'index'], ['perm:mesas.ver']);
$r->get('/mesas/crear', [MesaController::class, 'create'], ['perm:mesas.crear']);
$r->post('/mesas', [MesaController::class, 'store'], ['perm:mesas.crear']);
$r->get('/mesas/{id}/editar', [MesaController::class, 'edit'], ['perm:mesas.editar']);
$r->post('/mesas/{id}', [MesaController::class, 'update'], ['perm:mesas.editar']);
$r->post('/mesas/{id}/eliminar', [MesaController::class, 'destroy'], ['perm:mesas.eliminar']);

// Reservas
$r->get('/reservas', [ReservaController::class, 'index'], ['perm:reservas.ver']);
$r->get('/reservas/crear', [ReservaController::class, 'create'], ['perm:reservas.crear']);
$r->post('/reservas', [ReservaController::class, 'store'], ['perm:reservas.crear']);
$r->get('/reservas/{id}', [ReservaController::class, 'show'], ['perm:reservas.ver']);
$r->get('/reservas/{id}/editar', [ReservaController::class, 'edit'], ['perm:reservas.editar']);
$r->post('/reservas/{id}', [ReservaController::class, 'update'], ['perm:reservas.editar']);
$r->post('/reservas/{id}/confirmar', [ReservaController::class, 'confirmar'], ['perm:reservas.aprobar']);
$r->post('/reservas/{id}/cerrar', [ReservaController::class, 'cerrar'], ['perm:reservas.aprobar']);
$r->post('/reservas/{id}/cancelar', [ReservaController::class, 'cancelar'], ['perm:reservas.eliminar']);

// Órdenes (proceso principal)
$r->get('/ordenes', [OrdenController::class, 'index'], ['perm:ordenes.ver']);
$r->get('/ordenes/crear', [OrdenController::class, 'create'], ['perm:ordenes.crear']);
$r->post('/ordenes', [OrdenController::class, 'store'], ['perm:ordenes.crear']);
$r->get('/ordenes/{id}', [OrdenController::class, 'show'], ['perm:ordenes.ver']);
$r->post('/ordenes/{id}/items', [OrdenController::class, 'agregarItem'], ['perm:ordenes.editar']);
$r->post('/ordenes/{id}/items/{item}/cantidad', [OrdenController::class, 'cantidadItem'], ['perm:ordenes.editar']);
$r->post('/ordenes/{id}/items/{item}/eliminar', [OrdenController::class, 'quitarItem'], ['perm:ordenes.editar']);
$r->post('/ordenes/{id}/enviar', [OrdenController::class, 'enviar'], ['perm:ordenes.editar']);
$r->post('/ordenes/{id}/servir', [OrdenController::class, 'servir'], ['perm:ordenes.editar']);
$r->post('/ordenes/{id}/nueva-ronda', [OrdenController::class, 'nuevaRonda'], ['perm:ordenes.editar']);
$r->post('/ordenes/{id}/cancelar', [OrdenController::class, 'cancelar'], ['perm:ordenes.eliminar']);
$r->post('/ordenes/{id}/reasignar', [OrdenController::class, 'reasignar'], ['perm:ordenes.aprobar']);
$r->get('/ordenes/{id}/cuenta', [OrdenController::class, 'cuenta'], ['perm:cuentas.ver']);
$r->post('/ordenes/{id}/cuenta', [OrdenController::class, 'cobrar'], ['perm:cuentas.crear']);

// Cocina
$r->get('/cocina', [CocinaController::class, 'index'], ['perm:cocina.ver']);
$r->get('/cocina/tablero', [CocinaController::class, 'tablero'], ['perm:cocina.ver']);
$r->post('/cocina/{id}/iniciar', [CocinaController::class, 'iniciar'], ['perm:cocina.aprobar']);
$r->post('/cocina/{id}/lista', [CocinaController::class, 'lista'], ['perm:cocina.aprobar']);

// Reseñas
$r->get('/resenas', [ResenaController::class, 'index'], ['perm:resenas.ver']);
$r->get('/resenas/crear/{id}', [ResenaController::class, 'create'], ['perm:resenas.crear']);
$r->post('/resenas/crear/{id}', [ResenaController::class, 'store'], ['perm:resenas.crear']);
$r->post('/resenas/{id}/moderar', [ResenaController::class, 'moderar'], ['perm:resenas.aprobar']);

// Reportes
$r->get('/reportes', [ReporteController::class, 'index'], ['perm:reportes.ver']);
$r->get('/reportes/exportar', [ReporteController::class, 'exportar'], ['perm:reportes.ver']);
