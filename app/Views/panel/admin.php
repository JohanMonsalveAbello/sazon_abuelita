<?= partial('encabezado', ['titulo' => $titulo, 'subtitulo' => 'Cifras calculadas en vivo desde la base de datos.']) ?>

<div class="kpis">
  <?= partial('kpi', ['icono' => 'bi-cash-coin', 'etiqueta' => 'Ventas de hoy', 'valor' => dinero($kpi['hoy'])]) ?>
  <?= partial('kpi', ['icono' => 'bi-calendar3', 'etiqueta' => 'Ventas del mes', 'valor' => dinero($kpi['mes']), 'nota' => $kpi['cuentas_mes'] . ' cuentas cerradas']) ?>
  <?= partial('kpi', ['icono' => 'bi-receipt', 'etiqueta' => 'Ticket promedio del mes', 'valor' => dinero($kpi['ticket'])]) ?>
  <?= partial('kpi', ['icono' => 'bi-fire', 'etiqueta' => 'Órdenes activas', 'valor' => $kpi['ordenes_activas']]) ?>
</div>

<div class="row g-3">
  <div class="col-xl-8">
    <section class="tarjeta h-100">
      <h2>Ventas de los últimos 14 días</h2>
      <?= partial('grafica_ventas', ['serie' => $serie]) ?>
    </section>
  </div>
  <div class="col-xl-4">
    <section class="tarjeta h-100">
      <h2>Platos más vendidos <small>últimos 30 días</small></h2>
      <?= partial('top_platos', ['top' => $top]) ?>
    </section>
  </div>
  <div class="col-lg-6">
    <section class="tarjeta h-100">
      <h2>Pendiente por atender</h2>
      <ul class="lista-accesos">
        <li><a href="<?= e(url('/reservas?estado=pendiente')) ?>"><span><i class="bi bi-calendar-check" aria-hidden="true"></i> Reservas por confirmar</span><strong><?= e($kpi['reservas_pendientes']) ?></strong></a></li>
        <li><a href="<?= e(url('/resenas?estado=pendiente')) ?>"><span><i class="bi bi-star" aria-hidden="true"></i> Reseñas por moderar</span><strong><?= e($kpi['resenas_pendientes']) ?></strong></a></li>
        <li><a href="<?= e(url('/cocina')) ?>"><span><i class="bi bi-fire" aria-hidden="true"></i> Tablero de cocina</span><i class="bi bi-chevron-right" aria-hidden="true"></i></a></li>
      </ul>
    </section>
  </div>
  <div class="col-lg-6">
    <section class="tarjeta h-100">
      <h2>Reservas confirmadas de hoy</h2>
      <?= partial('reservas_hoy', ['hoy' => $hoy]) ?>
    </section>
  </div>
</div>
