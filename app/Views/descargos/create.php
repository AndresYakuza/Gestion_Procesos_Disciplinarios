<?= $this->extend('layouts/main'); ?>

<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/cargos-descargos.css'); ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<?php $errors = session('errors') ?? []; ?>

<div class="row g-4">
  <div class="col-12">
    <div class="card animate-in">


      <div class="card-header main-header">
        <span>📜 Acta de Cargos y Descargos</span>
      </div>

      <form class="card-body" method="post" action="<?= base_url('descargos'); ?>" novalidate>
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
        <div class="section-header">
          <i class="bi bi-clipboard2-pulse"></i>
          <h6>Datos del acta de cargos y descargos</h6>
        </div>



        <div class="row g-3 align-items-end">
          <div class="col-12 col-md-6">
            <label class="form-label d-flex align-items-center gap-1">
              Consecutivo del proceso
              <i class="bi bi-info-circle text-muted small"
                data-bs-toggle="tooltip"
                data-bs-placement="right"
                title="Ingresa el consecutivo del FURD. Ejemplo: PD-000123">
              </i>
            </label>

            <div class="input-group">
              <input
                id="consecutivo"
                name="consecutivo"
                type="text"
                class="form-control <?= !empty($errors['consecutivo'] ?? null) ? 'is-invalid' : '' ?>"
                placeholder="Ej: PD-000123"
                value="<?= old('consecutivo') ?>"
                required
                pattern="[Pp][Dd]-[0-9]{6}"
                title="Formato esperado: PD-000001">

              <button
                type="button"
                id="btnBuscarConsecutivo"
                class="btn btn-outline-success"
                title="Buscar registro">
                <i class="bi bi-search"></i>
              </button>
            </div>

            <?php if (!empty($errors['consecutivo'] ?? null)): ?>
              <div class="invalid-feedback d-block">
                <?= esc($errors['consecutivo']) ?>
              </div>
            <?php endif; ?>
          </div>



          <div class="col-6 col-lg-3">
            <label class="form-label">Fecha</label>
            <input
              id="fecha"
              type="text"
              class="form-control <?= !empty($errors['fecha_evento'] ?? null) ? 'is-invalid' : '' ?>"
              name="fecha_evento"
              placeholder="Selecciona una fecha..."
              value="<?= old('fecha_evento') ?>"
              required
            >
            <?php if (!empty($errors['fecha_evento'] ?? null)): ?>
              <div class="invalid-feedback d-block">
                <?= esc($errors['fecha_evento']) ?>
              </div>
            <?php endif; ?>
          </div>


          <div class="col-6 col-lg-3">
            <label class="form-label">Hora</label>
            <input id="hora" type="time" class="form-control" name="hora" placeholder="Selecciona hora..." required>
          </div>

          <div class="col-12 col-lg-2">
            <label class="form-label">Medio del Descargo</label>
            <select name="medio" class="form-select" required>
              <option value="" selected>Elige una opción...</option>
              <option value="presencial">Presencial</option>
              <option value="virtual">Virtual</option>
            </select>
          </div>
        </div>

        <!-- PREVIEW DE ADJUNTOS (opcional) -->
        <div class="mt-4">


          <div class="section-header mt-4">
            <i class="bi bi-paperclip"></i>
            <h6>Adjuntos del proceso (solo lectura)</h6>
            <small class="text-muted">(se cargan al ingresar el consecutivo)</small>
          </div>

          <div id="adjuntosBox" class="adjuntos-box">
            <div class="adjuntos-empty text-muted">Sin adjuntos para mostrar.</div>
          </div>
        </div>

        <!-- BOTONES -->
        <div class="sticky-actions bg-body border-top mt-4 pt-3 pb-3">
          <div class="d-flex gap-2 justify-content-end">
            <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary">
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
    const box = document.getElementById('adjuntosBox');

    function renderAdjuntos(items) {
      box.innerHTML = '';
      if (!items || !items.length) {
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
      if (!id) {
        renderAdjuntos([]);
        return;
      }

      try {
        const res = await fetch(
          '<?= base_url('furd/adjuntos'); ?>?consecutivo=' + encodeURIComponent(id)
        );
        if (!res.ok) {
          renderAdjuntos([]);
          return;
        }
        const data = await res.json();
        renderAdjuntos(Array.isArray(data) ? data : []);
      } catch {
        renderAdjuntos([]);
      }
    });
  })();

  (() => {
    const $btn   = document.getElementById('btnBuscarConsecutivo');
    const $input = document.getElementById('consecutivo');

    // Wrapper para usar el toast global (y tener un fallback feo si no existe)
    function notify(msg, type = 'info', ms = 3800) {
      if (typeof showToast === 'function') {
        showToast(msg, type, ms); // usa el SweetAlert2 global
      } else {
        // fallback muy básico por si acaso
        console[type === 'error' ? 'error' : 'log'](msg);
        alert(msg);
      }
    }

    async function buscar() {
      const id = ($input.value || '').trim();
      if (!id) {
        $input.focus();
        return;
      }

      $input.classList.add('loading');

      try {
        if (!/^PD-\d{6}$/i.test(id)) {
          notify('Formato inválido. Usa algo como PD-000123.', 'error');
          return;
        }

        const url = '<?= base_url('descargos/find'); ?>?consecutivo=' + encodeURIComponent(id);
        const res = await fetch(url);

        if (!res.ok) throw new Error('Error consultando el registro.');
        const data = await res.json();

        if (!data.ok) throw new Error('No se encontró el registro.');

        // Aquí podrías, si quisieras, sincronizar otros campos con data.furd

        notify('Registro encontrado y cargado.', 'success');
      } catch (e) {
        notify(e.message || 'No se encontró el registro.', 'error');
      } finally {
        $input.classList.remove('loading');
      }
    }

    $btn?.addEventListener('click', buscar);
    $input?.addEventListener('keydown', (e) => (e.key === 'Enter') && buscar());
  })();
</script>
<?= $this->endSection(); ?>
