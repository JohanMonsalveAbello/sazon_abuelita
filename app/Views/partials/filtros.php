<?php
/**
 * Barra de filtros reutilizable (GET).
 * $campos: lista de ['nombre', 'etiqueta', 'text|date|select', opciones?]; los valores se leen de la URL.
 */
$hayFiltros = false;
?>
<form class="filtros" method="get" action="<?= e(url(\App\Core\Request::current()->path())) ?>" role="search" aria-label="Filtrar listado">
  <?php foreach ($campos as $c): ?>
    <?php
    [$nombre, $etiqueta, $tipo] = $c;
    $valor = \App\Core\Request::current()->query($nombre, $c[4] ?? '');
    $hayFiltros = $hayFiltros || \App\Core\Request::current()->query($nombre) !== '';
    ?>
    <div class="filtro <?= $tipo === 'text' ? 'filtro-texto' : '' ?>">
      <label class="form-label" for="filtro-<?= e($nombre) ?>"><?= e($etiqueta) ?></label>
      <?php if ($tipo === 'select'): ?>
        <select class="form-select form-select-sm" id="filtro-<?= e($nombre) ?>" name="<?= e($nombre) ?>">
          <option value="">Todos</option>
          <?php foreach ($c[3] as $v => $t): ?>
            <option value="<?= e($v) ?>"<?= (string) $v === $valor ? ' selected' : '' ?>><?= e($t) ?></option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <input class="form-control form-control-sm" id="filtro-<?= e($nombre) ?>" type="<?= e($tipo) ?>" name="<?= e($nombre) ?>" value="<?= e($valor) ?>" maxlength="80">
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <div class="filtro-botones">
    <button class="btn btn-sm btn-dark" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i> Filtrar</button>
    <?php if ($hayFiltros): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(url(\App\Core\Request::current()->path())) ?>">Limpiar</a><?php endif; ?>
  </div>
</form>
