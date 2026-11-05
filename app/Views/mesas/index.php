<?php use App\Models\MesaModel; ?>
<?= partial('encabezado', ['titulo' => $titulo, 'acciones' => can('mesas.crear') ? [['Nueva mesa', '/mesas/crear', 'bi-plus-lg']] : []]) ?>
<section class="tarjeta p-0">
  <?php if ($lista === []): ?>
    <?= partial('vacio', ['icono' => 'bi-grid-3x3-gap', 'titulo' => 'Aún no hay mesas registradas']) ?>
  <?php else: ?>
    <table class="table tabla-apilable align-middle mb-0">
      <thead><tr><th scope="col">Mesa</th><th scope="col">Capacidad</th><th scope="col">Zona</th><th scope="col">Ahora</th><?php if (can('mesas.editar')): ?><th scope="col"><span class="visually-hidden">Acciones</span></th><?php endif; ?></tr></thead>
      <tbody>
      <?php foreach ($lista as $m): ?>
        <tr>
          <td data-etiqueta="Mesa"><strong><?= e($m['numero']) ?></strong></td>
          <td data-etiqueta="Capacidad"><?= e($m['capacidad']) ?> personas</td>
          <td data-etiqueta="Zona"><?= e(MesaModel::ZONAS[$m['zona']]) ?></td>
          <td data-etiqueta="Ahora">
            <?php if ((int) $m['activa'] !== 1): ?>
              <?= insignia_estado('inactivo') ?>
            <?php elseif ($m['orden_id']): ?>
              <span class="estado estado-fuego"><i class="bi bi-people" aria-hidden="true"></i> Ocupada · <?= e($m['orden_codigo']) ?></span>
            <?php else: ?>
              <span class="estado estado-ok"><i class="bi bi-check-circle" aria-hidden="true"></i> Libre</span>
            <?php endif; ?>
          </td>
          <?php if (can('mesas.editar')): ?>
          <td class="acciones-fila">
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/mesas/' . $m['id'] . '/editar')) ?>">Editar</a>
            <form method="post" action="<?= e(url('/mesas/' . $m['id'] . '/eliminar')) ?>" data-confirmar="<?= e('¿Eliminar la mesa ' . $m['numero'] . '?') ?>">
              <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button>
            </form>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>
