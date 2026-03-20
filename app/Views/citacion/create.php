<?= $this->extend('layouts/main'); ?>

<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/citacion.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<?php
$errors             = session('errors') ?? [];
$oldConsecutivo     = old('consecutivo') ?? (session('consecutivo') ?? '');
$fechasHabilitadas  = $fechasHabilitadas ?? [];
$plantillasDescargo = $plantillasDescargo ?? [];
?>

<div class="page-citacion">
  <div class="card animate-in">
    <div class="card-header main-header">
      <span>📅 Registro de citación</span>
    </div>

    <form id="citacionForm" class="card-body" method="post" action="<?= base_url('citacion'); ?>" novalidate>
      <?= csrf_field(); ?>

      <div class="section-header">
        <i class="bi bi-clipboard2-pulse"></i>
        <h6>Datos de la citación</h6>
      </div>

      <div class="row g-3 align-items-end">
        <div class="col-12 col-md-4">

          <label class="form-label d-flex align-items-center gap-2" for="consecutivo">
            Consecutivo del proceso
            <button
              type="button"
              class="btn-info-help"
              data-info-title="Consecutivo del proceso"
              data-info-text="Escribe el consecutivo completo del FURD. Ejemplo válido: <strong>PD-000123</strong>.">
              <i class="bi bi-info-lg"></i>
            </button>
          </label>

          <div class="input-group">
            <input
              type="text"
              id="consecutivo"
              name="consecutivo"
              class="form-control <?= !empty($errors['consecutivo']) ? 'is-invalid' : '' ?>"
              placeholder="Ej: PD-000123"
              value="<?= esc($oldConsecutivo) ?>"
              required
              pattern="PD-[0-9]{6}"
              title="Formato esperado: PD-000123">
            <button
              type="button"
              id="btnLoad"
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
        <br><br>
        <div class="col-6 col-md-4">
          <label class="form-label d-flex align-items-center gap-2">
            Fecha del descargo
            <button
              type="button"
              class="btn-info-help"
              data-info-title="Fecha del descargo"
              data-info-text="La fecha del descargo debe estar entre el <strong>5°</strong> y el <strong>7° día hábil</strong> contado desde mañana, teniendo en cuenta solo de lunes a sábado y excluyendo domingos y festivos no laborables en Colombia. Solo esas fechas aparecerán disponibles en el calendario.">
              <i class="bi bi-info-lg"></i>
            </button>
          </label>
          <input
            id="fecha"
            type="text"
            class="form-control <?= !empty($errors['fecha_evento']) ? 'is-invalid' : '' ?>"
            name="fecha_evento"
            value="<?= old('fecha_evento') ?>"
            placeholder="Selecciona una fecha..."
            autocomplete="off"
            required
            data-no-global-flatpickr="1">

          <?php if (!empty($errors['fecha_evento'] ?? null)): ?>
            <div class="invalid-feedback d-block">
              <?= esc($errors['fecha_evento']) ?>
            </div>
          <?php endif; ?>
        </div>


        <div class="col-6 col-md-4">
          <label class="form-label">Hora</label>
          <input id="hora" type="time" class="form-control" name="hora"
            placeholder="Selecciona hora..." required>
        </div>

        <div class="col-12 col-md-6">
          <i>
            Ingresa el consecutivo completo del proceso, por ejemplo: <strong>PD-000123</strong>.
          </i>
          <br>
          <label class="form-label">Modelo del descargo</label>
          <select id="selectModeloDescargo" name="medio" class="form-select" required>
            <option value="" disabled <?= old('medio') ? '' : 'selected' ?>>Elige una opción…</option>
            <option value="virtual" <?= old('medio') === 'virtual'    ? 'selected' : '' ?>>Virtual</option>
            <option value="presencial" <?= old('medio') === 'presencial' ? 'selected' : '' ?>>Presencial</option>
            <option value="escrito" <?= old('medio') === 'escrito' ? 'selected' : '' ?>>Escrito</option>
          </select>
        </div>

        <div class="form-text small text-muted mt-1">
          Según el medio elegido, el sistema generará automáticamente el documento formal de citación
          (formato RH-FO67) con los datos del proceso y lo enviará al correo del trabajador.
        </div>

        <div class="col-12">
          <label class="form-label" for="motivo">Hecho o motivo de la intervención</label>
          <textarea
            id="motivo"
            name="motivo"
            rows="3"
            class="form-control"
            placeholder="Describe el evento en forma detallada…"
            maxlength="7000"
            required><?= old('motivo') ?></textarea>

          <div class="d-flex justify-content-between small text-muted mt-1">
            <span>Máximo 7000 caracteres.</span>
            <span id="motivoCount">0/7000</span>
          </div>
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

      <!-- Historial de citaciones (solo lectura) -->
      <div class="section-header mt-4">
        <i class="bi bi-clock-history"></i>
        <h6>Historial de citaciones</h6>
      </div>

      <div id="citacionesHistory" class="citaciones-history">
        <p class="text-muted small mb-0">
          Aún no hay citaciones registradas para este proceso.
        </p>
      </div>

      <!-- Datos adicionales cuando es una nueva citación -->
      <div id="recitacionPanel" class="mt-4 d-none">
        <div class="alert alert-warning small mb-3">
          <strong>Nota:</strong> este proceso ya tiene al menos una citación previa.
          Estás registrando una nueva citación para el mismo trabajador.
          Describe brevemente el motivo (por ejemplo, inasistencia, novedad médica,
          falla de conexión, etc.).
        </div>

        <div class="mb-3">
          <label class="form-label" for="motivo_recitacion">
            Motivo de la nueva citación
          </label>
          <textarea
            id="motivo_recitacion"
            name="motivo_recitacion"
            class="form-control"
            rows="2"
            maxlength="1000"
            placeholder="Ej.: El trabajador no asistió a la citación anterior por incapacidad médica sin notificación previa…"><?= old('motivo_recitacion') ?></textarea>
        </div>
      </div>


      <div class="sticky-actions bg-body border-top mt-4 pt-3 pb-3">
        <div class="d-flex gap-2 justify-content-end">
          <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle me-1"></i> Cancelar
          </a>
          <button id="btnGenerar" type="submit" class="btn btn-success">
            <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
            <span class="btn-text">Generar citación</span>
          </button>
        </div>
      </div>
    </form>
  </div>

  <!-- Loader global, misma estructura que FURD -->
  <div id="globalLoader" class="loader-overlay d-none">
    <div class="loader-content">
      <lottie-player
        class="loader-lottie"
        src="<?= base_url('assets/lottie/sandy-loading.json') ?>"
        background="transparent"
        speed="1"
        style="width: 220px; height: 220px;"
        loop
        autoplay>
      </lottie-player>
      <p class="loader-text mb-0 text-muted">
        Generando citación, por favor espera…
      </p>
    </div>
  </div>

  <?= $this->endSection(); ?>


  <?= $this->section('scripts'); ?>
  <!-- <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script> -->
  <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

  <script>
    (() => {
      const baseFind = '<?= base_url('citacion/find'); ?>';
      // const PLANTILLAS_DESCARGO = <?= json_encode($plantillasDescargo ?? [], JSON_UNESCAPED_UNICODE); ?>;


      const enabledDates = <?= json_encode($fechasHabilitadas ?? []) ?>;
      const fechaInput = document.getElementById('fecha');

      const PREFIX = 'PD-';
      const consecutivo = document.getElementById('consecutivo');
      const btnLoad = document.getElementById('btnLoad');
      const wrap = document.getElementById('adjuntosWrap');
      const motivoField = document.querySelector('textarea[name="motivo"]');
      const form = document.getElementById('citacionForm');
      const btnGenerar = document.getElementById('btnGenerar');
      const globalLoader = document.getElementById('globalLoader');

      const showGlobalLoader = () => globalLoader?.classList.remove('d-none');
      const hideGlobalLoader = () => globalLoader?.classList.add('d-none');

      // const selectModelo = document.getElementById('selectModeloDescargo');
      // const boxPlantilla = document.getElementById('plantillaDescargoBox');
      // const textoPlantilla = document.getElementById('textoModeloDescargo');
      // const linkPlantilla = document.getElementById('linkModeloDescargo');

      const citHistory = document.getElementById('citacionesHistory');
      const recitacionPanel = document.getElementById('recitacionPanel');

      // function updateModeloDescargoBox() {
      //   if (!selectModelo || !boxPlantilla || !textoPlantilla || !linkPlantilla) return;

      //   const value = (selectModelo.value || '').toLowerCase();
      //   const cfg = PLANTILLAS_DESCARGO[value];

      //   if (!value || !cfg) {
      //     textoPlantilla.textContent =
      //       'Selecciona un modelo de descargo para ver aquí la plantilla descargable.';
      //     linkPlantilla.classList.add('d-none');
      //     linkPlantilla.removeAttribute('href');
      //     boxPlantilla.classList.add('plantilla-empty');
      //     return;
      //   }

      //   textoPlantilla.textContent =
      //     cfg.label || 'Plantilla sugerida para este modelo de descargo.';
      //   linkPlantilla.href = cfg.url;
      //   linkPlantilla.classList.remove('d-none');
      //   boxPlantilla.classList.remove('plantilla-empty');
      // }

      // if (selectModelo) {
      //   selectModelo.addEventListener('change', updateModeloDescargoBox);
      //   // Para que se actualice si viene con old('medio')
      //   updateModeloDescargoBox();
      // }

      // 🧮 Contador de caracteres para el motivo de citación
      const motivoCount = document.getElementById('motivoCount');
      const MAX_MOTIVO = 7000;

      const updateMotivoCount = () => {
        if (!motivoField || !motivoCount) return;
        const len = (motivoField.value || '').length;
        motivoCount.textContent = `${len}/${MAX_MOTIVO}`;

        // Colorear cuando se acerca al límite
        motivoCount.classList.remove('text-warning', 'text-danger');
        if (len > MAX_MOTIVO * 0.9) {
          motivoCount.classList.add('text-danger');
        } else if (len > MAX_MOTIVO * 0.7) {
          motivoCount.classList.add('text-warning');
        }
      };

      if (motivoField && motivoCount) {
        motivoField.addEventListener('input', updateMotivoCount);
        // inicial (por si viene con old('motivo'))
        updateMotivoCount();
      }

      function notify(msg, type = 'info', ms = 3800) {
        if (typeof showToast === 'function') {
          showToast(msg, type, ms);
        } else {
          console[type === 'error' ? 'error' : 'log'](msg);
          alert(msg);
        }
      }

      // ---------- Consecutivo: siempre con prefijo PD- ----------

      function onlyDigits(str) {
        return (str || '').replace(/\D/g, '');
      }

      function normalizeConsecutivoForUI(value) {
        const digits = onlyDigits(String(value));
        if (!digits) return PREFIX;
        return PREFIX + digits;
      }

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
        // el usuario solo escribe números, nosotros mantenemos el PD-
        const digits = onlyDigits(consecutivo.value);
        consecutivo.value = PREFIX + digits;
      });

      // ---------- Adjuntos: helpers UI ----------

      const setLoadingInput = (loading) => {
        if (!consecutivo) return;
        consecutivo.classList.toggle('loading', loading);
        if (btnLoad) btnLoad.disabled = loading;
      };

      const clearAdjuntos = () => {
        if (!wrap) return;
        wrap.innerHTML = '<div class="text-center text-muted py-4 small">Sin adjuntos para mostrar.</div>';
      };

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
        const url = a.url;
        const ico = iconByMime(a.mime || '');
        const name = a.nombre || a.nombre_original || `Adjunto #${a.id}`;
        const size = a.tamano ? human(Number(a.tamano)) : '';

        return `
      <div class="adj-card">
        <div class="adj-icon">
          <i class="bi bi-${ico}"></i>
        </div>
        <div class="adj-meta">
          <div class="adj-name" title="${name}">${name}</div>
          <div class="adj-sub">${(a.mime || 'archivo')}${size ? ' · ' + size : ''}</div>
        </div>
        <div class="adj-actions">
          <a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="${url}">
            <i class="bi bi-eye"></i> Ver
          </a>
        </div>
      </div>
    `;
      };

      const renderAdjuntos = (arr = []) => {
        if (!wrap) return;
        if (!arr.length) {
          clearAdjuntos();
          return;
        }
        wrap.innerHTML = arr.map(card).join('');
      };

      const renderCitaciones = (rows = []) => {
        if (!citHistory) return;

        // Estado vacío
        if (!rows.length) {
          citHistory.innerHTML = `
            <div class="cit-history-empty">
              <div class="cit-history-empty-icon">
                <i class="bi bi-calendar2-week"></i>
              </div>
              <div class="cit-history-empty-text">
                <p class="mb-1 fw-semibold">Sin citaciones registradas aún</p>
                <p class="mb-0 small text-muted">
                  Cuando generes la primera citación, aparecerá aquí el historial de todas las citaciones del trabajador.
                </p>
              </div>
            </div>
          `;

          if (recitacionPanel) {
            recitacionPanel.classList.add('d-none');
          }
          return;
        }

        const lastIndex = rows.length - 1;

        const items = rows.map((c, idx) => {
          const n = c.numero ?? 1;
          const fecha = c.fecha_evento ?? '';
          const hora = c.hora ?? '';
          const medioRaw = (c.medio || '').toLowerCase();
          const motivoR = c.motivo_recitacion || '';

          const isLatest = idx === lastIndex;

          let medioLabel = 'N/D';
          let medioIcon = 'bi-dot';

          switch (medioRaw) {
            case 'virtual':
              medioLabel = 'Virtual';
              medioIcon = 'bi-camera-video';
              break;
            case 'presencial':
              medioLabel = 'Presencial';
              medioIcon = 'bi-building';
              break;
            case 'escrito':
              medioLabel = 'Descargo escrito';
              medioIcon = 'bi-file-earmark-text';
              break;
            default:
              medioLabel = medioRaw ? medioRaw.charAt(0).toUpperCase() + medioRaw.slice(1) : 'N/D';
          }

          return `
          <div class="cit-item ${isLatest ? 'cit-item-latest' : ''}">
            <div class="cit-item-header">
              <div class="cit-item-title">
                <span class="cit-chip">Citación #${n}</span>
                ${isLatest ? '<span class="cit-chip-status">Citación vigente</span>' : ''}
              </div>
              <div class="cit-item-datetime">
                <i class="bi bi-calendar-event me-1"></i>
                <span>${fecha || 'Fecha N/D'}${hora ? ' · ' + hora : ''}</span>
              </div>
            </div>

            <div class="cit-item-body small">
              <div class="cit-item-tags">
                <span class="cit-badge">
                  <i class="bi ${medioIcon} me-1"></i>${medioLabel}
                </span>

                ${motivoR
                  ? `<span class="cit-badge cit-badge-warning">
                      <i class="bi bi-arrow-clockwise me-1"></i>Motivo recitación:
                      <span class="cit-motivo-text">${motivoR}</span>
                    </span>`
                  : ''
                }
              </div>
            </div>
          </div>
        `;
        });

        citHistory.innerHTML = items.join('');

        // Si ya existe al menos una citación, mostramos el panel de re–citación
        if (recitacionPanel) {
          recitacionPanel.classList.remove('d-none');
        }
      };


      // ---------- Cargar adjuntos + hecho desde el FURD ----------

      const loadAdjuntos = async () => {
        if (!consecutivo) return;

        const normalized = normalizeConsecutivoSixDigits(consecutivo.value);
        if (!normalized) {
          notify('Debes escribir un consecutivo válido (ej: PD-000123).', 'error');
          consecutivo.focus();
          return;
        }

        // dejamos el consecutivo formateado en el input
        consecutivo.value = normalized;

        try {
          setLoadingInput(true);
          clearAdjuntos();

          const res = await fetch(`${baseFind}?consecutivo=${encodeURIComponent(normalized)}`);
          if (!res.ok) throw new Error('No se pudo consultar el FURD');

          const data = await res.json();

          if (!data.ok) {
            renderAdjuntos([]);
            renderCitaciones([]);
            notify('No se encontró ningún registro con ese consecutivo.', 'error');
            return;
          }

          // adjuntos ya vienen con "url" apuntando al visor en Drive
          renderAdjuntos(data.adjuntos || []);
          renderCitaciones(data.citaciones || []);
          notify('Registro encontrado y cargado.', 'success');

          // rellenar el motivo con el "hecho" del FURD (solo si está vacío)
          if (data.furd && data.furd.hecho && motivoField && !motivoField.value.trim()) {
            motivoField.value = data.furd.hecho;
            updateMotivoCount?.();
          }
        } catch (e) {
          console.error(e);
          renderAdjuntos([]);
          renderCitaciones([]);
          notify('No se encontraron adjuntos para ese consecutivo.', 'error');
        } finally {
          setLoadingInput(false);
        }
      };

      btnLoad?.addEventListener('click', loadAdjuntos);

      consecutivo?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          loadAdjuntos();
        }
      });

      // ---------- Datepicker de fecha_evento limitado a días hábiles (5° a 8°) ----------
      if (fechaInput && typeof flatpickr !== 'undefined') {
        flatpickr(fechaInput, {
          dateFormat: 'Y-m-d',
          disableMobile: true,
          enable: enabledDates,
          minDate: enabledDates.length ? enabledDates[0] : null,
          maxDate: enabledDates.length ? enabledDates[enabledDates.length - 1] : null,

          // 👇 agregamos esta parte
          onReady(selectedDates, dateStr, instance) {
            instance.calendarContainer.classList.add('calendar-descargo');
          }
        });
      }

      // ---------- Envío del fo rmulario con loader global ----------

      if (form && btnGenerar) {
        let sending = false;
        const spin = btnGenerar.querySelector('.spinner-border');
        const txt = btnGenerar.querySelector('.btn-text');

        const resetButton = () => {
          sending = false;
          btnGenerar.disabled = false;
          if (spin) spin.classList.add('d-none');
          if (txt) txt.textContent = 'Generar citación';
        };

        form.addEventListener('submit', (e) => {
          e.preventDefault();

          // normalizamos consecutivo a PD-000000 antes de enviar
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

          let docWindow = null;

          try {
            docWindow = window.open('', '_blank');

            if (docWindow) {

              const medio = (document.getElementById('selectModeloDescargo')?.value || '').toLowerCase();
              let medioIcon = '📄';

              if (medio === 'virtual') medioIcon = '💻';
              else if (medio === 'presencial') medioIcon = '🏢';
              else if (medio === 'escrito') medioIcon = '📝';
              docWindow.document.write(`
  <!doctype html>
  <html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generando citación</title>
    <style>
      :root{
        --brand:#198754;
        --brand-soft:#e9f7ef;
        --brand-soft-2:#dff3e7;
        --text:#1f2937;
        --muted:#6b7280;
        --border:#dfe7e3;
        --bg:#f4f7f6;
        --card:#ffffff;
        --shadow:0 24px 70px rgba(16,24,40,.12);
      }

      *{ box-sizing:border-box; }

      body{
        margin:0;
        min-height:100vh;
        font-family:Arial, Helvetica, sans-serif;
        color:var(--text);
        background:
          radial-gradient(circle at top left, #eef8f2 0%, #f4f7f6 38%, #edf3f0 100%);
        display:flex;
        align-items:center;
        justify-content:center;
        padding:24px;
      }

      .shell{
        width:100%;
        max-width:620px;
      }

      .card{
        position:relative;
        overflow:hidden;
        width:100%;
        background:var(--card);
        border:1px solid var(--border);
        border-radius:28px;
        box-shadow:var(--shadow);
      }

      .glow{
        position:absolute;
        inset:auto auto 0 0;
        width:220px;
        height:220px;
        background:radial-gradient(circle, rgba(25,135,84,.14) 0%, rgba(25,135,84,0) 70%);
        pointer-events:none;
      }

      .top{
        padding:22px 24px;
        background:linear-gradient(135deg, #f7fcf9 0%, #eef8f2 100%);
        border-bottom:1px solid var(--border);
      }

      .badge{
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:8px 14px;
        border-radius:999px;
        background:var(--brand-soft);
        border:1px solid #cfe8d8;
        color:var(--brand);
        font-size:13px;
        font-weight:700;
        letter-spacing:.2px;
      }

      .badge-dot{
        width:8px;
        height:8px;
        border-radius:999px;
        background:var(--brand);
        box-shadow:0 0 0 6px rgba(25,135,84,.10);
        animation:pulse 1.8s ease-in-out infinite;
      }

      .content{
        padding:34px 30px 26px;
        text-align:center;
      }

      .spinner-wrap{
        position:relative;
        width:92px;
        height:92px;
        margin:0 auto 22px;
      }

      .spinner-ring{
        position:absolute;
        inset:0;
        border-radius:50%;
        border:6px solid #dff0e5;
      }

      .spinner{
        position:absolute;
        inset:0;
        border-radius:50%;
        border:6px solid transparent;
        border-top-color:var(--brand);
        border-right-color:#48a868;
        animation:spin .95s linear infinite;
      }

      .spinner-center{
        position:absolute;
        inset:18px;
        border-radius:50%;
        background:linear-gradient(180deg, #ffffff 0%, #f4fbf7 100%);
        border:1px solid #e1efe6;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:28px;
      }

      @keyframes spin {
        to { transform:rotate(360deg); }
      }

      @keyframes pulse {
        0%,100% { transform:scale(1); opacity:1; }
        50% { transform:scale(1.15); opacity:.85; }
      }

      h1{
        margin:0 0 10px;
        font-size:30px;
        line-height:1.12;
        letter-spacing:-.4px;
      }

      .lead{
        max-width:460px;
        margin:0 auto;
        color:var(--muted);
        font-size:15px;
        line-height:1.65;
      }

      .status{
        margin:18px auto 0;
        display:inline-flex;
        align-items:center;
        gap:8px;
        padding:10px 14px;
        border-radius:999px;
        background:#f8fbf9;
        border:1px solid #e5eeea;
        color:#456051;
        font-size:13px;
        font-weight:600;
      }

      .status .mini-dot{
        width:8px;
        height:8px;
        border-radius:50%;
        background:var(--brand);
        animation:pulse 1.8s ease-in-out infinite;
      }

      .steps{
        display:grid;
        gap:12px;
        margin:26px 0 0;
        text-align:left;
      }

      .step{
        display:flex;
        gap:12px;
        align-items:flex-start;
        background:#fafcfb;
        border:1px solid #e9f1ec;
        border-radius:18px;
        padding:14px 15px;
      }

      .step-num{
        width:28px;
        height:28px;
        min-width:28px;
        border-radius:50%;
        background:linear-gradient(180deg, #1f9b61 0%, #198754 100%);
        color:#fff;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:13px;
        font-weight:700;
        box-shadow:0 8px 18px rgba(25,135,84,.18);
      }

      .step b{
        display:block;
        font-size:14px;
        margin:0 0 3px;
      }

      .step span{
        display:block;
        color:var(--muted);
        font-size:13px;
        line-height:1.5;
      }

      .footer{
        padding:0 30px 28px;
        text-align:center;
        color:#7b8794;
        font-size:12px;
        line-height:1.6;
      }

      .progress-line{
        height:8px;
        width:100%;
        border-radius:999px;
        overflow:hidden;
        background:#ebf3ee;
        border:1px solid #e0ebe4;
        margin:20px 0 0;
      }

      .progress-bar{
        height:100%;
        width:42%;
        border-radius:999px;
        background:linear-gradient(90deg, #198754 0%, #47b26f 100%);
        animation:flow 1.8s ease-in-out infinite;
      }

      @keyframes flow {
        0%   { width:22%; }
        50%  { width:68%; }
        100% { width:38%; }
      }
    </style>
  </head>
  <body>
    <div class="shell">
      <div class="card">
        <div class="glow"></div>

        <div class="top">
          <div class="badge">
            <span class="badge-dot"></span>
            <span>CONTACTAMOS · DOCUMENTO EN PROCESO</span>
          </div>
        </div>

        <div class="content">
          <div class="spinner-wrap">
            <div class="spinner-ring"></div>
            <div class="spinner"></div>
          <div class="spinner-center">${medioIcon}</div>         
        </div>

          <h1>Generando citación</h1>
          <p class="lead">
            Estamos preparando el documento formal de citación y organizándolo en Google Drive
            para que quede disponible dentro del proceso disciplinario.
          </p>

          <div class="status">
            <span class="mini-dot"></span>
            <span>Subiendo y organizando archivo en Drive...</span>
          </div>

          <div class="progress-line">
            <div class="progress-bar"></div>
          </div>

          <div class="steps">
            <div class="step">
              <div class="step-num">1</div>
              <div>
                <b>Tomando la plantilla</b>
                <span>Se identifica el modelo seleccionado y se prepara para reemplazar la información del proceso.</span>
              </div>
            </div>

            <div class="step">
              <div class="step-num">2</div>
              <div>
                <b>Generando el documento</b>
                <span>Se insertan los datos del trabajador, la fecha, el medio y el detalle de la citación.</span>
              </div>
            </div>

            <div class="step">
              <div class="step-num">3</div>
              <div>
                <b>Guardando en Drive</b>
                <span>El archivo final se carga y se organiza dentro de la carpeta del proceso para su consulta.</span>
              </div>
            </div>
          </div>
        </div>

        <div class="footer">
          Esta pestaña se actualizará automáticamente cuando la citación esté lista.
        </div>
      </div>
    </div>
  </body>
  </html>
`);
              docWindow.document.close();
            }
          } catch (err) {
            docWindow = null;
          }

          btnGenerar.disabled = true;
          if (spin) spin.classList.remove('d-none');
          if (txt) txt.textContent = 'Generando…';

          showGlobalLoader();

          const formData = new FormData(form);
          const xhr = new XMLHttpRequest();
          xhr.open(form.method || 'POST', form.action);
          xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

          xhr.onload = () => {
            hideGlobalLoader();

            const contentType = xhr.getResponseHeader('Content-Type') || '';
            let data = null;

            if (contentType.includes('application/json')) {
              try {
                data = JSON.parse(xhr.responseText || '{}');
              } catch (e) {
                data = null;
              }
            }

            if (data) {
              if (data.ok && data.redirectTo) {
                if (docWindow && !docWindow.closed) {
                  if (data.driveDocUrl) {
                    docWindow.location.href = data.driveDocUrl;
                  } else if (data.docUrl) {
                    docWindow.location.href = data.docUrl;
                  } else {
                    docWindow.document.body.innerHTML = `
        <div style="font-family:Arial,Helvetica,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f4f7f6;padding:24px;">
          <div style="max-width:520px;background:#fff;border:1px solid #dfe7e3;border-radius:18px;padding:28px;text-align:center;box-shadow:0 18px 45px rgba(16,24,40,.10);">
            <h2 style="margin:0 0 10px;color:#1f2937;">Citación generada correctamente</h2>
            <p style="margin:0;color:#6b7280;line-height:1.6;">
              El proceso fue registrado, pero no se recibió la URL del documento.
            </p>
          </div>
        </div>
      `;
                  }
                }

                window.location.href = data.redirectTo;
                return;
              }

              if (data.ok === false && data.errors) {
                const allErrors = Array.isArray(data.errors) ?
                  data.errors :
                  Object.values(data.errors);

                const firstError = allErrors.length ?
                  allErrors[0] :
                  'Revisa los campos obligatorios.';

                if (docWindow && !docWindow.closed) {
                  docWindow.document.body.innerHTML = `
      <div style="font-family:Arial,Helvetica,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f4f7f6;padding:24px;">
        <div style="max-width:520px;background:#fff;border:1px solid #ead7d7;border-radius:18px;padding:28px;text-align:center;box-shadow:0 18px 45px rgba(16,24,40,.10);">
          <h2 style="margin:0 0 10px;color:#b42318;">No se pudo generar la citación</h2>
          <p style="margin:0;color:#6b7280;line-height:1.6;">
            ${firstError}
          </p>
        </div>
      </div>
    `;
                }

                notify(firstError, 'warning');
                resetButton();
                return;
              }
              if (docWindow && !docWindow.closed) {
                docWindow.document.body.innerHTML = `
    <div style="font-family:Arial,Helvetica,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f4f7f6;padding:24px;">
      <div style="max-width:520px;background:#fff;border:1px solid #ead7d7;border-radius:18px;padding:28px;text-align:center;box-shadow:0 18px 45px rgba(16,24,40,.10);">
        <h2 style="margin:0 0 10px;color:#b42318;">Error inesperado</h2>
        <p style="margin:0;color:#6b7280;line-height:1.6;">
          Ocurrió un error inesperado al registrar la citación.
        </p>
      </div>
    </div>
  `;
              }

              notify('Error inesperado al registrar la citación.', 'error');
              resetButton();
              return;
            }

            if (xhr.status >= 200 && xhr.status < 400) {
              const finalURL = xhr.responseURL || form.action;
              window.location.href = finalURL;
            } else {
              if (docWindow && !docWindow.closed) {
                docWindow.document.body.innerHTML = `
    <div style="font-family:Arial,Helvetica,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f4f7f6;padding:24px;">
      <div style="max-width:520px;background:#fff;border:1px solid #ead7d7;border-radius:18px;padding:28px;text-align:center;box-shadow:0 18px 45px rgba(16,24,40,.10);">
        <h2 style="margin:0 0 10px;color:#b42318;">No se pudo registrar la citación</h2>
        <p style="margin:0;color:#6b7280;line-height:1.6;">
          El servidor devolvió una respuesta no válida. Intenta nuevamente.
        </p>
      </div>
    </div>
  `;
              }

              notify('Ocurrió un error al registrar la citación.', 'error');
              resetButton();
            }
          };

          xhr.onerror = () => {
            hideGlobalLoader();

            if (docWindow && !docWindow.closed) {
              docWindow.document.body.innerHTML = `
      <div style="font-family:Arial,Helvetica,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f4f7f6;padding:24px;">
        <div style="max-width:520px;background:#fff;border:1px solid #ead7d7;border-radius:18px;padding:28px;text-align:center;box-shadow:0 18px 45px rgba(16,24,40,.10);">
          <h2 style="margin:0 0 10px;color:#b42318;">No se pudo generar la citación</h2>
          <p style="margin:0;color:#6b7280;line-height:1.6;">
            Ocurrió un problema de conexión con el servidor. Puedes cerrar esta pestaña e intentar nuevamente.
          </p>
        </div>
      </div>
    `;
            }

            notify('No se pudo conectar con el servidor. Revisa tu conexión.', 'error');
            resetButton();
          };

          xhr.send(formData);
        });
      }
    })();

    const hechoField = document.getElementById('motivo');
    if (hechoField) {
      const MAX_WORD = 120;
      let lastValid = hechoField.value;

      const checkHechoWords = () => {
        const words = (hechoField.value || '').split(/\s+/);
        const tooLong = words.some(w => w.length > MAX_WORD);

        if (tooLong) {
          // volvemos al valor anterior
          hechoField.value = lastValid;
          hechoField.selectionStart = hechoField.selectionEnd = hechoField.value.length;

          if (typeof showToast === 'function') {
            showToast(
              `No se permiten palabras de más de ${MAX_WORD} caracteres sin espacios.`,
              'warning'
            );
          } else {
            alert(`No se permiten palabras de más de ${MAX_WORD} caracteres sin espacios.`);
          }
        } else {
          lastValid = hechoField.value;
        }
      };

      hechoField.addEventListener('input', checkHechoWords);
    }

    // Botones de ayuda (info)
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.btn-info-help');
      if (!btn) return;

      const title = btn.dataset.infoTitle || 'Información';
      const html = btn.dataset.infoText || '';

      Swal.fire({
        icon: 'info',
        title: title,
        html: html,
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#0d6efd',
        customClass: {
          popup: 'swal2-popup-help'
        }
      });
    });
  </script>

  <?= $this->endSection(); ?>