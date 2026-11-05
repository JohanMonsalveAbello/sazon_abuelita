<?php
$u = usuario();
$mensajeExito = \App\Core\Session::pull('success');
$mensajeError = \App\Core\Session::pull('error');
$errs = errores();
$horas = ['12:00' => '12:00 PM', '13:00' => '1:00 PM', '14:00' => '2:00 PM', '18:00' => '6:00 PM', '19:00' => '7:00 PM', '20:00' => '8:00 PM'];
$personas = ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6+'];
$valorHora = (string) old('hora');
$valorPersonas = (string) old('personas');
$errorDe = static fn (string $c): string => isset($errs[$c]) ? '<span class="error-campo">' . e($errs[$c]) . '</span>' : '';
?>
  <header class="hero" id="inicio">
    <nav class="navbar container" aria-label="Principal">
      <a href="#inicio" class="brand">
        <span class="brand-top">Restaurante</span>
        <strong>El Sazón de la Abuela</strong>
      </a>

      <button class="menu-toggle" aria-label="Abrir menú" aria-expanded="false">☰</button>

      <div class="nav-links">
        <a href="#inicio">Inicio</a>
        <a href="#nosotros">Nosotros</a>
        <a href="#menu">Menú</a>
        <a href="#especialidades">Especialidades</a>
        <a href="#galeria">Galería</a>
        <a href="#opiniones">Opiniones</a>
        <a href="#ubicacion">Ubicación</a>
        <?php if ($u): ?>
          <a class="solo-menu-movil" href="<?= e(url('/panel')) ?>">Mi panel</a>
        <?php else: ?>
          <a class="solo-menu-movil" href="<?= e(url('/registro')) ?>">Crear Cuenta</a>
          <a class="solo-menu-movil" href="<?= e(url('/login')) ?>">Login</a>
        <?php endif; ?>
      </div>

      <div class="nav-actions">
        <a class="btn btn-light" href="#reservas">Reservar</a>
        <?php if ($u): ?>
          <a class="btn btn-green" href="<?= e(url('/panel')) ?>">Mi panel</a>
        <?php else: ?>
          <a class="btn btn-outline" href="<?= e(url('/registro')) ?>">Crear Cuenta</a>
          <a class="btn btn-green" href="<?= e(url('/login')) ?>">Login</a>
        <?php endif; ?>
      </div>
    </nav>

    <div class="hero-overlay"></div>
    <div class="hero-content container">
      <span class="eyebrow light">Bogotá, Colombia</span>
      <h1>Donde la Tradición<br><span>se hace Sabor</span></h1>
      <p>Descubre la auténtica gastronomía colombiana en un ambiente cálido y acogedor. Cada plato cuenta una historia de tradición y pasión.</p>
      <div class="hero-buttons">
        <a class="btn btn-gold" href="#menu">Ver Menú</a>
        <a class="btn btn-dark" href="#reservas">Reservar Mesa</a>
      </div>
    </div>
  </header>

  <main>
    <section class="section about" id="nosotros">
      <div class="container two-col">
        <img src="<?= e(asset('assets/img/historia.jpg')) ?>" alt="Plato tradicional servido en El Sazón de la Abuela" class="about-img" />
        <div class="about-copy">
          <span class="eyebrow">Nuestra Historia</span>
          <h2>Un Legado de<br><span>Sabor Colombiano</span></h2>
          <p>Desde 2009, <strong>El Sazón de la Abuela</strong> ha sido el punto de encuentro para amantes de la buena comida colombiana en el corazón de Bogotá. Nuestra historia comenzó con una simple misión: compartir los sabores auténticos de nuestra tierra con cada comensal que cruza nuestras puertas.</p>
          <p>Nos especializamos en cocina tradicional colombiana, desde el reconfortante ajiaco bogotano hasta la abundante bandeja paisa. Cada receta ha sido perfeccionada a lo largo de los años, manteniendo la esencia de los sabores que nos definen.</p>

          <div class="feature-grid">
            <article><div class="icon">🌿</div><div><h3>Ingredientes Frescos</h3><p>Productos locales seleccionados diariamente de los mejores proveedores.</p></div></article>
            <article><div class="icon">❤</div><div><h3>Hecho con Amor</h3><p>Cada plato es preparado con la pasión y dedicación de nuestros chefs.</p></div></article>
            <article><div class="icon">🍲</div><div><h3>Tradición Auténtica</h3><p>Recetas tradicionales colombianas transmitidas por generaciones.</p></div></article>
            <article><div class="icon">🏠</div><div><h3>Ambiente Familiar</h3><p>Un espacio cálido donde cada visita se convierte en un momento especial.</p></div></article>
          </div>
        </div>
      </div>
    </section>

    <section class="section menu-section" id="menu">
      <div class="container">
        <div class="section-title center">
          <span class="eyebrow">Nuestra Carta</span>
          <h2>Menú</h2>
          <p>Explora nuestra selección de platos tradicionales colombianos, preparados con los mejores ingredientes y mucho amor.</p>
        </div>

        <div class="filters" role="group" aria-label="Filtrar menú">
          <button class="filter active" data-filter="all">Todos</button>
          <?php foreach ($categorias as $c): ?>
            <button class="filter" data-filter="<?= e($c['slug']) ?>"><?= e($c['nombre']) ?></button>
          <?php endforeach; ?>
        </div>

        <div class="menu-grid">
          <?php foreach ($platos as $p): ?>
          <article class="dish-card" data-category="<?= e($p['categoria_slug']) ?>">
            <div class="dish-photo"><img src="<?= e(asset('assets/img/' . $p['imagen'])) ?>" alt="<?= e($p['nombre']) ?>" loading="lazy"><?php if ($p['etiqueta']): ?><span><?= e($p['etiqueta']) ?></span><?php endif; ?></div>
            <div class="dish-body"><div class="dish-head"><h3><?= e($p['nombre']) ?></h3><strong><?= e(dinero($p['precio'])) ?></strong></div><p><?= e($p['descripcion']) ?></p></div>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="section specialties" id="especialidades">
      <div class="container">
        <div class="section-title center">
          <span class="eyebrow">Lo Mejor de El Sazón de la Abuela</span>
          <h2>Nuestras Especialidades</h2>
          <p>Platos que han conquistado el paladar de nuestros comensales y se han convertido en favoritos de nuestra clientela.</p>
        </div>

        <?php if ($destacado): ?>
        <article class="featured-dish">
          <img src="<?= e(asset('assets/img/' . $destacado['imagen'])) ?>" alt="<?= e($destacado['nombre']) ?>">
          <div>
            <span class="mini-label">El Plato Estrella de la Casa</span>
            <h3><?= e($destacado['nombre']) ?></h3>
            <p><?= e($destacado['descripcion_larga'] ?: $destacado['descripcion']) ?></p>
            <div class="meta"><span>⏱ <?= e($destacado['tiempo_min']) ?> min</span><span>👥 <?= e($destacado['porciones']) ?></span><?php if ($indicadores['total'] > 0): ?><span>★ <?= e(number_format($indicadores['promedio'], 1)) ?> (<?= e($indicadores['total']) ?> reseñas de la casa)</span><?php endif; ?></div>
            <a href="#reservas" class="btn btn-dark">Reservar para Probar</a>
          </div>
          <strong class="price-pill"><?= e(dinero($destacado['precio'])) ?></strong>
        </article>
        <?php endif; ?>

        <article class="daily-menu">
          <span>Menú del Día</span>
          <h3>Almuerzo Ejecutivo desde $18.000</h3>
          <p>De lunes a viernes disfruta de nuestro menú ejecutivo con sopa, plato fuerte, bebida y postre incluidos.</p>
          <a href="#menu" class="btn btn-gold">Ver Opciones</a>
        </article>
      </div>
    </section>

    <section class="section reservation" id="reservas">
      <div class="container reservation-grid">
        <div class="reservation-info">
          <span class="eyebrow">Reservaciones</span>
          <h2>Reserva tu<br><span>Mesa Ahora</span></h2>
          <p>Asegura tu lugar en El Sazón de la Abuela. Completa el formulario o contáctanos directamente por WhatsApp o teléfono para una reservación inmediata.</p>
          <div class="contact-card whatsapp"><strong>WhatsApp</strong><span>Respuesta inmediata</span><b>+57 300 123 4567</b></div>
          <div class="contact-card phone"><strong>Llámanos directamente</strong><span>+57 (601) 765 4321</span></div>
          <div class="hours-card"><strong>Horarios de Atención</strong><p>Lunes - Viernes <span>11:30 AM - 10:00 PM</span></p><p>Sábado <span>12:00 PM - 11:00 PM</span></p><p>Domingo <span>12:00 PM - 8:00 PM</span></p></div>
        </div>

        <form class="reservation-form" id="reservationForm" method="post" action="<?= e(url('/reservas/solicitar')) ?>">
          <?= csrf_field() ?>
          <h3>Formulario de Reserva</h3>
          <?php if ($mensajeExito): ?><div class="aviso aviso-ok" role="status"><?= e($mensajeExito) ?></div><?php endif; ?>
          <?php if ($mensajeError): ?><div class="aviso aviso-error" role="alert"><?= e($mensajeError) ?></div><?php endif; ?>
          <div class="form-row"><label>Nombre Completo<input name="nombre" type="text" placeholder="Tu nombre" required maxlength="120" value="<?= e(old('nombre', $u['nombre'] ?? '')) ?>"><?= $errorDe('nombre') ?></label><label>Teléfono<input name="telefono" type="tel" placeholder="300 123 4567" required maxlength="20" value="<?= e(old('telefono', $u['telefono'] ?? '')) ?>"><?= $errorDe('telefono') ?></label></div>
          <label>Correo Electrónico<input name="correo" type="email" placeholder="tu@email.com" required maxlength="120" value="<?= e(old('correo', $u['correo'] ?? '')) ?>"><?= $errorDe('correo') ?></label>
          <div class="form-row three"><label>Fecha<input name="fecha" type="date" required min="<?= e(date('Y-m-d')) ?>" value="<?= e(old('fecha')) ?>"><?= $errorDe('fecha') ?></label><label>Hora<select name="hora" required><option value="">Seleccionar</option><?php foreach ($horas as $v => $t): ?><option value="<?= e($v) ?>"<?= $valorHora === $v ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select><?= $errorDe('hora') ?></label><label>Personas<select name="personas" required><option value="">Seleccionar</option><?php foreach ($personas as $v => $t): ?><option value="<?= e($v) ?>"<?= $valorPersonas === (string) $v ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select><?= $errorDe('personas') ?></label></div>
          <label>Mensaje o Solicitud Especial<textarea name="mensaje" rows="5" maxlength="500" placeholder="Alergias, celebraciones, preferencias..."><?= e(old('mensaje')) ?></textarea><?= $errorDe('mensaje') ?></label>
          <button type="submit" class="btn btn-green full">Enviar Reserva</button>
        </form>
      </div>
    </section>

    <section class="section gallery" id="galeria">
      <div class="container">
        <div class="section-title center"><span class="eyebrow">Galería</span><h2>Nuestro Espacio</h2><p>Conoce nuestro restaurante, nuestra cocina y nuestros platos a través de estas imágenes.</p></div>
        <div class="gallery-grid">
          <img class="big" src="<?= e(asset('assets/img/ajiaco.jpg')) ?>" alt="Ajiaco colombiano" loading="lazy">
          <img src="<?= e(asset('assets/img/terraza.jpg')) ?>" alt="Terraza del restaurante" loading="lazy">
          <img src="<?= e(asset('assets/img/bandeja-paisa.jpg')) ?>" alt="Bandeja paisa" loading="lazy">
          <img src="<?= e(asset('assets/img/cocina.jpg')) ?>" alt="Cocina del restaurante" loading="lazy">
          <img src="<?= e(asset('assets/img/restaurante-1.jpg')) ?>" alt="Interior del restaurante" loading="lazy">
          <img src="<?= e(asset('assets/img/postre-natas.jpg')) ?>" alt="Postre colombiano" loading="lazy">
          <img src="<?= e(asset('assets/img/restaurante-2.jpg')) ?>" alt="Mesa servida" loading="lazy">
          <img src="<?= e(asset('assets/img/restaurante-3.jpg')) ?>" alt="Ambiente cálido del restaurante" loading="lazy">
        </div>
      </div>
    </section>

    <section class="section testimonials" id="opiniones">
      <div class="container">
        <div class="section-title center"><span class="eyebrow">Testimonios</span><h2>Lo Que Dicen Nuestros<br>Clientes</h2><p>La satisfacción de nuestros comensales es nuestro mayor orgullo. Conoce sus experiencias en El Sazón de la Abuela.</p></div>
        <div class="stats"><div><strong><?= $indicadores['total'] > 0 ? e(number_format($indicadores['promedio'], 1)) : '—' ?></strong><span>Calificación Promedio</span></div><div><strong><?= e($indicadores['total']) ?></strong><span>Reseñas Publicadas</span></div><div><strong><?= $indicadores['total'] > 0 ? e($indicadores['satisfechos']) . '%' : '—' ?></strong><span>Clientes Satisfechos</span></div><div><strong><?= e($anios) ?></strong><span>Años de Experiencia</span></div></div>
        <?php if ($testimonios): ?>
        <div class="testimonial-grid">
          <?php foreach ($testimonios as $t): ?>
          <article><div class="stars" role="img" aria-label="<?= e($t['calificacion']) ?> de 5 estrellas"><?= str_repeat('★', (int) $t['calificacion']) ?></div><p>“<?= e($t['comentario']) ?>”</p><strong><?= e($t['cliente']) ?></strong><span><?= e($t['ciudad']) ?></span></article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <section class="section location" id="ubicacion">
      <div class="container">
        <div class="section-title center"><span class="eyebrow">Ubicación</span><h2>Nuestra Ubicación</h2><p>Estamos ubicados en el corazón de Bogotá, con fácil acceso desde cualquier punto de la ciudad.</p></div>
        <div class="location-grid">
          <img src="<?= e(asset('assets/img/mapa.jpg')) ?>" alt="Mapa de ubicación del restaurante" class="map-img" loading="lazy">
          <div class="location-cards">
            <article><h3>📍 Dirección</h3><p>Carrera 15 #82-58, Zona G<br>Bogotá D.C., Colombia</p><a href="https://www.google.com/maps/search/?api=1&query=Carrera+15+%2382-58+Bogota" target="_blank" rel="noopener">Abrir en Google Maps →</a></article>
            <article><h3>🕒 Horarios de Atención</h3><p>Lunes - Viernes <span>11:30 AM - 10:00 PM</span></p><p>Sábado <span>12:00 PM - 11:00 PM</span></p><p>Domingo y Festivos <span>12:00 PM - 8:00 PM</span></p></article>
            <article><h3>☎ Contacto Directo</h3><p>+57 300 123 4567<br>+57 (601) 765 4321</p></article>
            <article><h3>🚍 Cómo Llegar</h3><p><strong>TransMilenio</strong><br>Estación Calle 85 - A 5 minutos caminando</p></article>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer>
    <div class="container footer-grid">
      <div><div class="brand footer-brand"><span class="brand-top">Restaurante</span><strong>El Sazón de la Abuela</strong></div><p>Tradición colombiana servida con cariño.</p></div>
      <div><strong>Enlaces</strong><a href="#menu">Menú</a><a href="#reservas">Reservas</a><a href="#ubicacion">Ubicación</a></div>
      <div><strong>Contacto</strong><span>+57 300 123 4567</span><span>Bogotá D.C., Colombia</span></div>
    </div>
    <div class="copyright">© <?= e(date('Y')) ?> El Sazón de la Abuela. Todos los derechos reservados.</div>
  </footer>
