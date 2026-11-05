<?php /** @var string $titulo @var string|null $subtitulo @var array $acciones [[texto, url, icono]] */ ?>
<div class="encabezado">
  <div>
    <?php if (!empty($volver)): ?><a class="enlace-volver" href="<?= e(url($volver[1])) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> <?= e($volver[0]) ?></a><?php endif; ?>
    <h1><?= e($titulo) ?></h1>
    <?php if (!empty($subtitulo)): ?><p class="subtitulo"><?= e($subtitulo) ?></p><?php endif; ?>
  </div>
  <?php if (!empty($acciones)): ?>
    <div class="encabezado-acciones">
      <?php foreach ($acciones as [$texto, $destino, $icono]): ?>
        <a class="btn btn-primary" href="<?= e(url($destino)) ?>"><i class="bi <?= e($icono) ?>" aria-hidden="true"></i> <?= e($texto) ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
