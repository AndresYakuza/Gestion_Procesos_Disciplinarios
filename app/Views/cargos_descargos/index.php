<?= $this->extend('layouts/main'); ?>

<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/cargos-descargos.css'); ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<?php $errors = session('errors') ?? []; ?>

<div class="row g-4">
  <div class="col-12">
    <div class="card animate-in">
      <div class="card-header bg-success-subtle">
        <h5 class="mb-0">📜 Acta de Cargos y Descargos</h5>
      </div>

      <form class="card-body" method="post" action="<?= site_url('cargos-descargos'); ?>" novalidate>
        <?= csrf_field(); ?>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Corrige los siguientes campos:</div>
            <ul class="mb-0">
              <?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- BANDA / TÍTULO DE SECCIÓN -->
        <div class="cyd-band mb-3">Datos del acta de cargos y descargos</div>

        <div class="row g-3 align-items-end">
        <div class="col-12 col-md-6">
        <label class="form-label">Consecutivo           
            <i class="bi bi-info-circle text-muted small"
                data-bs-toggle="tooltip"
                data-bs-placement="right"
                title="Escribe el ID del FURD. Traerá los adjuntos automáticamente.">
            </i></label>
        <div class="input-group">
            <input id="consecutivo" name="consecutivo"
                type="text" class="form-control"
                placeholder="ID FURD o consecutivo">
            <button type="button" id="btnBuscarConsecutivo"
                    class="btn btn-outline-success" 
                    title="Buscar registro">
            <i class="bi bi-search"></i>
            </button>
        </div>
        </div>


          <div class="col-6 col-lg-3">
            <label class="form-label">Fecha</label>
            <input name="fecha" type="date" class="form-control" required>
          </div>

          <div class="col-6 col-lg-3">
            <label class="form-label">Hora</label>
            <input name="hora" type="time" class="form-control" required>
          </div>

          <div class="col-12 col-lg-2">
            <label class="form-label">Medio del Descargo</label>
            <select name="medio" class="form-select" required>
              <option value="" selected>Elige una opción...</option>
              <option value="PRESENCIAL">PRESENCIAL</option>
              <option value="VIRTUAL">VIRTUAL</option>
            </select>
          </div>
        </div>

        <!-- PREVIEW DE ADJUNTOS (opcional) -->
        <div class="mt-4">
          <div class="d-flex align-items-center gap-2 mb-2">
            <h6 class="mb-0 text-muted">Adjuntos del proceso</h6>
            <small class="text-muted">(se cargan al ingresar el consecutivo)</small>
          </div>
          <div id="adjuntosBox" class="adjuntos-box">
            <div class="adjuntos-empty text-muted">Sin adjuntos para mostrar.</div>
          </div>
        </div>

        <!-- BOTONES -->
        <div class="sticky-actions bg-body border-top mt-4 pt-3 pb-3">
          <div class="d-flex gap-2 justify-content-end">
            <a href="<?= site_url('/') ?>" class="btn btn-outline-secondary">
              <i class="bi bi-x-circle me-1"></i>Cancelar
            </a>
            <button class="btn btn-success">
              <i class="bi bi-check2-circle me-1"></i>Generar
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<script>
(() => {
  const input = document.getElementById('consecutivo');
  const box   = document.getElementById('adjuntosBox');

  function renderAdjuntos(items){
    box.innerHTML = '';
    if (!items || !items.length){
      box.innerHTML = '<div class="adjuntos-empty text-muted">Sin adjuntos para mostrar.</div>';
      return;
    }
    items.forEach(f => {
      const row = document.createElement('div');
      row.className = 'adjuntos-item';
      row.innerHTML = `
        <div class="file-icon">
          <i class="bi bi-file-earmark-text text-success"></i>
        </div>
        <div class="flex-fill">
          <div class="fw-semibold small">${f.nombre ?? 'archivo'}</div>
          <div class="text-muted xsmall">${f.mime ?? ''} ${f.tamano ?? ''}</div>
        </div>
        ${f.url ? `<a class="btn btn-sm btn-outline-secondary" href="${f.url}" target="_blank">Ver</a>` : ''}
      `;
      box.appendChild(row);
    });
  }

  // Carga adjuntos al salir del campo de consecutivo
  input?.addEventListener('blur', async () => {
    const id = input.value.trim();
    if (!id){ renderAdjuntos([]); return; }

    try {
      // Endpoint sugerido (impleméntalo cuando veas backend):
      // GET /furd/{id}/adjuntos  -> [{ nombre, mime, tamano, url }]
      const res = await fetch('<?= site_url('furd'); ?>/' + encodeURIComponent(id) + '/adjuntos');
      if (!res.ok) { renderAdjuntos([]); return; }
      const data = await res.json();
      renderAdjuntos(Array.isArray(data) ? data : []);
    } catch { renderAdjuntos([]); }
  });
})();

(() => {
  const $btn   = document.getElementById('btnBuscarConsecutivo');
  const $input = document.getElementById('consecutivo');

  // Toast minimalista (Bootstrap-like)
  function toast(msg, type = 'info', ms = 3800) {
    const t = document.createElement('div');
    t.className = `toast align-items-center text-bg-${type} show position-fixed top-0 end-0 m-3`;
    t.role = 'alert';
    t.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${msg}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto"
                data-bs-dismiss="toast" aria-label="Close"></button>
      </div>`;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), ms);
  }

  async function buscar() {
    const id = ($input.value || '').trim();
    if (!id) { $input.focus(); return; }

    // spinner si ya tienes la clase .loading en tu CSS global
    $input.classList.add('loading');

    try {
      // Endpoint de búsqueda (ajústalo a tu ruta real)
      const url = '<?= site_url('furd/lookup') ?>/' + encodeURIComponent(id);
      const res = await fetch(url);

      if (!res.ok) throw new Error('No se encontró el registro.');
      const data = await res.json();

      // TODO: aquí cargas en la vista lo que necesites de 'data', por ejemplo adjuntos o datos básicos
      // document.getElementById('medio').value = data.medio ?? '';
      // renderAdjuntos(data.adjuntos ?? []);

      toast('Registro encontrado y cargado.', 'success');
    } catch (e) {
      toast(e.message || 'No se encontró el registro.', 'danger');
    } finally {
      $input.classList.remove('loading');
    }
  }

  $btn?.addEventListener('click', buscar);
  $input?.addEventListener('keydown', (e) => (e.key === 'Enter') && buscar());
})();
</script>
<?= $this->endSection(); ?>
