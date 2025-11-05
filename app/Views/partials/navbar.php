<?php
  // Detectar ruta actual para marcar como activa
  $seg = trim(uri_string(), '/');
  $is  = fn(string $p) => str_starts_with($seg, trim($p, '/'));
?>

<nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="<?= site_url('/') ?>">
      <i class="bi bi-shield-exclamation text-danger me-1"></i> Procesos Disciplinarios
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto">

        <!-- FURD (Nuevo proceso) -->
        <li class="nav-item">
          <a class="nav-link <?= $is('furd') ? 'active' : '' ?>" href="<?= site_url('furd') ?>">
            <i class="bi bi-plus-circle me-1"></i> Nuevo proceso
          </a>
        </li>

        <!-- Dropdown GESTIÓN -->
        <li class="nav-item dropdown <?= ($is('citacion') || $is('cargos') || $is('soporte') || $is('decision')) ? 'active' : '' ?>">
          <a class="nav-link dropdown-toggle <?= ($is('citacion') || $is('cargos') || $is('soporte') || $is('decision')) ? 'active' : '' ?>"
             href="#" id="gestionDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-gear-wide-connected me-1"></i> Gestión
          </a>
          <ul class="dropdown-menu shadow-lg border-0 rounded-3 p-2" aria-labelledby="gestionDropdown">
            <li>
              <a class="dropdown-item rounded-2 <?= $is('citacion') ? 'active' : '' ?>" href="<?= site_url('citacion') ?>">
                <i class="bi bi-calendar-check me-2 text-primary"></i> Citación
              </a>
            </li>
            <li>
              <a class="dropdown-item rounded-2 <?= $is('cargos') ? 'active' : '' ?>" href="<?= site_url('cargos-descargos') ?>">
                <i class="bi bi-file-earmark-text me-2 text-warning"></i> Cargos y Descargos
              </a>
            </li>
            <li>
              <a class="dropdown-item rounded-2 <?= $is('soporte') ? 'active' : '' ?>" href="<?= site_url('soporte') ?>">
                <i class="bi bi-paperclip me-2 text-success"></i> Soporte de citación y acta
              </a>
            </li>
            <li>
              <a class="dropdown-item rounded-2 <?= $is('decision') ? 'active' : '' ?>" href="<?= site_url('decision') ?>">
                <i class="bi bi-check2-circle me-2 text-danger"></i> Decisión
              </a>
            </li>
          </ul>
        </li>

        <!-- Seguimiento -->
        <li class="nav-item">
          <a class="nav-link <?= $is('seguimiento') ? 'active' : '' ?>" href="<?= site_url('seguimiento') ?>">
            <i class="bi bi-list-check me-1"></i> Seguimiento
          </a>
        </li>

      </ul>
    </div>
  </div>
</nav>

<style>
/* ==== Estilos del navbar ==== */
.dropdown-menu {
  border: none;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
  min-width: 230px;
  border-radius: 0.75rem;
  padding: 0.4rem 0.3rem;
  animation: fadeIn 0.15s ease-in-out;
}
.dropdown-menu .dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.55rem 0.9rem;
  border-radius: 0.4rem;
  transition: all 0.15s ease;
  font-size: 0.94rem;
  color: #333;
  background: transparent;
}
.dropdown-menu .dropdown-item i { font-size: 1.05rem; opacity: 0.8; }
.dropdown-menu .dropdown-item:hover {
  background-color: rgba(25, 135, 84, 0.08);
  color: #0f5132;
  transform: translateX(2px);
}
.dropdown-menu .dropdown-item.active {
  background-color: rgba(25, 135, 84, 0.18);
  color: #0f5132;
  font-weight: 600;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-4px); }
  to   { opacity: 1; transform: translateY(0); }
}
.nav-link.active,
.nav-item.dropdown.active > .nav-link {
  color: var(--bs-primary) !important;
  font-weight: 600;
}
</style>
