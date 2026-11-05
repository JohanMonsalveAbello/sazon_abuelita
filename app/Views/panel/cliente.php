<?= partial('encabezado', ['titulo' => 'Hola, ' . usuario()['nombre'], 'acciones' => [['Reservar mesa', '/reservas/crear', 'bi-plus-lg']]]) ?>

<div class="kpis">
  <?= partial('kpi', ['icono' => 'bi-calendar-check', 'etiqueta' => 'Próximas reservas', 'valor' => $kpi['proximas']]) ?>
  <?= partial('kpi', ['icono' => 'bi-shop', 'etiqueta' => 'Visitas registradas', 'valor' => $kpi['visitas']]) ?>
  <?= partial('kpi', ['icono' => 'bi-receipt', 'etiqueta' => 'Total consumido', 'valor' => dinero($kpi['gastado'])]) ?>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <section class="tarjeta h-100">
      <h2>Mis próximas reservas</h2>
      <?php if ($proximas === []): ?>
        <?= partial('vacio', ['icono' => 'bi-calendar', 'titulo' => 'No tienes reservas próximas', 'texto' => 'Aparta tu mesa en un minuto.', 'accion' => ['Reservar mesa', '/reservas/crear']]) ?>
      <?php else: ?>
        <ul class="lista-accesos">
          <?php foreach ($proximas as $r): ?>
            <li><a href="<?= e(url('/reservas/' . $r['id'])) ?>">
              <span><strong><?= e(fecha_corta($r['fecha'])) ?> · <?= e(hora_12($r['hora'])) ?></strong> · <?= e($r['personas']) ?> pers.</span>
              <?= insignia_estado($r['estado']) ?>
            </a></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-lg-5">
    <section class="tarjeta h-100">
      <h2>Cuéntanos cómo te fue</h2>
      <?php if ($sinResena === []): ?>
        <?= partial('vacio', ['icono' => 'bi-star', 'titulo' => 'No tienes visitas por calificar']) ?>
      <?php else: ?>
        <ul class="lista-accesos">
          <?php foreach ($sinResena as $o): ?>
            <li><a href="<?= e(url('/resenas/crear/' . $o['id'])) ?>">
              <span><?= e(fecha_corta($o['creado_en'])) ?> · <?= e($o['codigo']) ?></span>
              <span class="text-nowrap"><i class="bi bi-star" aria-hidden="true"></i> Calificar</span>
            </a></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>
</div>
