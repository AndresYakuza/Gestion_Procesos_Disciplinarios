<?php
/**
 * Vista: Agradecimiento / confirmación de respuesta del cliente
 *
 * @var array  $furd
 * @var array  $soporte
 * @var string $cliente_estado   ('aprobado' | 'rechazado')
 */

$estado = $cliente_estado ?? ($soporte['cliente_estado'] ?? 'pendiente');

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Respuesta registrada</title>

  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= base_url('assets/css/pages/soporte-review-cliente.css'); ?>">
</head>

<body class="review-body">

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
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-8">

        <div class="card review-card">
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

          <div class="card-body review-card-body">

            <div class="mb-3">
              <p class="mb-2">
                <strong>¡Gracias!</strong> <?= esc($estadoTexto) ?>
              </p>
              <p class="small text-muted mb-0">
                El área de Gestión de Procesos Disciplinarios revisará tu respuesta
                y continuará con el proceso. No es necesario que realices ninguna
                acción adicional por este medio.
              </p>
            </div>

            <hr>

            <div class="review-summary-box mb-3">
              <h6>Resumen de la decisión original</h6>
              <p class="mb-1">
                <strong>Decisión sugerida:</strong>
                <?= esc($soporte['decision_propuesta'] ?? '') ?>
              </p>
              <p class="mb-0">
                <strong>Justificación:</strong><br>
              </p>
              <p class="small mb-0" style="white-space: pre-line;">
                <?= esc($soporte['justificacion'] ?? '') ?>
              </p>
            </div>

            <?php if (!empty($soporte['cliente_decision']) ||
                      !empty($soporte['cliente_justificacion']) ||
                      !empty($soporte['cliente_comentario'])): ?>

              <div class="review-summary-box">
                <h6>Tu respuesta</h6>

                <?php if (!empty($soporte['cliente_decision'])): ?>
                  <p class="mb-1">
                    <strong>Decisión / ajuste propuesto:</strong><br>
                    <span class="small" style="white-space: pre-line;">
                      <?= esc($soporte['cliente_decision']) ?>
                    </span>
                  </p>
                <?php endif; ?>

                <?php if (!empty($soporte['cliente_justificacion'])): ?>
                  <p class="mb-1">
                    <strong>Justificación:</strong><br>
                    <span class="small" style="white-space: pre-line;">
                      <?= esc($soporte['cliente_justificacion']) ?>
                    </span>
                  </p>
                <?php endif; ?>

                <?php if (!empty($soporte['cliente_comentario'])): ?>
                  <p class="mb-0">
                    <strong>Comentario adicional:</strong><br>
                    <span class="small" style="white-space: pre-line;">
                      <?= esc($soporte['cliente_comentario']) ?>
                    </span>
                  </p>
                <?php endif; ?>
              </div>

            <?php endif; ?>

          </div>
        </div>

      </div>
    </div>
  </div>
</main>

</body>
</html>
