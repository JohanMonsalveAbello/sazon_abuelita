<?= partial('encabezado', ['titulo' => $titulo, 'volver' => ['Mesas', '/mesas']]) ?>
<section class="tarjeta formulario-angosto">
  <form method="post" action="<?= e(url($m ? '/mesas/' . $m['id'] : '/mesas')) ?>" class="js-validar" novalidate>
    <?= csrf_field() ?>
    <?= campo('numero', 'Número', $m['numero'] ?? '', ['type' => 'number', 'required' => true, 'min' => 1, 'max' => 999]) ?>
    <?= campo('capacidad', 'Capacidad (personas)', $m['capacidad'] ?? '', ['type' => 'number', 'required' => true, 'min' => 1, 'max' => 20]) ?>
    <?= campo_select('zona', 'Zona', App\Models\MesaModel::ZONAS, $m['zona'] ?? '', ['required' => true]) ?>
    <?= campo_check('activa', 'Disponible para reservas y órdenes', (int) ($m['activa'] ?? 1) === 1) ?>
    <button class="btn btn-primary" type="submit">Guardar</button>
    <a class="btn btn-outline-secondary" href="<?= e(url('/mesas')) ?>">Cancelar</a>
  </form>
</section>
