<div class="vacio">
  <i class="bi <?= e($icono ?? 'bi-inbox') ?>" aria-hidden="true"></i>
  <p class="vacio-titulo"><?= e($titulo) ?></p>
  <?php if (!empty($texto)): ?><p><?= e($texto) ?></p><?php endif; ?>
  <?php if (!empty($accion)): ?><a class="btn btn-primary btn-sm" href="<?= e(url($accion[1])) ?>"><?= e($accion[0]) ?></a><?php endif; ?>
</div>
