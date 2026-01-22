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
              data-info-text="La fecha del descargo debe estar entre el <strong>5°</strong> y el <strong>7° día hábil</strong> contado desde mañana, contando solo de lunes a sábado y excluyendo domingos y festivos no laborables en Colombia. Solo esas fechas aparecerán disponibles en el calendario.">
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

        <div class="col-12 col-md-6">
          <label class="form-label">Plantilla sugerida</label>
          <div id="plantillaDescargoBox" class="plantilla-card plantilla-empty">
            <div class="plantilla-card-main">
              <div class="plantilla-card-icon">
                <i class="bi bi-file-earmark-text"></i>
              </div>
              <div class="plantilla-card-text" id="textoModeloDescargo">
                Selecciona un modelo de descargo para ver aquí la plantilla descargable.
              </div>
            </div>
            <div class="plantilla-card-action">
              <a
                id="linkModeloDescargo"
                href="#"
                class="btn btn-sm btn-outline-primary d-none"
                target="_blank"
                rel="noopener">
                <i class="bi bi-download me-1"></i> Descargar modelo
              </a>
            </div>
          </div>
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
        src="<?= base_url('assets/lottie/ChasquidoQik.json') ?>"
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
      const PLANTILLAS_DESCARGO = <?= json_encode($plantillasDescargo ?? [], JSON_UNESCAPED_UNICODE); ?>;


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

            const selectModelo = document.getElementById('selectModeloDescargo');
      const boxPlantilla = document.getElementById('plantillaDescargoBox');
      const textoPlantilla = document.getElementById('textoModeloDescargo');
      const linkPlantilla = document.getElementById('linkModeloDescargo');

      function updateModeloDescargoBox() {
        if (!selectModelo || !boxPlantilla || !textoPlantilla || !linkPlantilla) return;

        const value = (selectModelo.value || '').toLowerCase();
        const cfg = PLANTILLAS_DESCARGO[value];

        if (!value || !cfg) {
          textoPlantilla.textContent =
            'Selecciona un modelo de descargo para ver aquí la plantilla descargable.';
          linkPlantilla.classList.add('d-none');
          linkPlantilla.removeAttribute('href');
          boxPlantilla.classList.add('plantilla-empty');
          return;
        }

        textoPlantilla.textContent =
          cfg.label || 'Plantilla sugerida para este modelo de descargo.';
        linkPlantilla.href = cfg.url;
        linkPlantilla.classList.remove('d-none');
        boxPlantilla.classList.remove('plantilla-empty');
      }

      if (selectModelo) {
        selectModelo.addEventListener('change', updateModeloDescargoBox);
        // Para que se actualice si viene con old('medio')
        updateModeloDescargoBox();
      }

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
            notify('No se encontró ningún registro con ese consecutivo.', 'error');
            return;
          }

          // adjuntos ya vienen con "url" apuntando al visor en Drive
          renderAdjuntos(data.adjuntos || []);
          notify('Registro encontrado y cargado.', 'success');

          // rellenar el motivo con el "hecho" del FURD (solo si está vacío)
          if (data.furd && data.furd.hecho && motivoField && !motivoField.value.trim()) {
            motivoField.value = data.furd.hecho;
            updateMotivoCount?.();
          }
        } catch (e) {
          console.error(e);
          renderAdjuntos([]);
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
                // Seguimiento (lleva flash con el mensaje)
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

                notify(firstError, 'warning');
                resetButton();
                return;
              }

              notify('Error inesperado al registrar la citación.', 'error');
              resetButton();
              return;
            }

            if (xhr.status >= 200 && xhr.status < 400) {
              const finalURL = xhr.responseURL || form.action;
              window.location.href = finalURL;
            } else {
              notify('Ocurrió un error al registrar la citación.', 'error');
              resetButton();
            }
          };

          xhr.onerror = () => {
            hideGlobalLoader();
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

    // ==============================
    //  Modelo de descargo → plantilla
    // ==============================
    const PLANTILLAS_DESCARGO = <?= json_encode($plantillasDescargo ?? [], JSON_UNESCAPED_UNICODE); ?>;

    const selectModelo = document.getElementById('selectModeloDescargo');
    const boxModelo = document.getElementById('boxModeloDescargo');
    const txtModelo = document.getElementById('textoModeloDescargo');
    const linkModelo = document.getElementById('linkModeloDescargo');

    function updateModeloDescargoBox() {
      if (!selectModelo || !boxModelo || !txtModelo || !linkModelo) return;

      const val = (selectModelo.value || '').toLowerCase();
      const cfg = PLANTILLAS_DESCARGO[val] || null;

      if (!cfg || !cfg.url) {
        txtModelo.textContent =
          'Selecciona un modelo de descargo para ver aquí la plantilla descargable.';
        linkModelo.classList.add('d-none');
        linkModelo.href = '#';
        return;
      }

      txtModelo.textContent = cfg.label || 'Plantilla sugerida para este modelo de descargo.';
      linkModelo.href = cfg.url;
      linkModelo.classList.remove('d-none');
    }

    selectModelo?.addEventListener('change', updateModeloDescargoBox);

    // Estado inicial por si viene old('medio')
    updateModeloDescargoBox();
  </script>

  <?= $this->endSection(); ?>