<?php if ($m = \App\Core\Session::pull('success')): ?>
  <div class="alert alert-success alert-dismissible d-flex gap-2" role="status">
    <i class="bi bi-check-circle" aria-hidden="true"></i><div><?= e($m) ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
  </div>
<?php endif; ?>
<?php if ($m = \App\Core\Session::pull('error')): ?>
  <div class="alert alert-danger alert-dismissible d-flex gap-2" role="alert">
    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i><div><?= e($m) ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
  </div>
<?php endif; ?>
