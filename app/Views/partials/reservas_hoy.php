<?php if ($hoy === []): ?>
  <?= partial('vacio', ['icono' => 'bi-calendar', 'titulo' => 'No hay reservas confirmadas para hoy']) ?>
<?php else: ?>
  <ul class="lista-accesos">
    <?php foreach ($hoy as $r): ?>
      <li><a href="<?= e(url('/reservas/' . $r['id'])) ?>">
        <span><strong><?= e(hora_12($r['hora'])) ?></strong> · <?= e($r['nombre_contacto']) ?> · <?= e($r['personas']) ?> pers.</span>
        <span class="text-nowrap">Mesa <?= e($r['mesa_numero']) ?></span>
      </a></li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
