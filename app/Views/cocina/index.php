<?= partial('encabezado', ['titulo' => $titulo, 'subtitulo' => 'Se actualiza solo cada 15 segundos.', 'acciones' => can('platos.aprobar') ? [['Agotar platos', '/platos', 'bi-slash-circle']] : []]) ?>
<div id="tablero" data-url="<?= e(url('/cocina/tablero')) ?>" aria-live="polite">
  <?= \App\Core\View::fetch('cocina/_tablero', ['comandas' => $comandas]) ?>
</div>
