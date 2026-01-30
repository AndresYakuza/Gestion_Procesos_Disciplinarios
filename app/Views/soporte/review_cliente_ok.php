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
$fechaFinSusp    = $soporte['cliente_fecha_fin_suspension']   ?? null;

if ($estado === 'aprobado') {
  $estadoTexto = 'Has aprobado la decisión propuesta.';
  $badgeClass  = 'review-badge-success';
} elseif ($estado === 'rechazado') {
  $estadoTexto = 'Has solicitado ajustes a la decisión propuesta.';
  $badgeClass  = 'review-badge-danger';
} else {
  $estadoTexto = 'Hemos registrado tu respuesta.';
  $badgeClass  = 'review-badge-warning';
}

// Formateos de fechas (si existen)
$iniTxt = $fechaInicioSusp ? date('d/m/Y', strtotime($fechaInicioSusp)) : '—';
$finTxt = $fechaFinSusp    ? date('d/m/Y', strtotime($fechaFinSusp))    : '—';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <title>Respuesta registrada</title>

  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet"
    href="<?= base_url('assets/css/pages/soporte-review-cliente.css'); ?>">
  <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>

<body class="review-body">

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
    <div class="container py-4">
      <div class="row justify-content-center">
        <div class="col-12 col-lg-8">

          <div class="card review-card">
            <!-- Header de la tarjeta -->
            <div class="card-header review-card-header d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-semibold">Respuesta registrada correctamente</div>
                <div class="small text-muted review-meta-line">
                  Trabajador:
                  <strong><?= esc($furd['nombre_completo'] ?? '') ?></strong>
                  · Empresa usuaria:
                  <span><?= esc($furd['empresa_usuaria'] ?? '') ?></span>
                </div>
              </div>

              <span class="badge review-badge <?= $badgeClass ?>">
                <?= esc(strtoupper($estado)) ?>
              </span>
            </div>

            <!-- Cuerpo -->
            <div class="card-body review-card-body">

              <!-- Mensaje principal -->
              <section class="mb-3">
                <p class="mb-2">
                  <strong>¡Gracias!</strong> <?= esc($estadoTexto) ?>
                </p>
                <p class="small text-muted mb-0">
                  El área de Gestión de Procesos Disciplinarios revisará tu respuesta
                  y continuará con el proceso. No es necesario que realices ninguna
                  acción adicional por este medio.
                </p>
              </section>

              <hr>

              <!-- Resumen de la decisión original -->
              <section class="review-summary-box mb-3">
                <header class="mb-2 text-center">
                  <h6 class="mb-0">Resumen de la decisión original</h6>
                  <small class="text-muted">
                    Esta es la decisión que la organización propuso inicialmente.
                  </small>
                </header>

                <div class="mt-3">
                  <p class="mb-2">
                    <strong>Decisión sugerida:</strong><br>
                    <span class="small">
                      <?= esc($soporte['decision_propuesta'] ?? '') ?>
                    </span>
                  </p>

                  <p class="mb-0">
                    <strong>Justificación original:</strong><br>
                    <span class="small" style="white-space: pre-line;">
                      <?= esc($soporte['justificacion'] ?? '') ?>
                    </span>
                  </p>
                </div>
              </section>

              <!-- Resumen de la respuesta del cliente -->
              <?php if (
                !empty($soporte['cliente_decision']) ||
                !empty($soporte['cliente_justificacion']) ||
                !empty($soporte['cliente_comentario'])
              ): ?>
                <section class="review-summary-box">
                  <header class="mb-2 text-center">
                    <h6 class="mb-0">Tu respuesta</h6>
                    <small class="text-muted">
                      Así quedará registrada tu opinión sobre la decisión propuesta.
                    </small>
                  </header>

                  <div class="mt-3">
                    <?php if (!empty($soporte['cliente_decision'])): ?>
                      <p class="mb-2">
                        <strong>Decisión / ajuste propuesto:</strong><br>
                        <span class="small" style="white-space: pre-line;">
                          <?= esc($soporte['cliente_decision'] ?? '') ?>
                        </span>
                      </p>
                    <?php endif; ?>

                    <?php if ($isSuspension && ($fechaInicioSusp || $fechaFinSusp)): ?>
                      <div class="mb-2 p-2 rounded border bg-light small">
                        <strong>
                          <i class="bi bi-calendar-range me-1"></i>
                          Período de suspensión (según tu respuesta):
                        </strong><br>
                        Del <?= esc($iniTxt) ?> al <?= esc($finTxt) ?>
                      </div>
                    <?php endif; ?>

                    <?php if (!empty($soporte['cliente_justificacion'])): ?>
                      <p class="mb-2">
                        <strong>Justificación de tu respuesta:</strong><br>
                        <span class="small" style="white-space: pre-line;">
                          <?= esc($soporte['cliente_justificacion'] ?? '') ?>
                        </span>
                      </p>
                    <?php endif; ?>

                    <?php if (!empty($soporte['cliente_comentario'])): ?>
                      <p class="mb-0">
                        <strong>Comentario adicional para el área de gestión:</strong><br>
                        <span class="small" style="white-space: pre-line;">
                          <?= esc($soporte['cliente_comentario'] ?? '') ?>
                        </span>
                      </p>
                    <?php endif; ?>
                  </div>
                </section>
              <?php endif; ?>

            </div><!-- /.card-body -->
          </div><!-- /.card -->

        </div>
      </div>
    </div>
  </main>

</body>

</html>