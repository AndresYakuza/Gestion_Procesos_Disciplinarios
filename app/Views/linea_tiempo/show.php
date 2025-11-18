<?= $this->extend('layouts/main'); ?>

<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/timeline.css'); ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<?php
// --- Compatibilidad: si el controlador ya manda $proceso/$etapas, úsalos tal cual ---
// Fallback para vistas viejas que seguían usando $consecutivo/$empleado/$estado/$events

$proceso = $proceso ?? [
  'consecutivo' => $consecutivo ?? '',
  'cedula'      => $empleado['cedula']   ?? '',
  'nombre'      => $empleado['nombre']   ?? '',
  'proyecto'    => $empleado['proyecto'] ?? '',
  'estado'      => $estado ?? '',
];

if (!isset($etapas) || !is_array($etapas) || empty($etapas)) {
  $etapas = [];

  if (!empty($events)) {
    foreach ($events as $e) {
      $meta = [];
      if (!empty($e['medio']))    $meta['Medio']    = $e['medio'];
      if (!empty($e['decision'])) $meta['Decisión'] = $e['decision'];
      if (!empty($e['faltas']))   $meta['Faltas registradas'] = count($e['faltas']);

      $etapas[] = [
        'clave'    => $e['tipo'] ?? '',
        'titulo'   => ucfirst(str_replace('_', ' ', $e['tipo'] ?? '')),
        'fecha'    => $e['fecha'] ?? '',
        'detalle'  => $e['detalle'] ?? '', // abajo normalizamos con 'resumen'
        'faltas'   => $e['faltas'] ?? [],
        'meta'     => $meta,
      ];
    }
  }
}

// Normaliza: si viene 'resumen' desde el controlador, úsalo como 'detalle' para que se muestre.
$etapas = array_map(function($e){
  if (empty($e['detalle'] ?? null) && !empty($e['resumen'] ?? null)) {
    $e['detalle'] = $e['resumen'];
  }
  return $e;
}, $etapas);
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
          <div class="tl-item <?= strtolower(str_replace([' ', '_'], '-', $e['clave'] ?? '')) ?> <?= !empty($e['fecha']) ? 'done' : '' ?>">


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
    <ul class="list-unstyled ms-3 mt-2 tl-attach-list">
      <?php foreach ($e['adjuntos'] as $a): ?>
        <li class="tl-attach-item">
          <!-- Nombre + Drive -->
          <div class="tl-attach-name text-truncate">
            <i class="bi bi-file-earmark-text me-1"></i>
            <span class="tl-attach-filename text-truncate">
              <?= esc($a['nombre']) ?>
            </span>
            <?php if (($a['provider'] ?? 'local') === 'gdrive'): ?>
              <span class="badge bg-info-subtle text-info ms-1">Drive</span>
            <?php endif; ?>
          </div>

          <!-- Acciones (simétricas) -->
          <div class="tl-attach-actions">
            <!-- Ver (abre visor de Google en otra pestaña) -->
            <a href="<?= site_url('adjuntos/'.$a['id'].'/open') ?>"
               target="_blank" rel="noopener"
               class="btn btn-xs btn-outline-secondary"
               title="Abrir">
              <i class="bi bi-box-arrow-up-right"></i>
            </a>

            <!-- Descargar (descarga directa) -->
            <a href="<?= site_url('adjuntos/'.$a['id'].'/download') ?>"
               class="btn btn-xs btn-outline-primary btn-download"
               data-loading="Preparando descarga…">
              <i class="bi bi-download"></i>
            </a>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
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

  </div>
</div>

<!-- Loader global para descargas -->
<div id="globalLoader" class="loader-overlay d-none">
  <div class="loader-content">
    <lottie-player
      class="loader-lottie"
      src="<?= base_url('assets/lottie/hand-loader.json') ?>" 
      background="transparent"
      speed="1"
      style="width: 200px; height: 200px;"
      loop
      autoplay>
    </lottie-player>
    <p class="loader-text mb-0 text-muted">Preparando descarga, por favor espera...</p>
  </div>
</div>


<style>
  /* Overlay del loader */
/* LOADER CLEAN – mismo estilo que FURD */
.loader-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.65);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2050;
  backdrop-filter: blur(3px);
}

.loader-overlay.d-none {
  display: none !important;
}

.loader-content {
  background: #ffffff;
  border-radius: 1rem;
  padding: 2rem 2.5rem;
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.35);
  text-align: center;

  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: stretch;
  min-width: 320px;
  min-height: 240px;
}

/* Lottie centrado */
.loader-lottie {
  margin-top: auto;
  margin-bottom: auto;
}

/* Texto pegado abajo */
.loader-text {
  margin-top: 0.75rem;
}

</style>

<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

<script>
(function () {
  const globalLoader = document.getElementById('globalLoader');
  const showGlobalLoader = () => globalLoader && globalLoader.classList.remove('d-none');
  const hideGlobalLoader = () => globalLoader && globalLoader.classList.add('d-none');

  // Click en botones de descarga de adjuntos
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-download');
    if (!btn) return;

    // Evita doble click
    btn.classList.add('disabled');
    btn.setAttribute('aria-disabled', 'true');

    // Mostrar loader Lottie
    showGlobalLoader();

    // Fallback: si seguimos en la página después de X segundos, ocultamos loader y reactivamos botón
    setTimeout(() => {
      hideGlobalLoader();
      btn.classList.remove('disabled');
      btn.removeAttribute('aria-disabled');
    }, 12000);
  });

  // Cuando el navegador vuelve a mostrar la página (ej. después de back/forward)
  window.addEventListener('pageshow', hideGlobalLoader);
})();
</script>


<?= $this->endSection(); ?>
