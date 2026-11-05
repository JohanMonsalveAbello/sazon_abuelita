<form class="reservation-form" method="post" action="<?= e(url('/login')) ?>">
  <?= csrf_field() ?>
  <h1>Iniciar sesión</h1>
  <label>Correo electrónico
    <input type="email" name="correo" required maxlength="120" autocomplete="username" value="<?= e(old('correo')) ?>">
    <?php if (isset(errores()['correo'])): ?><span class="error-campo"><?= e(errores()['correo']) ?></span><?php endif; ?>
  </label>
  <label>Contraseña
    <input type="password" name="password" required maxlength="200" autocomplete="current-password">
    <?php if (isset(errores()['password'])): ?><span class="error-campo"><?= e(errores()['password']) ?></span><?php endif; ?>
  </label>
  <button class="btn btn-green full" type="submit">Entrar</button>
  <p class="acceso-pie">¿Aún no tienes cuenta? <a href="<?= e(url('/registro')) ?>">Crear cuenta</a></p>
  <p class="acceso-pie"><a href="<?= e(url('/')) ?>">Volver al inicio</a></p>
</form>
