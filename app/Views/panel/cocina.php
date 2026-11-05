<?= partial('encabezado', ['titulo' => $titulo, 'acciones' => [['Abrir tablero', '/cocina', 'bi-fire']]]) ?>

<div class="kpis">
  <?= partial('kpi', ['icono' => 'bi-send', 'etiqueta' => 'En cola', 'valor' => $kpi['en_cola']]) ?>
  <?= partial('kpi', ['icono' => 'bi-fire', 'etiqueta' => 'En preparación', 'valor' => $kpi['en_preparacion']]) ?>
  <?= partial('kpi', ['icono' => 'bi-check2-all', 'etiqueta' => 'Listas hoy', 'valor' => $kpi['listas_hoy']]) ?>
  <?= partial('kpi', ['icono' => 'bi-stopwatch', 'etiqueta' => 'Tiempo promedio', 'valor' => $kpi['minutos_promedio'] === null ? 'Sin datos' : $kpi['minutos_promedio'] . ' min', 'nota' => 'De recibida a lista, últimos 30 días']) ?>
</div>

<section class="tarjeta">
  <h2>Disponibilidad de la carta</h2>
  <ul class="lista-accesos">
    <li><a href="<?= e(url('/platos?estado=agotado')) ?>"><span><i class="bi bi-slash-circle" aria-hidden="true"></i> Platos agotados</span><strong><?= e($kpi['agotados']) ?></strong></a></li>
    <li><a href="<?= e(url('/platos')) ?>"><span><i class="bi bi-egg-fried" aria-hidden="true"></i> Marcar un plato como agotado</span><i class="bi bi-chevron-right" aria-hidden="true"></i></a></li>
  </ul>
</section>
