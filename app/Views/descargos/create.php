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
        src="<?= base_url('assets/lottie/ufo0-lottie.json') ?>"
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

    // ---------- Helpers para consecutivo PD-000000 ----------
    const onlyDigits = (str) => (str || '').replace(/\D/g, '');

    function normalizeConsecutivoSixDigits(value) {
      const digits = onlyDigits(String(value));
      if (!digits) return '';
      return PREFIX + digits.padStart(6, '0');
    }

    // Al hacer foco, autocompletar PD-
    consecutivo?.addEventListener('focus', () => {
      if (!consecutivo.value.trim()) {
        consecutivo.value = PREFIX;
        setTimeout(() => {
          const len = consecutivo.value.length;
          consecutivo.setSelectionRange(len, len);
        }, 0);
      }
    });

    // Mientras escribe, mantenemos el prefijo y solo números
    consecutivo?.addEventListener('input', () => {
      const digits = onlyDigits(consecutivo.value);
      consecutivo.value = PREFIX + digits;
    });

    // ---------- Buscar consecutivo (click o Enter) ----------
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

    // Importante: prevenir el submit cuando se presiona Enter en el campo
    consecutivo?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        buscar();
      }
    });

    // ---------- Envío con loader global ----------
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

          // Caso JSON (éxito vía AJAX)
          if (data) {
            if (data.ok && data.redirectTo) {
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

            notify('Error inesperado al registrar el acta de descargos.', 'error');
            resetButton();
            return;
          }

          // Fallback: respuesta HTML (redirect normal)
          if (xhr.status >= 200 && xhr.status < 400) {
            const finalURL = xhr.responseURL || form.action;
            window.location.href = finalURL;
          } else {
            notify('Ocurrió un error al registrar el acta de descargos.', 'error');
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
</script>

<?= $this->endSection(); ?>