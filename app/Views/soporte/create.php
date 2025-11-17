<?= $this->extend('layouts/main'); ?>

<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/soporte.css'); ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<?php
$errors      = session('errors') ?? [];
$responsable = $responsable ?? '';
$decisiones  = $decisiones ?? [
  'Llamado de atención',
  'Suspensión disciplinaria',
  'Terminación de contrato'
];
?>

<div class="row g-4">
  <div class="col-12">
    <div class="card animate-in">

      <div class="card-header main-header">
        <span>🗂️ Citación, acta de cargos y descargos diligenciadas</span>
      </div>

      <form class="card-body"
            method="post"
            action="<?= base_url('soporte'); ?>"
            enctype="multipart/form-data"
            novalidate>
        <?= csrf_field(); ?>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Corrige los siguientes campos:</div>
            <ul class="mb-0">
              <?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- Bloque principal -->
        <div class="row g-3">

          <div class="section-header">
            <i class="bi bi-clipboard2-pulse"></i>
            <h6>Datos</h6>
          </div>

          <!-- Consecutivo + búsqueda -->
          <div class="col-12 col-lg-4">
            <label class="form-label">Consecutivo</label>
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
                title="Formato esperado: PD-000123"
              >
              <button type="button" id="btnBuscar" class="btn btn-outline-success" title="Buscar FURD">
                <i class="bi bi-search"></i>
              </button>
            </div>
            <div class="form-text">
              Escribe el consecutivo del FURD y pulsa la lupa para cargar la información y adjuntos existentes.
            </div>

            <?php if (!empty($errors['consecutivo'] ?? null)): ?>
              <div class="invalid-feedback d-block">
                <?= esc($errors['consecutivo']) ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Responsable -->
          <div class="col-12 col-lg-4">
            <label class="form-label">Responsable</label>
            <input
              name="responsable"
              id="responsable"
              type="text"
              class="form-control <?= !empty($errors['responsable'] ?? null) ? 'is-invalid' : '' ?>"
              value="<?= old('responsable', $responsable) ?>"
              placeholder="Nombre de quien diligencia"
              required
            >
            <?php if (!empty($errors['responsable'] ?? null)): ?>
              <div class="invalid-feedback d-block">
                <?= esc($errors['responsable']) ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Decisión propuesta -->
          <div class="col-12 col-lg-4">
            <label class="form-label">Decisión propuesta</label>
            <select
              name="decision_propuesta"
              id="decision_propuesta"
              class="form-select <?= !empty($errors['decision_propuesta'] ?? null) ? 'is-invalid' : '' ?>"
              required
            >
              <option value="">Elige una opción..</option>
              <?php foreach ($decisiones as $opt): ?>
                <option value="<?= esc($opt) ?>"
                  <?= old('decision_propuesta') === $opt ? 'selected' : '' ?>>
                  <?= esc($opt) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['decision_propuesta'] ?? null)): ?>
              <div class="invalid-feedback d-block">
                <?= esc($errors['decision_propuesta']) ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Uploader -->
        <div class="section-header mt-4">
          <i class="bi bi-clipboard2-pulse"></i>
          <h6>Citación y acta escaneadas (subir)</h6>
        </div>

        <div class="row g-3">
          <div class="col-12 col-lg-8">
            <label class="form-label">Seleccionar archivos</label>
            <input
              id="adjuntos"
              name="adjuntos[]"
              type="file"
              class="form-control"
              multiple
              accept=".pdf,.jpg,.jpeg,.png,.heic,.tif,.tiff"
            >
            <div class="form-text">Puedes adjuntar varios archivos (PDF o imágenes).</div>
          </div>

          <div class="col-12 col-lg-4">
            <div class="dropzone text-center p-4 border rounded">
              <i class="bi bi-cloud-arrow-up fs-2 d-block mb-2"></i>
              Arrastra y suelta tus archivos aquí
            </div>
          </div>

          <div class="col-12">
            <div class="filelist border rounded p-2">
              <div class="small text-muted mb-2">Archivos seleccionados para subir:</div>
              <ul id="fileList" class="list-unstyled mb-0"></ul>
            </div>
          </div>
        </div>

                <!-- Adjuntos existentes -->
        <div class="section-header mt-4">
          <i class="bi bi-clipboard2-pulse"></i>
          <h6>Adjuntos del FURD (existentes)</h6>
        </div>

        <div id="adjuntosPrev" class="row g-3">
          <div class="col-12">
            <div class="alert alert-secondary small mb-0">
              Aún no se ha cargado un consecutivo. Usa la lupa para ver los adjuntos existentes.
            </div>
          </div>
        </div>

        <!-- Acciones -->
        <div class="sticky-actions bg-body border-top mt-4 pt-3 pb-3">
          <div class="d-flex gap-2 justify-content-end">
            <a href="<?= base_url('/'); ?>" class="btn btn-outline-secondary">
              <i class="bi bi-x-circle me-1"></i>Cancelar
            </a>
            <button class="btn btn-success">
              <i class="bi bi-send-check me-1"></i>Guardar soporte
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
    const $consec    = document.getElementById('consecutivo');
    const $buscar    = document.getElementById('btnBuscar');
    const $adjPrev   = document.getElementById('adjuntosPrev');
    const $adjuntos  = document.getElementById('adjuntos');
    const $fileList  = document.getElementById('fileList');

    const baseFind = '<?= base_url('soporte/find'); ?>';

    // Wrapper para usar el toast global bonito
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
      if (mime.includes('pdf')) return 'bi-filetype-pdf text-danger';
      if (mime.includes('image')) return 'bi-image text-success';
      if (mime.includes('excel') || mime.includes('spreadsheet')) return 'bi-filetype-xls text-success';
      if (mime.includes('word') || mime.includes('msword')) return 'bi-filetype-doc text-primary';
      return 'bi-file-earmark text-muted';
    };

    // Render de adjuntos existentes agrupados por fase
    function renderAdjuntosExistentes(prevAdj) {
      $adjPrev.innerHTML = '';

      const fases = [
        { key: 'registro', label: 'Fase 1 · Registro'   },
        { key: 'citacion', label: 'Fase 2 · Citación'   },
        { key: 'descargos', label: 'Fase 3 · Descargos' },
      ];

      let tieneAlgo = false;

      fases.forEach(f => {
        const arr = (prevAdj && prevAdj[f.key]) ? prevAdj[f.key] : [];
        if (!arr.length) return;

        tieneAlgo = true;

        const header = document.createElement('div');
        header.className = 'col-12';
        header.innerHTML = `<h6 class="text-muted mb-2">${f.label}</h6>`;
        $adjPrev.appendChild(header);

        arr.forEach(it => {
          const col = document.createElement('div');
          col.className = 'col-12 col-md-6 col-xl-4';

          const nombre = it.nombre_original || it.nombre || it.filename || `Adjunto #${it.id}`;
          const mime   = it.mime || '';
          const url    = it.url || it.display_url || `<?= base_url('adjuntos'); ?>/${it.id}/open`;

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
          $adjPrev.appendChild(col);
        });
      });

      if (!tieneAlgo) {
        $adjPrev.innerHTML = `
          <div class="col-12">
            <div class="alert alert-secondary small mb-0">
              No hay adjuntos anteriores para este FURD.
            </div>
          </div>`;
      }
    }

    // Buscar por consecutivo (lupa)
    async function buscar() {
      const id = ($consec.value || '').trim();
      if (!id) {
        $consec.focus();
        notify('Ingresa un consecutivo para buscar.', 'info');
        return;
      }

      if (!/^PD-\d{6}$/i.test(id)) {
        notify('Formato inválido. Usa algo como PD-000123.', 'error');
        return;
      }

      $consec.classList.add('loading');

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
        $consec.classList.remove('loading');
      }
    }

    $buscar?.addEventListener('click', buscar);
    $consec?.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        e.preventDefault();
        buscar();
      }
    });

    // Listado de archivos seleccionados
    function refreshFileList() {
      $fileList.innerHTML = '';
      const files = [...($adjuntos?.files || [])];

      if (!files.length) {
        $fileList.innerHTML = '<li class="text-muted small">No hay archivos seleccionados.</li>';
        return;
      }

      files.forEach(f => {
        const li = document.createElement('li');
        const icon = f.type.includes('pdf') ? 'bi-filetype-pdf text-danger'
                                            : 'bi-image text-success';
        const size = (f.size / 1024 / 1024).toFixed(2) + ' MB';

        li.className = 'd-flex align-items-center gap-2 py-1 border-bottom';
        li.innerHTML = `
          <i class="bi ${icon}"></i>
          <span class="flex-grow-1 text-truncate" title="${f.name}">${f.name}</span>
          <span class="badge text-bg-light">${size}</span>`;
        $fileList.appendChild(li);
      });
    }

    $adjuntos?.addEventListener('change', refreshFileList);
    refreshFileList();

    // Dropzone simple
    const dz = document.querySelector('.dropzone');
    if (dz) {
      dz.addEventListener('dragover', e => {
        e.preventDefault();
        dz.classList.add('dragging');
      });
      dz.addEventListener('dragleave', () => dz.classList.remove('dragging'));
      dz.addEventListener('drop', e => {
        e.preventDefault();
        dz.classList.remove('dragging');
        if (!e.dataTransfer?.files?.length) return;

        const dt = new DataTransfer();
        [...($adjuntos.files || []), ...e.dataTransfer.files].forEach(f => dt.items.add(f));
        $adjuntos.files = dt.files;
        refreshFileList();
      });
    }
  })();

  (() => {
    // Formulario de soporte
    const form = document.querySelector('form[action$="soporte"]');
    if (!form) return;

    // Botón de enviar (usa el que ya tienes)
    const submitBtn = form.querySelector('button[type="submit"], .btn-success');

    form.addEventListener('submit', () => {
      if (!submitBtn) return;

      // Deshabilitar para evitar doble envío
      submitBtn.disabled = true;
      submitBtn.classList.add('loading');   // por si ya tienes estilos para .loading

      // Guardar el contenido original por si algún día quieres restaurarlo
      if (!submitBtn.dataset.originalHtml) {
        submitBtn.dataset.originalHtml = submitBtn.innerHTML;
      }

      // Spinner + texto
      submitBtn.innerHTML = `
        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        Guardando...
      `;
      // NO hacemos preventDefault: el form se envía normal
    });
  })();
</script>
<?= $this->endSection(); ?>
