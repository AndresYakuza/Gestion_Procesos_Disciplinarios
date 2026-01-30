<?php
// Detectar ruta actual para marcar como activa
$seg = trim(uri_string(), '/');
$is  = fn(string $p) => str_starts_with($seg, trim($p, '/'));
?>

<nav class="navbar navbar-expand-lg navbar-glass fixed-top shadow-sm">
  <div class="container">
    <!-- Logo / título -->
    <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="<?= base_url('/') ?>">
      <i class="bi bi-shield-exclamation text-gradient"></i>
      <span>Gestión De Procesos Disciplinarios</span>
    </a>

    <!-- Toggle -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Menú principal -->
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">

        <li class="nav-item">
          <a class="nav-link <?= $is('furd') ? 'active' : '' ?>" href="<?= base_url('furd') ?>">
            <i class="bi bi-plus-circle me-1"></i> Nuevo proceso
          </a>
        </li>

        <li class="nav-item dropdown <?= ($is('citacion') || $is('cargos') || $is('soporte') || $is('decision')) ? 'active' : '' ?>">
          <a class="nav-link dropdown-toggle <?= ($is('citacion') || $is('cargos') || $is('soporte') || $is('decision')) ? 'active' : '' ?>"
            href="#" id="gestionDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-gear-wide-connected me-1"></i> Gestión
          </a>
          <ul class="dropdown-menu border-0 shadow-lg rounded-3 p-2" aria-labelledby="gestionDropdown">
            <li><a class="dropdown-item <?= $is('citacion') ? 'active' : '' ?>" href="<?= base_url('citacion') ?>">
                <i class="bi bi-calendar-check text-primary me-2"></i> Citación
              </a></li>
            <li><a class="dropdown-item <?= $is('cargos') ? 'active' : '' ?>" href="<?= base_url('descargos') ?>">
                <i class="bi bi-file-earmark-text text-warning me-2"></i> Cargos y Descargos
              </a></li>
            <li><a class="dropdown-item <?= $is('soporte') ? 'active' : '' ?>" href="<?= base_url('soporte') ?>">
                <i class="bi bi-paperclip text-success me-2"></i> Soporte de citación y acta
              </a></li>
            <li><a class="dropdown-item <?= $is('decision') ? 'active' : '' ?>" href="<?= base_url('decision') ?>">
                <i class="bi bi-check2-circle text-danger me-2"></i> Decisión
              </a></li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= $is('seguimiento') ? 'active' : '' ?>" href="<?= base_url('seguimiento') ?>">
            <i class="bi bi-list-check me-1"></i> Seguimiento
          </a>
        </li>

        <?php
        $ajustesPinOk = session('ajustesPinOk') === true;
        ?>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= $is('ajustes') ? 'active' : '' ?>" href="#" id="ajustesDropdown" data-bs-toggle="dropdown">
            <i class="bi bi-sliders me-1"></i> Ajustes
          </a>
          <ul class="dropdown-menu border-0 shadow-lg rounded-3 p-2" aria-labelledby="ajustesDropdown">
            <li><a class="dropdown-item" href="<?= base_url('ajustes/faltas') ?>">
                <i class="bi bi-exclamation-triangle text-warning me-2"></i> Faltas del RIT
              </a></li>

            <?php if ($ajustesPinOk): ?>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li>
                <a class="dropdown-item text-danger" href="<?= site_url('ajustes/salir') ?>">
                  <i class="bi bi-lock me-2"></i> Bloquear ajustes
                </a>
              </li>
            <?php endif; ?>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- =========================================================
     ESTILOS MODERNOS DE NAVBAR
     ========================================================= -->
<style>
  /* =========================================================
     NAVBAR CON GRADIENTE GLACIAL INVERTIDO + MARCA OSCURA
     ========================================================= */
  .navbar-glass {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1030;
    background: linear-gradient(135deg,
        #ffffff 0%,
        /* blanco inicial */
        #e8f5e9 20%,
        /* verde muy clarito */
        #a5d6a7 55%,
        /* verde pastel intermedio */
        #0c8e43 100%
        /* verde sólido tipo app de la captura */
      ) !important;
    backdrop-filter: blur(15px) saturate(160%);
    -webkit-backdrop-filter: blur(15px) saturate(160%);
    border-bottom: 1px solid rgba(12, 142, 67, 0.25);
    box-shadow: 0 8px 25px rgba(12, 142, 67, 0.25);
    background-size: 300% 300%;
    animation: glassShift 14s ease-in-out infinite alternate;
  }

  body {
    padding-top: 4.5rem;
    /* ajusta según la altura real del navbar */
  }

  @keyframes glassShift {
    0% {
      background-position: 0% 0%;
    }

    100% {
      background-position: 100% 100%;
    }
  }

  /* 🌟 Brillo suave */
  .navbar-glass::before {
    content: "";
    position: absolute;
    top: 0;
    left: -30%;
    width: 160%;
    height: 100%;
    background: radial-gradient(circle at top left, rgba(255, 255, 255, 0.6), transparent 60%);
    opacity: 0.4;
    pointer-events: none;
    animation: auroraShine 18s ease-in-out infinite;
  }

  @keyframes auroraShine {
    0% {
      transform: translateX(-30%);
      opacity: 0.5;
    }

    50% {
      transform: translateX(10%);
      opacity: 0.7;
    }

    100% {
      transform: translateX(-30%);
      opacity: 0.5;
    }
  }

  /* =========================================================
     MARCA / LOGO
     ========================================================= */
  .navbar-brand {
    font-size: 1.1rem;
    font-weight: 600;
    color: #0f172a;
    /* negro azulado */
    display: flex;
    align-items: center;
    gap: 0.4rem;
    text-shadow: none;
  }

  .text-gradient {
    background: linear-gradient(90deg, #4f46e5, #06b6d4);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  /* =========================================================
     ENLACES PRINCIPALES (ahora morados)
     ========================================================= */
  .navbar-nav .nav-link {
    color: #ffffffff;
    font-weight: 500;
    font-size: 0.95rem;
    border-radius: 8px;
    padding: 0.45rem 0.85rem;
    transition: all 0.25s ease;
  }

  .navbar-nav .nav-link:hover {
    color: #3730a3;
    background: rgba(79, 70, 229, 0.08);
    transform: translateY(-1px);
  }

  .navbar-nav .nav-link.active {
    color: #3730a3 !important;
    background: rgba(79, 70, 229, 0.12);
    font-weight: 600;
    box-shadow: inset 0 -2px 0 rgba(79, 70, 229, 0.6);
  }

  /* =========================================================
     DROPDOWNS
     ========================================================= */
  .dropdown-menu {
    background: #ffffff;
    border: 1px solid rgba(79, 70, 229, 0.1);
    box-shadow: 0 6px 24px rgba(79, 70, 229, 0.12);
    border-radius: 0.75rem;
    animation: fadeIn .25s ease-in-out;
    min-width: 240px;
  }

  .dropdown-menu .dropdown-item {
    color: #1e3a8a;
    font-weight: 500;
    border-radius: 6px;
    transition: all 0.15s ease;
  }

  .dropdown-menu .dropdown-item:hover {
    background: rgba(79, 70, 229, 0.08);
    color: #4f46e5;
    transform: translateX(4px);
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(-4px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* =========================================================
     BOTÓN “NUEVO PROCESO” (tono morado con efecto glass)
     ========================================================= */
  .navbar .btn,
  .navbar .nav-link.btn {
    background: rgba(79, 70, 229, 0.1);
    color: #4f46e5 !important;
    border: 1px solid rgba(79, 70, 229, 0.3);
    border-radius: 8px;
    padding: 0.4rem 0.9rem;
    font-weight: 600;
    transition: all 0.25s ease;
  }

  .navbar .btn:hover {
    background: rgba(79, 70, 229, 0.15);
    color: #3730a3 !important;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
  }

  /* =========================================================
     RESPONSIVE
     ========================================================= */
  @media (max-width: 992px) {
    .navbar-glass {
      background: linear-gradient(180deg,
          #f8fafc 0%,
          #e8f5e9 40%,
          #a5d6a7 75%,
          #44e989ff 100%) !important;
    }

    .navbar-nav .nav-link {
      color: #0c8e43;
      background: rgba(12, 142, 67, 0.05);
    }
  }
</style>