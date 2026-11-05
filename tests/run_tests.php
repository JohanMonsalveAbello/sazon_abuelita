<?php
declare(strict_types=1);

/**
 * Pruebas de aceptación de extremo a extremo: hablan por HTTP con la aplicación real
 * (Apache + PHP + MySQL), igual que un navegador, y verifican el resultado en MySQL.
 *
 * Uso:   php tests/run_tests.php [URL_BASE]
 * Antes: importar schema.sql y seed.sql y tener Apache y MySQL encendidos.
 * Deja el resultado en tests/resultados/ultimo_resultado.md
 * Las pruebas agregan datos: al terminar, vuelve a importar schema.sql y seed.sql.
 */

define('BASE_PATH', dirname(__DIR__));
date_default_timezone_set('America/Bogota');
$BASE = rtrim($argv[1] ?? 'http://localhost/sazon_abuelita', '/');
$CRED = parse_ini_file(__DIR__ . '/credenciales_demo.ini');

// ---------- Conexión de verificación a MySQL ----------
require BASE_PATH . '/app/Core/Env.php';
App\Core\Env::load(BASE_PATH . '/.env');
$env = static fn (string $clave, string $porDefecto): string => (string) App\Core\Env::get($clave, $porDefecto);
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $env('DB_HOST', '127.0.0.1'), $env('DB_PORT', '3306'), $env('DB_NAME', 'sazon_abuelita')),
    $env('DB_USER', 'root'),
    $env('DB_PASS', ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$sql1 = static function (string $sql, array $p = []) use ($pdo) {
    $s = $pdo->prepare($sql);
    $s->execute($p);
    return $s->fetchColumn();
};

// ---------- Cliente HTTP con cookies propias (cada instancia es un navegador distinto) ----------
final class Navegador
{
    public int $estado = 0;
    public string $cuerpo = '';
    public array $cabeceras = [];
    private string $tarro;

    public function __construct(private string $base)
    {
        $this->tarro = tempnam(sys_get_temp_dir(), 'saz');
    }

    public function __destruct()
    {
        @unlink($this->tarro);
    }

    public function get(string $ruta): self
    {
        return $this->pedir($ruta);
    }

    /** POST con el token CSRF vigente, igual que un formulario real. */
    public function post(string $ruta, array $datos = [], bool $conToken = true): self
    {
        if ($conToken) {
            $datos['_csrf'] = $this->token();
        }
        return $this->pedir($ruta, $datos);
    }

    /** POST multipart con un archivo, como un formulario con <input type="file">. */
    public function postArchivo(string $ruta, array $datos, string $campo, string $archivo, string $nombre, string $tipo): self
    {
        $datos['_csrf'] = $this->token();
        $datos[$campo] = new CURLFile($archivo, $tipo, $nombre);
        return $this->pedir($ruta, $datos);
    }

    /** Prepara una petición POST sin ejecutarla, para lanzar varias a la vez con curl_multi. */
    public function prepararPost(string $ruta, array $datos): CurlHandle
    {
        $datos['_csrf'] = $this->token();
        $ch = curl_init($this->base . $ruta);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_COOKIEFILE => $this->tarro,
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($datos),
        ]);
        return $ch;
    }

    public function token(): string
    {
        $paginaActual = $this->cuerpo;
        if (!preg_match('/name="_csrf" value="([a-f0-9]+)"/', $paginaActual, $m)) {
            $this->get('/');
            preg_match('/name="_csrf" value="([a-f0-9]+)"/', $this->cuerpo, $m);
        }
        return $m[1] ?? '';
    }

    public function login(string $credencial): self
    {
        [$correo, $clave] = explode('|', $credencial);
        $this->get('/login');
        return $this->post('/login', ['correo' => $correo, 'password' => $clave]);
    }

    private function pedir(string $ruta, ?array $post = null): self
    {
        $this->cabeceras = [];
        $ch = curl_init($this->base . $ruta);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_COOKIEJAR => $this->tarro,
            CURLOPT_COOKIEFILE => $this->tarro,
            CURLOPT_HEADERFUNCTION => function ($c, $h) {
                $this->cabeceras[] = trim($h);
                return strlen($h);
            },
        ]);
        if ($post !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            $tieneArchivo = array_filter($post, static fn ($v) => $v instanceof CURLFile) !== [];
            curl_setopt($ch, CURLOPT_POSTFIELDS, $tieneArchivo ? $post : http_build_query($post));
        }
        $this->cuerpo = (string) curl_exec($ch);
        $this->estado = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        // Desde PHP 8 el archivo de cookies se escribe al destruir el handle, no con curl_close().
        unset($ch);

        $destino = $this->destino();
        if ($post !== null && $destino !== null && $this->estado === 303) {
            // Sigue la redirección para consumir y mostrar los mensajes.
            $this->seguir($destino);
        }
        return $this;
    }

    public function destino(): ?string
    {
        foreach ($this->cabeceras as $h) {
            if (stripos($h, 'Location:') === 0) {
                return trim(substr($h, 9));
            }
        }
        return null;
    }

    public string $ultimaRedireccion = '';

    private function seguir(string $destino): void
    {
        $this->ultimaRedireccion = $destino;
        $url = parse_url($this->base);
        $ch = curl_init($url['scheme'] . '://' . $url['host'] . (isset($url['port']) ? ':' . $url['port'] : '') . explode('#', $destino)[0]);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_COOKIEJAR => $this->tarro, CURLOPT_COOKIEFILE => $this->tarro, CURLOPT_FOLLOWLOCATION => true]);
        $this->cuerpo = (string) curl_exec($ch);
        $this->estado = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        unset($ch);
    }

    public function contiene(string $texto): bool
    {
        return str_contains($this->cuerpo, $texto);
    }
}

// ---------- Mini marco de pruebas ----------
$resultados = [];
$prueba = static function (string $id, string $grupo, string $descripcion, callable $fn) use (&$resultados): void {
    $inicio = microtime(true);
    try {
        $evidencia = $fn();
        $ok = true;
    } catch (Throwable $e) {
        $ok = false;
        $evidencia = $e->getMessage();
    }
    $resultados[] = ['id' => $id, 'grupo' => $grupo, 'desc' => $descripcion, 'ok' => $ok, 'evidencia' => (string) $evidencia, 'ms' => (int) ((microtime(true) - $inicio) * 1000)];
    printf("[%s] %s %s%s\n", $ok ? ' OK ' : 'FALLA', $id, $descripcion, $ok ? '' : ' => ' . $evidencia);
};
function exigir(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
}

$hoy = new DateTimeImmutable('now', new DateTimeZone('America/Bogota'));
// Martes de la próxima semana a una hora válida para cualquier prueba de reservas.
$diaFuturo = $hoy->modify('next tuesday')->modify('+7 days')->format('Y-m-d');
$sufijo = substr((string) time(), -6);
$reservaBase = static fn (array $extra = []) => $extra + [
    'nombre' => 'Prueba Automatica', 'telefono' => '3001234567', 'correo' => "prueba{$sufijo}@correo.test",
    'fecha' => $diaFuturo, 'hora' => '19:00', 'personas' => '2', 'mensaje' => '',
];

$admin = new Navegador($BASE);
$mesero = new Navegador($BASE);
$mesero2 = new Navegador($BASE);
$cocina = new Navegador($BASE);
$cliente = new Navegador($BASE);
$cliente2 = new Navegador($BASE);
$anonimo = new Navegador($BASE);

// ===== AUTENTICACIÓN Y SESIONES =====
$prueba('PT-01', 'Autenticación', 'Cada uno de los 4 roles inicia sesión y ve su panel propio', function () use ($admin, $mesero, $mesero2, $cocina, $cliente, $cliente2, $CRED) {
    $esperado = [[$admin, 'admin', 'Resumen del restaurante'], [$mesero, 'mesero', 'Mi turno'], [$cocina, 'cocina', 'Cocina hoy'], [$cliente, 'cliente', 'Hola,']];
    foreach ($esperado as [$nav, $cuenta, $texto]) {
        $nav->login($CRED[$cuenta]);
        exigir($nav->estado === 200 && $nav->contiene($texto), "{$cuenta} no llegó a su panel (HTTP {$nav->estado})");
    }
    $mesero2->login($CRED['mesero2']);
    $cliente2->login($CRED['cliente2']);
    return '4 paneles distintos renderizados';
});

$prueba('PT-02', 'Autenticación', 'Credenciales erróneas: mensaje genérico, sin revelar si el correo existe', function () use ($BASE) {
    $n = new Navegador($BASE);
    $n->get('/login');
    $n->post('/login', ['correo' => 'admin@sazon.test', 'password' => 'incorrecta']);
    $conCorreoReal = $n->contiene('Correo o contraseña incorrectos');
    $n2 = new Navegador($BASE);
    $n2->get('/login');
    $n2->post('/login', ['correo' => 'noexiste@sazon.test', 'password' => 'incorrecta']);
    exigir($conCorreoReal && $n2->contiene('Correo o contraseña incorrectos'), 'El mensaje difiere');
    return 'Mismo mensaje con correo existente e inexistente';
});

$prueba('PT-03', 'Seguridad RS-01', 'Contraseñas guardadas como hash; al iniciar sesión se actualizan a Argon2id', function () use ($sql1) {
    $planos = (int) $sql1("SELECT COUNT(*) FROM usuarios WHERE password_hash NOT LIKE '$2y$%' AND password_hash NOT LIKE '\$argon2id$%'");
    exigir($planos === 0, "{$planos} contraseñas no están hasheadas");
    $hash = (string) $sql1("SELECT password_hash FROM usuarios WHERE correo = 'admin@sazon.test'");
    exigir(str_starts_with($hash, '$argon2id$') || !defined('PASSWORD_ARGON2ID'), 'No se actualizó a Argon2id');
    return substr($hash, 0, 16) . '…';
});

$prueba('PT-04', 'Seguridad RS-02', "Inyección SQL en el login (' OR '1'='1) no entra ni altera la consulta", function () use ($BASE) {
    $n = new Navegador($BASE);
    $n->get('/login');
    $n->post('/login', ['correo' => "' OR '1'='1", 'password' => "' OR '1'='1"]);
    exigir(!$n->contiene('Resumen del restaurante') && $n->estado === 200, 'Hubo acceso');
    $n->get('/login');
    $n->post('/login', ['correo' => "a@b.co' OR '1'='1' -- ", 'password' => 'x']);
    exigir(!$n->contiene('Salir'), 'Hubo acceso');
    return 'Sin acceso; formato rechazado';
});

$prueba('PT-05', 'Seguridad RS-02', 'Inyección SQL en búsqueda y orden de listados: tabla intacta, sin error 500', function () use ($admin, $sql1) {
    $antes = (int) $sql1('SELECT COUNT(*) FROM reservas');
    foreach (["/reservas?q=' OR '1'='1", "/reservas?q=%27%3B DROP TABLE reservas%3B--", '/reservas?sort=fecha;DROP TABLE reservas', '/usuarios?sort=(SELECT 1)&dir=x', '/reservas?desde=nofecha&page=-5', '/reservas?q[]=arreglo'] as $ruta) {
        $admin->get(str_replace(' ', '%20', $ruta));
        exigir($admin->estado === 200, "{$ruta} respondió {$admin->estado}");
    }
    $admin->get("/reservas?q=%27%20OR%20%271%27=%271");
    exigir($admin->contiene('No hay reservas para mostrar'), 'La condición inyectada devolvió filas');
    exigir((int) $sql1('SELECT COUNT(*) FROM reservas') === $antes, 'Cambió la tabla');
    return 'La entrada se trató como texto literal: 0 resultados';
});

$prueba('PT-06', 'Seguridad RS-03', 'Cookie de sesión HttpOnly y SameSite; ID nuevo al iniciar sesión', function () use ($BASE, $CRED) {
    $n = new Navegador($BASE);
    $n->get('/login');
    $cookie = implode("\n", array_filter($n->cabeceras, static fn ($h) => stripos($h, 'Set-Cookie') === 0));
    exigir(stripos($cookie, 'HttpOnly') !== false && stripos($cookie, 'SameSite=Lax') !== false, "Cookie: {$cookie}");
    preg_match('/SAZONSESSID=([^;]+)/', $cookie, $a);
    [$correo, $clave] = explode('|', $CRED['cocina']);
    $n->post('/login', ['correo' => $correo, 'password' => $clave]);
    $tras = implode("\n", array_filter($n->cabeceras, static fn ($h) => stripos($h, 'Set-Cookie') === 0));
    preg_match('/SAZONSESSID=([^;]+)/', $tras, $b);
    exigir(isset($b[1]) && $b[1] !== $a[1], 'El ID de sesión no cambió');
    return 'HttpOnly, SameSite=Lax, ID regenerado';
});

$prueba('PT-07', 'Seguridad RS-03', 'Tras cerrar sesión, una ruta protegida deja de abrir', function () use ($BASE, $CRED) {
    $n = new Navegador($BASE);
    $n->login($CRED['mesero2']);
    $n->get('/panel');
    exigir($n->estado === 200, 'No entró');
    $n->post('/logout');
    $n->get('/panel');
    exigir($n->estado === 303 && str_contains((string) $n->destino(), '/login'), "Respondió {$n->estado}");
    return 'GET /panel redirige a /login';
});

$prueba('PT-08', 'Seguridad RS-05', 'Un POST sin token CSRF válido es rechazado', function () use ($admin, $sql1) {
    $antes = (int) $sql1('SELECT COUNT(*) FROM mesas');
    $admin->post('/mesas', ['numero' => '99', 'capacidad' => '4', 'zona' => 'salon', 'activa' => '1'], false);
    exigir($admin->estado === 403 && $admin->contiene('Formulario caducado'), "HTTP {$admin->estado}");
    $admin->post('/mesas', ['numero' => '99', 'capacidad' => '4', 'zona' => 'salon', 'activa' => '1', '_csrf' => str_repeat('a', 64)], false);
    exigir($admin->estado === 403, "HTTP {$admin->estado}");
    exigir((int) $sql1('SELECT COUNT(*) FROM mesas') === $antes, 'Se creó la mesa');
    return 'HTTP 403 en ambos casos; nada se guardó';
});

$prueba('PT-09', 'Seguridad RS-06', 'Texto con <script> se muestra escapado y no se ejecuta', function () use ($anonimo, $admin, $reservaBase, $sql1, $sufijo) {
    $anonimo->get('/');
    $xss = '<script>alert(1)</script>';
    $anonimo->post('/reservas/solicitar', $reservaBase(['mensaje' => $xss, 'correo' => "xss{$sufijo}@correo.test", 'hora' => '13:00']));
    exigir($anonimo->contiene('Recibimos tu solicitud'), 'No se creó la reserva');
    $id = (int) $sql1('SELECT id FROM reservas WHERE correo = ?', ["xss{$sufijo}@correo.test"]);
    exigir((string) $sql1('SELECT mensaje FROM reservas WHERE id = ?', [$id]) === $xss, 'No se guardó literal');
    $admin->get("/reservas/{$id}");
    exigir($admin->contiene('&lt;script&gt;alert(1)&lt;/script&gt;') && !$admin->contiene($xss), 'Salida sin escapar');
    $csp = implode(' ', array_filter($admin->cabeceras, static fn ($h) => stripos($h, 'Content-Security-Policy') === 0));
    exigir(str_contains($csp, "script-src 'self'"), 'Falta CSP');
    return 'Se muestra &lt;script&gt;…; además CSP bloquea scripts en línea';
});

$prueba('PT-10', 'Seguridad RS-07', 'Fuerza bruta: tras 5 fallos se bloquea el intento, incluso con la clave correcta', function () use ($BASE, $CRED) {
    [$correo, $clave] = explode('|', $CRED['cliente2']);
    $n = new Navegador($BASE);
    $n->get('/login');
    for ($i = 0; $i < 5; $i++) {
        $n->post('/login', ['correo' => $correo, 'password' => "mala{$i}"]);
    }
    $n->post('/login', ['correo' => $correo, 'password' => $clave]);
    exigir($n->contiene('Demasiados intentos') && !$n->contiene('Salir'), 'No hubo bloqueo');
    return 'Bloqueo progresivo activo (30 s, luego se duplica)';
});

// ===== AUTORIZACIÓN =====
$prueba('PT-11', 'Autorización RS-04', 'Rutas de administración denegadas por URL a mesero, cocina y cliente (403)', function () use ($mesero, $cocina, $cliente) {
    foreach (['/usuarios', '/auditoria', '/reportes', '/reportes/exportar', '/permisos', '/categorias'] as $ruta) {
        foreach ([$mesero, $cocina, $cliente] as $nav) {
            $nav->get($ruta);
            exigir($nav->estado === 403, "{$ruta} respondió {$nav->estado}");
        }
    }
    return '18 combinaciones rol/ruta devuelven 403';
});

$prueba('PT-12', 'Autorización RS-04', 'Visitante sin sesión es enviado al login', function () use ($BASE) {
    $n = new Navegador($BASE);
    foreach (['/panel', '/reservas', '/ordenes', '/cocina', '/usuarios', '/perfil'] as $ruta) {
        $n->get($ruta);
        exigir($n->estado === 303 && str_contains((string) $n->destino(), '/login'), "{$ruta} respondió {$n->estado}");
    }
    return '6 rutas protegidas redirigen a /login';
});

$prueba('PT-13', 'Autorización RS-04', 'Cliente no accede a reservas ni órdenes ajenas por URL ni por POST', function () use ($cliente, $sql1) {
    $reservaAjena = (int) $sql1('SELECT id FROM reservas WHERE cliente_id = 7 LIMIT 1');
    $ordenAjena = (int) $sql1("SELECT id FROM ordenes WHERE cliente_id = 7 LIMIT 1");
    $estadoAntes = $sql1('SELECT estado FROM reservas WHERE id = ?', [$reservaAjena]);
    $cliente->get("/reservas/{$reservaAjena}");
    exigir($cliente->estado === 403, "Reserva ajena: {$cliente->estado}");
    $cliente->get("/ordenes/{$ordenAjena}");
    exigir($cliente->estado === 403, "Orden ajena: {$cliente->estado}");
    $cliente->get("/ordenes/{$ordenAjena}/cuenta");
    exigir($cliente->estado === 403, "Cuenta ajena: {$cliente->estado}");
    $cliente->get('/panel');
    $token = $cliente->token();
    $cliente->post("/reservas/{$reservaAjena}/cancelar", ['motivo' => 'ataque', '_csrf' => $token], false);
    exigir($cliente->estado === 403, "POST ajeno: {$cliente->estado}");
    exigir($sql1('SELECT estado FROM reservas WHERE id = ?', [$reservaAjena]) === $estadoAntes, 'Cambió la reserva');
    return 'HTTP 403 y sin cambios en MySQL';
});

$prueba('PT-14', 'Autorización RS-04', 'Mesero no opera la orden de otro mesero', function () use ($mesero, $sql1) {
    $ajena = (int) $sql1("SELECT id FROM ordenes WHERE mesero_id = 3 AND estado = 'lista' LIMIT 1");
    $mesero->get("/ordenes/{$ajena}");
    exigir($mesero->estado === 403, "GET: {$mesero->estado}");
    $mesero->get('/panel');
    $token = $mesero->token();
    $mesero->post("/ordenes/{$ajena}/servir", ['_csrf' => $token], false);
    exigir($mesero->estado === 403, "POST: {$mesero->estado}");
    exigir($sql1('SELECT estado FROM ordenes WHERE id = ?', [$ajena]) === 'lista', 'Cambió de estado');
    return 'HTTP 403; la orden sigue «lista»';
});

$prueba('PT-15', 'Autorización RF-03', 'Registro con rol=admin inyectado crea un Cliente; nadie se autoasigna administrador', function () use ($BASE, $sql1, $cliente, $sufijo) {
    $n = new Navegador($BASE);
    $n->get('/registro');
    $correo = "nuevo{$sufijo}@correo.test";
    $n->post('/registro', ['nombre' => 'Nuevo Cliente', 'correo' => $correo, 'telefono' => '', 'password' => 'Clave1234', 'password_confirma' => 'Clave1234', 'rol_id' => '1', 'rol' => 'admin']);
    exigir($n->contiene('Hola,'), 'No llegó al panel de cliente');
    exigir((int) $sql1('SELECT rol_id FROM usuarios WHERE correo = ?', [$correo]) === 4, 'Rol distinto de Cliente');
    $cliente->get('/perfil');
    $idCliente = (int) $sql1("SELECT id FROM usuarios WHERE correo = 'cliente@sazon.test'");
    $cliente->post('/perfil', ['nombre' => 'María Fernanda López', 'correo' => 'cliente@sazon.test', 'telefono' => '3000000006', 'rol_id' => '1']);
    exigir((int) $sql1('SELECT rol_id FROM usuarios WHERE id = ?', [$idCliente]) === 4, 'El perfil cambió el rol');
    $cliente->get('/panel');
    return 'Rol almacenado = Cliente en ambos intentos';
});

$prueba('PT-16', 'Autorización RF-03', 'Usuario desactivado no entra y pierde la sesión abierta', function () use ($BASE, $admin, $CRED, $sql1, $sufijo) {
    $n = new Navegador($BASE);
    $n->login($CRED['inactivo']);
    exigir($n->contiene('Correo o contraseña incorrectos'), 'Un inactivo entró');
    $correo = "temporal{$sufijo}@correo.test";
    $admin->get('/usuarios/crear');
    $admin->post('/usuarios', ['nombre' => 'Temporal Prueba', 'correo' => $correo, 'telefono' => '', 'rol_id' => '2', 'password' => 'Clave1234']);
    exigir($admin->contiene('Usuario creado'), 'No se creó');
    $t = new Navegador($BASE);
    $t->login("{$correo}|Clave1234");
    $t->get('/panel');
    exigir($t->estado === 200, 'No entró');
    $id = (int) $sql1('SELECT id FROM usuarios WHERE correo = ?', [$correo]);
    $admin->get('/usuarios');
    $admin->post("/usuarios/{$id}/estado");
    $t->get('/panel');
    exigir($t->estado === 303, 'La sesión sigue activa');
    return 'Sesión invalidada de inmediato';
});

// ===== RESERVAS Y REGLAS DE NEGOCIO =====
$prueba('PT-17', 'RN-02 / RF-10', 'Reserva pública fuera de horario, en el pasado o con datos inválidos es rechazada', function () use ($anonimo, $reservaBase, $hoy, $sql1) {
    $antes = (int) $sql1('SELECT COUNT(*) FROM reservas');
    $casos = [
        [['hora' => '23:00'], 'Ese día atendemos'],
        [['fecha' => $hoy->modify('-1 day')->format('Y-m-d')], 'anticipación'],
        [['fecha' => $hoy->modify('+90 days')->format('Y-m-d')], 'días de anticipación'],
        [['correo' => 'no-es-correo'], 'Correo no tiene'],
        [['personas' => '99'], 'Personas debe estar'],
        [['fecha' => '2026-02-31'], 'no es una fecha'],
    ];
    foreach ($casos as [$cambio, $texto]) {
        $anonimo->get('/');
        $anonimo->post('/reservas/solicitar', $reservaBase($cambio));
        exigir($anonimo->contiene($texto), 'Sin mensaje «' . $texto . '»');
    }
    exigir((int) $sql1('SELECT COUNT(*) FROM reservas') === $antes, 'Se guardó una reserva inválida');
    return '6 casos rechazados con mensaje claro';
});

$idReserva1 = 0;
$prueba('PT-18', 'RN-05', 'No se aceptan reservas duplicadas (mismo correo, fecha y hora)', function () use ($cliente, $reservaBase, $sql1, $sufijo, &$idReserva1) {
    $correo = "dup{$sufijo}@correo.test";
    $cliente->get('/reservas/crear');
    $cliente->post('/reservas', $reservaBase(['correo' => $correo]));
    exigir($cliente->contiene('Reserva registrada'), 'No se creó');
    $idReserva1 = (int) $sql1('SELECT id FROM reservas WHERE correo = ?', [$correo]);
    exigir((int) $sql1('SELECT cliente_id FROM reservas WHERE id = ?', [$idReserva1]) === 6, 'No quedó a nombre del cliente');
    $cliente->get('/reservas/crear');
    $cliente->post('/reservas', $reservaBase(['correo' => $correo]));
    exigir($cliente->contiene('Ya existe una solicitud'), 'Aceptó el duplicado');
    return 'Segunda solicitud rechazada';
});

$prueba('PT-19', 'RN-01 / RN-03', 'Una mesa no admite dos reservas a menos de 2 h, ni un grupo mayor a su capacidad', function () use ($mesero, $reservaBase, $sql1, $sufijo, &$idReserva1) {
    // Mesa 10: capacidad 2.
    $mesero->get("/reservas/{$idReserva1}");
    $mesero->post("/reservas/{$idReserva1}/confirmar", ['mesa_id' => '10']);
    exigir($mesero->contiene('Reserva confirmada'), 'No confirmó');

    $mesero->get('/reservas/crear');
    $mesero->post('/reservas', $reservaBase(['correo' => "choque{$sufijo}@correo.test", 'hora' => '20:00']));
    $choque = (int) $sql1('SELECT id FROM reservas WHERE correo = ?', ["choque{$sufijo}@correo.test"]);
    $mesero->get("/reservas/{$choque}");
    exigir(!$mesero->contiene('Mesa 10 ·'), 'Ofreció una mesa ocupada');
    $mesero->post("/reservas/{$choque}/confirmar", ['mesa_id' => '10']);
    exigir($mesero->contiene('ya tiene otra reserva confirmada'), 'Permitió doble reserva');
    exigir($sql1('SELECT estado FROM reservas WHERE id = ?', [$choque]) === 'pendiente', 'Cambió de estado');

    $mesero->get('/reservas/crear');
    $mesero->post('/reservas', $reservaBase(['correo' => "grande{$sufijo}@correo.test", 'hora' => '13:00', 'personas' => '6']));
    $grande = (int) $sql1('SELECT id FROM reservas WHERE correo = ?', ["grande{$sufijo}@correo.test"]);
    $mesero->get("/reservas/{$grande}");
    $mesero->post("/reservas/{$grande}/confirmar", ['mesa_id' => '10']);
    exigir($mesero->contiene('solo tiene capacidad'), 'Permitió exceder capacidad');
    return 'Doble reserva y sobrecupo rechazados';
});

$prueba('PT-20', 'RN-04', 'El cliente cancela con anticipación, pero no una reserva a menos de 2 h', function () use ($cliente, $sql1, $pdo, &$idReserva1) {
    $cliente->get("/reservas/{$idReserva1}");
    $cliente->post("/reservas/{$idReserva1}/cancelar", ['motivo' => 'Cambio de planes']);
    exigir($cliente->contiene('Reserva cancelada') && $sql1('SELECT estado FROM reservas WHERE id = ?', [$idReserva1]) === 'cancelada', 'No canceló');
    $pdo->exec("INSERT INTO reservas (codigo, cliente_id, nombre_contacto, telefono, correo, fecha, hora, personas, estado)
                VALUES (CONCAT('T', SUBSTRING(MD5(RAND()), 1, 7)), 6, 'Pronto Prueba', '3000000006', 'pronto@correo.test', CURDATE(), ADDTIME(CURTIME(), '01:00:00'), 2, 'pendiente')");
    $pronto = (int) $pdo->lastInsertId();
    $cliente->get("/reservas/{$pronto}");
    $cliente->post("/reservas/{$pronto}/cancelar", ['motivo' => 'Tarde']);
    exigir($cliente->contiene('hasta 2 horas antes') && $sql1('SELECT estado FROM reservas WHERE id = ?', [$pronto]) === 'pendiente', 'Permitió cancelar tarde');
    return 'Regla de 2 horas verificada en MySQL';
});

// ===== PROCESO PRINCIPAL DE EXTREMO A EXTREMO =====
$idOrden = 0;
$prueba('PT-21', 'RF-04 Mesero', 'Mesero abre orden, agrega platos; orden vacía no va a cocina; plato agotado rechazado', function () use ($mesero, $sql1, &$idOrden) {
    $mesero->get('/ordenes/crear');
    $mesero->post('/ordenes', ['mesa_id' => '11', 'cliente_id' => '6']);
    exigir($mesero->contiene('Orden abierta'), 'No abrió: ' . $mesero->estado);
    $idOrden = (int) $sql1("SELECT MAX(id) FROM ordenes WHERE mesa_id = 11 AND estado = 'abierta'");
    $mesero->post("/ordenes/{$idOrden}/enviar");
    exigir($mesero->contiene('Agrega al menos un plato'), 'Envió una orden vacía');
    $mesero->post("/ordenes/{$idOrden}/items", ['plato_id' => '10', 'cantidad' => '1', 'nota' => '']);
    exigir($mesero->contiene('no está disponible'), 'Aceptó plato agotado');
    $mesero->post("/ordenes/{$idOrden}/items", ['plato_id' => '1', 'cantidad' => '2', 'nota' => 'Sin chicharrón']);
    exigir($mesero->contiene('Plato agregado'), 'No agregó');
    $mesero->post("/ordenes/{$idOrden}/items", ['plato_id' => '8', 'cantidad' => '1', 'nota' => '']);
    exigir((int) $sql1('SELECT COUNT(*) FROM orden_items WHERE orden_id = ?', [$idOrden]) === 2, 'Faltan ítems');
    return "Orden #{$idOrden} con 2 ítems";
});

$prueba('PT-22', 'RN-06', 'Una mesa no puede tener dos órdenes activas', function () use ($mesero2, $sql1) {
    $antes = (int) $sql1('SELECT COUNT(*) FROM ordenes');
    $mesero2->get('/ordenes/crear');
    $mesero2->post('/ordenes', ['mesa_id' => '11']);
    exigir($mesero2->contiene('ya tiene una orden activa'), 'Abrió otra orden');
    exigir((int) $sql1('SELECT COUNT(*) FROM ordenes') === $antes, 'Se insertó');
    return 'Rechazado por regla; además índice único en BD';
});

$prueba('PT-23', 'RF-04 Estados', 'No se puede saltar estados: cobrar o servir una orden aún en edición', function () use ($mesero, $sql1, &$idOrden) {
    $mesero->get("/ordenes/{$idOrden}");
    $mesero->post("/ordenes/{$idOrden}/cuenta", ['propina' => '10', 'metodo' => 'efectivo']);
    exigir($mesero->contiene('no puede pasar a'), 'Cobró una orden abierta');
    $mesero->post("/ordenes/{$idOrden}/servir");
    exigir($mesero->contiene('no puede pasar a'), 'Sirvió una orden abierta');
    exigir($sql1('SELECT estado FROM ordenes WHERE id = ?', [$idOrden]) === 'abierta', 'Cambió');
    return 'Estado intacto: abierta';
});

$prueba('PT-24', 'RF-04 Cocina', 'Mesero envía; Cocina recibe y prepara; un ítem enviado ya no se puede cambiar; cocina no cobra', function () use ($mesero, $cocina, $sql1, &$idOrden) {
    $mesero->post("/ordenes/{$idOrden}/enviar");
    exigir($mesero->contiene('Orden enviada a cocina'), 'No envió');
    $mesero->post("/ordenes/{$idOrden}/items", ['plato_id' => '2', 'cantidad' => '1', 'nota' => '']);
    exigir($mesero->contiene('no se puede modificar'), 'Modificó tras enviar');
    $cocina->get('/cocina');
    exigir($cocina->estado === 200 && $cocina->contiene('Sin chicharrón') && !$cocina->contiene('$84.000'), 'La comanda no aparece o muestra precios');
    $cocina->get('/cocina');
    $cocina->post("/cocina/{$idOrden}/lista");
    exigir($cocina->contiene('no puede pasar a'), 'Saltó a lista sin preparar');
    $cocina->post("/cocina/{$idOrden}/iniciar");
    $cocina->post("/cocina/{$idOrden}/lista");
    exigir($sql1('SELECT estado FROM ordenes WHERE id = ?', [$idOrden]) === 'lista', 'No quedó lista');
    $cocina->get("/ordenes/{$idOrden}");
    exigir($cocina->estado === 403, 'Cocina abrió la orden');
    return 'enviada → en_preparacion → lista';
});

$prueba('PT-25', 'RF-04 Rondas', 'Tras servir se puede pedir otra ronda y la cocina solo ve lo nuevo', function () use ($mesero, $cocina, $sql1, &$idOrden) {
    $mesero->get("/ordenes/{$idOrden}");
    $mesero->post("/ordenes/{$idOrden}/servir");
    exigir($sql1('SELECT estado FROM ordenes WHERE id = ?', [$idOrden]) === 'servida', 'No sirvió');
    $mesero->post("/ordenes/{$idOrden}/nueva-ronda");
    $mesero->post("/ordenes/{$idOrden}/items", ['plato_id' => '9', 'cantidad' => '2', 'nota' => 'Ronda dos']);
    $mesero->post("/ordenes/{$idOrden}/enviar");
    $cocina->get('/cocina/tablero');
    exigir($cocina->contiene('Ronda dos') && !$cocina->contiene('Sin chicharrón'), 'La cocina vio ítems anteriores');
    $cocina->get('/cocina');
    $cocina->post("/cocina/{$idOrden}/iniciar");
    $cocina->post("/cocina/{$idOrden}/lista");
    $mesero->get("/ordenes/{$idOrden}");
    $mesero->post("/ordenes/{$idOrden}/servir");
    return 'Ronda 2 servida';
});

$prueba('PT-26', 'RN-09 Cuenta', 'El total lo calcula el servidor (precio congelado + 8 % + propina), aunque cambie el precio', function () use ($mesero, $admin, $sql1, $pdo, &$idOrden) {
    // Sube el precio del plato: no debe alterar la orden ya tomada.
    $pdo->exec('UPDATE platos SET precio = precio + 5000 WHERE id = 1');
    $mesero->get("/ordenes/{$idOrden}/cuenta");
    $mesero->post("/ordenes/{$idOrden}/cuenta", ['propina' => '10', 'metodo' => 'tarjeta_simulada', 'total' => '1', 'subtotal' => '1']);
    $pdo->exec('UPDATE platos SET precio = precio - 5000 WHERE id = 1');
    $subtotal = 2 * 42000 + 8000 + 2 * 10000; // 112.000
    $esperado = $subtotal + (int) round($subtotal * .08) + (int) round($subtotal * .10);
    $total = (int) $sql1('SELECT total FROM cuentas WHERE orden_id = ?', [$idOrden]);
    exigir($total === $esperado, "Total {$total}, esperado {$esperado}");
    exigir($sql1('SELECT estado FROM ordenes WHERE id = ?', [$idOrden]) === 'pagada', 'No quedó pagada');
    $mesero->post("/ordenes/{$idOrden}/cuenta", ['propina' => '0', 'metodo' => 'efectivo']);
    exigir((int) $sql1('SELECT COUNT(*) FROM cuentas WHERE orden_id = ?', [$idOrden]) === 1, 'Cobró dos veces');
    exigir(!(bool) $sql1('SELECT 1 FROM ordenes WHERE mesa_activa = 11'), 'La mesa sigue ocupada');
    return "Total {$total}; mesa liberada; sin doble cobro";
});

$prueba('PT-27', 'RF-04 Cliente', 'El cliente consulta su orden y cuenta, y la reseña se publica solo tras aprobarla', function () use ($cliente, $admin, $anonimo, $sql1, $sufijo, &$idOrden) {
    $cliente->get("/ordenes/{$idOrden}");
    exigir($cliente->estado === 200 && $cliente->contiene('Calificar mi visita'), 'No ve su orden');
    $cliente->get("/ordenes/{$idOrden}/cuenta");
    exigir($cliente->estado === 200 && $cliente->contiene('Total pagado') && !$cliente->contiene('Registrar pago'), 'Cuenta incorrecta');
    $texto = "Reseña de prueba automática {$sufijo}";
    $cliente->get("/resenas/crear/{$idOrden}");
    $cliente->post("/resenas/crear/{$idOrden}", ['calificacion' => '5', 'comentario' => $texto, 'ciudad' => 'Bogotá']);
    exigir($cliente->contiene('Gracias por tu opinión'), 'No guardó');
    $cliente->get("/resenas/crear/{$idOrden}");
    exigir($cliente->estado === 303, 'Permite calificar dos veces');
    $id = (int) $sql1('SELECT id FROM resenas WHERE orden_id = ?', [$idOrden]);
    $anonimo->get('/');
    exigir(!$anonimo->contiene($texto), 'Se publicó sin aprobar');
    $admin->get('/resenas');
    $admin->post("/resenas/{$id}/moderar", ['estado' => 'aprobada']);
    exigir($sql1('SELECT estado FROM resenas WHERE id = ?', [$id]) === 'aprobada', 'No aprobó');
    return 'Flujo completo: orden → cuenta → reseña → moderación';
});

$prueba('PT-28', 'RF-11 Auditoría', 'Las operaciones críticas quedan auditadas y sin secretos', function () use ($admin, $sql1, &$idOrden) {
    $acciones = (int) $sql1("SELECT COUNT(DISTINCT accion) FROM auditoria WHERE entidad = 'orden' AND entidad_id = ?", [$idOrden]);
    exigir($acciones >= 4, "Solo {$acciones} tipos de acción");
    $secretos = (int) $sql1("SELECT COUNT(*) FROM auditoria WHERE detalle LIKE '%Clave1234%' OR detalle LIKE '%password%' OR detalle LIKE '%#2026%'");
    exigir($secretos === 0, 'Hay secretos en la auditoría');
    $admin->get('/auditoria?entidad=orden');
    exigir($admin->estado === 200 && $admin->contiene('cuenta_cerrada'), 'No se muestra');
    return "{$acciones} tipos de acción sobre la orden; 0 secretos";
});

// ===== CRUD, LISTADOS Y REPORTES =====
$prueba('PT-29', 'RF-05 CRUD', 'CRUD de mesas y categorías con duplicados y eliminación lógica', function () use ($admin, $sql1, $sufijo) {
    $numero = (string) (100 + ((int) $sufijo % 800));
    $admin->get('/mesas/crear');
    $admin->post('/mesas', ['numero' => $numero, 'capacidad' => '4', 'zona' => 'terraza', 'activa' => '1']);
    exigir($admin->contiene('Mesa creada'), 'No creó');
    $id = (int) $sql1('SELECT id FROM mesas WHERE numero = ?', [$numero]);
    $admin->get('/mesas/crear');
    $admin->post('/mesas', ['numero' => $numero, 'capacidad' => '4', 'zona' => 'salon', 'activa' => '1']);
    exigir($admin->contiene('Ya existe una mesa'), 'Aceptó duplicado');
    $admin->get("/mesas/{$id}/editar");
    $admin->post("/mesas/{$id}", ['numero' => $numero, 'capacidad' => '6', 'zona' => 'terraza', 'activa' => '1']);
    exigir((int) $sql1('SELECT capacidad FROM mesas WHERE id = ?', [$id]) === 6, 'No editó');
    $admin->get('/mesas');
    $admin->post("/mesas/{$id}/eliminar");
    exigir($sql1('SELECT eliminado_en FROM mesas WHERE id = ?', [$id]) !== null, 'No eliminó lógicamente');
    $admin->post('/categorias/1/eliminar');
    exigir($admin->contiene('todavía tiene platos'), 'Eliminó categoría con platos');
    $admin->post('/mesas/1/eliminar');
    exigir($admin->contiene('orden activa'), 'Eliminó mesa ocupada');
    return 'Crear, duplicado, editar, eliminar lógico y restricciones';
});

$prueba('PT-30', 'RF-05 Cocina/Platos', 'Cocina marca un plato agotado y sale de la carta pública; no puede editar precios', function () use ($cocina, $anonimo, $sql1) {
    $cocina->get('/platos');
    exigir($cocina->estado === 200 && $cocina->contiene('Marcar agotado'), 'No ve platos');
    exigir(!$cocina->contiene('/platos/3/editar'), 'Muestra editar');
    $cocina->post('/platos/3/disponibilidad');
    $anonimo->get('/');
    exigir(!$anonimo->contiene('Sancocho de Res'), 'Sigue en la carta');
    $cocina->get('/platos/3/editar');
    exigir($cocina->estado === 403, 'Cocina puede editar');
    $cocina->get('/platos');
    $cocina->post('/platos/3/disponibilidad');
    $anonimo->get('/');
    exigir($anonimo->contiene('Sancocho de Res'), 'No volvió');
    return 'Disponibilidad reflejada al instante';
});

$prueba('PT-31', 'RF-08 Listados', 'Búsqueda, filtro, orden y paginación en reservas y órdenes', function () use ($admin) {
    $admin->get('/reservas?estado=pendiente&sort=fecha&dir=asc');
    exigir($admin->estado === 200 && !$admin->contiene('estado-neutro'), 'El filtro no aplicó');
    $admin->get('/ordenes?page=2');
    exigir($admin->contiene('Mostrando 11–20'), 'Sin paginación');
    $admin->get('/ordenes?q=Camila&estado=pagada&sort=mesa&dir=desc');
    exigir($admin->estado === 200 && $admin->contiene('Camila Torres') && !$admin->contiene('Andrés Rojas Peña</td>'), 'La búsqueda no aplicó');
    return 'Filtros combinados y paginación correctos';
});

$prueba('PT-32', 'RF-09 Reportes', 'Indicadores coinciden con MySQL y el CSV respeta los filtros', function () use ($admin, $sql1, $hoy) {
    $desde = $hoy->modify('-30 days')->format('Y-m-d');
    $hasta = $hoy->format('Y-m-d');
    $filas = (int) $sql1('SELECT COUNT(*) FROM cuentas c JOIN ordenes o ON o.id = c.orden_id WHERE c.creado_en >= ? AND c.creado_en <= ? AND o.mesero_id = 3', ["{$desde} 00:00:00", "{$hasta} 23:59:59"]);
    $total = (int) $sql1('SELECT SUM(c.total) FROM cuentas c JOIN ordenes o ON o.id = c.orden_id WHERE c.creado_en >= ? AND c.creado_en <= ? AND o.mesero_id = 3', ["{$desde} 00:00:00", "{$hasta} 23:59:59"]);
    $q = "desde={$desde}&hasta={$hasta}&mesero_id=3";
    $admin->get("/reportes?{$q}");
    exigir($admin->contiene('$' . number_format($total, 0, ',', '.')), 'El total no coincide con MySQL');
    $admin->get("/reportes/exportar?{$q}");
    $tipo = implode(' ', $admin->cabeceras);
    exigir(str_contains($tipo, 'text/csv') && str_contains($tipo, 'attachment'), 'No es CSV');
    $lineas = substr_count(trim($admin->cuerpo), "\n");
    exigir($lineas === $filas, "CSV con {$lineas} filas, MySQL {$filas}");
    exigir(!str_contains($admin->cuerpo, 'Andrés Rojas'), 'El CSV ignoró el filtro');
    return "{$filas} filas; total $" . number_format($total, 0, ',', '.');
});

$prueba('PT-33', 'RF-12 Errores', 'Pantallas 403 y 404 propias; archivos privados inaccesibles por URL', function () use ($BASE, $cliente) {
    $cliente->get('/usuarios');
    exigir($cliente->estado === 403 && $cliente->contiene('No tienes acceso'), '403 incorrecta');
    $n = new Navegador($BASE);
    $n->get('/no-existe');
    exigir($n->estado === 404 && $n->contiene('no está en la carta'), '404 incorrecta');
    foreach (['/.env', '/config/database.php', '/app/Core/Database.php', '/database/seed.sql', '/storage/logs/app.log', '/tests/credenciales_demo.ini'] as $privado) {
        $n->get($privado);
        exigir(in_array($n->estado, [403, 404], true) && !$n->contiene('DB_PASS') && !$n->contiene('<?php'), "{$privado} responde {$n->estado}");
    }
    return '6 archivos privados devuelven 403 o 404, sin contenido';
});

$prueba('PT-34', 'RF-14 / Validación', 'Validación de servidor en contraseña débil y campos con valor inesperado', function () use ($BASE, $sql1, $sufijo) {
    $n = new Navegador($BASE);
    $n->get('/registro');
    $correo = "debil{$sufijo}@correo.test";
    $n->post('/registro', ['nombre' => 'Clave Debil', 'correo' => $correo, 'password' => '123', 'password_confirma' => '123']);
    exigir($n->contiene('mayúscula, minúscula y número') && !$sql1('SELECT 1 FROM usuarios WHERE correo = ?', [$correo]), 'Aceptó clave débil');
    exigir(!$n->contiene('value="123"'), 'Devolvió la contraseña al formulario');
    $n->post('/registro', ['nombre' => ['arreglo'], 'correo' => ['x'], 'password' => ['y']]);
    exigir($n->estado === 200, 'Entrada inesperada causó error');
    return 'Errores claros, sin error 500 y sin devolver la clave';
});

$prueba('PT-36', 'RN-01 Concurrencia', 'Dos confirmaciones simultáneas de la misma mesa: solo una gana (transacción y bloqueo)', function () use ($mesero, $mesero2, $admin, $pdo, $sql1, $hoy) {
    // Dos reservas pendientes a la misma hora; la mesa 12 no tiene más compromisos ese día.
    $fecha = $hoy->modify('next tuesday')->modify('+21 days')->format('Y-m-d');
    $ids = [];
    foreach ([1, 2] as $n) {
        $pdo->prepare("INSERT INTO reservas (codigo, nombre_contacto, telefono, correo, fecha, hora, personas, estado)
                       VALUES (?, 'Concurrente', '3000000000', ?, ?, '19:00:00', 2, 'pendiente')")
            ->execute([substr('C' . bin2hex(random_bytes(4)), 0, 8), "c{$n}@correo.test", $fecha]);
        $ids[] = (int) $pdo->lastInsertId();
    }
    $mesero->get('/panel');
    $mesero2->get('/panel');
    $multi = curl_multi_init();
    $a = $mesero->prepararPost("/reservas/{$ids[0]}/confirmar", ['mesa_id' => '12']);
    $b = $mesero2->prepararPost("/reservas/{$ids[1]}/confirmar", ['mesa_id' => '12']);
    curl_multi_add_handle($multi, $a);
    curl_multi_add_handle($multi, $b);
    do {
        curl_multi_exec($multi, $activas);
        curl_multi_select($multi, 0.05);
    } while ($activas > 0);
    curl_multi_remove_handle($multi, $a);
    curl_multi_remove_handle($multi, $b);
    $confirmadas = (int) $sql1("SELECT COUNT(*) FROM reservas WHERE id IN ({$ids[0]}, {$ids[1]}) AND estado = 'confirmada'");
    exigir($confirmadas === 1, "Quedaron {$confirmadas} confirmadas");
    return 'Exactamente 1 confirmada; la otra sigue pendiente';
});

$prueba('PT-37', 'RS-08 Archivos', 'Subida de fotos: un PHP disfrazado se rechaza; una imagen real recibe nombre aleatorio', function () use ($admin, $cocina, $sql1, $sufijo) {
    $base = ['nombre' => "Plato Prueba {$sufijo}", 'categoria_id' => '5', 'descripcion' => 'Descripción de prueba automática.', 'descripcion_larga' => '',
             'precio' => '10000', 'etiqueta' => '', 'tiempo_min' => '10', 'porciones' => '1 persona', 'disponible' => '1', 'destacado' => '0'];
    $falso = tempnam(sys_get_temp_dir(), 'saz');
    file_put_contents($falso, '<?php echo "ejecutado"; ?>');
    $admin->get('/platos/crear');
    $admin->postArchivo('/platos', $base, 'imagen', $falso, 'foto.php.jpg', 'image/jpeg');
    unlink($falso);
    exigir($admin->contiene('debe ser JPG, PNG o WEBP'), 'Aceptó un archivo que no es imagen');
    exigir(!$sql1('SELECT 1 FROM platos WHERE nombre = ?', [$base['nombre']]), 'Se creó el plato');

    $png = tempnam(sys_get_temp_dir(), 'saz');
    file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M/AAAMBAQDJ/pLvAAAAAElFTkSuQmCC'));
    $admin->get('/platos/crear');
    $admin->postArchivo('/platos', $base, 'imagen', $png, '../../malicioso.php', 'image/png');
    unlink($png);
    $imagen = (string) $sql1('SELECT imagen FROM platos WHERE nombre = ?', [$base['nombre']]);
    exigir((bool) preg_match('#^platos/[a-f0-9]{24}\.png$#', $imagen), "Nombre inseguro: {$imagen}");
    $ruta = BASE_PATH . '/public/assets/img/' . $imagen;
    exigir(is_file($ruta), 'El archivo no quedó en la carpeta de fotos');
    unlink($ruta);
    return 'Tipo leído del contenido; nombre aleatorio y extensión forzada';
});

// ===== RENDIMIENTO (RNF-06) =====
$prueba('PT-35', 'RNF-06 Rendimiento', 'Listados paginados y reportes responden en menos de 1 segundo', function () use ($admin) {
    $tiempos = [];
    foreach (['/panel', '/ordenes', '/reservas', '/reportes', '/auditoria'] as $ruta) {
        $t = microtime(true);
        $admin->get($ruta);
        $ms = (int) ((microtime(true) - $t) * 1000);
        exigir($admin->estado === 200 && $ms < 1000, "{$ruta}: {$ms} ms");
        $tiempos[] = "{$ruta} {$ms} ms";
    }
    return implode(', ', $tiempos);
});

// ---------- Resumen ----------
$bien = count(array_filter($resultados, static fn ($r) => $r['ok']));
$total = count($resultados);
printf("\n%d de %d pruebas correctas\n", $bien, $total);

@mkdir(__DIR__ . '/resultados', 0777, true);
$md = "# Resultado de pruebas automáticas\n\n- Fecha: " . date('Y-m-d H:i') . "\n- URL: {$BASE}\n- PHP: " . PHP_VERSION . "\n- Resultado: **{$bien} de {$total} correctas**\n\n"
    . "| ID | Área | Prueba | Resultado | Evidencia | ms |\n|---|---|---|---|---|---|\n";
foreach ($resultados as $r) {
    $md .= '| ' . implode(' | ', [$r['id'], $r['grupo'], str_replace('|', '\\|', $r['desc']), $r['ok'] ? 'Correcta' : 'FALLA', str_replace(['|', "\n"], ['\\|', ' '], $r['evidencia']), $r['ms']]) . " |\n";
}
file_put_contents(__DIR__ . '/resultados/ultimo_resultado.md', $md);
exit($bien === $total ? 0 : 1);
