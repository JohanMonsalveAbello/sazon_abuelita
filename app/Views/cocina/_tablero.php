<?php
$columnas = [
    'enviada' => ['En cola', 'bi-send', 'iniciar', 'Empezar a preparar', 'btn-primary'],
    'en_preparacion' => ['En preparación', 'bi-fire', 'lista', 'Marcar lista', 'btn-success'],
    'lista' => ['Listas para servir', 'bi-bell', null, null, null],
];
?>
<div class="tablero">
  <?php foreach ($columnas as $estado => [$titulo, $icono, $accion, $textoBoton, $claseBoton]): ?>
    <?php $enColumna = array_filter($comandas, static fn ($c) => $c['estado'] === $estado); ?>
    <section class="columna" aria-label="<?= e($titulo) ?>">
      <h2><i class="bi <?= e($icono) ?>" aria-hidden="true"></i> <?= e($titulo) ?> <span class="contador"><?= count($enColumna) ?></span></h2>
      <?php if ($enColumna === []): ?>
        <p class="columna-vacia">Nada por aquí.</p>
      <?php endif; ?>
      <?php foreach ($enColumna as $c): ?>
        <?php $min = $c['enviada_en'] ? (int) floor((time() - strtotime($c['enviada_en'])) / 60) : 0; ?>
        <article class="comanda">
          <header>
            <strong>Mesa <?= e($c['mesa_numero']) ?></strong>
            <span class="comanda-tiempo <?= $min >= 30 && $estado !== 'lista' ? 'demorada' : '' ?>"><i class="bi bi-stopwatch" aria-hidden="true"></i> <?= e($min >= 90 ? floor($min / 60) . ' h' : $min . ' min') ?></span>
          </header>
          <p class="comanda-meta"><?= e($c['codigo']) ?> · <?= e($c['mesero']) ?><?= $c['ronda_actual'] > 1 ? ' · Ronda ' . e($c['ronda_actual']) : '' ?></p>
          <ul>
            <?php foreach ($c['items'] as $i): ?>
              <li><strong><?= e($i['cantidad']) ?> ×</strong> <?= e($i['plato']) ?><?php if ($i['nota'] !== ''): ?><em><?= e($i['nota']) ?></em><?php endif; ?></li>
            <?php endforeach; ?>
          </ul>
          <?php if ($accion && can('cocina.aprobar')): ?>
            <form method="post" action="<?= e(url('/cocina/' . $c['id'] . '/' . $accion)) ?>">
              <?= csrf_field() ?>
              <button class="btn btn-sm <?= e($claseBoton) ?> w-100" type="submit"><?= e($textoBoton) ?></button>
            </form>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>
</div>
