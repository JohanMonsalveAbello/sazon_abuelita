<?php use App\Core\Auth; use App\Models\ReservaModel; ?>
<?= partial('encabezado', ['titulo' => $titulo, 'acciones' => can('reservas.crear') ? [['Nueva reserva', '/reservas/crear', 'bi-plus-lg']] : []]) ?>
<?= partial('filtros', ['campos' => [
    ['q', 'Buscar nombre, código, teléfono o correo', 'text'],
    ['estado', 'Estado', 'select', ReservaModel::ESTADOS],
    ['desde', 'Desde', 'date'],
    ['hasta', 'Hasta', 'date'],
]]) ?>
<section class="tarjeta p-0">
  <?php if ($lista->items === []): ?>
    <?= partial('vacio', ['icono' => 'bi-calendar', 'titulo' => 'No hay reservas para mostrar', 'accion' => can('reservas.crear') ? ['Crear reserva', '/reservas/crear'] : null]) ?>
  <?php else: ?>
    <table class="table tabla-apilable align-middle mb-0">
      <thead><tr>
        <th scope="col">Código</th>
        <th scope="col"><?= orden_columna('fecha', 'Fecha') ?></th>
        <?php if (!Auth::es('cliente')): ?><th scope="col"><?= orden_columna('nombre', 'Nombre') ?></th><?php endif; ?>
        <th scope="col"><?= orden_columna('personas', 'Personas') ?></th>
        <th scope="col">Mesa</th>
        <th scope="col"><?= orden_columna('estado', 'Estado') ?></th>
        <th scope="col"><span class="visually-hidden">Acciones</span></th>
      </tr></thead>
      <tbody>
      <?php foreach ($lista->items as $r): ?>
        <tr>
          <td data-etiqueta="Código"><code><?= e($r['codigo']) ?></code></td>
          <td data-etiqueta="Fecha" class="text-nowrap"><?= e(fecha_corta($r['fecha'])) ?> · <?= e(hora_12($r['hora'])) ?></td>
          <?php if (!Auth::es('cliente')): ?><td data-etiqueta="Nombre"><?= e($r['nombre_contacto']) ?></td><?php endif; ?>
          <td data-etiqueta="Personas"><?= e($r['personas']) ?></td>
          <td data-etiqueta="Mesa"><?= $r['mesa_numero'] ? e($r['mesa_numero']) : '—' ?></td>
          <td data-etiqueta="Estado"><?= insignia_estado($r['estado']) ?></td>
          <td class="acciones-fila"><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/reservas/' . $r['id'])) ?>">Ver</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?= partial('paginacion', ['p' => $lista]) ?>
  <?php endif; ?>
</section>
