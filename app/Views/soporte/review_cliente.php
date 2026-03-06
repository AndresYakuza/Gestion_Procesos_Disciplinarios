<?php

/**
 * Vista: Revisión de decisión propuesta por el cliente
 *
 * @var array $furd
 * @var array $soporte
 * @var bool  $readonly (opcional)
 */

$estadoActual = $soporte['cliente_estado'] ?? 'pendiente';
$yaRespondio  = $estadoActual !== 'pendiente';
$decisionPropuesta = trim((string)($soporte['decision_propuesta'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Revisión de decisión propuesta</title>

  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <link rel="stylesheet" href="<?= base_url('assets/css/pages/soporte-review-cliente.css'); ?>">
</head>

<body class="review-body review-state-<?= esc($estadoActual) ?>">

  <header class="review-app-header">
    <div class="container review-app-header-inner">
      <div class="review-app-title">
        <span class="review-app-chip">
          <i class="bi bi-shield-check me-1"></i>
          Gestión de Procesos Disciplinarios
        </span>
      </div>

      <div class="review-app-process">
        <span class="review-pill">
          <span class="pill-label">Proceso</span>
          <span class="pill-value"><?= esc($furd['consecutivo'] ?? '') ?></span>
        </span>
      </div>
    </div>
  </header>

  <main class="review-main">
    <div class="container py-4 py-lg-5">
      <div class="row justify-content-center">
        <div class="col-12 col-xl-10 col-lg-11">

          <!-- HERO -->
          <section class="card review-card review-hero-card mb-4">
            <div class="card-body review-card-body">
              <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3">
                <div class="d-flex align-items-start gap-3">
                  <div class="review-hero-icon">
                    <i class="bi bi-journal-check"></i>
                  </div>

                  <div>
                    <div class="review-eyebrow">Portal de revisión</div>
                    <h1 class="review-page-title mb-2">Revisión de decisión propuesta</h1>
                    <p class="review-page-subtitle mb-0">
                      Revisa la decisión sugerida por la organización y registra tu respuesta frente al proceso disciplinario.
                    </p>
                  </div>
                </div>

                <div class="review-status-wrapper">
                  <?php if ($estadoActual === 'aprobado'): ?>
                    <span class="review-badge review-badge-success">
                      <i class="bi bi-check-circle-fill me-1"></i> Aprobado
                    </span>
                  <?php elseif ($estadoActual === 'rechazado'): ?>
                    <span class="review-badge review-badge-danger">
                      <i class="bi bi-x-circle-fill me-1"></i> Rechazado
                    </span>
                  <?php else: ?>
                    <span class="review-badge review-badge-warning">
                      <i class="bi bi-hourglass-split me-1"></i> Pendiente
                    </span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </section>

          <!-- KPIs -->
          <section class="card review-card mb-4">
            <div class="card-body review-card-body">
              <div class="row g-3 review-kpi-grid">
                <div class="col-12 col-md-4">
                  <div class="review-kpi-box">
                    <div class="review-kpi-label">Trabajador</div>
                    <div class="review-kpi-value"><?= esc($furd['nombre_completo'] ?? '') ?></div>
                  </div>
                </div>

                <div class="col-12 col-md-4">
                  <div class="review-kpi-box">
                    <div class="review-kpi-label">Empresa usuaria</div>
                    <div class="review-kpi-value"><?= esc($furd['empresa_usuaria'] ?? '') ?></div>
                  </div>
                </div>

                <div class="col-12 col-md-4">
                  <div class="review-kpi-box">
                    <div class="review-kpi-label">Estado actual</div>
                    <div class="review-kpi-value text-capitalize"><?= esc($estadoActual) ?></div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <div class="row g-4">
            <!-- COLUMNA IZQUIERDA -->
            <div class="col-12 col-lg-6">

              <section class="card review-card h-100">
                <div class="review-card-header">
                  <div class="review-card-title">Decisión propuesta por la organización</div>
                  <div class="review-meta-line">
                    Lee la propuesta completa antes de registrar tu respuesta.
                  </div>
                </div>

                <div class="card-body review-card-body">
                  <div class="review-detail-block mb-3">
                    <div class="review-detail-label">Decisión sugerida</div>
                    <div
                      class="review-detail-value review-highlight-value"
                      id="decisionSugeridaText"
                      data-decision="<?= esc($decisionPropuesta) ?>">
                      <?= esc($decisionPropuesta) ?>
                    </div>
                  </div>

                  <div class="review-detail-block">
                    <div class="review-detail-label">Justificación</div>
                    <div class="review-detail-text">
                      <?= nl2br(esc($soporte['justificacion'] ?? '')); ?>
                    </div>
                  </div>

                  <?php if (!empty($adjuntosSoporte)): ?>
                    <div class="review-detail-block mt-4">
                      <div class="review-detail-label mb-2">Adjuntos del soporte</div>

                      <div class="d-flex flex-column gap-2">
                        <?php foreach ($adjuntosSoporte as $adj): ?>
                          <div class="review-attachment-item">
                            <div class="review-attachment-name text-truncate">
                              <i class="bi bi-paperclip me-2 text-muted"></i>
                              <?= esc($adj['nombre']) ?>
                            </div>

                            <div class="d-flex gap-2 flex-wrap">
                              <a href="<?= esc($adj['url_open']) ?>"
                                target="_blank"
                                rel="noopener"
                                class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-box-arrow-up-right"></i> Ver
                              </a>

                              <a href="<?= esc($adj['url_download']) ?>"
                                class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-download"></i> Descargar
                              </a>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>
              </section>
            </div>

            <!-- COLUMNA DERECHA -->
            <div class="col-12 col-lg-6">

              <?php if ($yaRespondio): ?>
                <section class="card review-card h-100">
                  <div class="review-card-header">
                    <div class="review-card-title">Respuesta ya registrada</div>
                    <div class="review-meta-line">
                      Ya existe una respuesta asociada a este proceso.
                    </div>
                  </div>

                  <div class="card-body review-card-body">
                    <div class="alert review-alert mb-0">
                      <div class="d-flex align-items-start gap-2">
                        <span class="alert-icon">
                          <i class="bi bi-info-circle-fill"></i>
                        </span>
                        <div>
                          <div class="fw-semibold mb-1">Registro existente</div>
                          <div class="small">
                            Registraste una respuesta el
                            <?= !empty($soporte['cliente_respondido_at'])
                              ? esc(date('d/m/Y H:i', strtotime($soporte['cliente_respondido_at'])))
                              : 'día anterior' ?>.
                            Si necesitas cambiarla, comunícate con el área de Gestión de Procesos Disciplinarios.
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </section>
              <?php else: ?>

                <section class="card review-card h-100">
                  <div class="review-card-header">
                    <div class="review-card-title">Registrar respuesta</div>
                    <div class="review-meta-line">
                      Este paso registra tu opinión frente a la propuesta.
                    </div>
                  </div>

                  <div class="card-body review-card-body">

                    <div class="review-step-indicator mb-4">
                      <span class="step-pill active">Paso 4 de 5 · Revisión del cliente</span>
                      <span class="step-hint">Tu respuesta quedará guardada en la línea de tiempo del proceso.</span>
                    </div>

                    <form method="post" action="">
                      <?= csrf_field(); ?>

                      <div class="mb-4">
                        <label class="form-label">¿Qué deseas hacer con esta propuesta?</label>

                        <div class="review-radio-group">
                          <label class="review-radio-option">
                            <input
                              class="form-check-input"
                              type="radio"
                              name="cliente_estado"
                              id="estado_aprobar"
                              value="aprobado"
                              required>
                            <div class="option-content">
                              <div class="option-title">Estoy de acuerdo con la decisión propuesta</div>
                              <div class="option-help">Confirmas que estás de acuerdo con la decisión y su justificación.</div>
                            </div>
                          </label>

                          <label class="review-radio-option">
                            <input
                              class="form-check-input"
                              type="radio"
                              name="cliente_estado"
                              id="estado_rechazar"
                              value="rechazado">
                            <div class="option-content">
                              <div class="option-title">No estoy de acuerdo / deseo ajustes</div>
                              <div class="option-help">Indica cómo consideras que debería ajustarse la decisión o sus fundamentos.</div>
                            </div>
                          </label>
                        </div>
                      </div>

                      <div class="mb-4 d-none" id="wrapFechaSuspension">
                        <label class="form-label">
                          Período de suspensión disciplinaria
                        </label>

                        <div class="review-inline-note mb-3">
                          Define la fecha de inicio y de finalización de la suspensión.
                        </div>

                        <div class="row g-3">
                          <div class="col-12 col-md-6">
                            <input
                              type="date"
                              id="fechaSuspensionInicio"
                              name="cliente_fecha_inicio_suspension"
                              class="form-control">
                            <div class="form-text">
                              Fecha de inicio.
                            </div>
                          </div>

                          <div class="col-12 col-md-6">
                            <input
                              type="date"
                              id="fechaSuspensionFin"
                              name="cliente_fecha_fin_suspension"
                              class="form-control">
                            <div class="form-text">
                              Fecha de finalización.
                            </div>
                          </div>
                        </div>

                        <div class="form-text mt-2">
                          La fecha de inicio debe ser al menos tres días después de la fecha actual, sin contar domingos.
                        </div>

                        <div id="fechaSuspensionError" class="review-inline-error d-none mt-3" role="alert" aria-live="polite">
                          <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-triangle-fill review-inline-error-icon"></i>
                            <div id="fechaSuspensionErrorText">
                              La suspensión no puede iniciar ni finalizar en domingo.
                            </div>
                          </div>
                        </div>

                      </div>

                      <div class="mb-3">
                        <label class="form-label">
                          Cambio de decisión o ajuste sugerido
                          <span class="text-muted small">(opcional)</span>
                        </label>
                        <input
                          type="text"
                          name="cliente_decision"
                          class="form-control"
                          placeholder="Ej: Suspensión de 3 días en lugar de 5">
                      </div>

                      <div class="mb-3">
                        <label class="form-label">
                          Justificación ajustada
                          <span class="text-muted small">(opcional)</span>
                        </label>
                        <textarea
                          name="cliente_justificacion"
                          rows="4"
                          class="form-control"
                          placeholder="Si propones un cambio, cuéntanos cómo consideras que debería quedar la justificación…"></textarea>
                      </div>

                      <div class="mb-4">
                        <label class="form-label">
                          Comentario adicional para el área de gestión
                          <span class="text-muted small">(opcional)</span>
                        </label>
                        <textarea
                          name="cliente_comentario"
                          rows="4"
                          class="form-control"
                          placeholder="Aquí puedes dejar cualquier mensaje adicional o contexto importante."></textarea>
                      </div>

                      <div class="review-form-footer d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <span class="text-muted small">
                          Al enviar tu respuesta, esta quedará registrada en la línea de tiempo del proceso.
                        </span>

                        <button type="submit" class="btn btn-success review-submit-btn">
                          <i class="bi bi-send-check me-1"></i>
                          Enviar respuesta
                        </button>
                      </div>
                    </form>
                  </div>
                </section>
              <?php endif; ?>

            </div>
          </div>

        </div>
      </div>
    </div>
  </main>

</body>

</html>

<script>
(function () {
    const decisionEl   = document.getElementById('decisionSugeridaText');
    const wrapFecha    = document.getElementById('wrapFechaSuspension');
    const fechaInicio  = document.getElementById('fechaSuspensionInicio');
    const fechaFin     = document.getElementById('fechaSuspensionFin');
    const radiosEstado = document.querySelectorAll('input[name="cliente_estado"]');

    const errorBox     = document.getElementById('fechaSuspensionError');
    const errorText    = document.getElementById('fechaSuspensionErrorText');

    if (!decisionEl || !wrapFecha || !fechaInicio || !fechaFin || !radiosEstado.length) {
        return;
    }

    const decisionSugerida = (decisionEl.dataset.decision || decisionEl.textContent || '')
        .toLowerCase()
        .trim();

    function isSuspension() {
        return decisionSugerida === 'suspensión disciplinaria'
            || decisionSugerida === 'suspension disciplinaria';
    }

    let minFechaIso = '';

    function mostrarError(msg) {
        if (!errorBox || !errorText) return;
        errorText.textContent = msg;
        errorBox.classList.remove('d-none');
    }

    function ocultarError() {
        if (!errorBox) return;
        errorBox.classList.add('d-none');
    }

    function calcularMinFechaSuspension() {
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        let diasValidos = 0;
        const d = new Date(hoy);

        while (diasValidos < 3) {
            d.setDate(d.getDate() + 1);
            if (d.getDay() === 0) continue;
            diasValidos++;
        }

        const y  = d.getFullYear();
        const m  = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');

        minFechaIso = `${y}-${m}-${dd}`;
        fechaInicio.min = minFechaIso;
        fechaFin.min    = minFechaIso;
    }

    function esDomingo(date) {
        return date.getDay() === 0;
    }

    function resetFin() {
        fechaFin.value = '';
        fechaFin.min   = minFechaIso || '';
    }

    function limpiarEstadoVisualFechas() {
        fechaInicio.classList.remove('is-invalid');
        fechaFin.classList.remove('is-invalid');
    }

    function validarFechaInicio() {
        fechaInicio.classList.remove('is-invalid');
        ocultarError();

        if (!fechaInicio.value) {
            resetFin();
            return;
        }

        const seleccionada = new Date(fechaInicio.value + 'T00:00:00');

        if (esDomingo(seleccionada)) {
            fechaInicio.value = '';
            fechaInicio.classList.add('is-invalid');
            resetFin();
            mostrarError('No puedes seleccionar un domingo como fecha de inicio de la suspensión.');
            return;
        }

        if (minFechaIso && fechaInicio.value < minFechaIso) {
            fechaInicio.value = '';
            fechaInicio.classList.add('is-invalid');
            resetFin();
            mostrarError('La fecha de inicio debe ser al menos tres días después de la fecha actual, sin contar domingos.');
            return;
        }

        const nuevaMin = (!minFechaIso || fechaInicio.value > minFechaIso)
            ? fechaInicio.value
            : minFechaIso;

        fechaFin.min = nuevaMin;

        if (fechaFin.value) {
            validarFechaFin();
        }
    }

    function validarFechaFin() {
        fechaFin.classList.remove('is-invalid');
        ocultarError();

        if (!fechaFin.value) return;

        const seleccionada = new Date(fechaFin.value + 'T00:00:00');

        if (esDomingo(seleccionada)) {
            fechaFin.value = '';
            fechaFin.classList.add('is-invalid');
            mostrarError('No puedes seleccionar un domingo como fecha de finalización de la suspensión.');
            return;
        }

        const ref = fechaInicio.value || minFechaIso;

        if (ref && fechaFin.value < ref) {
            fechaFin.value = '';
            fechaFin.classList.add('is-invalid');
            mostrarError('La fecha de finalización debe ser igual o posterior a la fecha de inicio de la suspensión.');
        }
    }

    function actualizarVisibilidadFecha() {
        const radioChecked = document.querySelector('input[name="cliente_estado"]:checked');
        const estado       = radioChecked ? radioChecked.value : '';
        const debeMostrar  = isSuspension() && estado === 'aprobado';

        ocultarError();
        limpiarEstadoVisualFechas();

        if (debeMostrar) {
            wrapFecha.classList.remove('d-none');
            fechaInicio.required = true;
            fechaFin.required    = true;
            calcularMinFechaSuspension();
            validarFechaInicio();
            validarFechaFin();
        } else {
            wrapFecha.classList.add('d-none');
            fechaInicio.required = false;
            fechaFin.required    = false;
            fechaInicio.value    = '';
            resetFin();
        }
    }

    radiosEstado.forEach(r => r.addEventListener('change', actualizarVisibilidadFecha));
    fechaInicio.addEventListener('change', validarFechaInicio);
    fechaInicio.addEventListener('blur', validarFechaInicio);
    fechaFin.addEventListener('change', validarFechaFin);
    fechaFin.addEventListener('blur', validarFechaFin);

    actualizarVisibilidadFecha();
})();
</script>