<?= $this->extend('layouts/main'); ?>

<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/cargos-descargos.css'); ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<?php $errors = session('errors') ?? []; ?>

<div class="page-descargos">
  <div class="row g-4">
    <div class="col-12">
      <div class="card animate-in">

        <div class="card-header main-header">
          <span>📜 Acta de Cargos y Descargos</span>
        </div>

        <form id="cydForm" class="card-body" method="post" action="<?= base_url('descargos'); ?>" novalidate>
          <?= csrf_field(); ?>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <div class="fw-semibold mb-1">Corrige los siguientes campos:</div>
              <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                  <li><?= esc($e) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <!-- BANDA / TÍTULO DE SECCIÓN -->
          <div class="section-header">
            <i class="bi bi-clipboard2-pulse"></i>
            <h6>Datos del acta de cargos y descargos</h6>
          </div>

          <div class="row g-3 align-items-end">
            <!-- Consecutivo -->
            <div class="col-12 col-md-6">
              <label class="form-label d-flex align-items-center gap-1" for="consecutivo">
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
            </div>

            <!-- Fecha -->
            <div class="col-6 col-lg-3">
              <label class="form-label" for="fecha">Fecha</label>
              <input
                id="fecha"
                type="text"
                class="form-control <?= !empty($errors['fecha_evento'] ?? null) ? 'is-invalid' : '' ?>"
                name="fecha_evento"
                placeholder="Selecciona una fecha..."
                value="<?= old('fecha_evento') ?>"
                required>
              <?php if (!empty($errors['fecha_evento'] ?? null)): ?>
                <div class="invalid-feedback d-block">
                  <?= esc($errors['fecha_evento']) ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- Hora -->
            <div class="col-6 col-lg-3">
              <label class="form-label" for="hora">Hora</label>
              <input
                id="hora"
                type="time"
                class="form-control"
                name="hora"
                placeholder="Selecciona hora..."
                value="<?= old('hora') ?>"
                required>
            </div>

            <i>
              Ingresa el consecutivo completo del proceso, por ejemplo: <strong>PD-000123</strong>.
            </i>
            <br>

            <!-- Medio -->
            <div class="col-12 col-lg-2">

              <label class="form-label" for="medio">Medio del Descargo</label>
              <select id="medio" name="medio" class="form-select" required>
                <option value="" <?= old('medio') ? '' : 'selected' ?>>Elige una opción...</option>
                <option value="presencial" <?= old('medio') === 'presencial' ? 'selected' : '' ?>>Presencial</option>
                <option value="virtual" <?= old('medio') === 'virtual'    ? 'selected' : '' ?>>Virtual</option>
              </select>
              <?php if (!empty($errors['medio'] ?? null)): ?>
                <div class="invalid-feedback d-block">
                  <?= esc($errors['medio']) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- (Adjuntos eliminados para este módulo, ya no se muestran) -->

          <!-- BOTONES -->
          <div class="sticky-actions bg-body border-top mt-4 pt-3 pb-3">
            <div class="d-flex gap-2 justify-content-end">
              <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle me-1"></i>Cancelar
              </a>
              <button id="btnGenerar" type="submit" class="btn btn-success">
                <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                <span class="btn-text">Generar acta</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Loader global -->
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
        Generando acta de cargos y descargos, por favor espera…
      </p>
    </div>
  </div>
</div>

<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

<script>
  (() => {
    const PREFIX = 'PD-';
    const baseFind = '<?= base_url('descargos/find'); ?>';

    const form = document.getElementById('cydForm');
    const consecutivo = document.getElementById('consecutivo');
    const btnBuscar = document.getElementById('btnBuscarConsecutivo');
    const btnGenerar = document.getElementById('btnGenerar');
    const globalLoader = document.getElementById('globalLoader');

    const showGlobalLoader = () => globalLoader?.classList.remove('d-none');
    const hideGlobalLoader = () => globalLoader?.classList.add('d-none');

    function notify(msg, type = 'info', ms = 3800) {
      if (typeof showToast === 'function') {
        showToast(msg, type, ms);
      } else {
        console[type === 'error' ? 'error' : 'log'](msg);
        alert(msg);
      }
    }

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
        const res = await fetch(`${baseFind}?consecutivo=${encodeURIComponent(normalized)}`);
        const data = res.ok ? await res.json() : null;

        if (!data || !data.ok) {
          notify('El consecutivo no existe.', 'error');
          return;
        }

        notify('Consecutivo válido. Ya puedes generar el acta.', 'success');
      } catch (err) {
        console.error(err);
        notify('Ocurrió un error al validar el consecutivo.', 'error');
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

    if (form && btnGenerar) {
      let sending = false;
      const spin = btnGenerar.querySelector('.spinner-border');
      const txt = btnGenerar.querySelector('.btn-text');

      const resetButton = () => {
        sending = false;
        btnGenerar.disabled = false;
        if (spin) spin.classList.add('d-none');
        if (txt) txt.textContent = 'Generar acta';
      };

      form.addEventListener('submit', (e) => {
        e.preventDefault();

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

        let docTab = null;

        try {
          docTab = window.open('', '_blank');

          if (docTab) {
            docTab.document.write(`
      <!doctype html>
      <html lang="es">
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Generando acta de cargos y descargos</title>
        <style>
          :root{
            --brand:#198754;
            --brand-soft:#e9f7ef;
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
          .shell{ width:100%; max-width:620px; }
          .card{
            width:100%;
            background:#fff;
            border:1px solid var(--border);
            border-radius:28px;
            box-shadow:var(--shadow);
            overflow:hidden;
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
          }
          .dot{
            width:8px;
            height:8px;
            border-radius:50%;
            background:var(--brand);
            box-shadow:0 0 0 6px rgba(25,135,84,.10);
            animation:pulse 1.8s ease-in-out infinite;
          }
          .content{
            padding:34px 30px 26px;
            text-align:center;
          }
          .spinner{
            width:72px;
            height:72px;
            margin:0 auto 22px;
            border-radius:50%;
            border:6px solid #dff0e5;
            border-top-color:var(--brand);
            animation:spin .95s linear infinite;
          }
          @keyframes spin { to { transform:rotate(360deg); } }
          @keyframes pulse {
            0%,100% { transform:scale(1); opacity:1; }
            50% { transform:scale(1.15); opacity:.85; }
          }
          h1{
            margin:0 0 10px;
            font-size:30px;
            line-height:1.12;
          }
          p{
            margin:0 auto;
            max-width:470px;
            color:var(--muted);
            font-size:15px;
            line-height:1.65;
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
          }
          .step b{ display:block; margin:0 0 3px; font-size:14px; }
          .step span{ display:block; color:var(--muted); font-size:13px; line-height:1.5; }
          .footer{
            padding:0 30px 28px;
            text-align:center;
            color:#7b8794;
            font-size:12px;
          }
        </style>
      </head>
      <body>
        <div class="shell">
          <div class="card">
            <div class="top">
              <div class="badge">
                <span class="dot"></span>
                <span>CONTACTAMOS · ACTA EN PROCESO</span>
              </div>
            </div>
            <div class="content">
              <div class="spinner"></div>
              <h1>Generando acta de cargos y descargos</h1>
              <p>
                Estamos preparando el documento formal y organizándolo en Google Drive
                dentro del proceso disciplinario.
              </p>

              <div class="steps">
                <div class="step">
                  <div class="step-num">1</div>
                  <div>
                    <b>Tomando la plantilla</b>
                    <span>Se obtiene el formato base desde Google Drive.</span>
                  </div>
                </div>
                <div class="step">
                  <div class="step-num">2</div>
                  <div>
                    <b>Generando el acta</b>
                    <span>Se reemplazan los datos del trabajador, medio, fecha y hechos del proceso.</span>
                  </div>
                </div>
                <div class="step">
                  <div class="step-num">3</div>
                  <div>
                    <b>Guardando en Drive</b>
                    <span>El archivo final se sube y se organiza en la carpeta de Descargos.</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="footer">
              Esta pestaña se actualizará automáticamente cuando el acta esté lista.
            </div>
          </div>
        </div>
      </body>
      </html>
    `);
            docTab.document.close();
          }
        } catch (err) {
          docTab = null;
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
            } catch (_) {
              data = null;
            }
          }

          if (data) {
            if (data.ok) {
              if (data.openUrl) {
                if (docTab) {
                  docTab.location.href = data.openUrl;
                } else {
                  window.open(data.openUrl, '_blank');
                }
              } else if (docTab && !docTab.closed) {
                docTab.document.body.innerHTML = `
    <div style="font-family:Arial,Helvetica,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f4f7f6;padding:24px;">
      <div style="max-width:520px;background:#fff;border:1px solid #dfe7e3;border-radius:18px;padding:28px;text-align:center;box-shadow:0 18px 45px rgba(16,24,40,.10);">
        <h2 style="margin:0 0 10px;color:#1f2937;">Acta generada correctamente</h2>
        <p style="margin:0;color:#6b7280;line-height:1.6;">
          El proceso fue registrado, pero no se recibió la URL del documento.
        </p>
      </div>
    </div>
  `;
              }

              if (data.redirectTo) {
                window.location.href = data.redirectTo;
                return;
              }
            }

            if (data.ok === false && data.errors) {
              const allErrors = Array.isArray(data.errors) ?
                data.errors :
                Object.values(data.errors);

              const firstError = allErrors.length ?
                allErrors[0] :
                'Revisa los campos obligatorios.';

              if (docTab && !docTab.closed) {
                docTab.document.body.innerHTML = `
      <div style="font-family:Arial,Helvetica,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f4f7f6;padding:24px;">
        <div style="max-width:520px;background:#fff;border:1px solid #ead7d7;border-radius:18px;padding:28px;text-align:center;box-shadow:0 18px 45px rgba(16,24,40,.10);">
          <h2 style="margin:0 0 10px;color:#b42318;">No se pudo generar el acta</h2>
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

            if (docTab && !docTab.closed) {
              docTab.document.body.innerHTML = `
    <div style="font-family:Arial,Helvetica,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f4f7f6;padding:24px;">
      <div style="max-width:520px;background:#fff;border:1px solid #ead7d7;border-radius:18px;padding:28px;text-align:center;box-shadow:0 18px 45px rgba(16,24,40,.10);">
        <h2 style="margin:0 0 10px;color:#b42318;">Error inesperado</h2>
        <p style="margin:0;color:#6b7280;line-height:1.6;">
          Ocurrió un error inesperado al registrar el acta de cargos y descargos.
        </p>
      </div>
    </div>
  `;
            }

            notify('Error inesperado al registrar el acta de descargos.', 'error');
            resetButton();
            return;
          }

          if (xhr.status >= 200 && xhr.status < 400) {
            const finalURL = xhr.responseURL || form.action;
            window.location.href = finalURL;
          } else {
            if (docTab && !docTab.closed) {
              docTab.document.body.innerHTML = `
    <div style="font-family:Arial,Helvetica,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f4f7f6;padding:24px;">
      <div style="max-width:520px;background:#fff;border:1px solid #ead7d7;border-radius:18px;padding:28px;text-align:center;box-shadow:0 18px 45px rgba(16,24,40,.10);">
        <h2 style="margin:0 0 10px;color:#b42318;">No se pudo registrar el acta</h2>
        <p style="margin:0;color:#6b7280;line-height:1.6;">
          El servidor devolvió una respuesta no válida. Intenta nuevamente.
        </p>
      </div>
    </div>
  `;
            }

            notify('Ocurrió un error al registrar el acta de descargos.', 'error');
            resetButton();
          }
        };

        xhr.onerror = () => {
          hideGlobalLoader();

          if (docTab && !docTab.closed) {
            docTab.document.body.innerHTML = `
      <div style="font-family:Arial,Helvetica,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f4f7f6;padding:24px;">
        <div style="max-width:520px;background:#fff;border:1px solid #ead7d7;border-radius:18px;padding:28px;text-align:center;box-shadow:0 18px 45px rgba(16,24,40,.10);">
          <h2 style="margin:0 0 10px;color:#b42318;">No se pudo generar el acta</h2>
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
</script>

<?= $this->endSection(); ?>