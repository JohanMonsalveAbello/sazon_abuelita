<?php $o = $orden; $c = $cuenta; $fuente = $c ?? $previa; ?>
<?= partial('encabezado', ['titulo' => $titulo, 'volver' => ['Orden', '/ordenes/' . $o['id']]]) ?>
<div class="alert alert-warning d-flex gap-2" role="note">
  <i class="bi bi-info-circle" aria-hidden="true"></i>
  <div><strong>Pago simulado.</strong> Es un ejercicio académico: no se procesa dinero real ni se piden datos de tarjetas.</div>
</div>
<div class="row g-3">
  <div class="col-lg-7">
    <section class="tarjeta">
      <h2>Detalle</h2>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th scope="col">Plato</th><th scope="col" class="text-end">Cant.</th><th scope="col" class="text-end">Precio</th><th scope="col" class="text-end">Importe</th></tr></thead>
          <tbody>
            <?php foreach ($items as $i): ?>
              <tr><td><?= e($i['plato']) ?></td><td class="text-end"><?= e($i['cantidad']) ?></td><td class="text-end"><?= e(dinero($i['precio_unitario'])) ?></td><td class="text-end"><?= e(dinero($i['importe'])) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><th scope="row" colspan="3" class="text-end fw-normal">Subtotal</th><td class="text-end"><?= e(dinero($fuente['subtotal'])) ?></td></tr>
            <tr><th scope="row" colspan="3" class="text-end fw-normal">Impoconsumo (8 %)</th><td class="text-end"><?= e(dinero($fuente['impoconsumo'])) ?></td></tr>
            <?php if ($c): ?><tr><th scope="row" colspan="3" class="text-end fw-normal">Propina</th><td class="text-end"><?= e(dinero($c['propina'])) ?></td></tr><?php endif; ?>
            <tr><th scope="row" colspan="3" class="text-end"><?= $c ? 'Total pagado' : 'Total sin propina' ?></th><td class="text-end fw-bold"><?= e(dinero($fuente['total'])) ?></td></tr>
          </tfoot>
        </table>
      </div>
    </section>
  </div>
  <div class="col-lg-5">
    <section class="tarjeta">
      <?php if ($c): ?>
        <h2>Pago registrado</h2>
        <dl class="datos">
          <dt>Método</dt><dd><?= e($metodos[$c['metodo']] ?? $c['metodo']) ?></dd>
          <dt>Fecha</dt><dd><?= e(fecha_hora($c['creado_en'])) ?></dd>
          <dt>Registró</dt><dd><?= e($c['cerrada_por_nombre']) ?></dd>
        </dl>
      <?php elseif (can('cuentas.crear')): ?>
        <h2>Cobrar</h2>
        <form method="post" action="<?= e(url('/ordenes/' . $o['id'] . '/cuenta')) ?>" class="js-validar" novalidate data-confirmar="¿Registrar el pago? La orden se cerrará y la mesa quedará libre.">
          <?= csrf_field() ?>
          <?= campo_select('propina', 'Propina voluntaria', ['0' => 'Sin propina', '5' => '5 %', '10' => '10 %', '15' => '15 %', '20' => '20 %'], '10', ['required' => true], null) ?>
          <?= campo_select('metodo', 'Método de pago', $metodos, '', ['required' => true]) ?>
          <button class="btn btn-primary w-100" type="submit"><i class="bi bi-cash-coin" aria-hidden="true"></i> Registrar pago simulado</button>
        </form>
      <?php else: ?>
        <h2>Cuenta abierta</h2>
        <p class="mb-0">Tu mesero registrará el pago. El total final incluye la propina que decidas dejar.</p>
      <?php endif; ?>
    </section>
  </div>
</div>
