<?= $this->extend('layouts/main'); ?>

<?= $this->section('content'); ?>

<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/furd.css') ?>">
<?= $this->endSection(); ?>

<?php $errors = session('errors') ?? []; ?>

<div class="row g-4">
  <div class="col-12">
    <div class="card animate-in">
      <div class="card-header bg-success-subtle">
        <h5 class="mb-0">🧾 Registrar Proceso Disciplinario (FURD)</h5>
      </div>

      <form id="furdForm" class="card-body" method="post" action="<?= site_url('furd'); ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field(); ?>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Corrige los siguientes campos:</div>
            <ul class="mb-0">
              <?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <!-- DATOS DEL TRABAJADOR -->
        <div class="pb-2 mb-3 border-bottom">
          <h6 class="text-muted mb-0"><i class="bi bi-person-vcard me-1"></i>Datos del trabajador</h6>
        </div>
        <div class="row g-3">
          <div class="col-12 col-md-4">
            <label for="cedula" class="form-label">Número de cédula</label>
            <div class="input-group">
              <input id="cedula" name="cedula" type="text" inputmode="numeric"
                class="form-control" placeholder="Ej: 1234567890" required>
              <button type="button" id="btnBuscarEmpleado" class="btn btn-outline-success">
                <i class="bi bi-search"></i>
              </button>
            </div>
            <div class="form-text">Presiona el botón para buscar el empleado.</div>
          </div>


          <div class="col-12 col-md-4">
            <label for="expedida_en" class="form-label">Expedida en</label>
            <input id="expedida_en" name="expedida_en" type="text" class="form-control" readonly>
          </div>

          <div class="col-12 col-md-4">
            <label for="empresa_usuaria" class="form-label">Empresa Usuaria</label>
            <input id="empresa_usuaria" name="empresa_usuaria" type="text" class="form-control" readonly>
          </div>

          <div class="col-12">
            <label for="nombre_completo" class="form-label">Nombre completo</label>
            <input id="nombre_completo" name="nombre_completo" type="text" class="form-control" readonly>
          </div>

          <div class="col-12 col-md-6">
            <label for="correo" class="form-label">Correo electrónico</label>
            <input id="correo" name="correo" type="email" class="form-control" placeholder="correo@dominio.com">
          </div>
        </div>

        <!-- DATOS DEL EVENTO -->
        <div class="pt-4 pb-2 mt-4 mb-3 border-top border-bottom">
          <h6 class="text-muted mb-0"><i class="bi bi-clipboard2-pulse me-1"></i> Datos del evento</h6>
        </div>

        <div class="row g-3">
          <div class="col-12 col-md-3">
            <label for="fecha" class="form-label">Fecha</label>
            <input id="fecha" type="date" class="form-control" name="fecha" required>
          </div>

          <div class="col-12 col-md-3">
            <label for="hora" class="form-label">Hora</label>
            <input id="hora" type="time" class="form-control" name="hora" required>
          </div>

          <div class="col-12 col-md-6">
            <label for="superior" class="form-label">Superior que interviene</label>
            <input id="superior" type="text" class="form-control" name="superior" placeholder="Nombre del superior">
          </div>

          <div class="col-12">
            <label for="hecho" class="form-label">Hecho o motivo de la intervención</label>
            <textarea id="hecho" name="hecho" class="form-control" rows="3" placeholder="Describe el evento..." required></textarea>
          </div>

          <div class="col-12">
            <label for="evidencias" class="form-label">Evidencia (archivos múltiples)</label>
            <input id="evidencias" type="file" class="form-control" name="evidencias[]" multiple accept=".pdf,.jpg,.jpeg,.png,.heic,.doc,.docx,.xlsx,.xls">
            <div class="form-text">Se permiten varios archivos (imágenes, PDF, Office).</div>
          </div>
        </div>

        <!-- FALTAS -->
        <div class="pt-4 pb-2 mt-4 mb-3 border-top border-bottom">
          <h6 class="text-muted mb-0"><i class="bi bi-list-check me-1"></i>Presuntas faltas e incumplimientos al RIT</h6>
        </div>

        <div class="faltas-wrap">
          <input id="filtroFaltas" class="form-control mb-2" placeholder="Buscar faltas por código o descripción...">
          <div id="faltasList" class="scroll-area border rounded">
            <?php foreach ($faltas as $f): ?>
              <?php
              $sevRaw = (string)($f['gravedad'] ?? '');
              $sevKey = mb_strtolower(strtr($sevRaw, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u']));
              $sevClass = match (true) {
                str_contains($sevKey, 'gravisim') => 'badge-soft-danger',
                str_contains($sevKey, 'grave')    => 'badge-soft-warning',
                str_contains($sevKey, 'leve')     => 'badge-soft-success',
                default                          => 'badge-soft-secondary',
              };
              ?>
              <label class="list-group-item d-flex align-items-start gap-2">
                <input class="form-check-input mt-1 faltas-check"
                  type="checkbox" name="faltas[]" value="<?= esc($f['id']) ?>"
                  data-codigo="<?= esc($f['codigo']) ?>" data-gravedad="<?= esc($f['gravedad']) ?>">
                <div>
                  <div class="fw-semibold d-flex align-items-center gap-2">
                    <span class="text-mono small text-muted"><?= esc($f['codigo']) ?></span>
                    <span class="badge <?= $sevClass ?>"><?= esc(ucfirst($f['gravedad'])) ?></span>
                  </div>
                  <div class="small"><?= esc($f['descripcion']) ?></div>
                </div>
              </label>
            <?php endforeach; ?>
          </div>

          <div class="faltas-toolbar d-flex flex-wrap gap-3 align-items-center justify-content-between mt-3">
            <div class="text-muted small"><i class="bi bi-info-circle me-1"></i>Selecciona una o más faltas.</div>
            <div class="d-flex flex-wrap align-items-center gap-2">
              <div id="faltasPills" class="d-flex flex-wrap gap-2"></div>
              <span id="selCount" class="badge rounded-pill bg-secondary-subtle text-secondary">0 seleccionadas</span>
            </div>
          </div>
        </div>

        <!-- BOTONES -->
        <div class="sticky-actions bg-body border-top mt-4 pt-3 pb-3">
          <div class="d-flex gap-2 justify-content-end">
            <button type="reset" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>Limpiar</button>
            <a href="<?= site_url('/') ?>" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>Cancelar</a>
            <button class="btn btn-success"><i class="bi bi-send-check me-1"></i>Guardar registro</button>
          </div>
        </div>

      </form>
    </div>
  </div>
</div>
<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000;"></div>
<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
    <script>
    const BASE_LOOKUP_URL = "<?= base_url('empleados/lookup') ?>";
  </script>
  <script defer src="<?= base_url('assets/js/pages/furd.js') ?>"></script>
<?= $this->endSection(); ?>