<?= partial('encabezado', ['titulo' => $titulo, 'volver' => ['Orden', '/ordenes/' . $orden['id']], 'subtitulo' => 'Visita del ' . fecha_corta($orden['creado_en']) . '. Tu nombre aparecerá junto a tu opinión si se publica.']) ?>
<section class="tarjeta formulario-angosto">
  <form method="post" action="<?= e(url('/resenas/crear/' . $orden['id'])) ?>" class="js-validar" novalidate>
    <?= csrf_field() ?>
    <?= campo_select('calificacion', 'Calificación', ['5' => '5 · Excelente', '4' => '4 · Muy buena', '3' => '3 · Aceptable', '2' => '2 · Regular', '1' => '1 · Mala'], '', ['required' => true]) ?>
    <?= campo_area('comentario', 'Tu opinión', '', ['required' => true, 'minlength' => 10, 'maxlength' => 400, 'rows' => 4]) ?>
    <?= campo('ciudad', 'Ciudad', 'Bogotá', ['required' => true, 'maxlength' => 60]) ?>
    <button class="btn btn-primary" type="submit">Enviar reseña</button>
  </form>
</section>
