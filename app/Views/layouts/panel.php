<?php
$u = usuario();
$rutaActual = \App\Core\Request::current()->path();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo ?? 'Panel') ?> · El Sazón de la Abuela</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/bootstrap.min.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/panel.css')) ?>">
</head>
<body>
<a class="visually-hidden-focusable saltar" href="#contenido">Saltar al contenido</a>

<header class="barra-movil d-lg-none">
  <button class="btn btn-sm boton-menu" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuPanel" aria-controls="menuPanel" aria-label="Abrir menú">
    <i class="bi bi-list" aria-hidden="true"></i>
  </button>
  <a class="marca-movil" href="<?= e(url('/panel')) ?>">El Sazón de la Abuela</a>
</header>

<div class="contenedor-panel">
  <aside class="offcanvas-lg offcanvas-start lateral" tabindex="-1" id="menuPanel" aria-label="Menú del panel">
    <div class="lateral-marca">
      <a href="<?= e(url('/')) ?>" title="Ver la página pública">
        <span class="marca-sup">Restaurante</span>
        <strong>El Sazón de la Abuela</strong>
      </a>
      <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#menuPanel" aria-label="Cerrar menú"></button>
    </div>
    <nav class="lateral-nav" aria-label="Módulos">
      <?php foreach (config('menu') as [$texto, $icono, $ruta, $permiso]): ?>
        <?php if ($permiso === null || can($permiso)): ?>
          <?php $activa = $rutaActual === $ruta || str_starts_with($rutaActual, $ruta . '/'); ?>
          <a class="<?= $activa ? 'activa' : '' ?>" href="<?= e(url($ruta)) ?>"<?= $activa ? ' aria-current="page"' : '' ?>>
            <i class="bi <?= e($icono) ?>" aria-hidden="true"></i> <?= e($texto) ?>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <div class="lateral-usuario">
      <a class="lateral-perfil" href="<?= e(url('/perfil')) ?>">
        <span class="nombre"><?= e($u['nombre']) ?></span>
        <span class="rol"><?= e($u['rol_nombre']) ?></span>
      </a>
      <form method="post" action="<?= e(url('/logout')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-sm boton-salir" type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Salir</button>
      </form>
    </div>
  </aside>

  <main class="principal" id="contenido" tabindex="-1">
    <?= partial('mensajes') ?>
    <?= $contenido ?>
  </main>
</div>

<div class="modal fade" id="modalConfirmar" tabindex="-1" aria-labelledby="modalConfirmarTitulo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h2 class="modal-title fs-5" id="modalConfirmarTitulo">Confirmar acción</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
      <div class="modal-body" id="modalConfirmarTexto"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Volver</button>
        <button type="button" class="btn btn-danger" id="modalConfirmarSi">Sí, continuar</button>
      </div>
    </div>
  </div>
</div>
<div id="tip" class="tip-grafica" role="status" hidden></div>

<script src="<?= e(asset('vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset('js/panel.js')) ?>"></script>
</body>
</html>
