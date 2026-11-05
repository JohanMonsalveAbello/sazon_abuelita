<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($titulo ?? 'Acceso') ?> · El Sazón de la Abuela</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/styles.css')) ?>" />
  <link rel="stylesheet" href="<?= e(asset('css/publico-extra.css')) ?>" />
</head>
<body>
  <main class="acceso">
    <div class="acceso-caja">
      <a href="<?= e(url('/')) ?>" class="brand">
        <span class="brand-top">Restaurante</span>
        <strong>El Sazón de la Abuela</strong>
      </a>
      <?php if ($m = \App\Core\Session::pull('error')): ?><div class="aviso aviso-error" role="alert"><?= e($m) ?></div><?php endif; ?>
      <?php if ($m = \App\Core\Session::pull('success')): ?><div class="aviso aviso-ok" role="status"><?= e($m) ?></div><?php endif; ?>
      <?= $contenido ?>
    </div>
  </main>
</body>
</html>
