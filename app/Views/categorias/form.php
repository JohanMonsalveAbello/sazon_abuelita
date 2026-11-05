<?= partial('encabezado', ['titulo' => $titulo, 'volver' => ['Categorías', '/categorias']]) ?>
<section class="tarjeta formulario-angosto">
  <form method="post" action="<?= e(url($c ? '/categorias/' . $c['id'] : '/categorias')) ?>" class="js-validar" novalidate>
    <?= csrf_field() ?>
    <?= campo('nombre', 'Nombre', $c['nombre'] ?? '', ['required' => true, 'minlength' => 3, 'maxlength' => 60]) ?>
    <?= campo('orden', 'Orden en la carta', $c['orden'] ?? 0, ['type' => 'number', 'required' => true, 'min' => 0, 'max' => 999]) ?>
    <button class="btn btn-primary" type="submit">Guardar</button>
    <a class="btn btn-outline-secondary" href="<?= e(url('/categorias')) ?>">Cancelar</a>
  </form>
</section>
