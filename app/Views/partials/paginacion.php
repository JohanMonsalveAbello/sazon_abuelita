<?php
/** @var App\Core\Paginator $p */
$total = $p->paginas();
$actual = $p->pagina;
$inicio = max(1, $actual - 2);
$fin = min($total, $actual + 2);
?>
<div class="paginacion">
  <span class="paginacion-resumen">Mostrando <?= $p->desde() ?>–<?= $p->hasta() ?> de <?= $p->total ?></span>
  <?php if ($total > 1): ?>
  <nav aria-label="Páginas">
    <ul class="pagination pagination-sm mb-0">
      <li class="page-item <?= $actual === 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(url_lista(['page' => max(1, $actual - 1)])) ?>" aria-label="Anterior"><i class="bi bi-chevron-left" aria-hidden="true"></i></a></li>
      <?php for ($i = $inicio; $i <= $fin; $i++): ?>
        <li class="page-item <?= $i === $actual ? 'active' : '' ?>"><a class="page-link" href="<?= e(url_lista(['page' => $i])) ?>"<?= $i === $actual ? ' aria-current="page"' : '' ?>><?= $i ?></a></li>
      <?php endfor; ?>
      <li class="page-item <?= $actual === $total ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(url_lista(['page' => min($total, $actual + 1)])) ?>" aria-label="Siguiente"><i class="bi bi-chevron-right" aria-hidden="true"></i></a></li>
    </ul>
  </nav>
  <?php endif; ?>
</div>
