<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;

function env(string $clave, ?string $porDefecto = null): ?string
{
    return App\Core\Env::get($clave, $porDefecto);
}

/** Lee configuración con notación archivo.clave, p. ej. config('app.name'). */
function config(string $ruta, mixed $porDefecto = null): mixed
{
    static $cache = [];
    [$archivo, $clave] = array_pad(explode('.', $ruta, 2), 2, null);
    $cache[$archivo] ??= require BASE_PATH . "/config/{$archivo}.php";
    return $clave === null ? $cache[$archivo] : ($cache[$archivo][$clave] ?? $porDefecto);
}

/** Escapa toda salida HTML (RS-06). */
function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $ruta = '/'): string
{
    return Request::basePath() . '/' . ltrim($ruta, '/');
}

function asset(string $ruta): string
{
    return url($ruta);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function partial(string $vista, array $datos = []): string
{
    return View::fetch('partials/' . $vista, $datos);
}

function can(string $permiso): bool
{
    return Auth::can($permiso);
}

function usuario(): ?array
{
    return Auth::user();
}

// ---------- Datos de formularios tras una redirección (errores y valores previos) ----------

function errores(): array
{
    static $errores = null;
    return $errores ??= Session::pull('errors', []);
}

function old(string $campo, mixed $porDefecto = ''): mixed
{
    static $viejos = null;
    $viejos ??= Session::pull('old', []);
    return $viejos[$campo] ?? $porDefecto;
}

// ---------- Campos de formulario Bootstrap (etiqueta, error y accesibilidad en un solo lugar) ----------

function atributos(array $attrs): string
{
    $html = '';
    foreach ($attrs as $nombre => $valor) {
        if ($valor === false || $valor === null) {
            continue;
        }
        $html .= $valor === true ? ' ' . e($nombre) : ' ' . e($nombre) . '="' . e($valor) . '"';
    }
    return $html;
}

function etiqueta_campo(string $id, string $label, array $attrs): string
{
    $req = !empty($attrs['required']) ? ' <span class="text-danger" aria-hidden="true">*</span>' : '';
    return '<label class="form-label" for="' . e($id) . '">' . e($label) . $req . '</label>';
}

function error_campo(string $nombre, string $id): string
{
    $msg = errores()[$nombre] ?? '';
    return '<div class="invalid-feedback" id="' . e($id) . '-error">' . e($msg) . '</div>';
}

function attrs_error(string $nombre, string $id, string $clase): array
{
    $hayError = isset(errores()[$nombre]);
    return [
        'class' => $clase . ($hayError ? ' is-invalid' : ''),
        'aria-invalid' => $hayError ? 'true' : null,
        'aria-describedby' => $id . '-error',
    ];
}

function campo(string $nombre, string $label, mixed $valor = '', array $attrs = []): string
{
    $id = 'campo-' . $nombre;
    $esPassword = ($attrs['type'] ?? '') === 'password';
    $attrs = $attrs + ['type' => 'text', 'id' => $id, 'name' => $nombre,
        'value' => $esPassword ? '' : old($nombre, $valor)] + attrs_error($nombre, $id, 'form-control');
    return '<div class="mb-3">' . etiqueta_campo($id, $label, $attrs)
        . '<input' . atributos($attrs) . '>' . error_campo($nombre, $id) . '</div>';
}

function campo_area(string $nombre, string $label, mixed $valor = '', array $attrs = []): string
{
    $id = 'campo-' . $nombre;
    $attrs = ['id' => $id, 'name' => $nombre, 'rows' => 3] + attrs_error($nombre, $id, 'form-control') + $attrs;
    return '<div class="mb-3">' . etiqueta_campo($id, $label, $attrs)
        . '<textarea' . atributos($attrs) . '>' . e(old($nombre, $valor)) . '</textarea>'
        . error_campo($nombre, $id) . '</div>';
}

/** @param array<int|string,string> $opciones valor => texto */
function campo_select(string $nombre, string $label, array $opciones, mixed $valor = '', array $attrs = [], ?string $vacio = 'Seleccionar'): string
{
    $id = 'campo-' . $nombre;
    $actual = (string) old($nombre, $valor);
    $attrs = ['id' => $id, 'name' => $nombre] + attrs_error($nombre, $id, 'form-select') + $attrs;
    $html = $vacio !== null ? '<option value="">' . e($vacio) . '</option>' : '';
    foreach ($opciones as $v => $t) {
        $html .= '<option value="' . e($v) . '"' . ((string) $v === $actual ? ' selected' : '') . '>' . e($t) . '</option>';
    }
    return '<div class="mb-3">' . etiqueta_campo($id, $label, $attrs)
        . '<select' . atributos($attrs) . '>' . $html . '</select>' . error_campo($nombre, $id) . '</div>';
}

function campo_check(string $nombre, string $label, bool $marcado = false): string
{
    $id = 'campo-' . $nombre;
    $check = (bool) old($nombre, $marcado) ? ' checked' : '';
    return '<div class="form-check mb-3"><input type="hidden" name="' . e($nombre) . '" value="0">'
        . '<input class="form-check-input" type="checkbox" value="1" id="' . e($id) . '" name="' . e($nombre) . '"' . $check . '>'
        . '<label class="form-check-label" for="' . e($id) . '">' . e($label) . '</label></div>';
}

// ---------- Formato ----------

function dinero(int|float|string|null $valor): string
{
    return '$' . number_format((float) $valor, 0, ',', '.');
}

function fecha_corta(?string $valor): string
{
    return $valor ? date('d/m/Y', strtotime($valor)) : '—';
}

function fecha_hora(?string $valor): string
{
    return $valor ? date('d/m/Y g:i A', strtotime($valor)) : '—';
}

function hora_12(?string $valor): string
{
    return $valor ? date('g:i A', strtotime($valor)) : '—';
}

// ---------- Listados: conservar filtros en enlaces, ordenar y paginar ----------

function url_lista(array $cambios = []): string
{
    $query = array_filter(
        array_merge($_GET, $cambios),
        static fn ($v) => is_scalar($v) && $v !== ''
    );
    $ruta = Request::current()->path();
    return url($ruta) . ($query ? '?' . http_build_query($query) : '');
}

/** Encabezado de tabla que ordena por columna. Devuelve el contenido de un <th>. */
function orden_columna(string $campo, string $label): string
{
    $sort = $_GET['sort'] ?? '';
    $dir  = ($_GET['dir'] ?? '') === 'desc' ? 'desc' : 'asc';
    $activo = $sort === $campo;
    $siguiente = $activo && $dir === 'asc' ? 'desc' : 'asc';
    $icono = $activo ? ($dir === 'asc' ? ' <i class="bi bi-arrow-up" aria-hidden="true"></i>' : ' <i class="bi bi-arrow-down" aria-hidden="true"></i>') : '';
    $texto = $activo ? ($dir === 'asc' ? ' (ascendente)' : ' (descendente)') : '';
    return '<a class="enlace-orden" href="' . e(url_lista(['sort' => $campo, 'dir' => $siguiente, 'page' => 1])) . '">'
        . e($label) . $icono . '<span class="visually-hidden">' . e($texto) . '</span></a>';
}

/** Insignia de estado: siempre icono + texto, nunca solo color. */
function insignia_estado(string $estado): string
{
    static $mapa = [
        'pendiente'      => ['Pendiente', 'bi-hourglass-split', 'estado-aviso'],
        'confirmada'     => ['Confirmada', 'bi-check-circle', 'estado-info'],
        'cumplida'       => ['Cumplida', 'bi-check2-all', 'estado-ok'],
        'no_asistio'     => ['No asistió', 'bi-person-x', 'estado-neutro'],
        'cancelada'      => ['Cancelada', 'bi-x-circle', 'estado-neutro'],
        'abierta'        => ['Abierta', 'bi-pencil', 'estado-aviso'],
        'enviada'        => ['En cola de cocina', 'bi-send', 'estado-info'],
        'en_preparacion' => ['En preparación', 'bi-fire', 'estado-fuego'],
        'lista'          => ['Lista para servir', 'bi-bell', 'estado-ok'],
        'servida'        => ['Servida', 'bi-cup-hot', 'estado-info'],
        'pagada'         => ['Pagada', 'bi-cash-coin', 'estado-ok'],
        'aprobada'       => ['Aprobada', 'bi-check-circle', 'estado-ok'],
        'rechazada'      => ['Rechazada', 'bi-x-circle', 'estado-neutro'],
        'activo'         => ['Activo', 'bi-check-circle', 'estado-ok'],
        'inactivo'       => ['Inactivo', 'bi-slash-circle', 'estado-neutro'],
        'disponible'     => ['Disponible', 'bi-check-circle', 'estado-ok'],
        'agotado'        => ['Agotado', 'bi-slash-circle', 'estado-fuego'],
    ];
    [$texto, $icono, $clase] = $mapa[$estado] ?? [$estado, 'bi-dot', 'estado-neutro'];
    return '<span class="estado ' . $clase . '"><i class="bi ' . $icono . '" aria-hidden="true"></i> ' . e($texto) . '</span>';
}
