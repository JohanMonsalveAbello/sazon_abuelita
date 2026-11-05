<?php
use App\Core\Auth;
$u = usuario();
$esCliente = Auth::es('cliente');
$horas = [];
for ($m = 11 * 60 + 30; $m <= 22 * 60; $m += 30) {
    $h = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    $horas[$h] = hora_12($h);
}
$r = $reserva;
?>
<?= partial('encabezado', ['titulo' => $titulo, 'volver' => $r ? ['Reserva', '/reservas/' . $r['id']] : ['Reservas', '/reservas'], 'subtitulo' => 'Duración estimada de 2 horas. Horario de reservas: lun-vie 11:30 AM a 9:00 PM, sáb 12:00 PM a 10:00 PM, dom 12:00 PM a 7:00 PM.']) ?>
<section class="tarjeta formulario-medio">
  <form method="post" action="<?= e(url($r ? '/reservas/' . $r['id'] : '/reservas')) ?>" class="js-validar" novalidate>
    <?= csrf_field() ?>
    <?php if (!$r && !$esCliente): ?>
      <?= campo_select('cliente_id', 'Cliente registrado (opcional)', $clientes, '', [], 'Sin cuenta / visitante') ?>
    <?php endif; ?>
    <div class="row">
      <div class="col-md-6"><?= campo('nombre', 'Nombre de contacto', $r['nombre_contacto'] ?? ($esCliente ? $u['nombre'] : ''), ['required' => true, 'minlength' => 3, 'maxlength' => 120]) ?></div>
      <div class="col-md-6"><?= campo('telefono', 'Teléfono', $r['telefono'] ?? ($esCliente ? $u['telefono'] : ''), ['type' => 'tel', 'required' => true, 'maxlength' => 20]) ?></div>
    </div>
    <?= campo('correo', 'Correo', $r['correo'] ?? ($esCliente ? $u['correo'] : ''), ['type' => 'email', 'required' => true, 'maxlength' => 120]) ?>
    <div class="row">
      <div class="col-md-5"><?= campo('fecha', 'Fecha', $r['fecha'] ?? '', ['type' => 'date', 'required' => true, 'min' => date('Y-m-d')]) ?></div>
      <div class="col-6 col-md-4"><?= campo_select('hora', 'Hora', $horas, isset($r['hora']) ? substr($r['hora'], 0, 5) : '', ['required' => true]) ?></div>
      <div class="col-6 col-md-3"><?= campo('personas', 'Personas', $r['personas'] ?? '', ['type' => 'number', 'required' => true, 'min' => 1, 'max' => 20]) ?></div>
    </div>
    <?= campo_area('mensaje', 'Mensaje o solicitud especial', $r['mensaje'] ?? '', ['maxlength' => 500, 'placeholder' => 'Alergias, celebraciones, preferencias…']) ?>
    <button class="btn btn-primary" type="submit">Guardar reserva</button>
    <a class="btn btn-outline-secondary" href="<?= e(url($r ? '/reservas/' . $r['id'] : '/reservas')) ?>">Cancelar</a>
  </form>
</section>
