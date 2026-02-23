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
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Revisión de decisión propuesta</title>

    <!-- Bootstrap -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <!-- Estilos específicos de la página -->
    <link rel="stylesheet" href="<?= base_url('assets/css/pages/soporte-review-cliente.css'); ?>">
</head>

<body class="review-body">

    <!-- Header tipo app, siguiendo la línea de Gestión de Procesos Disciplinarios -->
    <header class="review-app-header">
        <div class="container review-app-header-inner">
            <div class="review-app-title">
                <span class="review-app-chip">
                    <i class="bi bi-shield-check me-1"></i>
                    Gestión de Procesos Disciplinarios
                </span>
                <!-- <span class="review-app-chip">Revisión de decisión propuesta</span> -->
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
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-xl-9 col-lg-10">

                    <div class="card review-card">
                        <!-- Cabecera de la tarjeta -->
                        <div class="review-card-header">
                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                                <div>
                                    <span class="review-app-chip">
                                        <!-- <i class="bi bi-shield-check me-1"></i> -->
                                        Revisión de decisión propuesta
                                    </span>
                                    <br><br>
                                    <div class="review-card-title">Detalle del proceso</div>
                                    <div class="review-meta-line">
                                        <span class="label">Trabajador:</span>
                                        <span class="value fw-semibold"><?= esc($furd['nombre_completo'] ?? '') ?></span>
                                        <span class="dot-separator">•</span>
                                        <span class="label">Empresa usuaria:</span>
                                        <span class="value"><?= esc($furd['empresa_usuaria'] ?? '') ?></span>
                                    </div>
                                </div>

                                <div class="review-status-wrapper">
                                    <?php if ($estadoActual === 'aprobado'): ?>
                                        <span class="review-badge review-badge-success">
                                            <i class="bi bi-check-circle-fill me-1"></i> Aprobado por el cliente
                                        </span>
                                    <?php elseif ($estadoActual === 'rechazado'): ?>
                                        <span class="review-badge review-badge-danger">
                                            <i class="bi bi-x-circle-fill me-1"></i> Rechazado por el cliente
                                        </span>
                                    <?php else: ?>
                                        <span class="review-badge review-badge-warning">
                                            <i class="bi bi-hourglass-split me-1"></i> Pendiente de respuesta
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Cuerpo de la tarjeta -->
                        <div class="card-body review-card-body">

                            <!-- Paso / breadcrumb interno -->
                            <div class="review-step-indicator mb-3">
                                <span class="step-pill active">Paso 4 de 5 · Revisión del cliente</span>
                                <span class="step-hint">Este paso no modifica aún la decisión final, solo registra tu opinión.</span>
                            </div>

                            <!-- Resumen de decisión -->
                            <section class="review-summary-section mb-4">
                                <div class="review-summary-header">
                                    <div class="summary-title">
                                        <span class="summary-icon"><i class="bi bi-file-text"></i></span>
                                        <div>
                                            <h6 class="summary-heading mb-0">Decisión propuesta por la organización</h6>
                                            <span class="summary-subtitle">Lee la propuesta antes de dar tu respuesta.</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="review-summary-box">
                                    <?php
                                    $decisionPropuesta = trim((string)($soporte['decision_propuesta'] ?? ''));
                                    ?>
                                    <p class="mb-2">
                                        <span class="summary-label">Decisión sugerida:</span>
                                        <span
                                            class="summary-value"
                                            id="decisionSugeridaText"
                                            data-decision="<?= esc($decisionPropuesta) ?>">
                                            <?= esc($decisionPropuesta) ?>
                                        </span>
                                    </p>
                                    <p class="mb-1 summary-label">Justificación:</p>
                                    <div class="summary-justificacion">
                                        <?= nl2br(esc($soporte['justificacion'] ?? '')); ?>
                                    </div>

                                    <?php if (!empty($adjuntosSoporte)): ?>
                                        <div class="mt-3">
                                            <h6 class="mb-1">
                                                <i class="bi bi-paperclip me-1"></i>
                                                Adjuntos del soporte
                                            </h6>

                                            <ul class="list-unstyled mb-0">
                                                <?php foreach ($adjuntosSoporte as $adj): ?>
                                                    <li class="d-flex justify-content-between align-items-center border rounded-3 px-3 py-2 mb-2">
                                                        <div class="text-truncate">
                                                            <i class="bi bi-file-earmark-text me-1 text-muted"></i>
                                                            <span class="text-truncate">
                                                                <?= esc($adj['nombre']) ?>
                                                            </span>
                                                        </div>
                                                        <div class="d-flex gap-2">
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
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </section>

                            <?php if ($yaRespondio): ?>
                                <section class="mb-0">
                                    <div class="alert alert-info review-alert">
                                        <div class="d-flex align-items-start gap-2">
                                            <span class="alert-icon">
                                                <i class="bi bi-info-circle-fill"></i>
                                            </span>
                                            <div>
                                                <div class="fw-semibold mb-1">Respuesta ya registrada</div>
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
                                </section>

                            <?php else: ?>

                                <!-- Formulario -->
                                <section class="review-form-section">
                                    <header class="mb-3">
                                        <div class="review-form-section-title">Indícanos tu respuesta</div>
                                        <p class="review-form-help mb-0">
                                            Selecciona si estás de acuerdo con la propuesta o si consideras que se deben hacer ajustes.
                                        </p>
                                    </header>

                                    <form method="post" action="">
                                        <?= csrf_field(); ?>

                                        <!-- Estado -->
                                        <div class="mb-3">
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

                                        <!-- Campo fecha de inicio suspensión (oculto inicialmente) -->
                                        <!-- Campo fechas de suspensión (oculto inicialmente) -->
                                        <div class="mb-3 d-none" id="wrapFechaSuspension">
                                            <label class="form-label">
                                                ¿Desde qué fecha inicia y hasta qué fecha termina la suspensión disciplinaria?
                                            </label>

                                            <div class="row g-2">
                                                <div class="col-12 col-md-6">
                                                    <input
                                                        type="date"
                                                        id="fechaSuspensionInicio"
                                                        name="cliente_fecha_inicio_suspension"
                                                        class="form-control">
                                                    <div class="form-text">
                                                        Fecha de inicio de la suspensión.
                                                    </div>
                                                </div>

                                                <div class="col-12 col-md-6">
                                                    <input
                                                        type="date"
                                                        id="fechaSuspensionFin"
                                                        name="cliente_fecha_fin_suspension"
                                                        class="form-control">
                                                    <div class="form-text">
                                                        Fecha de finalización de la suspensión.
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-text">
                                                La fecha de inicio debe ser al menos tres días
                                                después de la fecha actual (sin contar domingos).
                                            </div>
                                        </div>


                                        <!-- Cambio de decisión -->
                                        <div class="mb-3">
                                            <label class="form-label">
                                                Si deseas cambiar la decisión o su detalle, escríbelo aquí
                                                <span class="text-muted small">(opcional)</span>
                                            </label>
                                            <input
                                                type="text"
                                                name="cliente_decision"
                                                class="form-control"
                                                placeholder="Ej: Suspensión de 3 días en lugar de 5">
                                        </div>

                                        <!-- Justificación ajustada -->
                                        <div class="mb-3">
                                            <label class="form-label">
                                                Justificación ajustada
                                                <span class="text-muted small">(opcional)</span>
                                            </label>
                                            <textarea
                                                name="cliente_justificacion"
                                                rows="3"
                                                class="form-control"
                                                placeholder="Si propones un cambio, cuéntanos cómo consideras que debería quedar la justificación…"></textarea>
                                        </div>

                                        <!-- Comentario adicional -->
                                        <div class="mb-4">
                                            <label class="form-label">
                                                Comentario adicional para el área de gestión
                                                <span class="text-muted small">(opcional)</span>
                                            </label>
                                            <textarea
                                                name="cliente_comentario"
                                                rows="3"
                                                class="form-control"
                                                placeholder="Aquí puedes dejar cualquier mensaje adicional o contexto que consideres importante."></textarea>
                                        </div>

                                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                                            <span class="align-self-center text-muted small">
                                                Al enviar tu respuesta, esta quedará registrada en la línea de tiempo del proceso.
                                            </span>
                                            <button type="submit" class="btn btn-success review-submit-btn">
                                                Enviar respuesta
                                            </button>
                                        </div>
                                    </form>
                                </section>

                            <?php endif; ?>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <!-- Iconos Bootstrap (para los <i class="bi ..."> ) -->
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

</body>

</html>

<script>
    (function () {
        const decisionEl   = document.getElementById('decisionSugeridaText');
        const wrapFecha    = document.getElementById('wrapFechaSuspension');
        const fechaInicio  = document.getElementById('fechaSuspensionInicio');
        const fechaFin     = document.getElementById('fechaSuspensionFin');
        const radiosEstado = document.querySelectorAll('input[name="cliente_estado"]');

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

        // Calcula la fecha mínima: 3 días después de hoy, sin contar domingos
        function calcularMinFechaSuspension() {
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);

            let diasValidos = 0;
            const d = new Date(hoy);

            while (diasValidos < 3) {
                d.setDate(d.getDate() + 1);

                // 0 = domingo
                if (d.getDay() === 0) {
                    continue;
                }
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

        function validarFechaInicio() {
            if (!fechaInicio.value) {
                resetFin();
                return;
            }

            const seleccionada = new Date(fechaInicio.value + 'T00:00:00');

            if (esDomingo(seleccionada)) {
                alert('No puedes seleccionar domingos como fecha de inicio de la suspensión.');
                fechaInicio.value = '';
                resetFin();
                return;
            }

            if (minFechaIso && fechaInicio.value < minFechaIso) {
                alert('La fecha de inicio debe ser al menos tres días después de la fecha actual (sin contar domingos).');
                fechaInicio.value = '';
                resetFin();
                return;
            }

            // Ajustamos el mínimo de la fecha fin para que no pueda ser menor que el inicio
            const nuevaMin = (!minFechaIso || fechaInicio.value > minFechaIso)
                ? fechaInicio.value
                : minFechaIso;

            fechaFin.min = nuevaMin;

            if (fechaFin.value) {
                validarFechaFin();
            }
        }

        function validarFechaFin() {
            if (!fechaFin.value) return;

            const seleccionada = new Date(fechaFin.value + 'T00:00:00');

            if (esDomingo(seleccionada)) {
                alert('No puedes seleccionar domingos como fecha de finalización de la suspensión.');
                fechaFin.value = '';
                return;
            }

            const ref = fechaInicio.value || minFechaIso;

            if (ref && fechaFin.value < ref) {
                alert('La fecha de finalización debe ser igual o posterior a la fecha de inicio de la suspensión.');
                fechaFin.value = '';
            }
        }

        function actualizarVisibilidadFecha() {
            const radioChecked = document.querySelector('input[name="cliente_estado"]:checked');
            const estado       = radioChecked ? radioChecked.value : '';

            const debeMostrar = isSuspension() && estado === 'aprobado';

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

        // Estado inicial
        actualizarVisibilidadFecha();
    })();
</script>

