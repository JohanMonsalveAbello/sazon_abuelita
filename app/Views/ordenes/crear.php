<?php use App\Core\Auth; ?>
<?= partial('encabezado', ['titulo' => $titulo, 'volver' => ['Órdenes', '/ordenes']]) ?>
<section class="tarjeta formulario-angosto">
  <form method="post" action="<?= e(url('/ordenes')) ?>" class="js-validar" novalidate>
    <?= csrf_field() ?>
    <?php if ($reserva && $reserva['estado'] === 'confirmada'): ?>
      <input type="hidden" name="reserva_id" value="<?= e($reserva['id']) ?>">
      <div class="alert alert-info">
        Reserva <strong><?= e($reserva['codigo']) ?></strong> de <?= e($reserva['nombre_contacto']) ?>:
        mesa <?= e($reserva['mesa_numero']) ?>, <?= e($reserva['personas']) ?> personas. Al abrir la orden, la reserva quedará cumplida.
      </div>
    <?php else: ?>
      <?php if ($mesas === []): ?>
        <div class="alert alert-warning">Todas las mesas tienen una orden activa en este momento.</div>
      <?php endif; ?>
      <?= campo_select('mesa_id', 'Mesa', $mesas, '', ['required' => true]) ?>
    <?php endif; ?>
    <?php if (Auth::es('admin')): ?>
      <?= campo_select('mesero_id', 'Mesero responsable', $meseros, '', ['required' => true]) ?>
    <?php endif; ?>
    <?php if (!$reserva || $reserva['cliente_id'] === null): ?>
      <?= campo_select('cliente_id', 'Cliente registrado (opcional)', $clientes, '', [], 'Sin cuenta / visitante') ?>
    <?php endif; ?>
    <button class="btn btn-primary" type="submit">Abrir orden</button>
    <a class="btn btn-outline-secondary" href="<?= e(url('/ordenes')) ?>">Cancelar</a>
  </form>
</section>
