<?= $this->extend('layouts/main'); ?>

<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/decision.css'); ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<?php
// fallback por si el controlador no pasa el catálogo
$decisiones = $decisiones ?? [
  'Llamado de atención',
  'Suspensión disciplinaria',
  'Terminación de contrato',
];

$errors = session('errors') ?? [];
$msg    = session('msg') ?? null;
?>

<div class="row g-4">
  <div class="col-12">
    <div class="card animate-in">

      <div class="card-header main-header">
        <span>⚖️ Datos de la decisión</span>
      </div>

      <form id="decForm" class="card-body"
            method="post"
            action="<?= base_url('decision'); ?>"
            enctype="multipart/form-data"
            novalidate>
        <?= csrf_field(); ?>

        <?php if ($msg): ?>
          <div class="alert alert-success"><?= esc($msg) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Corrige los siguientes campos:</div>
            <ul class="mb-0">
              <?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div class="section-header">
          <i class="bi bi-flag me-1"></i>
          <h6>Decisión</h6>
        </div>

        <div class="row g-3 align-items-start uniform-row">

          <!-- Consecutivo -->
          <div class="col-12 col-md-4">
            <label class="form-label d-flex align-items-center gap-1">
              Consecutivo
              <i class="bi bi-info-circle text-muted small"
                 data-bs-toggle="tooltip"
                 data-bs-placement="right"
                 title="Ingresa el consecutivo del FURD. Ejemplo: PD-000123">
              </i>
            </label>
            <div class="input-group">
              <input
                id="dec_consecutivo"
                name="consecutivo"
                type="text"
                class="form-control <?= !empty($errors['consecutivo'] ?? null) ? 'is-invalid' : '' ?>"
                placeholder="Ej: PD-000123"
                value="<?= old('consecutivo') ?>"
                required
                pattern="[Pp][Dd]-[0-9]{6}"
                title="Formato esperado: PD-000123"
              >
              <button class="btn btn-outline-success" type="button" id="btnDecBuscar" title="Buscar">
                <i class="bi bi-search"></i>
              </button>
            </div>
            <div class="form-text">
              Usa la lupita para cargar soportes previos del FURD.
            </div>
            <?php if (!empty($errors['consecutivo'] ?? null)): ?>
              <div class="invalid-feedback d-block">
                <?= esc($errors['consecutivo']) ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Fecha de la decisión -->
          <div class="col-12 col-md-4">
            <label class="form-label">Fecha de la decisión</label>
            <input
              id="fecha"
              type="text"
              name="fecha_evento"
              class="form-control <?= !empty($errors['fecha_evento'] ?? null) ? 'is-invalid' : '' ?>"
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

          <!-- Tipo de decisión -->
          <div class="col-12 col-md-4">
            <label class="form-label">Tipo de decisión</label>
            <select name="decision" class="form-select <?= !empty($errors['decision'] ?? null) ? 'is-invalid' : '' ?>" required>
              <option value="">Elige una opción...</option>
              <?php foreach ($decisiones as $opt): ?>
                <option value="<?= esc($opt) ?>" <?= old('decision') === $opt ? 'selected' : '' ?>>
                  <?= esc($opt) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['decision'] ?? null)): ?>
              <div class="invalid-feedback d-block">
                <?= esc($errors['decision']) ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-12">
            <label class="form-label">Detalle / fundamentación</label>
            <textarea
              name="decision_text"
              rows="4"
              class="form-control <?= !empty($errors['decision_text'] ?? null) ? 'is-invalid' : '' ?>"
              placeholder="Describe en detalle la decisión tomada..."><?= old('decision_text') ?></textarea>
            <?php if (!empty($errors['decision_text'] ?? null)): ?>
              <div class="invalid-feedback d-block">
                <?= esc($errors['decision_text']) ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="row g-3 mt-1">
          <!-- Soporte subida -->
          <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label">Soporte firmado (opcional)</label>
            <input
              id="dec_soporte"
              name="adjuntos[]"
              type="file"
              class="form-control"
              multiple
              accept=".pdf,.jpg,.jpeg,.png,.heic,.doc,.docx,.xlsx,.xls"
            >
            <div class="form-text">Puedes adjuntar uno o varios documentos de decisión firmados.</div>
          </div>
        </div>

        <!-- PREVIEW DE SOPORTES EXISTENTES DEL FURD -->
        <div id="dec_prev_wrap" class="mt-4 d-none">
          <div class="section-header">
            <i class="bi bi-paperclip me-1"></i>
            <h6>Soportes ya asociados al proceso</h6>
          </div>

          <div id="dec_prev_adjuntos" class="row g-3"></div>
        </div>

        <div class="sticky-actions bg-body border-top mt-4 pt-3 pb-3">
          <div class="d-flex gap-2 justify-content-end">
            <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary">
              <i class="bi bi-x-circle me-1"></i>Cancelar
            </a>
            <button id="btnDecGuardar" type="submit" class="btn btn-success">
              <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
              <span class="btn-text"><i class="bi bi-save2 me-1"></i>Guardar</span>
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
    const $cons  = document.getElementById('dec_consecutivo');
    const $btn   = document.getElementById('btnDecBuscar');
    const $grid  = document.getElementById('dec_prev_adjuntos');
    const baseFind = '<?= base_url('decision/find'); ?>';

    function notify(msg, type = 'info', ms = 3800) {
      if (typeof showToast === 'function') {
        showToast(msg, type, ms);
      } else {
        console[type === 'error' ? 'error' : 'log'](msg);
        alert(msg);
      }
    }

    const iconByMime = (mime = '') => {
      mime = mime.toLowerCase();
      if (mime.includes('pdf'))   return 'bi-filetype-pdf text-danger';
      if (mime.includes('image')) return 'bi-image text-success';
      if (mime.includes('excel') || mime.includes('spreadsheet')) return 'bi-filetype-xls text-success';
      if (mime.includes('word') || mime.includes('msword'))       return 'bi-filetype-doc text-primary';
      return 'bi-file-earmark text-muted';
    };

    // Render igual que en Soporte, pero con Soporte/Decisión también
    function renderAdjuntosExistentes(prevAdj) {
      $grid.innerHTML = '';

      const fases = [
        { key: 'registro', label: 'Fase 1 · Registro'   },
        { key: 'citacion', label: 'Fase 2 · Citación'   },
        { key: 'descargos', label: 'Fase 3 · Descargos' },
        { key: 'soporte',  label: 'Fase 4 · Soporte'    },
        { key: 'decision', label: 'Fase 5 · Decisión'   },
      ];

      let tieneAlgo = false;

      fases.forEach(f => {
        const arr = (prevAdj && prevAdj[f.key]) ? prevAdj[f.key] : [];
        if (!arr.length) return;

        tieneAlgo = true;

        const header = document.createElement('div');
        header.className = 'col-12';
        header.innerHTML = `<h6 class="text-muted mb-2">${f.label}</h6>`;
        $grid.appendChild(header);

        arr.forEach(it => {
          const col    = document.createElement('div');
          col.className = 'col-12 col-md-6 col-xl-4';

          const nombre = it.nombre_original || it.nombre || it.filename || `Adjunto #${it.id}`;
          const mime   = it.mime || '';
          const url    = it.url || it.display_url || '<?= base_url('adjuntos'); ?>/' + it.id + '/open';

          col.innerHTML = `
            <div class="adj-card border rounded p-3 h-100 d-flex flex-column gap-2">
              <div class="d-flex align-items-center gap-2">
                <i class="bi ${iconByMime(mime)} fs-4"></i>
                <div class="small fw-semibold text-truncate" title="${nombre}">${nombre}</div>
              </div>
              <div class="small text-muted">${mime || 'archivo'}</div>
              <div class="mt-auto">
                <a class="btn btn-sm btn-outline-primary" href="${url}" target="_blank" rel="noopener">
                  <i class="bi bi-box-arrow-up-right me-1"></i>Ver / Descargar
                </a>
              </div>
            </div>`;
          $grid.appendChild(col);
        });
      });

      if (!tieneAlgo) {
        $grid.innerHTML = `
          <div class="col-12">
            <div class="alert alert-secondary small mb-0">
              Este proceso no tiene soportes previos.
            </div>
          </div>`;
      }
    }

    async function buscar() {
      const id = ($cons.value || '').trim();

      if (!id) {
        notify('Ingresa un consecutivo para buscar.', 'info');
        $cons.focus();
        return;
      }

      if (!/^PD-\d{6}$/i.test(id)) {
        notify('Formato inválido. Usa algo como PD-000123.', 'error');
        return;
      }

      $cons.classList.add('loading');

      try {
        const url = `${baseFind}?consecutivo=${encodeURIComponent(id)}`;
        const res = await fetch(url);
        if (!res.ok) throw new Error('No se pudo consultar el FURD.');

        const data = await res.json();
        if (!data.ok) {
          renderAdjuntosExistentes(null);
          throw new Error('No se encontró un FURD con ese consecutivo.');
        }

        renderAdjuntosExistentes(data.prevAdj || {});
        notify('Registro cargado correctamente.', 'success');
      } catch (e) {
        renderAdjuntosExistentes(null);
        notify(e.message || 'No se encontró el registro.', 'error');
      } finally {
        $cons.classList.remove('loading');
      }
    }

    $btn?.addEventListener('click', buscar);
    $cons?.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        e.preventDefault();
        buscar();
      }
    });
  })();

  // (opcional) loader del botón Guardar, igual que en Soporte
  (() => {
    const form = document.querySelector('form[action$="decision"]');
    if (!form) return;

    const submitBtn = form.querySelector('button[type="submit"], .btn-success');

    form.addEventListener('submit', () => {
      if (!submitBtn) return;
      submitBtn.disabled = true;
      if (!submitBtn.dataset.originalHtml) {
        submitBtn.dataset.originalHtml = submitBtn.innerHTML;
      }
      submitBtn.innerHTML = `
        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        Guardando...
      `;
    });
  })();
</script>
<?= $this->endSection(); ?>

