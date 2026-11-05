<?php use App\Models\ReporteModel; ?>
<?php
$query = array_filter($f, static fn ($v) => $v !== '');
?>
<div class="encabezado">
  <div><h1><?= e($titulo) ?></h1><p class="subtitulo">Cuentas cerradas entre <?= e(fecha_corta($f['desde'])) ?> y <?= e(fecha_corta($f['hasta'])) ?>.</p></div>
  <div class="encabezado-acciones">
    <a class="btn btn-primary" href="<?= e(url('/reportes/exportar') . '?' . http_build_query($query)) ?>"><i class="bi bi-download" aria-hidden="true"></i> Exportar CSV</a>
  </div>
</div>
<?= partial('filtros', ['campos' => [
    ['desde', 'Desde', 'date', [], $f['desde']],
    ['hasta', 'Hasta', 'date', [], $f['hasta']],
    ['mesero_id', 'Mesero', 'select', $meseros],
    ['metodo', 'Método', 'select', ReporteModel::METODOS],
]]) ?>
<div class="kpis">
  <?= partial('kpi', ['icono' => 'bi-cash-coin', 'etiqueta' => 'Ventas totales', 'valor' => dinero($resumen['total'])]) ?>
  <?= partial('kpi', ['icono' => 'bi-receipt', 'etiqueta' => 'Cuentas', 'valor' => $resumen['cuentas']]) ?>
  <?= partial('kpi', ['icono' => 'bi-graph-up', 'etiqueta' => 'Ticket promedio', 'valor' => dinero($resumen['ticket'])]) ?>
  <?= partial('kpi', ['icono' => 'bi-heart', 'etiqueta' => 'Propinas', 'valor' => dinero($resumen['propina'])]) ?>
</div>
<div class="row g-3">
  <div class="col-xl-8">
    <section class="tarjeta p-0 h-100">
      <?php if ($lista->items === []): ?>
        <?= partial('vacio', ['icono' => 'bi-bar-chart', 'titulo' => 'No hay ventas con esos filtros']) ?>
      <?php else: ?>
        <table class="table table-sm tabla-apilable align-middle mb-0">
          <thead><tr><th scope="col">Fecha</th><th scope="col">Orden</th><th scope="col">Mesa</th><th scope="col">Mesero</th><th scope="col">Método</th><th scope="col" class="text-lg-end">Total</th></tr></thead>
          <tbody>
          <?php foreach ($lista->items as $v): ?>
            <tr>
              <td data-etiqueta="Fecha" class="text-nowrap"><?= e(fecha_hora($v['creado_en'])) ?></td>
              <td data-etiqueta="Orden"><a href="<?= e(url('/ordenes/' . $v['orden_id'])) ?>"><code><?= e($v['codigo']) ?></code></a></td>
              <td data-etiqueta="Mesa"><?= e($v['mesa']) ?></td>
              <td data-etiqueta="Mesero"><?= e($v['mesero']) ?></td>
              <td data-etiqueta="Método"><?= e(ReporteModel::METODOS[$v['metodo']] ?? $v['metodo']) ?></td>
              <td data-etiqueta="Total" class="text-lg-end"><?= e(dinero($v['total'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?= partial('paginacion', ['p' => $lista]) ?>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-xl-4">
    <section class="tarjeta h-100">
      <h2>Platos más vendidos</h2>
      <?= partial('top_platos', ['top' => $top]) ?>
    </section>
  </div>
</div>
