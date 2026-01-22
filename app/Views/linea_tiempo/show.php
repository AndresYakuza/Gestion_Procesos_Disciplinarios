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
// Normaliza: si viene 'resumen' desde el controlador, úsalo como 'detalle'
$etapas = array_map(function ($e) {
  if (empty($e['detalle'] ?? null) && !empty($e['resumen'] ?? null)) {
    $e['detalle'] = $e['resumen'];
  }

  if (empty($e['detalle_full'] ?? null) && !empty($e['detalle'] ?? null)) {
    $e['detalle_full'] = $e['detalle'];
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

              <?php
              $isSoporte   = ($e['clave'] ?? '') === 'soporte';
              $hasDetalle  = !empty($e['detalle']);
              $hasFaltas   = !empty($e['faltas']);
              $hasMeta     = !empty($e['meta']);
              $hasAdjuntos = !empty($e['adjuntos']);
              ?>

              <?php if ($isSoporte): ?>
                <?php
                $estadoCliente   = $e['cliente_estado']        ?? 'pendiente';
                $respondidoAt    = $e['cliente_respondido_at'] ?? null;
                $decOriginal     = $e['decision_propuesta']    ?? ($e['meta']['Decisión propuesta'] ?? null);
                $decCliente      = $e['cliente_decision']      ?? null;
                $justOrig        = $e['justificacion_original'] ?? null;
                $justCliente     = $e['cliente_justificacion']  ?? null;
                $comentario      = $e['cliente_comentario']     ?? null;

                $hayCambiosDecision = $decCliente && $decOriginal && ($decCliente !== $decOriginal);
                $hayCambiosJustif   = $justCliente && $justOrig && ($justCliente !== $justOrig);
                ?>

                <div class="mb-3">
                  <p class="mb-1">
                    <strong>Decisión propuesta:</strong>
                    <?= esc($decOriginal ?: '—') ?>
                  </p>
                  <?php if ($justOrig): ?>
                    <p class="mb-2 small">
                      <strong>Justificación original:</strong><br>
                      <span class="fw-semibold">
                        <?= nl2br(esc($justOrig)) ?>
                      </span>
                    </p>
                  <?php endif; ?>

                  <?php if ($estadoCliente === 'pendiente'): ?>
                    <div class="alert alert-warning small mb-0">
                      <i class="bi bi-hourglass-split me-1"></i>
                      A la espera de respuesta del cliente sobre la decisión propuesta.
                    </div>
                  <?php else: ?>
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <span class="badge bg-<?= $estadoCliente === 'aprobado' ? 'success' : 'danger' ?>">
                        Cliente <?= $estadoCliente === 'aprobado' ? 'APROBÓ' : 'RECHAZÓ' ?>
                      </span>
                      <?php if ($respondidoAt): ?>
                        <small class="text-muted">
                          el <?= esc(date('d/m/Y H:i', strtotime($respondidoAt))) ?>
                        </small>
                      <?php endif; ?>
                    </div>

                    <?php if ($hayCambiosDecision || $hayCambiosJustif): ?>
                      <div class="alert alert-info small mb-2">
                        <div class="fw-semibold mb-1">
                          <i class="bi bi-pencil-square me-1"></i>
                          Cambios sugeridos por el cliente
                        </div>

                        <?php if ($hayCambiosDecision): ?>
                          <div class="mb-2">
                            <span class="text-muted">Decisión original:</span>
                            <span class="text-decoration-line-through">
                              <?= esc($decOriginal) ?>
                            </span><br>
                            <span class="text-muted">Decisión ajustada:</span>
                            <span class="fw-semibold">
                              <?= esc($decCliente) ?>
                            </span>
                          </div>
                        <?php endif; ?>

                        <?php if ($hayCambiosJustif): ?>
                          <div>
                            <span class="text-muted">Justificación ajustada por el cliente:</span>
                            <span class="fw-semibold">
                              <?= nl2br(esc($justCliente)) ?>
                            </span>
                          </div>
                        <?php endif; ?>
                      </div>
                    <?php else: ?>
                      <div class="alert alert-success small mb-2">
                        <i class="bi bi-hand-thumbs-up me-1"></i>
                        El cliente aprobó la decisión sin solicitar cambios.
                      </div>
                    <?php endif; ?>

                    <?php if ($comentario): ?>
                      <div class="small text-muted">
                        <span class="fw-semibold">Comentario del cliente:</span><br>
                        <span class="fw-semibold">
                          <?= nl2br(esc($comentario)) ?>
                        </span>
                      </div>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>

                <?php
                // 👉 NUEVO: pintar meta de soporte (Responsable, Notificación, Recordatorio, etc.)
                $metaSoporteView = $e['meta'] ?? [];
                if (!empty($metaSoporteView)):
                ?>
                  <div class="tl-section-separator"></div>
                  <dl class="row small mb-3">
                    <?php foreach ($metaSoporteView as $k => $v): ?>
                      <dt class="col-sm-3 text-muted"><?= esc($k) ?></dt>
                      <dd class="col-sm-9"><?= esc($v) ?></dd>
                    <?php endforeach; ?>
                  </dl>
                <?php endif; ?>

                <?php
                // ya mostramos detalle y meta aquí para soporte; evita render genérico abajo
                $hasDetalle = false;
                $hasMeta    = false;
                ?>
              <?php endif; ?>

              <?php if ($hasDetalle): ?>
                <p class="mb-3 tl-detalle-text">
                  <strong>Detalle:</strong>
                  <span class="tl-detalle-resumen"><?= esc($e['detalle']) ?></span>

                  <?php
                  $full  = $e['detalle_full'] ?? '';
                  $short = $e['detalle']      ?? '';
                  $showButton = !empty($full) && ($full !== $short);
                  ?>

                  <?php if ($showButton): ?>
                    <button
                      type="button"
                      class="btn btn-sm tl-detalle-ver-mas ms-2"
                      data-bs-toggle="modal"
                      data-bs-target="#modalDetalleEtapa"
                      data-detalle-full="<?= esc($full, 'attr') ?>"
                      data-etapa="<?= esc($e['titulo'] ?? 'Detalle', 'attr') ?>">
                      <span class="tl-detalle-pill-icon">
                        <i class="bi bi-arrows-fullscreen"></i>
                      </span>
                      <span class="tl-detalle-pill-text">Ver completo</span>
                    </button>
                  <?php endif; ?>

                </p>
              <?php endif; ?>

              <?php if ($hasDetalle && ($hasFaltas || $hasMeta || $hasAdjuntos)): ?>
                <div class="tl-section-separator"></div>
              <?php endif; ?>

              <?php if ($hasFaltas): ?>
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

              <?php if ($hasFaltas && ($hasMeta || $hasAdjuntos)): ?>
                <div class="tl-section-separator"></div>
              <?php endif; ?>

              <?php if ($hasMeta): ?>
                <dl class="row small mb-3">
                  <?php foreach ($e['meta'] as $k => $v): ?>
                    <dt class="col-sm-3 text-muted"><?= esc($k) ?></dt>
                    <dd class="col-sm-9"><?= esc($v) ?></dd>
                  <?php endforeach; ?>
                </dl>
              <?php endif; ?>

              <?php if ($hasMeta && $hasAdjuntos): ?>
                <div class="tl-section-separator"></div>
              <?php endif; ?>

              <?php if ($hasAdjuntos): ?>
                <div class="small">
                  <i class="bi bi-paperclip me-1"></i><strong>Adjuntos:</strong>
                  <ul class="list-unstyled ms-3 mt-2 tl-attach-list">
                    <?php foreach ($e['adjuntos'] as $a): ?>
                      <li class="tl-attach-item">
                        <div class="tl-attach-name text-truncate">
                          <i class="bi bi-file-earmark-text me-1"></i>
                          <span class="tl-attach-filename text-truncate">
                            <?= esc($a['nombre']) ?>
                          </span>
                          <?php if (($a['provider'] ?? 'local') === 'gdrive'): ?>
                            <span class="badge bg-info-subtle text-info ms-1">Drive</span>
                          <?php endif; ?>
                        </div>

                        <div class="tl-attach-actions">
                          <a href="<?= site_url('adjuntos/' . $a['id'] . '/open') ?>"
                            target="_blank" rel="noopener"
                            class="btn btn-xs btn-outline-secondary"
                            title="Abrir">
                            <i class="bi bi-box-arrow-up-right"></i>
                          </a>

                          <a href="<?= site_url('adjuntos/' . $a['id'] . '/download') ?>"
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

<!-- Modal Detalle completo de etapa -->
<div class="modal fade" id="modalDetalleEtapa" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-detalle-etapa-dialog">
    <div class="modal-content modal-detalle-etapa">
      <div class="modal-header modal-detalle-etapa-header">
        <div class="d-flex align-items-center gap-2">
          <span class="modal-detalle-icon">
            <i class="bi bi-journal-text"></i>
          </span>
          <div>
            <h5 class="modal-title fw-semibold mb-0 text-success">
              <span id="modalDetalleEtapaTitulo">Detalle</span>
            </h5>
            <small class="text-muted d-none d-sm-block">
              Texto completo de la etapa seleccionada
            </small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body modal-detalle-etapa-body">
        <div class="modal-detalle-scroll">
          <p id="modalDetalleEtapaTexto"
            class="fs-6 mb-0"
            style="white-space: pre-line;"></p>
        </div>
      </div>

      <div class="modal-footer modal-detalle-etapa-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
          <i class="bi bi-x-lg me-1"></i> Cerrar
        </button>
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
  // Loader global para descargas de adjuntos (lo de antes, sin tocar)
  (function() {
    const globalLoader = document.getElementById('globalLoader');
    const showGlobalLoader = () => globalLoader && globalLoader.classList.remove('d-none');
    const hideGlobalLoader = () => globalLoader && globalLoader.classList.add('d-none');

    document.addEventListener('click', function(e) {
      const btn = e.target.closest('.btn-download');
      if (!btn) return;

      btn.classList.add('disabled');
      btn.setAttribute('aria-disabled', 'true');
      showGlobalLoader();

      setTimeout(() => {
        hideGlobalLoader();
        btn.classList.remove('disabled');
        btn.removeAttribute('aria-disabled');
      }, 12000);
    });

    window.addEventListener('pageshow', hideGlobalLoader);
  })();

  // === Modal "Ver completo" para detalle de cada etapa ===
  (function() {
    const modalEl = document.getElementById('modalDetalleEtapa');
    if (!modalEl) return;

    const modalBody = document.getElementById('modalDetalleEtapaTexto');
    const modalTitle = document.getElementById('modalDetalleEtapaTitulo');

    // Este evento lo dispara Bootstrap cuando se va a abrir el modal
    modalEl.addEventListener('show.bs.modal', function(event) {
      const button = event.relatedTarget; // botón que abrió el modal
      if (!button) return;

      const full = button.getAttribute('data-detalle-full') || '(Sin texto)';
      const titulo = button.getAttribute('data-etapa') || 'Detalle';

      modalTitle.textContent = titulo;
      modalBody.textContent = full; // respeta saltos de línea por el white-space: pre-line
    });
  })();
</script>

<?= $this->endSection(); ?>