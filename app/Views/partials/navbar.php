<?php
$seg = trim(uri_string(), '/');
$is  = fn(string $p) => str_starts_with($seg, trim($p, '/'));

$inGestion = $is('citacion') || $is('cargos') || $is('descargos') || $is('soporte') || $is('decision');
$ajustesPinOk = session('ajustesPinOk') === true;
?>

<nav class="navbar navbar-expand-lg navbar-glass fixed-top shadow-sm">
  <div class="container">
    <!-- Marca -->
    <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="<?= base_url('/') ?>">
      <i class="bi bi-shield-exclamation brand-icon"></i>
      <span class="brand-text">Gestión de Procesos Disciplinarios</span>
    </a>

    <!-- Toggle -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Abrir menú">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Menú -->
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">

        <li class="nav-item">
          <a class="nav-link <?= $is('furd') ? 'active' : '' ?>" href="<?= base_url('furd') ?>">
            <i class="bi bi-plus-circle me-1"></i> Nuevo proceso
          </a>
        </li>

        <li class="nav-item dropdown">
          <a
            class="nav-link dropdown-toggle <?= $inGestion ? 'active' : '' ?>"
            href="#"
            id="gestionDropdown"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
            <i class="bi bi-gear-wide-connected me-1"></i> Gestión
          </a>

          <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3 p-2" aria-labelledby="gestionDropdown">
            <li>
              <a class="dropdown-item <?= $is('citacion') ? 'active' : '' ?>" href="<?= base_url('citacion') ?>">
                <i class="bi bi-calendar-check text-primary me-2"></i> Citación
              </a>
            </li>
            <li>
              <a class="dropdown-item <?= ($is('cargos') || $is('descargos')) ? 'active' : '' ?>" href="<?= base_url('descargos') ?>">
                <i class="bi bi-file-earmark-text text-warning me-2"></i> Cargos y Descargos
              </a>
            </li>
            <li>
              <a class="dropdown-item <?= $is('soporte') ? 'active' : '' ?>" href="<?= base_url('soporte') ?>">
                <i class="bi bi-paperclip text-success me-2"></i> Soporte de citación y acta
              </a>
            </li>
            <li>
              <a class="dropdown-item <?= $is('decision') ? 'active' : '' ?>" href="<?= base_url('decision') ?>">
                <i class="bi bi-check2-circle text-danger me-2"></i> Decisión
              </a>
            </li>
          </ul>
        </li>

        <li class="nav-item">
          <a class="nav-link <?= $is('seguimiento') ? 'active' : '' ?>" href="<?= base_url('seguimiento') ?>">
            <i class="bi bi-list-check me-1"></i> Seguimiento
          </a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= $is('ajustes') ? 'active' : '' ?>" href="#" id="ajustesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-sliders me-1"></i> Ajustes
          </a>

          <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3 p-2" aria-labelledby="ajustesDropdown">
            <li>
              <a class="dropdown-item <?= $is('ajustes/faltas') ? 'active' : '' ?>" href="<?= base_url('ajustes/faltas') ?>">
                <i class="bi bi-exclamation-triangle text-warning me-2"></i> Faltas del RIT
              </a>
            </li>

            <?php if ($ajustesPinOk): ?>
              <li><hr class="dropdown-divider"></li>
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
