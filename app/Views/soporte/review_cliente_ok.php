<?php

/**
 * Vista: Agradecimiento / confirmación de respuesta del cliente
 *
 * @var array  $furd
 * @var array  $soporte
 * @var string $cliente_estado   ('aprobado' | 'rechazado')
 */

$estado = $cliente_estado ?? ($soporte['cliente_estado'] ?? 'pendiente');

$isSuspension    = strcasecmp($soporte['decision_propuesta'] ?? '', 'Suspensión disciplinaria') === 0;
$fechaInicioSusp = $soporte['cliente_fecha_inicio_suspension'] ?? null;
$fechaFinSusp    = $soporte['cliente_fecha_fin_suspension'] ?? null;

if ($estado === 'aprobado') {
  $estadoTexto = 'Has aceptado la decisión propuesta.';
  $badgeClass  = 'review-badge-success';
  $iconClass   = 'bi-check-circle-fill';
} elseif ($estado === 'rechazado') {
  $estadoTexto = 'Has solicitado ajustes a la decisión propuesta.';
  $badgeClass  = 'review-badge-danger';
  $iconClass   = 'bi-x-circle-fill';
} else {
  $estadoTexto = 'Hemos registrado tu respuesta.';
  $badgeClass  = 'review-badge-warning';
  $iconClass   = 'bi-hourglass-split';
}

// Formateos de fechas (si existen)
$iniTxt = $fechaInicioSusp ? date('d/m/Y', strtotime($fechaInicioSusp)) : '—';
$finTxt = $fechaFinSusp    ? date('d/m/Y', strtotime($fechaFinSusp))    : '—';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Respuesta registrada</title>

  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet"
    href="<?= base_url('assets/css/pages/soporte-review-cliente-ok.css'); ?>">
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body class="review-body review-state-<?= esc($estado) ?>">

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
        <div class="col-12 col-xl-9">

          <!-- Hero / Confirmación -->
          <section class="card review-card review-hero-card mb-4">
            <div class="card-body review-card-body">
              <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
                <div class="d-flex align-items-start gap-3">
                  <div class="review-hero-icon">
                    <i class="bi <?= esc($iconClass) ?>"></i>
                  </div>

                  <div>
                    <div class="small text-uppercase text-muted fw-semibold mb-1">
                      Confirmación de respuesta
                    </div>

                    <h1 class="h4 mb-2 fw-bold">
                      Respuesta registrada correctamente
                    </h1>

                    <p class="mb-2">
                      <strong>¡Gracias!</strong> <?= esc($estadoTexto) ?>
                    </p>

                    <p class="text-muted mb-0">
                      El área de Gestión de Procesos Disciplinarios revisará tu respuesta y continuará con el proceso.
                      No es necesario que realices ninguna acción adicional por este medio.
                    </p>
                  </div>
                </div>

                <div class="text-lg-end">
                  <span class="badge review-badge <?= esc($badgeClass) ?>">
                    <?= esc(strtoupper($estado)) ?>
                  </span>
                </div>
              </div>
            </div>
          </section>

          <!-- Datos rápidos -->
          <section class="card review-card mb-4">
            <div class="card-body review-card-body">
              <div class="row g-3 review-kpi-grid">
                <div class="col-12 col-md-4">
                  <div class="review-kpi-box">
                    <div class="review-kpi-label">Proceso</div>
                    <div class="review-kpi-value"><?= esc($furd['consecutivo'] ?? '') ?></div>
                  </div>
                </div>

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
              </div>
            </div>
          </section>

          <div class="row g-4">
            <!-- Decisión original -->
            <div class="col-12 col-lg-6">
              <section class="card review-card h-100">
                <div class="card-header review-card-header">
                  <div class="fw-semibold">Decisión original propuesta</div>
                  <div class="small text-muted review-meta-line">
                    Información base registrada por el área de soporte.
                  </div>
                </div>

                <div class="card-body review-card-body">
                  <div class="review-detail-block mb-3">
                    <div class="review-detail-label">Decisión sugerida</div>
                    <div class="review-detail-value">
                      <?= esc($soporte['decision_propuesta'] ?? '') ?>
                    </div>
                  </div>

                  <div class="review-detail-block">
                    <div class="review-detail-label">Justificación original</div>
                    <div class="review-detail-value review-detail-text">
                      <?= nl2br(esc($soporte['justificacion'] ?? '')) ?>
                    </div>
                  </div>
                </div>
              </section>
            </div>

            <!-- Respuesta del cliente -->
            <div class="col-12 col-lg-6">
              <section class="card review-card h-100">
                <div class="card-header review-card-header">
                  <div class="fw-semibold">Tu respuesta</div>
                  <div class="small text-muted review-meta-line">
                    Así quedó registrada tu decisión frente a la propuesta.
                  </div>
                </div>

                <div class="card-body review-card-body">

                  <?php if (!empty($soporte['cliente_decision'])): ?>
                    <div class="review-detail-block mb-3">
                      <div class="review-detail-label">Decisión / ajuste propuesto</div>
                      <div class="review-detail-value review-detail-text">
                        <?= nl2br(esc($soporte['cliente_decision'])) ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <?php if ($isSuspension && ($fechaInicioSusp || $fechaFinSusp)): ?>
                    <div class="review-inline-alert mb-3">
                      <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-calendar-range review-inline-alert-icon"></i>
                        <div>
                          <div class="fw-semibold">Período de suspensión</div>
                          <div class="small">
                            Del <strong><?= esc($iniTxt) ?></strong> al <strong><?= esc($finTxt) ?></strong>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>

                  <?php if (!empty($soporte['cliente_justificacion'])): ?>
                    <div class="review-detail-block mb-3">
                      <div class="review-detail-label">Justificación de tu respuesta</div>
                      <div class="review-detail-value review-detail-text">
                        <?= nl2br(esc($soporte['cliente_justificacion'])) ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <?php if (!empty($soporte['cliente_comentario'])): ?>
                    <div class="review-detail-block">
                      <div class="review-detail-label">Comentario adicional</div>
                      <div class="review-detail-value review-detail-text">
                        <?= nl2br(esc($soporte['cliente_comentario'])) ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <?php if (
                    empty($soporte['cliente_decision']) &&
                    empty($soporte['cliente_justificacion']) &&
                    empty($soporte['cliente_comentario']) &&
                    !($isSuspension && ($fechaInicioSusp || $fechaFinSusp))
                  ): ?>
                    <div class="text-muted small">
                      No se registraron observaciones adicionales en tu respuesta.
                    </div>
                  <?php endif; ?>

                </div>
              </section>
            </div>
          </div>

          <!-- Mensaje final -->
          <section class="card review-card mt-4">
            <div class="card-body review-card-body">
              <div class="d-flex align-items-start gap-3">
                <div class="review-footer-icon">
                  <i class="bi bi-info-circle"></i>
                </div>
                <div>
                  <div class="fw-semibold mb-1">¿Qué sigue ahora?</div>
                  <p class="mb-0 text-muted">
                    Tu respuesta ya fue almacenada correctamente. El equipo encargado continuará con la validación
                    y con las actuaciones correspondientes dentro del proceso disciplinario.
                  </p>
                </div>
              </div>
            </div>
          </section>

        </div>
      </div>
    </div>
  </main>

</body>

</html>