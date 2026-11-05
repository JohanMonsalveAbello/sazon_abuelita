<?= partial('encabezado', ['titulo' => $titulo, 'subtitulo' => 'Quién hizo qué y cuándo. Nunca se registran contraseñas ni tokens.']) ?>
<?= partial('filtros', ['campos' => [
    ['q', 'Buscar acción, detalle o usuario', 'text'],
    ['entidad', 'Entidad', 'select', array_combine($entidades, $entidades)],
    ['desde', 'Desde', 'date'],
    ['hasta', 'Hasta', 'date'],
]]) ?>
<section class="tarjeta p-0">
  <?php if ($lista->items === []): ?>
    <?= partial('vacio', ['icono' => 'bi-clock-history', 'titulo' => 'No hay registros con esos filtros']) ?>
  <?php else: ?>
    <table class="table table-sm tabla-apilable align-middle mb-0">
      <thead><tr>
        <th scope="col"><?= orden_columna('fecha', 'Fecha') ?></th>
        <th scope="col"><?= orden_columna('usuario', 'Usuario') ?></th>
        <th scope="col"><?= orden_columna('accion', 'Acción') ?></th>
        <th scope="col"><?= orden_columna('entidad', 'Entidad') ?></th>
        <th scope="col">Detalle</th>
        <th scope="col">IP</th>
      </tr></thead>
      <tbody>
      <?php foreach ($lista->items as $a): ?>
        <tr>
          <td data-etiqueta="Fecha" class="text-nowrap"><?= e(fecha_hora($a['creado_en'])) ?></td>
          <td data-etiqueta="Usuario"><?= e($a['usuario'] ?? 'Visitante') ?></td>
          <td data-etiqueta="Acción"><code><?= e($a['accion']) ?></code></td>
          <td data-etiqueta="Entidad"><?= e($a['entidad']) ?><?= $a['entidad_id'] ? ' #' . e($a['entidad_id']) : '' ?></td>
          <td data-etiqueta="Detalle" class="celda-detalle"><?= e($a['detalle'] ?? '—') ?></td>
          <td data-etiqueta="IP"><?= e($a['ip']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?= partial('paginacion', ['p' => $lista]) ?>
  <?php endif; ?>
</section>
