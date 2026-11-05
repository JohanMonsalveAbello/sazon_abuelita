<?= partial('encabezado', ['titulo' => $titulo, 'subtitulo' => 'Estas categorías son los filtros de la carta en la página pública.', 'acciones' => [['Nueva categoría', '/categorias/crear', 'bi-plus-lg']]]) ?>
<section class="tarjeta p-0">
  <?php if ($lista === []): ?>
    <?= partial('vacio', ['icono' => 'bi-tags', 'titulo' => 'Aún no hay categorías', 'accion' => ['Crear categoría', '/categorias/crear']]) ?>
  <?php else: ?>
    <table class="table tabla-apilable align-middle mb-0">
      <thead><tr><th scope="col">Orden</th><th scope="col">Nombre</th><th scope="col">Platos</th><th scope="col"><span class="visually-hidden">Acciones</span></th></tr></thead>
      <tbody>
      <?php foreach ($lista as $c): ?>
        <tr>
          <td data-etiqueta="Orden"><?= e($c['orden']) ?></td>
          <td data-etiqueta="Nombre"><?= e($c['nombre']) ?></td>
          <td data-etiqueta="Platos"><?= e($c['platos']) ?></td>
          <td class="acciones-fila">
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/categorias/' . $c['id'] . '/editar')) ?>">Editar</a>
            <form method="post" action="<?= e(url('/categorias/' . $c['id'] . '/eliminar')) ?>" data-confirmar="<?= e('¿Eliminar la categoría ' . $c['nombre'] . '?') ?>">
              <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
