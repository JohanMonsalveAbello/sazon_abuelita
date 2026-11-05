<?php /** @var array $top [['nombre','unidades','ingresos']] */ ?>
<?php if ($top === []): ?>
  <?= partial('vacio', ['icono' => 'bi-egg-fried', 'titulo' => 'Sin ventas en este periodo']) ?>
<?php else: ?>
  <?php $maximo = max(array_map('intval', array_column($top, 'unidades'))); ?>
  <ul class="ranking">
    <?php foreach ($top as $t): $pct = round((int) $t['unidades'] * 100 / $maximo, 1); ?>
      <li data-tip="<?= e($t['nombre'] . ' · ' . $t['unidades'] . ' unidades · ' . dinero($t['ingresos'])) ?>">
        <span class="ranking-nombre"><?= e($t['nombre']) ?></span>
        <svg class="ranking-barra" aria-hidden="true"><rect x="0" y="2" width="<?= e($pct) ?>%" height="10" rx="3"/></svg>
        <span class="ranking-valor"><?= e($t['unidades']) ?> <span class="visually-hidden">unidades</span></span>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
