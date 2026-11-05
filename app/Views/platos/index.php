<?= partial('encabezado', [
    'titulo' => $titulo,
    'subtitulo' => can('platos.aprobar') && !can('platos.editar') ? 'Marca los platos agotados: dejarán de ofrecerse de inmediato.' : null,
    'acciones' => can('platos.crear') ? [['Nuevo plato', '/platos/crear', 'bi-plus-lg']] : [],
]) ?>
<?= partial('filtros', ['campos' => [
    ['q', 'Buscar plato', 'text'],
    ['categoria', 'Categoría', 'select', $categorias],
    ['estado', 'Estado', 'select', ['disponible' => 'Disponible', 'agotado' => 'Agotado']],
]]) ?>
<section class="tarjeta p-0">
  <?php if ($lista->items === []): ?>
    <?= partial('vacio', ['icono' => 'bi-egg-fried', 'titulo' => 'No hay platos con esos filtros']) ?>
  <?php else: ?>
    <table class="table tabla-apilable align-middle mb-0">
      <thead><tr>
        <th scope="col"><span class="visually-hidden">Foto</span></th>
        <th scope="col"><?= orden_columna('nombre', 'Plato') ?></th>
        <th scope="col"><?= orden_columna('categoria', 'Categoría') ?></th>
        <th scope="col"><?= orden_columna('precio', 'Precio') ?></th>
        <th scope="col">Estado</th>
        <?php if (can('platos.aprobar') || can('platos.editar')): ?><th scope="col"><span class="visually-hidden">Acciones</span></th><?php endif; ?>
      </tr></thead>
      <tbody>
      <?php foreach ($lista->items as $p): ?>
        <tr>
          <td class="celda-foto"><img src="<?= e(asset('assets/img/' . $p['imagen'])) ?>" alt="" class="foto-plato" loading="lazy"></td>
          <td data-etiqueta="Plato"><strong><?= e($p['nombre']) ?></strong><?php if ((int) $p['destacado'] === 1): ?> <span class="badge text-bg-light"><i class="bi bi-star-fill" aria-hidden="true"></i> Estrella</span><?php endif; ?></td>
          <td data-etiqueta="Categoría"><?= e($p['categoria']) ?></td>
          <td data-etiqueta="Precio"><?= e(dinero($p['precio'])) ?></td>
          <td data-etiqueta="Estado"><?= insignia_estado((int) $p['disponible'] === 1 ? 'disponible' : 'agotado') ?></td>
          <?php if (can('platos.aprobar') || can('platos.editar')): ?>
          <td class="acciones-fila">
            <?php if (can('platos.aprobar')): ?>
              <form method="post" action="<?= e(url('/platos/' . $p['id'] . '/disponibilidad')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-outline-secondary" type="submit"><?= (int) $p['disponible'] === 1 ? 'Marcar agotado' : 'Marcar disponible' ?></button>
              </form>
            <?php endif; ?>
            <?php if (can('platos.editar')): ?>
              <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/platos/' . $p['id'] . '/editar')) ?>">Editar</a>
            <?php endif; ?>
            <?php if (can('platos.eliminar')): ?>
              <form method="post" action="<?= e(url('/platos/' . $p['id'] . '/eliminar')) ?>" data-confirmar="<?= e('¿Eliminar ' . $p['nombre'] . ' de la carta?') ?>">
                <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
              </form>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?= partial('paginacion', ['p' => $lista]) ?>
  <?php endif; ?>
</section>
