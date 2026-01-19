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
                                    <p class="mb-2">
                                        <span class="summary-label">Decisión sugerida:</span>
                                        <span class="summary-value"><?= esc($soporte['decision_propuesta'] ?? '') ?></span>
                                    </p>
                                    <p class="mb-1 summary-label">Justificación:</p>
                                    <div class="summary-justificacion">
                                        <?= nl2br(esc($soporte['justificacion'] ?? '')); ?>
                                    </div>
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
                                                        <div class="option-title">Aprobar la decisión propuesta</div>
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