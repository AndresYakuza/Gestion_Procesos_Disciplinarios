<?= $this->extend('layouts/main'); ?>

<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/timeline.css'); ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<?php
// Adaptar la estructura del controlador a la vista
$proceso = [
  'consecutivo' => $consecutivo ?? '',
  'cedula'      => $empleado['cedula'] ?? '',
  'nombre'      => $empleado['nombre'] ?? '',
  'proyecto'    => $empleado['proyecto'] ?? '',
  'estado'      => $estado ?? '',
];

$etapas = [];
if (!empty($events)) {
  foreach ($events as $e) {
    $meta = [];

    // Agregar propiedades dinámicas si existen
    if (!empty($e['medio'])) {
      $meta['Medio'] = $e['medio'];
    }
    if (!empty($e['decision'])) {
      $meta['Decisión'] = $e['decision'];
    }

    // Faltas (si existen)
    if (!empty($e['faltas'])) {
      $meta['Faltas registradas'] = count($e['faltas']);
    }

    $etapas[] = [
      'clave'    => $e['tipo'] ?? '',
      'titulo'   => ucfirst(str_replace('_', ' ', $e['tipo'] ?? '')),
      'fecha'    => $e['fecha'] ?? '',
      'detalle'  => $e['detalle'] ?? '',
      'faltas'   => $e['faltas'] ?? [],
      'meta'     => $meta,
      'adjuntos' => array_map(fn($a) => ['nombre' => $a, 'url' => '#'], $e['adjuntos'] ?? []),
    ];
  }
}
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-1"><i class="bi bi-activity text-success me-2"></i>Línea temporal</h5>
    <div class="small text-muted">
      Consecutivo <span class="text-mono fw-semibold"><?= esc($proceso['consecutivo'] ?? '-') ?></span> ·
      Cédula <span class="text-mono"><?= esc($proceso['cedula'] ?? '-') ?></span> ·
      Nombre <?= esc($proceso['nombre'] ?? '-') ?> ·
      Proyecto <?= esc($proceso['proyecto'] ?? '-') ?> ·
      Estado <span class="fw-semibold"><?= esc($proceso['estado'] ?? '-') ?></span>
    </div>
  </div>
  <a href="<?= site_url('seguimiento') ?>" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left"></i> Volver
  </a>
</div>

<div class="card animate-in">
  <div class="card-body">
    <?php if (empty($etapas)): ?>
      <div class="text-center text-muted py-5">Sin información de etapas.</div>
    <?php else: ?>
      <div class="timeline">
        <?php foreach ($etapas as $i => $e): ?>
          <?php $isLast = $i === array_key_last($etapas); ?>
          <div class="tl-item <?= !empty($e['fecha']) ? 'done' : '' ?>">
            <div class="tl-node">
              <span class="tl-dot"></span>
              <span class="tl-date text-mono"><?= esc($e['fecha'] ?? '—') ?></span>
            </div>

            <div class="tl-card">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="mb-0 text-success fw-semibold"><?= esc($e['titulo'] ?? 'Etapa') ?></h6>
                <span class="badge <?= !empty($e['fecha']) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?>">
                  <?= !empty($e['fecha']) ? 'Completado' : 'Pendiente' ?>
                </span>
              </div>

              <?php if (!empty($e['detalle'])): ?>
                <p class="mb-3"><strong>Detalle:</strong> <?= esc($e['detalle']) ?></p>
              <?php endif; ?>

              <?php if (!empty($e['faltas'])): ?>
                <div class="mb-3">
                  <strong>Faltas asociadas:</strong>
                  <ul class="list-group list-group-flush small mt-1">
                    <?php foreach ($e['faltas'] as $f): ?>
                      <li class="list-group-item px-0 py-1">
                        <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>
                        <strong><?= esc($f['codigo'] ?? '') ?></strong> –
                        <span class="text-muted"><?= esc($f['gravedad'] ?? '') ?></span>:
                        <?= esc($f['desc'] ?? '') ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>

              <?php if (!empty($e['meta'])): ?>
                <dl class="row small mb-3">
                  <?php foreach ($e['meta'] as $k => $v): ?>
                    <dt class="col-sm-3 text-muted"><?= esc($k) ?></dt>
                    <dd class="col-sm-9"><?= esc($v) ?></dd>
                  <?php endforeach; ?>
                </dl>
              <?php endif; ?>

              <?php if (!empty($e['adjuntos'])): ?>
                <div class="small">
                  <i class="bi bi-paperclip me-1"></i><strong>Adjuntos:</strong>
                  <?php foreach ($e['adjuntos'] as $a): ?>
                    <a class="link-success me-2" target="_blank" href="<?= esc($a['url']) ?>">
                      <i class="bi bi-file-earmark-text"></i> <?= esc($a['nombre']) ?>
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <?php if (!$isLast): ?>
              <div class="tl-spine"></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?= $this->endSection(); ?>
