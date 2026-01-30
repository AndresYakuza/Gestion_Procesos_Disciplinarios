<?= $this->extend('layouts/main'); ?>

<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/ajustes-acceso.css'); ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<div class="ajustes-access-page">
  <!-- Fondo decorativo -->
  <div class="ajustes-access-bg"></div>

  <div class="ajustes-access-card ajustes-animate-in">
    <!-- Columna izquierda: formulario -->
    <div class="ajustes-access-main">
      <div class="ajustes-access-header">
        <div class="ajustes-access-icon">
          <i class="bi bi-shield-lock-fill"></i>
        </div>
        <div>
          <h2 class="ajustes-access-title">Acceso a ajustes</h2>
          <p class="ajustes-access-subtitle">
            Zona de administración de <strong>Faltas del RIT</strong>.
            Solo personal autorizado.
          </p>
        </div>
      </div>

      <form method="post" action="<?= site_url('ajustes/acceso') ?>" class="ajustes-access-form">
        <?= csrf_field(); ?>

        <?php if ($error = session('error')): ?>
          <div class="alert alert-danger ajustes-alert">
            <i class="bi bi-exclamation-triangle me-1"></i><?= esc($error) ?>
          </div>
        <?php endif; ?>

        <label for="pin" class="ajustes-label">PIN de administración</label>

        <div class="ajustes-input-wrapper">
          <span class="ajustes-input-icon">
            <i class="bi bi-key"></i>
          </span>
          <input
            type="password"
            id="pin"
            name="pin"
            class="form-control ajustes-input"
            autocomplete="off"
            inputmode="numeric"
            required
          >
          <!-- ojo clickeable con data-target para el JS -->
          <button
            type="button"
            class="ajustes-input-eye ajustes-toggle-visibility"
            data-target="#pin"
            aria-label="Mostrar u ocultar PIN"
          >
            <i class="bi bi-eye-slash"></i>
          </button>
        </div>

        <p class="ajustes-help-text">
          El PIN no se guarda en el navegador.
        </p>

        <div class="ajustes-actions">
          <a href="<?= site_url('/') ?>" class="btn btn-outline-secondary ajustes-btn-ghost">
            <i class="bi bi-arrow-left me-1"></i> Volver al inicio
          </a>

          <!-- Botón con candadito animado -->
          <button type="submit" class="btn ajustes-btn-primary">
            <span class="ajustes-btn-lock">
              <i class="bi bi-lock-fill"></i>
            </span>
            <span>Entrar</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Columna derecha: contexto / texto -->
    <aside class="ajustes-access-aside">
      <div class="ajustes-pill">
        <span class="ajustes-pill-dot"></span>
        Panel interno · Procesos disciplinarios
      </div>

      <p class="ajustes-aside-text">
        El acceso a este módulo permite modificar la
        <strong>tabla maestra de faltas del RIT</strong>.
        Verifica siempre antes de guardar cambios.
      </p>

      <ul class="ajustes-aside-list">
        <li>
          <i class="bi bi-check-circle-fill"></i>
          Solo usa este PIN desde equipos de confianza.
        </li>
        <li>
          <i class="bi bi-check-circle-fill"></i>
          Registra los cambios importantes en tu bitácora interna.
        </li>
        <li>
          <i class="bi bi-check-circle-fill"></i>
          Si sospechas uso indebido, solicita el cambio de PIN.
        </li>
      </ul>

      <div class="ajustes-chip">
        <i class="bi bi-shield-check"></i>
        Seguridad ligera, sin afectar el flujo del portal.
      </div>
    </aside>
  </div>
</div>

<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<script>
  // Toggle de mostrar/ocultar PIN
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.ajustes-toggle-visibility').forEach(btn => {
      btn.addEventListener('click', () => {
        const targetSelector = btn.getAttribute('data-target');
        const input = document.querySelector(targetSelector);
        if (!input) return;

        const icon = btn.querySelector('i');
        const isPassword = input.getAttribute('type') === 'password';

        input.setAttribute('type', isPassword ? 'text' : 'password');
        icon.classList.toggle('bi-eye-slash', !isPassword);
        icon.classList.toggle('bi-eye', isPassword);
      });
    });
  });
</script>
<?= $this->endSection(); ?>