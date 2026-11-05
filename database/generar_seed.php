<?php
declare(strict_types=1);

/**
 * Genera database/seed.sql con datos 100 % ficticios y fechas relativas al día de la importación.
 * Es determinista: siempre produce el mismo archivo.  Uso:  php database/generar_seed.php
 * Las contraseñas de demostración están en README.md; aquí solo aparecen sus hashes bcrypt.
 * Al primer inicio de sesión el sistema las actualiza a Argon2id (password_needs_rehash).
 */
mt_srand(2026);

$HASH = [
    'admin'   => '$2y$11$h8cGjr8kZBgqtdkmJTrew.LAdDwkNfkIP3BlgQo/4ajlyOQ3S5UNO',
    'mesero'  => '$2y$11$tPCCtsD.8wk08gkWHNxSx.i5Cst/uJVWCAKT./UMHgzxdJ38Fd42q',
    'cocina'  => '$2y$11$WDZBbsJJz.maEHDiYlYkxuQYAIChGtOCaVJJ10mGamC7btNf.VGbO',
    'cliente' => '$2y$11$22LGNdBT67rrTupsyGk1V.Ja3hYjKpbjhs7uZgTD7dgWGeXSZ3bW2',
];
$ROL = ['admin' => 1, 'mesero' => 2, 'cocina' => 3, 'cliente' => 4];

function q(?string $v): string
{
    return $v === null ? 'NULL' : "'" . str_replace("'", "''", $v) . "'";
}

/** Expresión SQL de fecha: $dias atrás + $minutos desde medianoche. */
function momento(int $dias, int $minutos): string
{
    return "CURDATE() - INTERVAL {$dias} DAY + INTERVAL {$minutos} MINUTE";
}

function insertar(string $tabla, string $columnas, array $filas): string
{
    $sql = '';
    foreach (array_chunk($filas, 100) as $grupo) {
        $sql .= "INSERT INTO {$tabla} ({$columnas}) VALUES\n  " . implode(",\n  ", $grupo) . ";\n";
    }
    return $sql . "\n";
}

$sql = "-- =====================================================================\n"
     . "-- El Sazón de la Abuela · Datos de demostración FICTICIOS\n"
     . "-- Archivo generado por database/generar_seed.php. Importar después de schema.sql.\n"
     . "-- Las fechas son relativas al momento de importar.\n"
     . "-- =====================================================================\n"
     . "SET NAMES utf8mb4;\nSET time_zone = '-05:00';\nUSE sazon_abuelita;\n\n";

// ---------- Usuarios ----------
$usuarios = [
    [1, 'admin', 'Elvira Castro Núñez', 'admin@sazon.test', '3000000001', 1],
    [2, 'mesero', 'Andrés Rojas Peña', 'mesero@sazon.test', '3000000002', 1],
    [3, 'mesero', 'Camila Torres Díaz', 'mesero2@sazon.test', '3000000003', 1],
    [4, 'cocina', 'Rosa Gutiérrez Mora', 'cocina@sazon.test', '3000000004', 1],
    [5, 'cocina', 'Jhon Pardo León', 'cocina2@sazon.test', '3000000005', 1],
    [6, 'cliente', 'María Fernanda López', 'cliente@sazon.test', '3000000006', 1],
    [7, 'cliente', 'Carlos Andrés Mejía', 'cliente2@sazon.test', '3000000007', 1],
    [8, 'cliente', 'Laura Gómez Prieto', 'cliente3@sazon.test', '3000000008', 1],
    [9, 'cliente', 'Diego Vargas Ruiz', 'cliente4@sazon.test', '3000000009', 1],
    [10, 'cliente', 'Paula Ríos Cárdenas', 'cliente5@sazon.test', '3000000010', 1],
    [11, 'mesero', 'Tomás Herrera Silva', 'mesero.inactivo@sazon.test', '3000000011', 0],
];
$filas = [];
foreach ($usuarios as [$id, $rol, $nombre, $correo, $tel, $activo]) {
    $filas[] = "({$id}, {$ROL[$rol]}, " . q($nombre) . ', ' . q($correo) . ', ' . q($tel) . ', ' . q($HASH[$rol]) . ", {$activo}, " . momento(90, 0) . ')';
}
$sql .= insertar('usuarios', 'id, rol_id, nombre, correo, telefono, password_hash, activo, creado_en', $filas);

// ---------- Categorías ----------
$sql .= "INSERT INTO categorias (id, slug, nombre, orden) VALUES\n"
      . "  (1, 'entradas', 'Entradas', 1), (2, 'sopas', 'Sopas', 2), (3, 'fuertes', 'Platos Fuertes', 3),\n"
      . "  (4, 'bebidas', 'Bebidas', 4), (5, 'postres', 'Postres', 5);\n\n";

// ---------- Platos: los de la interfaz original, en el mismo orden ----------
$ajiacoLargo = 'Nuestra versión del clásico ajiaco santafereño, preparado con tres tipos de papa, pollo desmenuzado, mazorca tierna y el toque especial de guascas frescas.';
$platos = [
    [1, 3, 'Bandeja Paisa', 'Frijoles, arroz, carne molida, chicharrón, chorizo, huevo, aguacate, arepa y patacón.', null, 42000, 'bandeja-paisa.jpg', 'Popular', 35, '1 persona', 1, 0],
    [2, 2, 'Ajiaco Santafereño', 'Sopa tradicional bogotana con pollo, tres tipos de papa, mazorca, guascas, crema y alcaparras.', $ajiacoLargo, 32000, 'ajiaco.jpg', 'Popular', 45, '1-2 personas', 1, 1],
    [3, 2, 'Sancocho de Res', 'Caldo tradicional con costilla de res, yuca, plátano, papa, mazorca y cilantro.', null, 35000, 'sancocho.jpg', null, 40, '1 persona', 1, 0],
    [4, 1, 'Chorizo Santarrosano', 'Chorizo artesanal a la parrilla con arepa, hogao y limón.', null, 18000, 'chorizo.jpg', 'Picante', 15, '1 persona', 1, 0],
    [5, 1, 'Empanadas de la Casa', 'Empanadas crujientes rellenas de carne o pollo, servidas con ají casero.', null, 12000, 'empanadas.jpg', 'Popular', 12, '1 persona', 1, 0],
    [6, 3, 'Tamal Tolimense', 'Tamal tradicional envuelto en hoja de plátano con pollo, cerdo, arroz y verduras.', null, 15000, 'tamal.jpg', null, 15, '1 persona', 1, 0],
    [7, 3, 'Lechona Tolimense', 'Cerdo relleno de arroz, arvejas y especias, servido con arepa.', null, 38000, 'lechona.jpg', 'Tradicional', 20, '1 persona', 1, 0],
    [8, 4, 'Limonada de Coco', 'Refrescante limonada con crema de coco, perfecta para acompañar cualquier plato.', null, 8000, 'limonada-coco.jpg', 'Vegano', 5, '1 persona', 1, 0],
    [9, 5, 'Postre de Natas', 'Tradicional postre bogotano con natas de leche, canela y azúcar.', null, 10000, 'postre-natas.jpg', null, 5, '1 persona', 1, 0],
    [10, 5, 'Arroz con Leche', 'Arroz cremoso con leche, canela y clavo. Hoy agotado para demostrar la regla de disponibilidad.', null, 9000, 'postre-natas.jpg', null, 5, '1 persona', 0, 0],
];
$filas = [];
foreach ($platos as [$id, $cat, $nombre, $desc, $larga, $precio, $img, $etq, $t, $porc, $disp, $dest]) {
    $filas[] = "({$id}, {$cat}, " . q($nombre) . ', ' . q($desc) . ', ' . q($larga) . ", {$precio}, " . q($img) . ', ' . q($etq) . ", {$t}, " . q($porc) . ", {$disp}, {$dest})";
}
$sql .= insertar('platos', 'id, categoria_id, nombre, descripcion, descripcion_larga, precio, imagen, etiqueta, tiempo_min, porciones, disponible, destacado', $filas);
$precios = array_column($platos, 5, 0);

// ---------- Mesas ----------
$mesas = [[1, 2, 'salon'], [2, 2, 'salon'], [3, 4, 'salon'], [4, 4, 'salon'], [5, 4, 'salon'], [6, 6, 'salon'],
          [7, 4, 'terraza'], [8, 4, 'terraza'], [9, 6, 'terraza'], [10, 2, 'salon'], [11, 4, 'salon'], [12, 10, 'privado']];
$filas = [];
foreach ($mesas as [$n, $cap, $zona]) {
    $filas[] = "({$n}, {$n}, {$cap}, '{$zona}', 1)";
}
$sql .= insertar('mesas', 'id, numero, capacidad, zona, activa', $filas);

// ---------- Reservas ----------
$contactos = [
    [6, 'María Fernanda López', '3000000006', 'cliente@sazon.test'],
    [7, 'Carlos Andrés Mejía', '3000000007', 'cliente2@sazon.test'],
    [8, 'Laura Gómez Prieto', '3000000008', 'cliente3@sazon.test'],
    [9, 'Diego Vargas Ruiz', '3000000009', 'cliente4@sazon.test'],
    [10, 'Paula Ríos Cárdenas', '3000000010', 'cliente5@sazon.test'],
    [null, 'Visitante Sin Cuenta', '3000000099', 'visitante@correo.test'],
];
$horas = ['12:00:00', '13:00:00', '14:00:00', '18:00:00', '19:00:00'];
$mensajes = [null, null, 'Celebración de cumpleaños', 'Alergia a los frutos secos', 'Mesa en terraza si es posible', null];
$filas = [];
$rid = 0;
$fila = static function (int $id, array $c, ?int $mesa, string $fecha, string $hora, int $pers, ?string $msg, string $estado, ?string $motivo = null): string {
    $codigo = sprintf('R-%06X', 0x10000 + $id * 4099);
    return "({$id}, '{$codigo}', " . ($c[0] ?? 'NULL') . ', ' . ($mesa ?? 'NULL') . ', ' . q($c[1]) . ', ' . q($c[2]) . ', ' . q($c[3]) . ", {$fecha}, {$hora}, {$pers}, " . q($msg) . ", '{$estado}', " . q($motivo) . ')';
};
// Pasadas (una por día, en mesas alternas: nunca se solapan).
for ($d = 45; $d >= 1; $d -= 1) {
    $c = $contactos[mt_rand(0, 5)];
    $estado = ['cumplida', 'cumplida', 'cumplida', 'cumplida', 'no_asistio', 'cancelada'][mt_rand(0, 5)];
    $mesa = $estado === 'cancelada' ? null : ($d % 12) + 1;
    $cap = (int) ($mesas[($d % 12)][1]);
    $filas[] = $fila(++$rid, $c, $mesa, "CURDATE() - INTERVAL {$d} DAY", q($horas[mt_rand(0, 4)]), mt_rand(1, $mesa ? $cap : 4), $mensajes[mt_rand(0, 5)], $estado, $estado === 'cancelada' ? 'El cliente avisó que no podía asistir' : null);
}
// Hoy: confirmadas en terraza. La primera ya llegó, las otras llegan pronto.
$filas[] = $fila(++$rid, $contactos[2], 7, 'CURDATE()', "TIME_FORMAT(NOW() - INTERVAL 10 MINUTE, '%H:%i:00')", 3, 'Celebración de cumpleaños', 'confirmada');
$filas[] = $fila(++$rid, $contactos[1], 8, 'CURDATE()', "TIME_FORMAT(NOW() + INTERVAL 1 HOUR, '%H:%i:00')", 4, null, 'confirmada');
$filas[] = $fila(++$rid, $contactos[5], 9, 'CURDATE()', "TIME_FORMAT(NOW() + INTERVAL 3 HOUR, '%H:%i:00')", 5, null, 'confirmada');
// Futuras (próximas dos semanas): pendientes y confirmadas. Máximo una por día en cada mesa.
for ($d = 1; $d <= 14; $d++) {
    $c = $contactos[$d % 6];
    $confirmada = $d % 2 === 0;
    $filas[] = $fila(++$rid, $c, $confirmada ? ($d % 6) + 7 : null, "CURDATE() + INTERVAL {$d} DAY", q($horas[$d % 5]), 2 + $d % 3, $mensajes[$d % 6], $confirmada ? 'confirmada' : 'pendiente');
}
$sql .= insertar('reservas', 'id, codigo, cliente_id, mesa_id, nombre_contacto, telefono, correo, fecha, hora, personas, mensaje, estado, motivo_cancelacion', $filas);

// ---------- Órdenes históricas, ítems, historial y cuentas ----------
$ordenes = $items = $historial = $cuentas = [];
$oid = 0;
$codigoOrden = static fn (int $id): string => sprintf('O-%06X', 0x20000 + $id * 3571);
$agregarItems = static function (int $id, array &$items, array $precios, int $n = 0): int {
    $subtotal = 0;
    $elegidos = (array) array_rand(array_slice($precios, 0, 9, true), $n ?: mt_rand(2, 5));
    foreach ($elegidos as $plato) {
        $cant = mt_rand(1, 3);
        $items[] = "({$id}, {$plato}, 1, {$cant}, {$precios[$plato]})";
        $subtotal += $cant * $precios[$plato];
    }
    return $subtotal;
};
$clientesId = [6, 7, 8, 9, 10];
$pagadasPorCliente = [];
for ($d = 60; $d >= 1; $d--) {
    // Más movimiento los fines de semana relativos, para que la gráfica no sea plana.
    $porDia = 2 + ($d % 7 < 2 ? 3 : 0) + mt_rand(0, 2);
    for ($j = 0; $j < $porDia; $j++) {
        $id = ++$oid;
        $inicio = mt_rand(720, 1230);
        $mesa = mt_rand(1, 12);
        $mesero = mt_rand(2, 3);
        $cliente = mt_rand(0, 2) === 0 ? 'NULL' : $clientesId[mt_rand(0, 4)];
        $cancelada = mt_rand(1, 14) === 1;
        $subtotal = $agregarItems($id, $items, $precios);
        $ordenes[] = "({$id}, '{$codigoOrden($id)}', {$mesa}, {$mesero}, {$cliente}, " . q($cancelada ? 'cancelada' : 'pagada') . ', ' . momento($d, $inicio) . ')';
        $historial[] = "({$id}, NULL, 'abierta', {$mesero}, NULL, " . momento($d, $inicio) . ')';
        if ($cancelada) {
            $historial[] = "({$id}, 'abierta', 'cancelada', {$mesero}, 'El cliente se retiró', " . momento($d, $inicio + 6) . ')';
            continue;
        }
        $prep = mt_rand(12, 34);
        $cocinero = mt_rand(4, 5);
        $historial[] = "({$id}, 'abierta', 'enviada', {$mesero}, NULL, " . momento($d, $inicio + 5) . ')';
        $historial[] = "({$id}, 'enviada', 'en_preparacion', {$cocinero}, NULL, " . momento($d, $inicio + 7) . ')';
        $historial[] = "({$id}, 'en_preparacion', 'lista', {$cocinero}, NULL, " . momento($d, $inicio + 5 + $prep) . ')';
        $historial[] = "({$id}, 'lista', 'servida', {$mesero}, NULL, " . momento($d, $inicio + 8 + $prep) . ')';
        $historial[] = "({$id}, 'servida', 'pagada', {$mesero}, NULL, " . momento($d, $inicio + 60 + $prep) . ')';
        $impo = (int) round($subtotal * 0.08);
        $propina = (int) round($subtotal * [0, 5, 10, 10, 10, 15][mt_rand(0, 5)] / 100);
        $metodo = ['efectivo', 'tarjeta_simulada', 'transferencia_simulada'][mt_rand(0, 2)];
        $cuentas[] = "({$id}, {$subtotal}, {$impo}, {$propina}, " . ($subtotal + $impo + $propina) . ", '{$metodo}', {$mesero}, " . momento($d, $inicio + 60 + $prep) . ')';
        if ($cliente !== 'NULL') {
            $pagadasPorCliente[$cliente][] = [$id, $d];
        }
    }
}
// Pagadas hoy, para que los indicadores del día tengan datos reales.
for ($j = 0; $j < 5; $j++) {
    $id = ++$oid;
    $mesero = 2 + $j % 2;
    $hace = 90 + $j * 45;
    $subtotal = $agregarItems($id, $items, $precios);
    $ordenes[] = "({$id}, '{$codigoOrden($id)}', " . (10 + $j % 3) . ", {$mesero}, NULL, 'pagada', NOW() - INTERVAL {$hace} MINUTE)";
    $historial[] = "({$id}, NULL, 'abierta', {$mesero}, NULL, NOW() - INTERVAL {$hace} MINUTE)";
    $historial[] = "({$id}, 'abierta', 'enviada', {$mesero}, NULL, NOW() - INTERVAL " . ($hace - 5) . ' MINUTE)';
    $historial[] = "({$id}, 'enviada', 'en_preparacion', 4, NULL, NOW() - INTERVAL " . ($hace - 7) . ' MINUTE)';
    $historial[] = "({$id}, 'en_preparacion', 'lista', 4, NULL, NOW() - INTERVAL " . ($hace - 25) . ' MINUTE)';
    $historial[] = "({$id}, 'lista', 'servida', {$mesero}, NULL, NOW() - INTERVAL " . ($hace - 28) . ' MINUTE)';
    $historial[] = "({$id}, 'servida', 'pagada', {$mesero}, NULL, NOW() - INTERVAL " . ($hace - 70) . ' MINUTE)';
    $impo = (int) round($subtotal * 0.08);
    $propina = (int) round($subtotal * 0.1);
    $cuentas[] = "({$id}, {$subtotal}, {$impo}, {$propina}, " . ($subtotal + $impo + $propina) . ", 'tarjeta_simulada', {$mesero}, NOW() - INTERVAL " . ($hace - 70) . ' MINUTE)';
}
// Órdenes activas: una en cada estado del proceso, para demostrar el flujo sin preparar nada.
$activas = [
    [1, 2, 'NULL', 'abierta', ['abierta']],
    [2, 2, 'NULL', 'enviada', ['abierta', 'enviada']],
    [3, 3, 8, 'enviada', ['abierta', 'enviada']],
    [4, 2, 'NULL', 'en_preparacion', ['abierta', 'enviada', 'en_preparacion']],
    [5, 3, 'NULL', 'lista', ['abierta', 'enviada', 'en_preparacion', 'lista']],
    [6, 2, 6, 'servida', ['abierta', 'enviada', 'en_preparacion', 'lista', 'servida']],
];
foreach ($activas as $k => [$mesa, $mesero, $cliente, $estado, $pasos]) {
    $id = ++$oid;
    $agregarItems($id, $items, $precios, 2 + $k % 2);
    $hace = 12 * count($pasos) + 6;
    $ordenes[] = "({$id}, '{$codigoOrden($id)}', {$mesa}, {$mesero}, {$cliente}, '{$estado}', NOW() - INTERVAL {$hace} MINUTE)";
    $anterior = 'NULL';
    foreach ($pasos as $n => $paso) {
        $autor = in_array($paso, ['en_preparacion', 'lista'], true) ? 4 : $mesero;
        $hist = $hace - $n * 10;
        $historial[] = "({$id}, {$anterior}, '{$paso}', {$autor}, NULL, NOW() - INTERVAL {$hist} MINUTE)";
        $anterior = "'{$paso}'";
    }
}
$sql .= insertar('ordenes', 'id, codigo, mesa_id, mesero_id, cliente_id, estado, creado_en', $ordenes);
$sql .= insertar('orden_items', 'orden_id, plato_id, ronda, cantidad, precio_unitario', $items);
$sql .= insertar('orden_historial', 'orden_id, estado_anterior, estado_nuevo, usuario_id, nota, creado_en', $historial);
$sql .= insertar('cuentas', 'orden_id, subtotal, impoconsumo, propina, total, metodo, cerrada_por, creado_en', $cuentas);

// ---------- Reseñas (solo sobre órdenes pagadas del propio cliente) ----------
$textos = [
    [5, 'Muy buena atención y el sancocho estaba delicioso. Volveremos con toda la familia.', 'Bogotá', 'aprobada'],
    [4, 'La bandeja paisa es abundante y sabrosa. Tuvimos que esperar un poco, pero valió la pena.', 'Chía', 'aprobada'],
    [5, 'Las empanadas con ají casero son lo mejor para empezar. Ambiente muy agradable.', 'Bogotá', 'aprobada'],
    [3, 'La comida estuvo bien, aunque ese día había mucho ruido en el salón.', 'Soacha', 'aprobada'],
    [4, 'Buen servicio y postre de natas muy casero. Precios razonables.', 'Bogotá', 'aprobada'],
    [5, 'Celebramos un cumpleaños y estuvieron muy pendientes de la mesa.', 'Zipaquirá', 'aprobada'],
    [4, 'Nos gustó mucho la terraza. La limonada de coco es muy recomendada.', 'Bogotá', 'pendiente'],
    [2, 'El plato llegó tibio y tuvimos que pedir que lo calentaran.', 'Bogotá', 'pendiente'],
];
$filas = [];
$usadas = [];
$reservadas = [];
// María Fernanda y Carlos conservan los testimonios de la interfaz original (los más recientes).
$originales = [
    6 => [5, 'El mejor ajiaco que he probado en mi vida. Me transportó a la cocina de mi abuela. El servicio es impecable y el ambiente muy acogedor.', 'Bogotá'],
    7 => [5, 'Vine buscando una buena bandeja paisa fuera de mi ciudad y la encontré aquí. Abundante, deliciosa y con todos los elementos perfectos.', 'Medellín'],
];
foreach ($originales as $cliente => [$cal, $txt, $ciudad]) {
    [$orden] = end($pagadasPorCliente[$cliente]);
    $usadas[$orden] = true;
    $filas[] = "({$orden}, {$cliente}, {$cal}, " . q($txt) . ', ' . q($ciudad) . ", 'aprobada', NOW() - INTERVAL 1 HOUR)";
}
foreach ($textos as $i => [$cal, $txt, $ciudad, $estado]) {
    $cliente = $clientesId[($i + 2) % 5];
    foreach ($pagadasPorCliente[$cliente] as [$orden, $d]) {
        if (!isset($usadas[$orden]) && $d >= 2) {
            $usadas[$orden] = true;
            $filas[] = "({$orden}, {$cliente}, {$cal}, " . q($txt) . ', ' . q($ciudad) . ", '{$estado}', " . momento($d, 1320) . ')';
            break;
        }
    }
}
$sql .= insertar('resenas', 'orden_id, cliente_id, calificacion, comentario, ciudad, estado, creado_en', $filas);

$sql .= "INSERT INTO auditoria (usuario_id, accion, entidad, entidad_id, detalle, ip, creado_en) VALUES\n"
      . "  (1, 'sistema_instalado', 'sistema', NULL, '{\"origen\":\"seed.sql\"}', '127.0.0.1', NOW());\n";

file_put_contents(__DIR__ . '/seed.sql', $sql);
echo 'seed.sql generado: ' . count($ordenes) . ' órdenes, ' . $rid . " reservas.\n";
