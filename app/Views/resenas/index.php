<?php use App\Core\Auth; ?>
<?= partial('encabezado', ['titulo' => $titulo, 'subtitulo' => Auth::es('cliente') ? 'Puedes calificar tus visitas pagadas desde «Mis consumos» o desde Inicio.' : 'Solo las reseñas aprobadas aparecen en la página pública y cuentan en sus indicadores.']) ?>
<?= partial('filtros', ['campos' => [['estado', 'Estado', 'select', ['pendiente' => 'Pendiente', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada']]]]) ?>
<section class="tarjeta p-0">
  <?php if ($lista->items === []): ?>
    <?= partial('vacio', ['icono' => 'bi-star', 'titulo' => 'No hay reseñas para mostrar']) ?>
  <?php else: ?>
    <table class="table tabla-apilable align-middle mb-0">
      <thead><tr>
        <th scope="col">Fecha</th>
        <?php if (!Auth::es('cliente')): ?><th scope="col">Cliente</th><?php endif; ?>
        <th scope="col">Calificación</th><th scope="col">Comentario</th><th scope="col">Estado</th>
        <?php if (can('resenas.aprobar')): ?><th scope="col"><span class="visually-hidden">Acciones</span></th><?php endif; ?>
      </tr></thead>
      <tbody>
      <?php foreach ($lista->items as $r): ?>
        <tr>
          <td data-etiqueta="Fecha" class="text-nowrap"><?= e(fecha_corta($r['creado_en'])) ?></td>
          <?php if (!Auth::es('cliente')): ?><td data-etiqueta="Cliente"><?= e($r['cliente']) ?><br><small class="text-body-secondary"><?= e($r['ciudad']) ?> · <?= e($r['orden_codigo']) ?></small></td><?php endif; ?>
          <td data-etiqueta="Calificación" class="text-nowrap"><?= e($r['calificacion']) ?>/5 <i class="bi bi-star-fill estrella" aria-hidden="true"></i></td>
          <td data-etiqueta="Comentario"><?= e($r['comentario']) ?></td>
          <td data-etiqueta="Estado"><?= insignia_estado($r['estado']) ?></td>
          <?php if (can('resenas.aprobar')): ?>
          <td class="acciones-fila">
            <?php if ($r['estado'] !== 'aprobada'): ?>
              <form method="post" action="<?= e(url('/resenas/' . $r['id'] . '/moderar')) ?>"><?= csrf_field() ?><input type="hidden" name="estado" value="aprobada"><button class="btn btn-sm btn-outline-success" type="submit">Aprobar</button></form>
            <?php endif; ?>
            <?php if ($r['estado'] !== 'rechazada'): ?>
              <form method="post" action="<?= e(url('/resenas/' . $r['id'] . '/moderar')) ?>" data-confirmar="¿Rechazar esta reseña? No se mostrará en la página pública."><?= csrf_field() ?><input type="hidden" name="estado" value="rechazada"><button class="btn btn-sm btn-outline-danger" type="submit">Rechazar</button></form>
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
