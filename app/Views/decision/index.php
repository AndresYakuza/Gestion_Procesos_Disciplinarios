<?php
/**
 * View: Decisión (Fase final)
 * Ruta sugerida GET  /decision  -> DecisionController::create
 *             POST /decision  -> DecisionController::store
 */
?>
<?= $this->extend('layouts/main'); ?>

<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/decision.css'); ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<?php
// fallback por si el controlador no pasa el catálogo
$decisiones = $decisiones ?? [
  'Llamado de atención',
  'Suspensión disciplinaria',
  'Terminación de contrato',
];
$errors = session('errors') ?? [];
$msg    = session('msg') ?? null;
?>

<div class="row g-4">
  <div class="col-12">
    <div class="card animate-in">
      <div class="card-header bg-success-subtle">
        <h5 class="mb-0">⚖️ Datos de la decisión</h5>
      </div>

      <form class="card-body" method="post" action="<?= site_url('decision'); ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field(); ?>

        <?php if ($msg): ?>
          <div class="alert alert-success"><?= esc($msg) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Corrige los siguientes campos:</div>
            <ul class="mb-0">
              <?php foreach($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <div class="pb-2 mb-3 border-bottom">
          <h6 class="text-muted mb-0"><i class="bi bi-flag me-1"></i>Decisión</h6>
        </div>

        <div class="row g-3 align-items-start uniform-row">
        <div class="col-12 col-md-4">
            <label class="form-label">Consecutivo</label>
            <div class="input-group">
            <input id="dec_consecutivo" name="consecutivo" type="number" min="1" class="form-control" placeholder="Ej: 10245" required>
            <button class="btn btn-outline-success" type="button" id="btnDecBuscar" title="Buscar">
                <i class="bi bi-search"></i>
            </button>
            </div>
            <div class="form-text">Usa la lupita para cargar soportes previos del FURD.</div>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label">Soporte (opcional)</label>
            <input id="dec_soporte" name="soporte" type="file" class="form-control"
                accept=".pdf,.jpg,.jpeg,.png,.heic,.doc,.docx,.xlsx,.xls">
            <div class="form-text">Puedes adjuntar el documento de decisión firmado.</div>
        </div>

        <div class="col-12 col-md-4">
            <label class="form-label">Decisión</label>
            <select name="decision" class="form-select" required>
            <option value="" selected>Elige una opción...</option>
            <?php foreach ($decisiones as $opt): ?>
                <option value="<?= esc($opt) ?>"><?= esc($opt) ?></option>
            <?php endforeach; ?>
            </select>
            <div class="form-text invisible">placeholder</div>
        </div>
        </div>


        <!-- PREVIEW DE SOPORTES EXISTENTES DEL FURD -->
        <div id="dec_prev_wrap" class="mt-4 d-none">
          <div class="pb-2 mb-3 border-top border-bottom">
            <h6 class="text-muted mb-0"><i class="bi bi-paperclip me-1"></i>Soportes ya asociados al proceso</h6>
          </div>

          <div id="dec_prev_adjuntos" class="row g-3"></div>
        </div>

        <div class="sticky-actions bg-body border-top mt-4 pt-3 pb-3">
          <div class="d-flex gap-2 justify-content-end">
            <a href="<?= site_url('/') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>Cancelar</a>
            <button class="btn btn-success"><i class="bi bi-save2 me-1"></i>Guardar</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<script>
(() => {
  const $cons = document.getElementById('dec_consecutivo');
  const $btn  = document.getElementById('btnDecBuscar');
  const $wrap = document.getElementById('dec_prev_wrap');
  const $grid = document.getElementById('dec_prev_adjuntos');

  function cardAdjunto(a) {
    const isImg = (a.mime || '').startsWith('image/');
    const icon  = isImg ? 'file-image' : 'file-earmark-pdf';
    const name  = a.nombre_original || 'adjunto';
    const link  = a.ruta || a.url || '#';

    return `
      <div class="col-12 col-md-6 col-lg-4">
        <div class="adj-card p-3 border rounded h-100">
          <div class="d-flex align-items-start gap-2">
            <i class="bi bi-${icon} fs-4 text-success"></i>
            <div class="flex-grow-1">
              <div class="fw-semibold small text-truncate" title="${name}">${name}</div>
              <div class="small text-muted">${a.mime ?? ''}</div>
              <a class="stretched-link small" target="_blank" href="${link}">Abrir</a>
            </div>
          </div>
        </div>
      </div>`;
  }

  async function buscar() {
    const id = ($cons.value || '').trim();
    if (!id) return;

    $btn.disabled = true;
    $grid.innerHTML = '';
    $wrap.classList.add('d-none');

    try {
      const res = await fetch('<?= site_url('furd/lookup') ?>/' + encodeURIComponent(id));
      if (!res.ok) throw new Error('No encontrado');

      const data = await res.json();   // { adjuntos: [...] , ... }
      const adj = data?.adjuntos ?? [];

      if (adj.length) {
        $grid.innerHTML = adj.map(cardAdjunto).join('');
        $wrap.classList.remove('d-none');
      } else {
        $grid.innerHTML = '<div class="col-12"><div class="alert alert-info small mb-0">Este consecutivo no tiene soportes previos.</div></div>';
        $wrap.classList.remove('d-none');
      }
    } catch (e) {
      $grid.innerHTML = '<div class="col-12"><div class="alert alert-warning small mb-0">No se encontró el FURD indicado.</div></div>';
      $wrap.classList.remove('d-none');
    } finally {
      $btn.disabled = false;
    }
  }

  $btn?.addEventListener('click', buscar);
})();
</script>
<?= $this->endSection(); ?>
