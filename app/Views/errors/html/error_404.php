<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Página no encontrada</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/pages/error-404.css') ?>" />
</head>
<body>
  <!-- Fondo animado -->
  <div class="bg-animated" aria-hidden="true">
    <span class="blob b1"></span>
    <span class="blob b2"></span>
    <span class="blob b3"></span>

    <div class="grid-overlay"></div>
    <div class="scanline"></div>
    <div class="noise-overlay"></div>
    <div class="vignette"></div>
  </div>

  <main class="error404-wrap">
    <section class="error404-card" role="alert" aria-live="polite">
      <div class="card-glow-border" aria-hidden="true"></div>
      <div class="card-aurora" aria-hidden="true"></div>

      <div class="top-meta">
        <div class="badge-404">ERROR 404</div>
        <div class="module-pill">Módulo: Navegación</div>
      </div>

      <h1 class="title">Ups… esta página se perdió en el espacio</h1>
      <p class="subtitle">
        La ruta que buscas no existe, cambió de URL o fue movida.
      </p>

      <!-- Visual central -->
      <div class="scene" aria-hidden="true">
        <div class="planet"></div>
        <div class="ring"></div>

        <div class="orbit-dot o1"></div>
        <div class="orbit-dot o2"></div>

        <div class="astronaut">
          <div class="helmet"></div>
          <div class="body"></div>
        </div>

        <div class="star s1"></div>
        <div class="star s2"></div>
        <div class="star s3"></div>
        <div class="star s4"></div>
        <div class="star s5"></div>

        <div class="pulse"></div>
      </div>

      <div class="actions">
        <a href="<?= site_url('/') ?>" class="btn btn-primary">Ir al inicio</a>
        <a href="javascript:history.back()" class="btn btn-ghost">Volver atrás</a>
      </div>

      <div class="status-line">
        <span class="dot"></span>
        <span>Sistema estable · Contactamos · Cumplimiento y Trazabilidad</span>
      </div>

      <p class="hint">
        Si crees que es un error del sistema, comparte esta URL con soporte.
      </p>
    </section>
  </main>
</body>
</html>
