<!-- app/Views/soporte/index.php -->
<?= $this->extend('layouts/main'); ?>

<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/soporte.css'); ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<?php
  $errors      = session('errors') ?? [];
  // puedes pasar $responsable desde el controller si lo deseas
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
      <div class="card-header bg-success-subtle">
        <h5 class="mb-0"> 🗂️ Citación y acta de cargos y descargos diligenciadas</h5>
      </div>

      <form class="card-body"
            method="post"
            action="<?= site_url('soporte'); ?>"
            enctype="multipart/form-data"
            novalidate>
        <?= csrf_field(); ?>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Corrige los siguientes campos:</div>
            <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
          </div>
        <?php endif; ?>

        <!-- Bloque principal -->
        <div class="row g-3">
          <!-- Consecutivo + búsqueda -->
          <div class="col-12 col-lg-4">
            <label class="form-label">Consecutivo</label>
            <div class="input-group">
              <input id="consecutivo" name="consecutivo" type="text"
                     class="form-control" placeholder="ID FURD o consecutivo">
              <button type="button" id="btnBuscar" class="btn btn-outline-primary" title="Buscar FURD">
                <i class="bi bi-search"></i>
              </button>
            </div>
            <div class="form-text">Escribe el ID y pulsa la lupa para cargar la información y adjuntos existentes.</div>
          </div>

          <!-- Responsable -->
          <div class="col-12 col-lg-4">
            <label class="form-label">Responsable</label>
            <input name="responsable" id="responsable" type="text"
                   class="form-control" value="<?= esc($responsable) ?>"
                   placeholder="Nombre de quien diligencia">
          </div>

          <!-- Decisión propuesta -->
          <div class="col-12 col-lg-4">
            <label class="form-label">Decisión propuesta</label>
            <select name="decision" id="decision" class="form-select" required>
              <option value="">Elige una opción..</option>
              <?php foreach ($decisiones as $opt): ?>
                <option value="<?= esc($opt) ?>"><?= esc($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Adjuntos existentes -->
        <div class="pt-4 pb-2 mt-4 mb-3 border-top border-bottom">
          <h6 class="text-muted mb-0"><i class="bi bi-paperclip me-1"></i>Adjuntos del FURD (existentes)</h6>
        </div>

        <div id="adjuntosPrev" class="row g-3">
          <div class="col-12">
            <div class="alert alert-secondary small mb-0">
              Aún no se ha cargado un consecutivo. Usa la lupa para ver los adjuntos existentes.
            </div>
          </div>
        </div>

        <!-- Uploader -->
        <div class="pt-4 pb-2 mt-4 mb-3 border-top border-bottom">
          <h6 class="text-muted mb-0"><i class="bi bi-upload me-1"></i>Citación y acta escaneadas (subir)</h6>
        </div>

        <div class="row g-3">
          <div class="col-12 col-lg-8">
            <label class="form-label">Seleccionar archivos</label>
            <input id="soportes" name="soportes[]" type="file" class="form-control"
                   multiple accept=".pdf,.jpg,.jpeg,.png,.heic,.tif,.tiff">
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

        <!-- Acciones -->
        <div class="sticky-actions bg-body border-top mt-4 pt-3 pb-3">
          <div class="d-flex gap-2 justify-content-end">
            <a href="<?= site_url('/') ?>" class="btn btn-outline-secondary">
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

<!-- contenedor para toasts -->
<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index:2000;"></div>

<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<script>
(() => {
  const $consec   = document.getElementById('consecutivo');
  const $buscar   = document.getElementById('btnBuscar');
  const $adjPrev  = document.getElementById('adjuntosPrev');
  const $soportes = document.getElementById('soportes');
  const $fileList = document.getElementById('fileList');

  // Toast helper
  function toast(msg, type='info', ms=3800){
    const wrap = document.getElementById('toastContainer');
    const el = document.createElement('div');
    el.className = `toast text-bg-${type} show`;
    el.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${msg}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>`;
    wrap.appendChild(el);
    setTimeout(()=>el.remove(), ms);
  }

  // Render de adjuntos existentes
  function renderAdjuntosExistentes(items){
    $adjPrev.innerHTML = '';
    if (!items || !items.length){
      $adjPrev.innerHTML = `
        <div class="col-12">
          <div class="alert alert-secondary small mb-0">
            No hay adjuntos anteriores para este FURD.
          </div>
        </div>`;
      return;
    }
    items.forEach(it=>{
      const col = document.createElement('div');
      col.className = 'col-12 col-md-6 col-xl-4';
      const nombre = it.nombre || it.filename || 'archivo';
      const url    = it.url    || it.display_url || it.path || '#';
      const mime   = it.mime   || 'application/octet-stream';
      col.innerHTML = `
        <div class="adj-card border rounded p-3 h-100 d-flex flex-column gap-2">
          <div class="d-flex align-items-center gap-2">
            <i class="bi ${mime.includes('pdf') ? 'bi-filetype-pdf text-danger' : 'bi-image text-success'} fs-4"></i>
            <div class="small fw-semibold text-truncate" title="${nombre}">${nombre}</div>
          </div>
          <div class="mt-auto">
            <a class="btn btn-sm btn-outline-primary" href="${url}" target="_blank" rel="noopener">
              <i class="bi bi-box-arrow-up-right me-1"></i>Ver / Descargar
            </a>
          </div>
        </div>`;
      $adjPrev.appendChild(col);
    });
  }

  // Buscar por consecutivo (lupa)
  async function buscar(){
    const id = ($consec.value || '').trim();
    if (!id) { $consec.focus(); return; }
    $consec.classList.add('loading');
    try {
      const res = await fetch('<?= site_url('furd/lookup') ?>/' + encodeURIComponent(id));
      if (!res.ok) throw new Error('No se encontró el registro.');
      const data = await res.json();
      renderAdjuntosExistentes(data.adjuntos || []);
      toast('Registro cargado correctamente.', 'success');
    } catch (e) {
      renderAdjuntosExistentes([]);
      toast(e.message || 'No se encontró el registro.', 'danger');
    } finally {
      $consec.classList.remove('loading');
    }
  }
  $buscar?.addEventListener('click', buscar);
  $consec?.addEventListener('keydown', e => (e.key === 'Enter') && (e.preventDefault(), buscar()));

  // Listado bonito de archivos seleccionados
  function refreshFileList(){
    if (!$fileList) return;
    $fileList.innerHTML = '';
    const files = [...($soportes?.files || [])];
    if (!files.length){
      $fileList.innerHTML = '<li class="text-muted small">No hay archivos seleccionados.</li>';
      return;
    }
    files.forEach(f=>{
      const li = document.createElement('li');
      li.className = 'd-flex align-items-center gap-2 py-1 border-bottom';
      const icon = f.type.includes('pdf') ? 'bi-filetype-pdf text-danger' : 'bi-image text-success';
      const size = (f.size/1024/1024).toFixed(2)+' MB';
      li.innerHTML = `
        <i class="bi ${icon}"></i>
        <span class="flex-grow-1 text-truncate" title="${f.name}">${f.name}</span>
        <span class="badge text-bg-light">${size}</span>`;
      $fileList.appendChild(li);
    });
  }
  $soportes?.addEventListener('change', refreshFileList);
  refreshFileList();

  // Dropzone simple
  const dz = document.querySelector('.dropzone');
  if (dz){
    dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('dragging'); });
    dz.addEventListener('dragleave', ()=> dz.classList.remove('dragging'));
    dz.addEventListener('drop', e => {
      e.preventDefault();
      dz.classList.remove('dragging');
      if (!e.dataTransfer?.files?.length) return;
      // merge a lo ya seleccionado
      const dt = new DataTransfer();
      [...($soportes.files || []), ...e.dataTransfer.files].forEach(f => dt.items.add(f));
      $soportes.files = dt.files;
      refreshFileList();
    });
  }
})();
</script>
<?= $this->endSection(); ?>
