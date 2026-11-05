<?php $err = static fn (string $c): string => isset(errores()[$c]) ? '<span class="error-campo">' . e(errores()[$c]) . '</span>' : ''; ?>
<form class="reservation-form" method="post" action="<?= e(url('/registro')) ?>">
  <?= csrf_field() ?>
  <h1>Crear cuenta</h1>
  <label>Nombre completo
    <input type="text" name="nombre" required minlength="3" maxlength="120" autocomplete="name" value="<?= e(old('nombre')) ?>"><?= $err('nombre') ?>
  </label>
  <label>Correo electrónico
    <input type="email" name="correo" required maxlength="120" autocomplete="email" value="<?= e(old('correo')) ?>"><?= $err('correo') ?>
  </label>
  <label>Teléfono (opcional)
    <input type="tel" name="telefono" maxlength="20" autocomplete="tel" value="<?= e(old('telefono')) ?>"><?= $err('telefono') ?>
  </label>
  <label>Contraseña
    <input type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password"><?= $err('password') ?>
    <span class="ayuda">Mínimo 8 caracteres, con mayúscula, minúscula y número.</span>
  </label>
  <label>Confirmar contraseña
    <input type="password" name="password_confirma" required maxlength="72" autocomplete="new-password"><?= $err('password_confirma') ?>
  </label>
  <button class="btn btn-green full" type="submit">Crear cuenta</button>
  <p class="acceso-pie">¿Ya tienes cuenta? <a href="<?= e(url('/login')) ?>">Inicia sesión</a></p>
</form>
