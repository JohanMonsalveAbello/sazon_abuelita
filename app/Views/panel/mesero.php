<?= partial('encabezado', ['titulo' => $titulo, 'acciones' => [['Abrir orden', '/ordenes/crear', 'bi-plus-lg']]]) ?>

<div class="kpis">
  <?= partial('kpi', ['icono' => 'bi-bell', 'etiqueta' => 'Listas para servir', 'valor' => $kpi['listas']]) ?>
  <?= partial('kpi', ['icono' => 'bi-receipt', 'etiqueta' => 'Mis órdenes activas', 'valor' => $kpi['activas']]) ?>
  <?= partial('kpi', ['icono' => 'bi-cash-coin', 'etiqueta' => 'Mis ventas de hoy', 'valor' => dinero($kpi['ventas_hoy'])]) ?>
  <?= partial('kpi', ['icono' => 'bi-calendar-check', 'etiqueta' => 'Reservas por confirmar', 'valor' => $kpi['reservas_pendientes']]) ?>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <section class="tarjeta h-100">
      <h2>Mis órdenes activas</h2>
      <?php if ($ordenes === []): ?>
        <?= partial('vacio', ['icono' => 'bi-receipt', 'titulo' => 'No tienes órdenes activas', 'texto' => 'Abre una orden cuando sientes a un cliente.', 'accion' => ['Abrir orden', '/ordenes/crear']]) ?>
      <?php else: ?>
        <ul class="lista-accesos">
          <?php foreach ($ordenes as $o): ?>
            <li><a href="<?= e(url('/ordenes/' . $o['id'])) ?>">
              <span><strong>Mesa <?= e($o['mesa_numero']) ?></strong> · <?= e($o['codigo']) ?></span>
              <?= insignia_estado($o['estado']) ?>
            </a></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-lg-5">
    <section class="tarjeta h-100">
      <h2>Reservas confirmadas de hoy</h2>
      <?= partial('reservas_hoy', ['hoy' => $hoy]) ?>
    </section>
  </div>
</div>
