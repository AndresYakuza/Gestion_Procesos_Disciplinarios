<?= $this->extend('layouts/main'); ?>

<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/decision.css'); ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<?php
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

        <!-- Uploader de soportes -->
        <div class="section-header mt-3">
          <i class="bi bi-paperclip me-1"></i>
          <h6>Soportes de decisión (Obligatorio)</h6>
        </div>

        <div class="row g-3">
          <div class="col-12 col-lg-8">
            <label class="form-label">Archivos de soporte</label>
            <div class="dropzone text-center p-4 border" id="dzDecision">
              <i class="bi bi-cloud-arrow-up fs-2 d-block mb-2"></i>
              <div class="fw-semibold">Arrastra y suelta tus archivos aquí</div>
              <div class="text-muted small">o haz clic para seleccionarlos desde tu equipo.</div>

              <!-- input real, oculto -->
              <input
                id="dec_adjuntos"
                name="adjuntos[]"
                type="file"
                class="d-none"
                multiple
                accept=".pdf,.jpg,.jpeg,.png,.heic,.doc,.docx,.xls,.xlsx">
            </div>
            <div class="form-text">
              Se permiten varios archivos (PDF, imágenes, Office). Máx. 16 MB por archivo.
            </div>
          </div>

          <div class="col-12 col-lg-4">
            <div class="filelist border rounded p-2">
              <div class="small text-muted mb-2">Archivos seleccionados para subir:</div>
              <ul id="dec_fileList" class="list-unstyled mb-0"></ul>
            </div>
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

<!-- Toasts por si el layout no los tiene -->
<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000;"></div>

<!-- Loader global -->
<div id="globalLoader" class="loader-overlay d-none">
  <div class="loader-content">
    <lottie-player
      class="loader-lottie"
      src="<?= base_url('assets/lottie/dancing_dog.json') ?>"
      background="transparent"
      speed="1"
      style="width: 220px; height: 220px;"
      loop
      autoplay>
    </lottie-player>
    <p class="loader-text mb-0 text-muted">
      Guardando decisión, por favor espera…
    </p>
  </div>
</div>

<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

<script>
(() => {
  const PREFIX = 'PD-';
  const baseFind = '<?= base_url('decision/find'); ?>';

  const form         = document.getElementById('decForm');
  const consecutivo  = document.getElementById('dec_consecutivo');
  const btnBuscar    = document.getElementById('btnDecBuscar');
  const prevWrap     = document.getElementById('dec_prev_wrap');
  const prevGrid     = document.getElementById('dec_prev_adjuntos');
  const dz           = document.getElementById('dzDecision');
  const adjuntos     = document.getElementById('dec_adjuntos');
  const fileList     = document.getElementById('dec_fileList');
  const btnGuardar   = document.getElementById('btnDecGuardar');
  const globalLoader = document.getElementById('globalLoader');

  const MAX_FILE_SIZE = 16 * 1024 * 1024; // 16 MB
  const ALLOWED_EXT = ['pdf','jpg','jpeg','png','heic','doc','docx','xls','xlsx'];

  const showGlobalLoader = () => globalLoader?.classList.remove('d-none');
  const hideGlobalLoader = () => globalLoader?.classList.add('d-none');

  // Toast simple (si no existe showToast global)
  function showToast(message, type = 'info') {
    const colors = {
      success: 'bg-success text-white',
      error:   'bg-danger text-white',
      warning: 'bg-warning text-dark',
      info:    'bg-info text-dark',
    };
    const icon = {
      success: 'bi-check-circle-fill',
      error:   'bi-x-circle-fill',
      warning: 'bi-exclamation-triangle-fill',
      info:    'bi-info-circle-fill',
    };

    const toast = document.createElement('div');
    toast.className = `toast align-items-center border-0 show ${colors[type]} mt-2 shadow`;
    toast.role = 'alert';
    toast.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">
          <i class="bi ${icon[type]} me-2"></i>${message}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>`;

    const container =
      document.getElementById('toastContainer') ||
      (() => {
        const c = document.createElement('div');
        c.id = 'toastContainer';
        c.className = 'toast-container position-fixed top-0 end-0 p-3';
        c.style.zIndex = 2000;
        document.body.appendChild(c);
        return c;
      })();

    container.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
  }

  const notify = (msg, type = 'info') => showToast(msg, type);

  // ----- Normalización PD- -----
  const onlyDigits = (str) => (str || '').replace(/\D/g, '');

  function normalizeConsecutivoSixDigits(value) {
    const digits = onlyDigits(String(value));
    if (!digits) return '';
    return PREFIX + digits.padStart(6, '0');
  }

  consecutivo?.addEventListener('focus', () => {
    if (!consecutivo.value.trim()) {
      consecutivo.value = PREFIX;
      setTimeout(() => {
        const len = consecutivo.value.length;
        consecutivo.setSelectionRange(len, len);
      }, 0);
    }
  });

  consecutivo?.addEventListener('input', () => {
    const digits = onlyDigits(consecutivo.value);
    consecutivo.value = PREFIX + digits;
  });

  // ----- Adjuntos previos (registro/citación/descargos/soporte/decisión) -----
  const iconByMime = (mime = '') => {
    mime = mime.toLowerCase();
    if (mime.includes('pdf'))   return 'bi-filetype-pdf text-danger';
    if (mime.includes('image')) return 'bi-image text-success';
    if (mime.includes('excel') || mime.includes('spreadsheet')) return 'bi-filetype-xls text-success';
    if (mime.includes('word') || mime.includes('msword'))       return 'bi-filetype-doc text-primary';
    return 'bi-file-earmark text-muted';
  };

  function renderAdjuntosExistentes(prevAdj) {
    if (!prevGrid) return;
    prevGrid.innerHTML = '';

    const fases = [
      { key: 'registro', label: 'Fase 1 · Registro' },
      { key: 'citacion', label: 'Fase 2 · Citación' },
      { key: 'descargos',label: 'Fase 3 · Descargos' },
      { key: 'soporte',  label: 'Fase 4 · Soporte' },
      { key: 'decision', label: 'Fase 5 · Decisión' },
    ];

    let tieneAlgo = false;

    fases.forEach(f => {
      const arr = (prevAdj && prevAdj[f.key]) ? prevAdj[f.key] : [];
      if (!arr.length) return;

      tieneAlgo = true;

      const header = document.createElement('div');
      header.className = 'col-12';
      header.innerHTML = `<h6 class="text-muted mb-2">${f.label}</h6>`;
      prevGrid.appendChild(header);

      arr.forEach(it => {
        const col = document.createElement('div');
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
        prevGrid.appendChild(col);
      });
    });

    if (tieneAlgo) {
      prevWrap?.classList.remove('d-none');
    } else {
      prevWrap?.classList.add('d-none');
    }
  }

  async function buscar() {
    if (!consecutivo) return;

    const normalized = normalizeConsecutivoSixDigits(consecutivo.value);
    if (!normalized) {
      notify('Debes escribir un consecutivo válido (ej: PD-000123).', 'warning');
      consecutivo.focus();
      return;
    }

    consecutivo.value = normalized;
    consecutivo.classList.add('loading');

    try {
      const url = `${baseFind}?consecutivo=${encodeURIComponent(normalized)}`;
      const res = await fetch(url);
      const data = res.ok ? await res.json() : null;

      if (!data || !data.ok) {
        renderAdjuntosExistentes(null);
        notify('No se encontró un FURD con ese consecutivo.', 'error');
        return;
      }

      renderAdjuntosExistentes(data.prevAdj || {});
      notify('Registro cargado correctamente.', 'success');
    } catch (e) {
      console.error(e);
      renderAdjuntosExistentes(null);
      notify('Ocurrió un error al consultar el FURD.', 'error');
    } finally {
      consecutivo.classList.remove('loading');
    }
  }

  btnBuscar?.addEventListener('click', buscar);
  consecutivo?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      buscar();
    }
  });

  // ----- Adjuntos nuevos: validación + listado + recorte de nombres + quitar -----
  const shortenName = (name, max = 28) => {
    if (!name) return '';
    if (name.length <= max) return name;

    const dotIndex = name.lastIndexOf('.');
    const hasExt   = dotIndex > 0;
    const ext      = hasExt ? name.slice(dotIndex) : '';
    const base     = hasExt ? name.slice(0, dotIndex) : name;

    const maxBase = Math.max(8, max - ext.length - 1);
    const start   = base.slice(0, maxBase);
    return `${start}…${ext}`;
  };

  function refreshFileList() {
    if (!fileList || !adjuntos) return;
    fileList.innerHTML = '';

    const files = Array.from(adjuntos.files || []);
    if (!files.length) {
      fileList.innerHTML = '<li class="text-muted small">No hay archivos seleccionados.</li>';
      return;
    }

    files.forEach((f, idx) => {
      const sizeMb      = (f.size / (1024 * 1024)).toFixed(2);
      const displayName = shortenName(f.name);

      const li = document.createElement('li');
      li.className = 'd-flex flex-column gap-1 py-1 border-bottom';

      li.innerHTML = `
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-paperclip"></i>
          <span class="flex-grow-1 text-truncate file-name" title="${f.name}">${displayName}</span>
          <span class="badge text-bg-light">${sizeMb} MB</span>
          <button type="button" class="btn btn-sm btn-link text-danger p-0 js-remove-file"
                  data-file-idx="${idx}" title="Quitar archivo">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="progress mt-1" style="height:4px;">
          <div class="progress-bar" role="progressbar" data-file-idx="${idx}" style="width:0%;"></div>
        </div>`;
      fileList.appendChild(li);
    });
  }

  function handleAdjuntosChange() {
    if (!adjuntos) return;
    const dt = new DataTransfer();

    Array.from(adjuntos.files || []).forEach((file) => {
      const ext = file.name.split('.').pop().toLowerCase();
      const isAllowedExt  = ALLOWED_EXT.includes(ext);
      const isAllowedSize = file.size <= MAX_FILE_SIZE;

      if (!isAllowedExt) {
        notify(
          `El archivo "${file.name}" no está permitido. Solo se permiten imágenes (JPG, JPEG, PNG, HEIC), PDF y Office (DOC, DOCX, XLS, XLSX).`,
          'warning'
        );
        return;
      }

      if (!isAllowedSize) {
        notify(
          `El archivo "${file.name}" supera el límite de 16 MB y no se cargará.`,
          'warning'
        );
        return;
      }

      dt.items.add(file);
    });

    adjuntos.files = dt.files;
    refreshFileList();
  }

  adjuntos?.addEventListener('change', handleAdjuntosChange);

  fileList?.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-remove-file');
    if (!btn || !adjuntos) return;

    const idx = parseInt(btn.dataset.fileIdx, 10);
    if (Number.isNaN(idx)) return;

    const dt = new DataTransfer();
    Array.from(adjuntos.files || []).forEach((file, i) => {
      if (i !== idx) dt.items.add(file);
    });

    adjuntos.files = dt.files;
    refreshFileList();

    if (!dt.files.length) {
      notify('Se han quitado todos los archivos seleccionados.', 'info');
    }
  });

  // Dropzone click + drag&drop
  if (dz && adjuntos) {
    dz.addEventListener('click', () => adjuntos.click());

    dz.addEventListener('dragover', (e) => {
      e.preventDefault();
      dz.classList.add('dragging');
    });
    dz.addEventListener('dragleave', () => dz.classList.remove('dragging'));
    dz.addEventListener('drop', (e) => {
      e.preventDefault();
      dz.classList.remove('dragging');
      if (!e.dataTransfer?.files?.length) return;

      const dt = new DataTransfer();
      [...(adjuntos.files || []), ...e.dataTransfer.files].forEach(f => dt.items.add(f));
      adjuntos.files = dt.files;
      handleAdjuntosChange();
    });
  }

  // ----- Progreso por archivo -----
  let uploadFilesMeta = [];

  const buildUploadMeta = () => {
    if (!adjuntos) {
      uploadFilesMeta = [];
      return 0;
    }

    const files = Array.from(adjuntos.files || []);
    let offset = 0;
    uploadFilesMeta = files.map((file, idx) => {
      const start = offset;
      const end   = start + file.size;
      offset = end;
      return { index: idx, start, end, size: file.size };
    });

    return offset;
  };

  const updateUploadProgressBars = (loaded) => {
    if (!uploadFilesMeta.length || !fileList) return;

    uploadFilesMeta.forEach((meta) => {
      const bar = fileList.querySelector(`.progress-bar[data-file-idx="${meta.index}"]`);
      if (!bar) return;

      let percent = 0;
      if (loaded <= meta.start) {
        percent = 0;
      } else if (loaded >= meta.end) {
        percent = 100;
      } else {
        percent = ((loaded - meta.start) / meta.size) * 100;
      }

      bar.style.width = `${percent}%`;
    });
  };

  // ----- Envío AJAX con loader y manejo de errores sin perder adjuntos -----
  if (form && btnGuardar) {
    const spin = btnGuardar.querySelector('.spinner-border');
    const txt  = btnGuardar.querySelector('.btn-text');
    let sending = false;

    form.addEventListener('submit', (e) => {
      e.preventDefault();

      // Normalizar consecutivo antes de enviar
      if (consecutivo) {
        const normalized = normalizeConsecutivoSixDigits(consecutivo.value);
        if (!normalized) {
          notify('El consecutivo es obligatorio y debe tener formato PD-000123.', 'error');
          consecutivo.focus();
          return;
        }
        consecutivo.value = normalized;
      }

      if (sending) return;
      sending = true;

      btnGuardar.disabled = true;
      if (spin) spin.classList.remove('d-none');
      if (txt)  txt.textContent = 'Guardando...';

      showGlobalLoader();

      const formData  = new FormData(form);
      const totalByte = buildUploadMeta(); // por si quieres usar totalByte a futuro

      const xhr = new XMLHttpRequest();
      xhr.open(form.method || 'POST', form.action);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

      xhr.upload.onprogress = (evt) => {
        if (!evt.lengthComputable) return;
        updateUploadProgressBars(evt.loaded);
      };

      const resetBtn = () => {
        sending = false;
        btnGuardar.disabled = false;
        if (spin) spin.classList.add('d-none');
        if (txt)  txt.textContent = 'Guardar';
      };

      xhr.onload = () => {
        hideGlobalLoader();

        const contentType = xhr.getResponseHeader('Content-Type') || '';
        let data = null;

        if (contentType.includes('application/json')) {
          try {
            data = JSON.parse(xhr.responseText || '{}');
          } catch (_) {
            data = null;
          }
        }

        if (data) {
          if (data.ok && data.redirectTo) {
            window.location.href = data.redirectTo;
            return;
          }

          if (data.ok === false && data.errors) {
            const allErrors = Array.isArray(data.errors)
              ? data.errors
              : Object.values(data.errors);

            const firstError = allErrors.length
              ? allErrors[0]
              : 'Revisa los campos obligatorios.';

            notify(firstError, 'warning');
            resetBtn();
            return;
          }

          notify('Error inesperado al registrar la decisión.', 'error');
          resetBtn();
          return;
        }

        if (xhr.status >= 200 && xhr.status < 400) {
          const finalURL = xhr.responseURL || form.action;
          window.location.href = finalURL;
        } else {
          notify('Ocurrió un error al registrar la decisión.', 'error');
          resetBtn();
        }
      };

      xhr.onerror = () => {
        hideGlobalLoader();
        notify('No se pudo conectar con el servidor. Revisa tu conexión.', 'error');
        resetBtn();
      };

      xhr.send(formData);
    });
  }

  // Inicializar lista vacía
  refreshFileList();
})();
</script>

<?= $this->endSection(); ?>
