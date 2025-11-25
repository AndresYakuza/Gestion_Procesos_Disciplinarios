<?= $this->extend('layouts/main'); ?>

<?= $this->section('content'); ?>

<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/furd.css') ?>">
<?= $this->endSection(); ?>

<?php 
  $errors = session('errors') ?? []; 
  $oldFaltas = old('faltas') ?? [];
?>
<div class="page-furd">
  <div class="row g-4">
    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-header main-header">
          <i class="bi bi-journal-text me-2"></i> Registrar Proceso Disciplinario (FURD)
        </div>

        <form id="furdForm" class="card-body" method="post" action="<?= base_url('furd'); ?>" enctype="multipart/form-data" novalidate>
          <?= csrf_field(); ?>

          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert">
              <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> Corrige los siguientes campos:</strong>
              <ul class="mt-2 mb-0">
                <?php foreach ($errors as $key => $e): ?>
                  <li><?= esc($e) ?></li>
                <?php endforeach; ?>
              </ul>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>


          <!-- DATOS DEL TRABAJADOR -->
          <div class="section-header">
            <h6 class="text-muted mb-0"><i class="bi bi-person-vcard me-1"></i>Datos del trabajador</h6>
          </div>

          <input type="hidden" name="empleado_id" id="empleado_id">
          <input type="hidden" name="proyecto_id" id="proyecto_id">

          <div class="row g-3">
            <div class="col-12 col-md-4">
              <label for="cedula" class="form-label">Número de cédula</label>
              <div class="input-group">
                <input id="cedula" name="cedula" type="text" inputmode="numeric"
                  class="form-control" placeholder="Ej: 1234567890"
                  value="<?= old('cedula') ?>" required>
                <button type="button" id="btnBuscarEmpleado" class="btn btn-outline-success">
                  <i class="bi bi-search"></i>
                </button>
              </div>
              <div class="form-text">Presiona el botón para buscar el empleado.</div>
            </div>

            <div class="col-12 col-md-4">
              <label for="expedida_en" class="form-label">Expedida en</label>
              <input id="expedida_en" name="expedida_en" type="text" class="form-control"
                     value="<?= old('expedida_en') ?>" readonly>
            </div>

            <div class="col-12 col-md-4">
              <label for="empresa_usuaria" class="form-label">Empresa Usuaria</label>
              <input id="empresa_usuaria" name="empresa_usuaria" type="text" class="form-control"
                     value="<?= old('empresa_usuaria') ?>" readonly>
            </div>

            <div class="col-12">
              <label for="nombre_completo" class="form-label">Nombre completo</label>
              <input id="nombre_completo" name="nombre_completo" type="text" class="form-control"
                     value="<?= old('nombre_completo') ?>" readonly>
            </div>

            <div class="col-12 col-md-6">
              <label for="correo" class="form-label">Correo electrónico</label>
              <input id="correo" name="correo" type="email" class="form-control"
                     placeholder="correo@dominio.com" value="<?= old('correo') ?>">
            </div>
          </div>

          <!-- DATOS DEL EVENTO -->
          <div class="section-header">
            <i class="bi bi-clipboard2-pulse"></i>
            <h6>Datos del evento</h6>
          </div>

          <div class="row g-3">
            <div class="col-12 col-md-3">
              <label for="fecha" class="form-label">Fecha</label>
              <input id="fecha" type="text" class="form-control" name="fecha_evento"
                     placeholder="Selecciona una fecha..."
                     value="<?= old('fecha_evento') ?>" required>
            </div>

            <div class="col-12 col-md-3">
              <label for="hora" class="form-label">Hora</label>
              <input id="hora" type="time" class="form-control" name="hora"
                     placeholder="Selecciona hora..." value="<?= old('hora') ?>" required>
            </div>

            <div class="col-12 col-md-6">
              <label for="superior" class="form-label">Superior que interviene</label>
              <input id="superior" type="text" class="form-control" name="superior"
                    placeholder="Nombre del superior"
                    value="<?= old('superior') ?>"
                    maxlength="60">
              <div class="form-text text-end small">
                <span id="superiorCount">0</span> caracteres
              </div>
            </div>


            <div class="col-12">
              <label for="hecho" class="form-label">Hecho o motivo de la intervención</label>
              <textarea id="hecho" name="hecho" class="form-control" rows="3"
                        placeholder="Describe el evento..."
                        maxlength="5000"
                        required><?= old('hecho') ?></textarea>
              <div class="d-flex justify-content-between small text-muted mt-1">
                <span>Máximo 5000 caracteres.</span>
                <span id="hechoCount">0/5000</span>
              </div>
            </div>

            <div class="col-12">
              <label for="evidencias" class="form-label">Evidencia (archivos múltiples)</label>
                <?php if ($temp = session('temp_evidencias')): ?>
                <div class="alert alert-info small">
                  <i class="bi bi-paperclip"></i> Archivos cargados previamente:
                  <ul class="mb-0">
                    <?php foreach ($temp as $f): ?>
                      <li><?= esc($f) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>
              <input id="evidencias" type="file" class="form-control" name="evidencias[]" multiple accept=".pdf,.jpg,.jpeg,.png,.heic,.doc,.docx,.xlsx,.xls">
              <div class="form-text">Se permiten varios archivos (imágenes, PDF, Office).</div>

              <div id="evidenciasPreview" class="mt-2 small"></div>
            </div>
          </div>

          <!-- FALTAS -->
          <div class="section-header">
            <i class="bi bi-list-check"></i>
            <h6>Presuntas faltas e incumplimientos al RIT</h6>
          </div>

          <div class="faltas-wrap">
            <input id="filtroFaltas" class="form-control mb-2" placeholder="Buscar faltas por código o descripción...">
            <div id="faltasList" class="scroll-area border rounded">
              <?php foreach ($faltas as $f): ?>
                <?php
                $sevRaw = (string)($f['gravedad'] ?? '');
                $sevKey = mb_strtolower(strtr($sevRaw, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u']));
                $checked = in_array($f['id'], $oldFaltas) ? 'checked' : '';
                ?>
                <label class="list-group-item d-flex align-items-start gap-2">
                  <input class="form-check-input mt-1 faltas-check"
                    type="checkbox" name="faltas[]" value="<?= esc($f['id']) ?>"
                    <?= $checked ?>
                    data-codigo="<?= esc($f['codigo']) ?>" data-gravedad="<?= esc($f['gravedad']) ?>"
                    data-descripcion="<?= esc($f['descripcion']) ?>">
                  <div>
                    <div class="falta-head">
                      <span class="falta-codigo"><?= esc($f['codigo']) ?></span>
                      <span class="falta-gravedad-text small text-muted"><?= esc(ucfirst($f['gravedad'])) ?></span>
                    </div>
                    <div class="falta-desc"><?= esc($f['descripcion']) ?></div>
                  </div>
                </label>
              <?php endforeach; ?>
            </div>

            <div class="faltas-toolbar d-flex flex-wrap gap-3 align-items-center justify-content-between mt-3">
              <div class="text-muted small"><i class="bi bi-info-circle me-1"></i>Selecciona una o más faltas.</div>
              <div class="d-flex flex-wrap align-items-center gap-2">
                <div id="faltasPills" class="d-flex flex-wrap gap-2"></div>
                <span id="selCount" class="badge rounded-pill bg-secondary-subtle text-secondary">
                  <?= count($oldFaltas) ?> seleccionadas
                </span>
              </div>
            </div>
          </div>

          <!-- BOTONES -->
          <div class="sticky-actions bg-body border-top mt-4 pt-3 pb-3">
            <div class="d-flex gap-2 justify-content-end">
              <button type="reset" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Limpiar
              </button>
              <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-x-circle me-1"></i>Cancelar
              </a>

                  <!-- NUEVO BOTÓN PARA PROBAR EL LOADER -->
              <!-- <button type="button" id="btnTestLoader" class="btn btn-primary">
                <i class="bi bi-hourglass-split me-1"></i>Probar loader
              </button> -->


              <button id="btnGuardar" type="submit" class="btn btn-success">
                <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                <span class="btn-text">Guardar registro</span>
              </button>
            </div>
          </div>

          <?php if (!empty($errors)): ?>
          <script>
          document.addEventListener('DOMContentLoaded', () => {
            const errors = <?= json_encode(array_keys($errors)) ?>;
            let firstInvalid = null;

            errors.forEach(name => {
              const field = document.querySelector(`[name="${name}"]`);
              if (field) {
                field.classList.add('is-invalid');
                if (!firstInvalid) firstInvalid = field;
              }
            });

            if (firstInvalid) {
              firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
              firstInvalid.focus();
            }
          });
          </script>
          <?php endif; ?>



        </form>
      </div>
    </div>
  </div>
  <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000;"></div>

<div id="globalLoader" class="loader-overlay d-none">
  <div class="loader-content">
    <lottie-player
      class="loader-lottie"
      src="<?= base_url('assets/lottie/catloader.json') ?>"
      background="transparent"
      speed="1"
      style="width: 200px; height: 200px;"
      loop
      autoplay>
    </lottie-player>
    <p class="loader-text mb-0 text-muted">Guardando Proceso Disciplinario, por favor espera...</p>
  </div>
</div>

</div>

</div>
<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

<script>
  const BASE_LOOKUP_URL = "<?= base_url('empleados/lookup') ?>";  
</script>
<script defer src="<?= base_url('assets/js/pages/furd.js') ?>"></script>

<!-- <script>
document.addEventListener('DOMContentLoaded', () => {
  const btnTestLoader = document.getElementById('btnTestLoader');
  const globalLoader  = document.getElementById('globalLoader');

  if (btnTestLoader && globalLoader) {
    btnTestLoader.addEventListener('click', () => {
      // Mostrar loader
      globalLoader.classList.remove('d-none');

      // Opcional: ocultarlo después de 3 segundos
      setTimeout(() => {
        globalLoader.classList.add('d-none');
      }, 9000);
    });
  }
});
</script> -->
<?= $this->endSection(); ?>
