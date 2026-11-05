<?php
use App\Core\Auth;
use App\Models\MesaModel;
$r = $reserva;
$abierta = in_array($r['estado'], ['pendiente', 'confirmada'], true);
$yaPaso = strtotime($r['fecha'] . ' ' . $r['hora']) <= time();
$puedeEditar = can('reservas.editar') && $abierta && (!Auth::es('cliente') || $r['estado'] === 'pendiente');
?>
<?= partial('encabezado', ['titulo' => $titulo, 'volver' => ['Reservas', '/reservas'], 'acciones' => $puedeEditar ? [['Editar', '/reservas/' . $r['id'] . '/editar', 'bi-pencil']] : []]) ?>

<div class="row g-3">
  <div class="col-lg-7">
    <section class="tarjeta">
      <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
        <h2 class="mb-0"><?= e(fecha_corta($r['fecha'])) ?> · <?= e(hora_12($r['hora'])) ?></h2>
        <?= insignia_estado($r['estado']) ?>
      </div>
      <dl class="datos">
        <dt>Contacto</dt><dd><?= e($r['nombre_contacto']) ?></dd>
        <dt>Teléfono</dt><dd><?= e($r['telefono']) ?></dd>
        <dt>Correo</dt><dd><?= e($r['correo']) ?></dd>
        <dt>Personas</dt><dd><?= e($r['personas']) ?></dd>
        <dt>Mesa</dt><dd><?= $r['mesa_numero'] ? 'Mesa ' . e($r['mesa_numero']) . ' (' . e($r['mesa_capacidad']) . ' personas)' : 'Por asignar' ?></dd>
        <dt>Solicitud especial</dt><dd><?= e($r['mensaje'] ?: 'Ninguna') ?></dd>
        <?php if ($r['motivo_cancelacion']): ?><dt>Motivo de cancelación</dt><dd><?= e($r['motivo_cancelacion']) ?></dd><?php endif; ?>
        <dt>Registrada</dt><dd><?= e(fecha_hora($r['creado_en'])) ?></dd>
      </dl>
    </section>
  </div>

  <div class="col-lg-5">
    <?php if (can('reservas.aprobar') && $abierta): ?>
      <section class="tarjeta mb-3">
        <h2><?= $r['estado'] === 'pendiente' ? 'Confirmar y asignar mesa' : 'Cambiar de mesa' ?></h2>
        <?php if ($mesas === []): ?>
          <?= partial('vacio', ['icono' => 'bi-grid-3x3-gap', 'titulo' => 'No hay mesas libres para este grupo a esa hora', 'texto' => 'Propón otra hora al cliente.']) ?>
        <?php else: ?>
          <form method="post" action="<?= e(url('/reservas/' . $r['id'] . '/confirmar')) ?>" class="js-validar" novalidate>
            <?= csrf_field() ?>
            <?php
            $opciones = [];
            foreach ($mesas as $m) {
                $opciones[$m['id']] = "Mesa {$m['numero']} · {$m['capacidad']} personas · " . MesaModel::ZONAS[$m['zona']];
            }
            ?>
            <?= campo_select('mesa_id', 'Mesas libres en esa franja', $opciones, $r['mesa_id'] ?? '', ['required' => true]) ?>
            <button class="btn btn-primary" type="submit"><?= $r['estado'] === 'pendiente' ? 'Confirmar reserva' : 'Cambiar mesa' ?></button>
          </form>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <?php if ($r['estado'] === 'confirmada' && can('reservas.aprobar') && $yaPaso): ?>
      <section class="tarjeta mb-3">
        <h2>Llegada del cliente</h2>
        <div class="d-flex flex-wrap gap-2">
          <?php if ($r['fecha'] === date('Y-m-d') && can('ordenes.crear')): ?>
            <a class="btn btn-primary" href="<?= e(url('/ordenes/crear?reserva=' . $r['id'])) ?>"><i class="bi bi-receipt" aria-hidden="true"></i> Abrir orden</a>
          <?php endif; ?>
          <form method="post" action="<?= e(url('/reservas/' . $r['id'] . '/cerrar')) ?>">
            <?= csrf_field() ?><input type="hidden" name="estado" value="cumplida">
            <button class="btn btn-outline-secondary" type="submit">Marcar cumplida</button>
          </form>
          <form method="post" action="<?= e(url('/reservas/' . $r['id'] . '/cerrar')) ?>" data-confirmar="¿Registrar que el cliente no asistió?">
            <?= csrf_field() ?><input type="hidden" name="estado" value="no_asistio">
            <button class="btn btn-outline-secondary" type="submit">No asistió</button>
          </form>
        </div>
      </section>
    <?php elseif ($r['estado'] === 'confirmada' && can('ordenes.crear') && $r['fecha'] === date('Y-m-d')): ?>
      <section class="tarjeta mb-3">
        <h2>Llegada del cliente</h2>
        <a class="btn btn-primary" href="<?= e(url('/ordenes/crear?reserva=' . $r['id'])) ?>"><i class="bi bi-receipt" aria-hidden="true"></i> Abrir orden</a>
      </section>
    <?php endif; ?>

    <?php if (can('reservas.eliminar') && $abierta): ?>
      <section class="tarjeta">
        <h2>Cancelar reserva</h2>
        <?php if (Auth::es('cliente')): ?><p class="form-text">Puedes cancelar hasta 2 horas antes.</p><?php endif; ?>
        <form method="post" action="<?= e(url('/reservas/' . $r['id'] . '/cancelar')) ?>" data-confirmar="¿Cancelar esta reserva? Esta acción no se puede deshacer.">
          <?= csrf_field() ?>
          <?= campo('motivo', 'Motivo (opcional)', '', ['maxlength' => 255]) ?>
          <button class="btn btn-outline-danger" type="submit">Cancelar reserva</button>
        </form>
      </section>
    <?php endif; ?>
  </div>
</div>
