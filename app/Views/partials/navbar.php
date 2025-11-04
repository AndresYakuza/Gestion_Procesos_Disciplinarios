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
        <li class="nav-item"><a class="nav-link <?= uri_string()===''?'active':'' ?>" href="<?= site_url('/') ?>">Nuevo proceso</a></li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Gestión</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= site_url('furd') ?>">Registrar FURD</a></li>
            <li><a class="dropdown-item" href="<?= site_url('proyecto-aliases') ?>">Alias de Nómina</a></li>
            <!-- agrega aquí más vistas cuando las tengas -->
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>
<script>
  // Toggle claro/oscuro (opcional)
  document.getElementById('toggleTheme')?.addEventListener('click', ()=>{
    const r = document.documentElement;
    r.setAttribute('data-bs-theme', r.getAttribute('data-bs-theme')==='dark'?'light':'dark');
  });
</script>
