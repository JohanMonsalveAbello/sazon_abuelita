<?= partial('encabezado', ['titulo' => $titulo, 'volver' => ['Usuarios', '/usuarios']]) ?>
<section class="tarjeta formulario-angosto">
  <form method="post" action="<?= e(url($u ? '/usuarios/' . $u['id'] : '/usuarios')) ?>" class="js-validar" novalidate>
    <?= csrf_field() ?>
    <?= campo('nombre', 'Nombre', $u['nombre'] ?? '', ['required' => true, 'minlength' => 3, 'maxlength' => 120]) ?>
    <?= campo('correo', 'Correo', $u['correo'] ?? '', ['type' => 'email', 'required' => true, 'maxlength' => 120]) ?>
    <?= campo('telefono', 'Teléfono', $u['telefono'] ?? '', ['type' => 'tel', 'maxlength' => 20]) ?>
    <?= campo_select('rol_id', 'Rol', $roles, $u['rol_id'] ?? '', ['required' => true, 'disabled' => $u && $u['id'] === usuario()['id']]) ?>
    <?php if ($u && $u['id'] === usuario()['id']): ?>
      <input type="hidden" name="rol_id" value="<?= e($u['rol_id']) ?>">
      <p class="form-text">No puedes cambiar tu propio rol.</p>
    <?php endif; ?>
    <?= campo('password', $u ? 'Nueva contraseña (opcional)' : 'Contraseña', '', ['type' => 'password', 'required' => !$u, 'minlength' => 8, 'maxlength' => 72, 'autocomplete' => 'new-password']) ?>
    <p class="form-text">Mínimo 8 caracteres, con mayúscula, minúscula y número.</p>
    <button class="btn btn-primary" type="submit">Guardar</button>
    <a class="btn btn-outline-secondary" href="<?= e(url('/usuarios')) ?>">Cancelar</a>
  </form>
</section>
