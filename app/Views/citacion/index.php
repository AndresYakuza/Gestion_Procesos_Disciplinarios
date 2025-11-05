<?= $this->extend('layouts/main'); ?>
<?= $this->section('content'); ?>

<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/citacion.css') ?>">
<?= $this->endSection(); ?>

<div class="page-citacion">


  <div class="card animate-in">
    <div class="card-header bg-success-subtle">
      <h5 class="mb-0">📅 Datos de la citación</h5>
    </div>

    <form class="card-body" method="post" action="<?= site_url('citaciones'); ?>" novalidate>
      <?= csrf_field(); ?>

      <div class="row g-3 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label d-flex align-items-center gap-1">
            Consecutivo
            <i class="bi bi-info-circle text-muted small"
                data-bs-toggle="tooltip"
                data-bs-placement="right"
                title="Escribe el ID del FURD. Traerá los adjuntos automáticamente.">
            </i>
            </label>
            <div class="input-group">
            <input id="consecutivo" name="consecutivo" type="number"
                    class="form-control" placeholder="ID del FURD" required>
            <button id="btnLoad" class="btn btn-outline-success" type="button">
                <i class="bi bi-search"></i>
            </button>
            </div>
        </div>


        <div class="col-6 col-md-4">
          <label class="form-label">Fecha</label>
          <input name="fecha" type="date" class="form-control" required>
        </div>

        <div class="col-6 col-md-4">
          <label class="form-label">Hora</label>
          <input name="hora" type="time" class="form-control" required>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label">Medio de la citación</label>
          <select name="medio" class="form-select" required>
            <option value="" selected disabled>Elige una opción…</option>
            <option value="telefono">Llamada telefónica</option>
            <option value="whatsapp">WhatsApp</option>
            <option value="correo">Correo electrónico</option>
            <option value="presencial">Presencial</option>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Hecho o motivo de la intervención</label>
          <textarea name="hecho" rows="3" class="form-control" placeholder="Describe el evento en forma detallada…" required></textarea>
        </div>
      </div>

      <!-- Adjuntos del FURD -->
      <div class="pt-4 pb-2 mt-4 mb-3 border-top border-bottom">
        <h6 class="text-muted mb-0">
          <i class="bi bi-paperclip me-1"></i>Adjuntos del registro (solo lectura)
        </h6>
      </div>

      <div id="adjuntosWrap" class="adjuntos-grid">
        <!-- tarjetas de adjuntos se inyectan por JS -->
      </div>

      <div class="d-flex justify-content-end mt-4">
        <button class="btn btn-success">
          <i class="bi bi-send-check me-1"></i>Generar
        </button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection(); ?>


<?= $this->section('scripts'); ?>
<script>
(() => {
  const baseFurdShow = '<?= site_url('api/furd'); ?>';         // GET /api/furd/{id}
  const baseAdjunto  = '<?= site_url('furd/adjuntos'); ?>';    // GET /furd/adjuntos/{id}

  const consecutivo = document.getElementById('consecutivo');
  const btnLoad     = document.getElementById('btnLoad');
  const wrap        = document.getElementById('adjuntosWrap');

  const iconByMime = (mime='') => {
    mime = mime.toLowerCase();
    if (mime.includes('pdf'))     return 'filetype-pdf';
    if (mime.includes('image'))   return 'image';
    if (mime.includes('excel') || mime.includes('spreadsheet')) return 'filetype-xls';
    if (mime.includes('word')  || mime.includes('msword'))      return 'filetype-doc';
    if (mime.includes('zip'))     return 'file-zip';
    return 'file-earmark';
  };

  const human = (bytes=0) => {
    const u = ['B','KB','MB','GB']; let i = 0;
    while (bytes >= 1024 && i < u.length-1) { bytes/=1024; i++; }
    return `${bytes.toFixed( (i?1:0) )} ${u[i]}`;
  };

  const card = (a) => {
    const url = `${baseAdjunto}/${a.id}`;
    const ico = iconByMime(a.mime);
    const name = a.nombre_original || `Adjunto #${a.id}`;
    const size = a.tamano_bytes ? human(Number(a.tamano_bytes)) : '';

    return `
      <div class="adj-card">
        <div class="adj-icon">
          <i class="bi bi-${ico}"></i>
        </div>
        <div class="adj-meta">
          <div class="adj-name" title="${name}">${name}</div>
          <div class="adj-sub">${a.mime || 'archivo'} · ${size}</div>
        </div>
        <div class="adj-actions">
          <a class="btn btn-sm btn-outline-secondary" target="_blank" href="${url}">
            <i class="bi bi-eye"></i> Ver
          </a>
        </div>
      </div>`;
  };

  const setLoading = (loading) => {
    btnLoad.disabled = loading;
    consecutivo.classList.toggle('loading', loading);
  };

  const clearAdjuntos = () => wrap.innerHTML = `
    <div class="text-center text-muted py-4 small">Sin adjuntos para mostrar.</div>`;

  const render = (arr=[]) => {
    if (!arr.length) return clearAdjuntos();
    wrap.innerHTML = arr.map(card).join('');
  };

  const loadAdjuntos = async () => {
    const id = (consecutivo.value || '').trim();
    if (!id) return;
    try{
      setLoading(true);
      clearAdjuntos();
      const res = await fetch(`${baseFurdShow}/${encodeURIComponent(id)}`);
      if (!res.ok) throw new Error('No se pudo consultar el FURD');
      const data = await res.json();
      render(data?.adjuntos || []);
    }catch(e){
      wrap.innerHTML = `<div class="alert alert-warning small mb-0">No se encontraron adjuntos para ese consecutivo.</div>`;
    }finally{
      setLoading(false);
    }
  };

  btnLoad?.addEventListener('click', loadAdjuntos);
  consecutivo?.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); loadAdjuntos(); }
  });
})();
</script>
<?= $this->endSection(); ?>
