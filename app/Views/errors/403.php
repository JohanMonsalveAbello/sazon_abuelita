<p class="codigo"><?= e($estado) ?></p>
<h1><?= $mensaje !== '' ? 'Formulario caducado' : 'No tienes acceso a esta página' ?></h1>
<p><?= e($mensaje ?: 'Tu cuenta no tiene permiso para ver este recurso. Si crees que es un error, habla con el administrador.') ?></p>
<a class="btn btn-dark" href="<?= e(url(usuario() ? '/panel' : '/')) ?>"><?= usuario() ? 'Volver a mi panel' : 'Ir al inicio' ?></a>
