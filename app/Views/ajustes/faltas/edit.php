<?= $this->extend('layouts/main'); ?>
<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/ajustes-faltas.css'); ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<div class="row g-4">
  <div class="col-12 col-lg-10 mx-auto">
    <div class="card animate-in">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Editar falta</h5>
      </div>

      <form class="card-body" method="post" action="<?= base_url('ajustes/faltas/'.$falta['id']) ?>">
        <?= csrf_field(); ?>

        <?php if ($errors = session('errors')): ?>
          <div class="alert alert-danger">
            <?php foreach($errors as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="row g-3">
          <div class="col-12 col-md-4">
            <label class="form-label">Código</label>
            <input name="codigo" type="text" class="form-control" value="<?= esc($falta['codigo']) ?>" required>
          </div>
          <div class="col-12 col-md-8">
            <label class="form-label">Gravedad</label>
            <select name="gravedad" class="form-select" required>
              <?php
                $g = $falta['gravedad'];
                $opts = ['Leve','Grave','Gravísima'];
                foreach ($opts as $o):
              ?>
              <option value="<?= $o ?>" <?= $g===$o ? 'selected':'' ?>><?= $o ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Falta / Descripción</label>
            <textarea name="descripcion" class="form-control" rows="4" required><?= esc($falta['descripcion']) ?></textarea>
          </div>
        </div>

        <div class="sticky-actions bg-body border-top mt-4 pt-3 pb-3">
          <div class="d-flex justify-content-end gap-2">
            <a href="<?= base_url('ajustes/faltas') ?>" class="btn btn-outline-secondary">Cancelar</a>
            <button class="btn btn-success"><i class="bi bi-save me-1"></i>Guardar cambios</button>
          </div>
        </div>
      </form>

    </div>
  </div>
</div>

  <!-- Loader global para editar falta -->
  <div id="globalLoader" class="loader-overlay d-none">
    <div class="loader-content">
      <lottie-player
        class="loader-lottie"
        src="<?= base_url('assets/lottie/confetti-animation.json') ?>"
        background="transparent"
        speed="1"
        style="width: 220px; height: 220px;"
        loop
        autoplay>
      </lottie-player>
      <p class="loader-text mb-0 text-muted">
        Guardando cambios de la falta…
      </p>
    </div>
  </div>

<?= $this->endSection(); ?>


<?= $this->section('scripts'); ?>
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form         = document.querySelector('form[action*="ajustes/faltas/"]');
  const globalLoader = document.getElementById('globalLoader');

  const showGlobalLoader = () => globalLoader?.classList.remove('d-none');

  if (!form) return;

  form.addEventListener('submit', () => {
    const btn = form.querySelector('.btn-success');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `
        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        Guardando...
      `;
    }
    showGlobalLoader();
  });
});
</script>
<?= $this->endSection(); ?>
