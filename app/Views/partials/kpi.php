<div class="kpi">
  <span class="kpi-etiqueta"><i class="bi <?= e($icono) ?>" aria-hidden="true"></i> <?= e($etiqueta) ?></span>
  <strong class="kpi-valor"><?= e($valor) ?></strong>
  <?php if (!empty($nota)): ?><span class="kpi-nota"><?= e($nota) ?></span><?php endif; ?>
</div>
