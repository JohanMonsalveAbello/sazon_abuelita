<p class="codigo"><?= e($estado) ?></p>
<h1>Esta página no está en la carta</h1>
<p>La dirección no existe o el registro ya no está disponible.</p>
<a class="btn btn-dark" href="<?= e(url(usuario() ? '/panel' : '/')) ?>"><?= usuario() ? 'Volver a mi panel' : 'Ir al inicio' ?></a>
