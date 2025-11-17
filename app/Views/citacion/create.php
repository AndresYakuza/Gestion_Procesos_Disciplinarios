<?= $this->extend('layouts/main'); ?>

<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/citacion.css') ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<?php $errors = session('errors') ?? []; ?>

<div class="page-citacion">
  <div class="card animate-in">
    <div class="card-header main-header">
      <span>📅 Registro de citación</span>
    </div>

    <form class="card-body" method="post" action="<?= base_url('citacion'); ?>" novalidate>
      <?= csrf_field(); ?>

      <div class="section-header">
        <i class="bi bi-clipboard2-pulse"></i>
        <h6>Datos de la citación</h6>
      </div>

      <div class="row g-3 align-items-end">
        <div class="col-12 col-md-4">

          <label class="form-label d-flex align-items-center gap-1" for="consecutivo">
            Consecutivo del proceso
            <i class="bi bi-info-circle text-muted small"
              data-bs-toggle="tooltip"
              data-bs-placement="right"
              title="Escribe el consecutivo del FURD (Ej: PD-000123).">
            </i>
          </label>

          <!-- Input + lupa, igual que en Descargos -->
          <div class="input-group">
            <input
              type="text"
              id="consecutivo"
              name="consecutivo"
              class="form-control <?= !empty($errors['consecutivo']) ? 'is-invalid' : '' ?>"
              placeholder="Ej: PD-000123"
              value="<?= old('consecutivo') ?>"
              required
              pattern="PD-[0-9]{6}"
              title="Formato esperado: PD-000123"
            >
            <button
              type="button"
              id="btnLoad"
              class="btn btn-outline-success"
              title="Buscar registro">
              <i class="bi bi-search"></i>
            </button>
          </div>

          <div class="form-text">
            Ingresa el consecutivo completo del proceso, por ejemplo: <strong>PD-000123</strong>.
          </div>

          <?php if (!empty($errors['consecutivo'] ?? null)): ?>
            <div class="invalid-feedback d-block">
              <?= esc($errors['consecutivo']) ?>
            </div>
          <?php endif; ?>

        </div>

        <div class="col-6 col-md-4">
          <label class="form-label">Fecha</label>
          <input id="fecha" type="text" class="form-control" name="fecha_evento" value="<?= old('fecha_evento') ?>" placeholder="Selecciona una fecha..." required>
        </div>

        <div class="col-6 col-md-4">
          <label class="form-label">Hora</label>
          <input id="hora" type="time" class="form-control" name="hora" placeholder="Selecciona hora..." required>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label">Medio de la citación</label>
          <select name="medio" class="form-select" required>
            <option value="" selected disabled>Elige una opción…</option>
            <option value="virtual">Virtual</option>
            <option value="presencial">Presencial</option>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Hecho o motivo de la intervención</label>
          <textarea name="motivo" rows="3" class="form-control" placeholder="Describe el evento en forma detallada…" required></textarea>
        </div>
      </div>

      <!-- Adjuntos del FURD -->
      <div class="section-header mt-4">
        <i class="bi bi-paperclip"></i>
        <h6>Adjuntos del registro (solo lectura)</h6>
      </div>

      <div id="adjuntosWrap" class="adjuntos-grid">
        <!-- tarjetas de adjuntos se inyectan por JS -->
      </div>

      <div class="sticky-actions bg-body border-top mt-4 pt-3 pb-3">
        <div class="d-flex gap-2 justify-content-end">
          <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i> Cancelar
          </a>
          <button class="btn btn-success">
            <i class="bi bi-send-check me-1"></i> Generar
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection(); ?>


<?= $this->section('scripts'); ?>
<script>
  (() => {
    const baseFind    = '<?= base_url('citacion/find'); ?>';
    const baseAdjunto = '<?= base_url('furd/adjuntos'); ?>';

    const consecutivo = document.getElementById('consecutivo');
    const btnLoad     = document.getElementById('btnLoad');
    const wrap        = document.getElementById('adjuntosWrap');

    // wrapper para usar el toast moderno global
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
      if (mime.includes('pdf')) return 'filetype-pdf';
      if (mime.includes('image')) return 'image';
      if (mime.includes('excel') || mime.includes('spreadsheet')) return 'filetype-xls';
      if (mime.includes('word') || mime.includes('msword')) return 'filetype-doc';
      if (mime.includes('zip')) return 'file-zip';
      return 'file-earmark';
    };

    const human = (bytes = 0) => {
      const u = ['B', 'KB', 'MB', 'GB'];
      let i = 0;
      while (bytes >= 1024 && i < u.length - 1) {
        bytes /= 1024;
        i++;
      }
      return `${bytes.toFixed(i ? 1 : 0)} ${u[i]}`;
    };

    const card = (a) => {
      const url  = `${baseAdjunto}/${a.id}`;
      const ico  = iconByMime(a.mime);
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
      if (btnLoad) btnLoad.disabled = loading;
      if (consecutivo) consecutivo.classList.toggle('loading', loading);
    };

    const clearAdjuntos = () => {
      wrap.innerHTML = `
        <div class="text-center text-muted py-4 small">Sin adjuntos para mostrar.</div>`;
    };

    const render = (arr = []) => {
      if (!arr.length) return clearAdjuntos();
      wrap.innerHTML = arr.map(card).join('');
    };

    const loadAdjuntos = async () => {
      const id = (consecutivo.value || '').trim();
      if (!id) {
        notify('Ingresa un consecutivo para buscar.', 'info');
        return;
      }

      if (!/^PD-\d{6}$/i.test(id)) {
        notify('Formato inválido. Usa algo como PD-000123.', 'error');
        return;
      }

      try {
        setLoading(true);
        clearAdjuntos();

        const res = await fetch(`${baseFind}?consecutivo=${encodeURIComponent(id)}`);
        if (!res.ok) throw new Error('No se pudo consultar el FURD');

        const data = await res.json();

        if (!data.ok) {
          render([]);
          notify('No se encontró ningún registro con ese consecutivo.', 'error');
          return;
        }

        render(data.adjuntos || []);
        notify('Registro encontrado y cargado.', 'success');
      } catch (e) {
        console.error(e);
        render([]);
        notify('No se encontraron adjuntos para ese consecutivo.', 'error');
      } finally {
        setLoading(false);
      }
    };

    btnLoad?.addEventListener('click', loadAdjuntos);

    consecutivo?.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        e.preventDefault();
        loadAdjuntos();
      }
    });
  })();
</script>

<?= $this->endSection(); ?>
