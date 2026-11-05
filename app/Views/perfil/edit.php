<?= partial('encabezado', ['titulo' => $titulo, 'subtitulo' => 'Rol: ' . $u['rol_nombre'] . '. El rol solo lo puede cambiar un administrador.']) ?>
<div class="row g-3">
  <div class="col-lg-6">
    <section class="tarjeta">
      <h2>Mis datos</h2>
      <form method="post" action="<?= e(url('/perfil')) ?>" class="js-validar" novalidate>
        <?= csrf_field() ?>
        <?= campo('nombre', 'Nombre', $u['nombre'], ['required' => true, 'minlength' => 3, 'maxlength' => 120, 'autocomplete' => 'name']) ?>
        <?= campo('correo', 'Correo', $u['correo'], ['type' => 'email', 'required' => true, 'maxlength' => 120, 'autocomplete' => 'email']) ?>
        <?= campo('telefono', 'Teléfono', $u['telefono'], ['type' => 'tel', 'maxlength' => 20, 'autocomplete' => 'tel']) ?>
        <button class="btn btn-primary" type="submit">Guardar cambios</button>
      </form>
    </section>
  </div>
  <div class="col-lg-6">
    <section class="tarjeta">
      <h2>Cambiar contraseña</h2>
      <form method="post" action="<?= e(url('/perfil/password')) ?>" class="js-validar" novalidate>
        <?= csrf_field() ?>
        <?= campo('password_actual', 'Contraseña actual', '', ['type' => 'password', 'required' => true, 'autocomplete' => 'current-password']) ?>
        <?= campo('password', 'Nueva contraseña', '', ['type' => 'password', 'required' => true, 'minlength' => 8, 'maxlength' => 72, 'autocomplete' => 'new-password']) ?>
        <?= campo('password_confirma', 'Confirmar nueva contraseña', '', ['type' => 'password', 'required' => true, 'maxlength' => 72, 'autocomplete' => 'new-password']) ?>
        <p class="form-text">Mínimo 8 caracteres, con mayúscula, minúscula y número.</p>
        <button class="btn btn-primary" type="submit">Actualizar contraseña</button>
      </form>
    </section>
  </div>
</div>
