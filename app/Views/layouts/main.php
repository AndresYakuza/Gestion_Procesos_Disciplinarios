<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title ?? 'Procesos Disciplinarios') ?></title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- App CSS -->
  <link rel="stylesheet" href="<?= base_url('assets/css/app.css'); ?>">

  <!-- Estilos adicionales (por vista) -->
  <?= $this->renderSection('styles') ?> 

  <style>
    @keyframes fadeInUp { from {opacity:0; transform: translate3d(0,8px,0);} to {opacity:1; transform:none;} }
    .animate-in { animation: fadeInUp .35s ease both; }
    .card { transition: box-shadow .2s ease, transform .15s ease; }
    .card:hover { box-shadow: 0 0.5rem 1.2rem rgba(0,0,0,.10); transform: translateY(-1px); }
    .sticky-actions { position: sticky; bottom: 0; z-index: 5; backdrop-filter: blur(6px); }
    .scroll-area { max-height: 340px; overflow: auto; }
  </style>
</head>

<body class="theme-rich">

  <?= $this->include('partials/navbar'); ?>

  <main class="container py-4">
    <?= $this->renderSection('content'); ?>
  </main>

  <!-- Bootstrap JS (bundle con Popper incluido) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
          integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
          crossorigin="anonymous"></script>

  <!-- App JS -->
  <script src="<?= base_url('assets/js/app.js'); ?>"></script>

  <!-- Scripts adicionales (por vista) -->
  <?= $this->renderSection('scripts'); ?>

</body>
</html>
