<?php use App\Core\Auth; use App\Models\OrdenModel; ?>
<?= partial('encabezado', ['titulo' => $titulo, 'acciones' => can('ordenes.crear') ? [['Abrir orden', '/ordenes/crear', 'bi-plus-lg']] : []]) ?>
<?= partial('filtros', ['campos' => [
    ['q', Auth::es('cliente') ? 'Buscar por código' : 'Buscar código, cliente o mesero', 'text'],
    ['estado', 'Estado', 'select', OrdenModel::ESTADOS],
    ['desde', 'Desde', 'date'],
    ['hasta', 'Hasta', 'date'],
]]) ?>
<section class="tarjeta p-0">
  <?php if ($lista->items === []): ?>
    <?= partial('vacio', ['icono' => 'bi-receipt', 'titulo' => 'No hay órdenes para mostrar', 'accion' => can('ordenes.crear') ? ['Abrir orden', '/ordenes/crear'] : null]) ?>
  <?php else: ?>
    <table class="table tabla-apilable align-middle mb-0">
      <thead><tr>
        <th scope="col"><?= orden_columna('codigo', 'Código') ?></th>
        <th scope="col"><?= orden_columna('fecha', 'Fecha') ?></th>
        <th scope="col"><?= orden_columna('mesa', 'Mesa') ?></th>
        <?php if (Auth::es('admin')): ?><th scope="col"><?= orden_columna('mesero', 'Mesero') ?></th><?php endif; ?>
        <?php if (!Auth::es('cliente')): ?><th scope="col">Cliente</th><?php endif; ?>
        <th scope="col">Subtotal</th>
        <th scope="col"><?= orden_columna('estado', 'Estado') ?></th>
        <th scope="col"><span class="visually-hidden">Acciones</span></th>
      </tr></thead>
      <tbody>
      <?php foreach ($lista->items as $o): ?>
        <tr>
          <td data-etiqueta="Código"><code><?= e($o['codigo']) ?></code></td>
          <td data-etiqueta="Fecha" class="text-nowrap"><?= e(fecha_hora($o['creado_en'])) ?></td>
          <td data-etiqueta="Mesa"><?= e($o['mesa_numero']) ?></td>
          <?php if (Auth::es('admin')): ?><td data-etiqueta="Mesero"><?= e($o['mesero']) ?></td><?php endif; ?>
          <?php if (!Auth::es('cliente')): ?><td data-etiqueta="Cliente"><?= e($o['cliente'] ?? 'Sin registrar') ?></td><?php endif; ?>
          <td data-etiqueta="Subtotal"><?= e(dinero($o['subtotal'])) ?></td>
          <td data-etiqueta="Estado"><?= insignia_estado($o['estado']) ?></td>
          <td class="acciones-fila"><a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/ordenes/' . $o['id'])) ?>">Ver</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?= partial('paginacion', ['p' => $lista]) ?>
  <?php endif; ?>
</section>
