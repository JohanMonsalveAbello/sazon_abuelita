<?php
use App\Core\Auth;
use App\Models\OrdenModel;

$o = $orden;
$estado = $o['estado'];
$esCliente = Auth::es('cliente');
$rondaActual = (int) $o['ronda_actual'];
$rondas = [];
foreach ($items as $i) {
    $rondas[(int) $i['ronda']][] = $i;
}
$total = array_sum(array_column($items, 'importe'));
$cartaPorCategoria = [];
foreach ($carta as $p) {
    $cartaPorCategoria[$p['categoria']][$p['id']] = $p['nombre'] . ' · ' . dinero($p['precio']);
}
$pasos = ['abierta' => 'Tomada', 'enviada' => 'En cola', 'en_preparacion' => 'Preparación', 'lista' => 'Lista', 'servida' => 'Servida', 'pagada' => 'Pagada'];
$indice = array_search($estado, array_keys($pasos), true);
?>
<?= partial('encabezado', ['titulo' => $titulo, 'volver' => [$esCliente ? 'Mis consumos' : 'Órdenes', '/ordenes'], 'subtitulo' => 'Mesa ' . $o['mesa_numero'] . ' · Mesero: ' . $o['mesero'] . ($o['cliente'] && !$esCliente ? ' · Cliente: ' . $o['cliente'] : '') . ($o['reserva_codigo'] ? ' · Reserva ' . $o['reserva_codigo'] : '')]) ?>

<?php if ($estado === 'cancelada'): ?>
  <div class="mb-3"><?= insignia_estado('cancelada') ?></div>
<?php else: ?>
  <ol class="pasos" aria-label="Progreso de la orden">
    <?php foreach (array_values($pasos) as $n => $texto): ?>
      <li class="<?= $n < $indice ? 'hecho' : ($n === $indice ? 'actual' : '') ?>"<?= $n === $indice ? ' aria-current="step"' : '' ?>><?= e($texto) ?></li>
    <?php endforeach; ?>
  </ol>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-7">
    <section class="tarjeta mb-3">
      <h2>Platos</h2>
      <?php if ($items === []): ?>
        <?= partial('vacio', ['icono' => 'bi-egg-fried', 'titulo' => 'La orden aún no tiene platos', 'texto' => $editable ? 'Agrégalos con el formulario de esta página.' : null]) ?>
      <?php else: ?>
        <?php foreach ($rondas as $numero => $filas): ?>
          <?php if (count($rondas) > 1 || $numero > 1): ?><h3 class="titulo-ronda">Ronda <?= e($numero) ?></h3><?php endif; ?>
          <ul class="lista-items">
            <?php foreach ($filas as $i): $modificable = $editable && $numero === $rondaActual; ?>
              <li>
                <div class="item-info">
                  <strong><?= e($i['plato']) ?></strong>
                  <?php if ($i['nota'] !== ''): ?><span class="item-nota"><i class="bi bi-chat-left-text" aria-hidden="true"></i> <?= e($i['nota']) ?></span><?php endif; ?>
                  <span class="item-precio"><?= e(dinero($i['precio_unitario'])) ?> c/u</span>
                </div>
                <div class="item-acciones">
                  <?php if ($modificable): ?>
                    <form method="post" action="<?= e(url('/ordenes/' . $o['id'] . '/items/' . $i['id'] . '/cantidad')) ?>" class="form-cantidad">
                      <?= csrf_field() ?>
                      <label class="visually-hidden" for="cantidad-<?= e($i['id']) ?>">Cantidad de <?= e($i['plato']) ?></label>
                      <input class="form-control form-control-sm" id="cantidad-<?= e($i['id']) ?>" type="number" name="cantidad" min="1" max="50" required value="<?= e($i['cantidad']) ?>">
                      <button class="btn btn-sm btn-outline-secondary" type="submit" aria-label="Actualizar cantidad"><i class="bi bi-check-lg" aria-hidden="true"></i></button>
                    </form>
                    <form method="post" action="<?= e(url('/ordenes/' . $o['id'] . '/items/' . $i['id'] . '/eliminar')) ?>" data-confirmar="<?= e('¿Quitar ' . $i['plato'] . ' de la orden?') ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Quitar <?= e($i['plato']) ?>"><i class="bi bi-trash" aria-hidden="true"></i></button>
                    </form>
                  <?php else: ?>
                    <span class="item-cantidad">× <?= e($i['cantidad']) ?></span>
                  <?php endif; ?>
                  <strong class="item-importe"><?= e(dinero($i['importe'])) ?></strong>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endforeach; ?>
        <p class="total-linea"><span>Subtotal</span><strong><?= e(dinero($total)) ?></strong></p>
      <?php endif; ?>
    </section>

    <?php if ($editable): ?>
      <section class="tarjeta">
        <h2>Agregar plato</h2>
        <?php if ($carta === []): ?>
          <?= partial('vacio', ['icono' => 'bi-egg-fried', 'titulo' => 'No hay platos disponibles en este momento']) ?>
        <?php else: ?>
          <form method="post" action="<?= e(url('/ordenes/' . $o['id'] . '/items')) ?>" class="js-validar" novalidate>
            <?= csrf_field() ?>
            <div class="row">
              <div class="col-sm-8">
                <div class="mb-3">
                  <label class="form-label" for="campo-plato">Plato <span class="text-danger" aria-hidden="true">*</span></label>
                  <select class="form-select" id="campo-plato" name="plato_id" required>
                    <option value="">Seleccionar</option>
                    <?php foreach ($cartaPorCategoria as $categoria => $opciones): ?>
                      <optgroup label="<?= e($categoria) ?>">
                        <?php foreach ($opciones as $id => $texto): ?><option value="<?= e($id) ?>"><?= e($texto) ?></option><?php endforeach; ?>
                      </optgroup>
                    <?php endforeach; ?>
                  </select>
                  <div class="invalid-feedback"></div>
                </div>
              </div>
              <div class="col-sm-4"><?= campo('cantidad', 'Cantidad', 1, ['type' => 'number', 'required' => true, 'min' => 1, 'max' => 50]) ?></div>
            </div>
            <?= campo('nota', 'Nota para cocina (opcional)', '', ['maxlength' => 160, 'placeholder' => 'Sin cilantro, término medio…']) ?>
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-plus-lg" aria-hidden="true"></i> Agregar</button>
          </form>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </div>

  <div class="col-lg-5">
    <section class="tarjeta mb-3">
      <div class="d-flex justify-content-between gap-2 flex-wrap mb-2"><h2 class="mb-0">Estado</h2><?= insignia_estado($estado) ?></div>
      <div class="acciones-orden">
        <?php if ($estado === 'abierta' && can('ordenes.editar')): ?>
          <form method="post" action="<?= e(url('/ordenes/' . $o['id'] . '/enviar')) ?>">
            <?= csrf_field() ?><button class="btn btn-primary w-100" type="submit"><i class="bi bi-send" aria-hidden="true"></i> Enviar a cocina</button>
          </form>
        <?php elseif ($estado === 'lista' && can('ordenes.editar')): ?>
          <form method="post" action="<?= e(url('/ordenes/' . $o['id'] . '/servir')) ?>">
            <?= csrf_field() ?><button class="btn btn-primary w-100" type="submit"><i class="bi bi-cup-hot" aria-hidden="true"></i> Marcar servida</button>
          </form>
        <?php elseif ($estado === 'servida' && can('ordenes.editar')): ?>
          <a class="btn btn-primary w-100" href="<?= e(url('/ordenes/' . $o['id'] . '/cuenta')) ?>"><i class="bi bi-cash-coin" aria-hidden="true"></i> Cobrar cuenta</a>
          <form method="post" action="<?= e(url('/ordenes/' . $o['id'] . '/nueva-ronda')) ?>">
            <?= csrf_field() ?><button class="btn btn-outline-secondary w-100" type="submit"><i class="bi bi-plus-lg" aria-hidden="true"></i> Pedir algo más (nueva ronda)</button>
          </form>
        <?php elseif (in_array($estado, ['enviada', 'en_preparacion'], true)): ?>
          <p class="mb-0 text-body-secondary"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Cocina está trabajando en esta orden.</p>
        <?php endif; ?>
        <?php if (in_array($estado, ['servida', 'pagada'], true) && !can('ordenes.editar')): ?>
          <a class="btn btn-outline-secondary w-100" href="<?= e(url('/ordenes/' . $o['id'] . '/cuenta')) ?>"><i class="bi bi-receipt" aria-hidden="true"></i> Ver cuenta</a>
        <?php elseif ($estado === 'pagada'): ?>
          <a class="btn btn-outline-secondary w-100" href="<?= e(url('/ordenes/' . $o['id'] . '/cuenta')) ?>"><i class="bi bi-receipt" aria-hidden="true"></i> Ver cuenta</a>
        <?php endif; ?>
        <?php if ($estado === 'pagada' && $esCliente && $o['cliente_id'] === Auth::id() && !$tieneResena): ?>
          <a class="btn btn-primary w-100" href="<?= e(url('/resenas/crear/' . $o['id'])) ?>"><i class="bi bi-star" aria-hidden="true"></i> Calificar mi visita</a>
        <?php endif; ?>
      </div>

      <?php if (in_array($estado, ['abierta', 'enviada'], true) && can('ordenes.eliminar')): ?>
        <hr>
        <form method="post" action="<?= e(url('/ordenes/' . $o['id'] . '/cancelar')) ?>" class="js-validar" novalidate data-confirmar="<?= $rondaActual > 1 ? '¿Descartar esta ronda? Las rondas anteriores se conservan para cobrar.' : '¿Cancelar esta orden? La mesa quedará libre.' ?>">
          <?= csrf_field() ?>
          <?= campo('motivo', $rondaActual > 1 ? 'Motivo para descartar la ronda' : 'Motivo de cancelación', '', ['required' => true, 'minlength' => 3, 'maxlength' => 255]) ?>
          <button class="btn btn-outline-danger btn-sm" type="submit"><?= $rondaActual > 1 ? 'Descartar ronda' : 'Cancelar orden' ?></button>
        </form>
      <?php endif; ?>

      <?php if ($meseros !== [] && !in_array($estado, ['pagada', 'cancelada'], true)): ?>
        <hr>
        <form method="post" action="<?= e(url('/ordenes/' . $o['id'] . '/reasignar')) ?>" class="js-validar" novalidate>
          <?= csrf_field() ?>
          <?= campo_select('mesero_id', 'Reasignar mesero', $meseros, $o['mesero_id'], ['required' => true]) ?>
          <button class="btn btn-outline-secondary btn-sm" type="submit">Reasignar</button>
        </form>
      <?php endif; ?>
    </section>

    <section class="tarjeta">
      <h2>Historial</h2>
      <ol class="historial">
        <?php foreach ($historial as $h): ?>
          <li>
            <strong><?= e(OrdenModel::ESTADOS[$h['estado_nuevo']] ?? $h['estado_nuevo']) ?></strong>
            <span><?= e(fecha_hora($h['creado_en'])) ?><?= $esCliente ? '' : ' · ' . e($h['usuario']) . ' (' . e($h['rol']) . ')' ?></span>
            <?php if ($h['nota']): ?><span class="historial-nota"><?= e($h['nota']) ?></span><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>
  </div>
</div>
