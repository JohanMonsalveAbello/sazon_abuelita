<?= partial('encabezado', ['titulo' => $titulo, 'volver' => ['Platos', '/platos']]) ?>
<section class="tarjeta formulario-medio">
  <form method="post" action="<?= e(url($p ? '/platos/' . $p['id'] : '/platos')) ?>" enctype="multipart/form-data" class="js-validar" novalidate>
    <?= csrf_field() ?>
    <div class="row">
      <div class="col-md-7"><?= campo('nombre', 'Nombre', $p['nombre'] ?? '', ['required' => true, 'minlength' => 3, 'maxlength' => 80]) ?></div>
      <div class="col-md-5"><?= campo_select('categoria_id', 'Categoría', $categorias, $p['categoria_id'] ?? '', ['required' => true]) ?></div>
    </div>
    <?= campo_area('descripcion', 'Descripción en la carta', $p['descripcion'] ?? '', ['required' => true, 'minlength' => 10, 'maxlength' => 255]) ?>
    <div class="row">
      <div class="col-6 col-md-3"><?= campo('precio', 'Precio (COP)', $p['precio'] ?? '', ['type' => 'number', 'required' => true, 'min' => 500, 'max' => 2000000, 'step' => 100]) ?></div>
      <div class="col-6 col-md-3"><?= campo('tiempo_min', 'Minutos', $p['tiempo_min'] ?? 20, ['type' => 'number', 'required' => true, 'min' => 1, 'max' => 240]) ?></div>
      <div class="col-6 col-md-3"><?= campo('porciones', 'Porciones', $p['porciones'] ?? '1 persona', ['required' => true, 'maxlength' => 20]) ?></div>
      <div class="col-6 col-md-3"><?= campo('etiqueta', 'Etiqueta', $p['etiqueta'] ?? '', ['maxlength' => 20, 'placeholder' => 'Popular, Picante…']) ?></div>
    </div>
    <div class="mb-3">
      <label class="form-label" for="campo-imagen">Foto<?= $p ? ' (déjala vacía para conservar la actual)' : '' ?><?= $p ? '' : ' <span class="text-danger" aria-hidden="true">*</span>' ?></label>
      <input class="form-control" type="file" id="campo-imagen" name="imagen" accept="image/jpeg,image/png,image/webp"<?= $p ? '' : ' required' ?> aria-describedby="ayuda-imagen">
      <div class="form-text" id="ayuda-imagen">JPG, PNG o WEBP de máximo 2 MB.</div>
      <div class="invalid-feedback"></div>
      <?php if ($p): ?><img src="<?= e(asset('assets/img/' . $p['imagen'])) ?>" alt="Foto actual de <?= e($p['nombre']) ?>" class="foto-actual"><?php endif; ?>
    </div>
    <?= campo_check('disponible', 'Disponible para pedir', (int) ($p['disponible'] ?? 1) === 1) ?>
    <?= campo_check('destacado', 'Plato estrella de la página principal (reemplaza al actual)', (int) ($p['destacado'] ?? 0) === 1) ?>
    <?= campo_area('descripcion_larga', 'Texto para el plato estrella (opcional)', $p['descripcion_larga'] ?? '', ['maxlength' => 500]) ?>
    <button class="btn btn-primary" type="submit">Guardar</button>
    <a class="btn btn-outline-secondary" href="<?= e(url('/platos')) ?>">Cancelar</a>
  </form>
</section>
