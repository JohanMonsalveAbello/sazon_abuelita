<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Error <?= e($estado) ?> · El Sazón de la Abuela</title>
  <link rel="stylesheet" href="<?= e(asset('css/styles.css')) ?>" />
  <link rel="stylesheet" href="<?= e(asset('css/publico-extra.css')) ?>" />
</head>
<body>
  <main class="acceso">
    <div class="acceso-caja">
      <div class="reservation-form error-pagina">
        <?= $contenido ?>
        <?php if (!empty($detalle)): ?><p class="error-detalle"><?= e($detalle) ?></p><?php endif; ?>
      </div>
    </div>
  </main>
</body>
</html>
