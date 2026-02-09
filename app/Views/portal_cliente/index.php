<?= $this->extend('layouts/main'); ?>

<?= $this->section('styles'); ?>
<!-- Reutilizamos los estilos que ya tienes -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= base_url('assets/css/pages/furd.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/pages/seguimiento.css'); ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/pages/timeline.css'); ?>">

<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<?php
$clienteEmail       = $clienteEmail ?? '';
$consecutivoInicial = $consecutivoInicial ?? '';
?>

<div
  class="page-furd"
  id="portalClienteRoot"
  data-portal-email="<?= esc($clienteEmail) ?>"
  data-portal-consecutivo="<?= esc($consecutivoInicial) ?>"
  data-timeline-url="<?= base_url('portal-cliente/timeline-json'); ?>"
  data-lookup-url="<?= base_url('empleados/lookup') ?>">

  <div class="row g-4">
    <div class="col-12">
      <div class="card shadow-sm border-0">

        <!-- Header principal -->
        <div class="card-header main-header d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-people-fill me-1"></i>
            <span class="fw-semibold">Portal del Cliente - Registrar Proceso Disciplinario (FURD)</span>
          </div>

          <div class="d-flex align-items-center gap-3">
            <div class="small">
              Correo asociado:
              <strong><?= $clienteEmail ? esc($clienteEmail) : '— (no enviado en la URL)' ?></strong>
            </div>

            <button
              id="btnPortalRefresh"
              type="button"
              class="btn btn-portal-refresh">
              Actualizar
            </button>
          </div>
        </div>

        <!-- Tabs -->
        <div class="card-body p-0">
          <ul class="nav nav-tabs nav-fill px-3 pt-3" id="portalClienteTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button
                class="nav-link active"
                id="tab-registrar-furd-tab"
                data-bs-toggle="tab"
                data-bs-target="#tab-registrar-furd"
                type="button"
                role="tab">
                <i class="bi bi-journal-plus me-1"></i> Registrar FURD
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button
                class="nav-link"
                id="tab-mis-procesos-tab"
                data-bs-toggle="tab"
                data-bs-target="#tab-mis-procesos"
                type="button"
                role="tab">
                <i class="bi bi-list-ul me-1"></i> Mis procesos
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button
                class="nav-link"
                id="tab-timeline-tab"
                data-bs-toggle="tab"
                data-bs-target="#tab-timeline"
                type="button"
                role="tab">
                <i class="bi bi-activity me-1"></i> Línea de tiempo / Respuesta
              </button>
            </li>
          </ul>

          <div class="tab-content p-3">

            <!-- ===================================
                 TAB 1: Registrar FURD (COMPLETO)
            ==================================== -->
            <div
              class="tab-pane fade show active"
              id="tab-registrar-furd"
              role="tabpanel"
              aria-labelledby="tab-registrar-furd-tab">
              <?php
              // Para que los old() no rompan nada si se entra por primera vez
              $errors    = session('errors') ?? [];
              $oldFaltas = old('faltas') ?? [];
              ?>

              <div id="portalFurdAlert" class="alert d-none" role="alert"></div>

              <form
                id="portalFurdForm"
                class="card-body p-0"
                method="post"
                action="<?= base_url('furd'); ?>"
                enctype="multipart/form-data"
                novalidate>
                <?= csrf_field(); ?>

                <!-- DATOS DEL TRABAJADOR -->
                <div class="section-header mt-2">
                  <h6 class="text-muted mb-0">
                    <i class="bi bi-person-vcard me-1"></i>Datos del trabajador
                  </h6>
                </div>

                <input type="hidden" name="empleado_id" id="empleado_id">
                <input type="hidden" name="proyecto_id" id="proyecto_id">

                <div class="row g-3">
                  <div class="col-12 col-md-4">
                    <label for="cedula" class="form-label">Número de cédula</label>
                    <div class="input-group">
                      <input
                        id="cedula"
                        name="cedula"
                        type="text"
                        inputmode="numeric"
                        class="form-control"
                        placeholder="Ej: 1234567890"
                        value="<?= old('cedula') ?>"
                        required>
                      <button type="button" id="btnBuscarEmpleado" class="btn btn-outline-success">
                        <i class="bi bi-search"></i>
                      </button>
                    </div>
                    <div class="form-text">Presiona el botón para buscar el empleado.</div>
                  </div>

                  <div class="col-12 col-md-4">
                    <label for="expedida_en" class="form-label">Expedida en</label>
                    <input
                      id="expedida_en"
                      name="expedida_en"
                      type="text"
                      class="form-control"
                      value="<?= old('expedida_en') ?>"
                      readonly>
                  </div>

                  <div class="col-12 col-md-4">
                    <label for="empresa_usuaria" class="form-label">Empresa Usuaria</label>
                    <input
                      id="empresa_usuaria"
                      name="empresa_usuaria"
                      type="text"
                      class="form-control"
                      value="<?= old('empresa_usuaria') ?>"
                      readonly>
                  </div>

                  <div class="col-12">
                    <label for="nombre_completo" class="form-label">Nombre completo</label>
                    <input
                      id="nombre_completo"
                      name="nombre_completo"
                      type="text"
                      class="form-control"
                      value="<?= old('nombre_completo') ?>"
                      readonly>
                  </div>

                  <div class="col-12 col-md-6">
                    <label for="correo" class="form-label">Correo del colaborador</label>
                    <input
                      id="correo"
                      name="correo"
                      type="email"
                      class="form-control"
                      placeholder="colaborador@dominio.com"
                      value="<?= old('correo') ?>">
                  </div>

                  <div class="col-12 col-md-6">
                    <label
                      class="form-label d-flex align-items-center gap-2"
                      for="correo_cliente">
                      Correo del cliente (donde recibirá notificaciones)


                      <button
                        type="button"
                        class="btn-info-help"
                        data-info-title="Correo electrónico del cliente"
                        data-info-text="Este correo se utilizará para enviar notificaciones sobre el avance y las decisiones del proceso disciplinario. 
                Asegúrate de escribirlo correctamente, ya que será uno de los principales canales de comunicación con el cliente.">
                        <i class="bi bi-info-lg"></i>
                      </button>
                    </label>

                    <input
                      id="correo_cliente"
                      name="correo_cliente"
                      type="email"
                      class="form-control"
                      placeholder="cliente@empresa.com"
                      value="<?= old('correo_cliente', $clienteEmail) ?>">
                    <div class="form-text">
                      Se usará para notificar al cliente sobre el estado del proceso disciplinario.
                    </div>
                  </div>
                </div>

                <!-- DATOS DEL EVENTO -->
                <div class="section-header mt-4">
                  <i class="bi bi-clipboard2-pulse"></i>
                  <h6>Datos del evento</h6>
                </div>

                <div class="row g-3">
                  <div class="col-12 col-md-3">
                    <label for="fecha" class="form-label">Fecha del evento</label>
                    <input
                      id="fecha"
                      type="text"
                      class="form-control"
                      name="fecha_evento"
                      placeholder="Selecciona una fecha..."
                      value="<?= old('fecha_evento') ?>"
                      required>
                  </div>

                  <div class="col-12 col-md-3">
                    <label for="hora" class="form-label">Hora</label>
                    <input
                      id="hora"
                      type="time"
                      class="form-control"
                      name="hora"
                      value="<?= old('hora') ?>"
                      required>
                  </div>

                  <div class="col-12 col-md-6">
                    <label for="superior" class="form-label">Superior que interviene</label>
                    <input
                      id="superior"
                      type="text"
                      class="form-control"
                      name="superior"
                      placeholder="Nombre del superior"
                      value="<?= old('superior') ?>"
                      maxlength="60">
                    <div class="form-text text-end small">
                      <span id="superiorCount">0</span> caracteres
                    </div>
                  </div>

                  <div class="col-12">
                    <label for="hecho" class="form-label">Hecho o motivo de la intervención</label>
                    <textarea
                      id="hecho"
                      name="hecho"
                      class="form-control"
                      rows="3"
                      placeholder="Describe el evento..."
                      maxlength="5000"
                      required><?= old('hecho') ?></textarea>
                    <div class="d-flex justify-content-between small text-muted mt-1">
                      <span>Máximo 5000 caracteres.</span>
                      <span id="hechoCount">0/5000</span>
                    </div>
                  </div>

                  <div class="col-12">
                    <label for="evidencias" class="form-label">Adjuntar evidencias (opcional)</label>
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
                    <input
                    id="evidencias"
                    type="file"
                    class="form-control"
                    name="evidencias[]"
                    multiple
                    accept=".pdf,.jpg,.jpeg,.png,.heic,.doc,.docx,.xlsx,.xls,.mp4,.mov,.avi,.mkv,.webm">
                    <div class="form-text">
                      Puedes adjuntar capturas, correos o documentos relacionados.
                    </div>
                    <div id="evidenciasPreview" class="mt-2 small"></div>
                  </div>
                </div>

                <!-- FALTAS -->
                <div class="section-header mt-4">
                  <i class="bi bi-list-check"></i>
                  <h6>Presuntas faltas e incumplimientos al RIT</h6>
                </div>

                <div class="faltas-wrap">
                  <input
                    id="filtroFaltas"
                    class="form-control mb-2"
                    placeholder="Buscar faltas por código o descripción...">
                  <div id="faltasList" class="scroll-area border rounded">
                    <?php foreach ($faltas as $f): ?>
                      <?php
                      $checked = in_array($f['id'], $oldFaltas) ? 'checked' : '';
                      ?>
                      <label class="list-group-item d-flex align-items-start gap-2">
                        <input
                          class="form-check-input mt-1 faltas-check"
                          type="checkbox"
                          name="faltas[]"
                          value="<?= esc($f['id']) ?>"
                          <?= $checked ?>
                          data-codigo="<?= esc($f['codigo']) ?>"
                          data-gravedad="<?= esc($f['gravedad']) ?>"
                          data-descripcion="<?= esc($f['descripcion']) ?>">
                        <div>
                          <div class="falta-head">
                            <span class="falta-codigo"><?= esc($f['codigo']) ?></span>
                            <span class="falta-gravedad-text small text-muted">
                              <?= esc(ucfirst($f['gravedad'])) ?>
                            </span>
                          </div>
                          <div class="falta-desc"><?= esc($f['descripcion']) ?></div>
                        </div>
                      </label>
                    <?php endforeach; ?>
                  </div>

                  <div class="faltas-toolbar d-flex flex-wrap gap-3 align-items-center justify-content-between mt-3">
                    <div class="text-muted small">
                      <i class="bi bi-info-circle me-1"></i>Selecciona una o más faltas.
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                      <div id="faltasPills" class="d-flex flex-wrap gap-2"></div>
                      <span
                        id="selCount"
                        class="badge rounded-pill bg-secondary-subtle text-secondary">
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
                    <button id="btnEnviarFurd" type="submit" class="btn btn-success">
                      <span
                        class="spinner-border spinner-border-sm me-2 d-none"
                        role="status"
                        aria-hidden="true"></span>
                      <span class="btn-text">Enviar FURD</span>
                    </button>
                  </div>
                </div>

              </form>
            </div>

            <!-- ===================================
     TAB 2: Mis procesos (con filtros + paginador)
==================================== -->
            <div
              class="tab-pane fade"
              id="tab-mis-procesos"
              role="tabpanel"
              aria-labelledby="tab-mis-procesos-tab">

              <div id="misProcesosMsg" class="alert alert-info d-none"></div>

              <!-- Filtros (reutiliza estilos de seguimiento.css) -->
              <div class="row g-2 align-items-end mb-3">
                <div class="col-12 col-md-3">
                  <label class="form-label">Buscar</label>
                  <input
                    id="qMisProcesos"
                    type="search"
                    class="form-control"
                    placeholder="Consecutivo, cédula, nombre, proyecto...">
                </div>

                <div class="col-6 col-md-2">
                  <label class="form-label">Estado</label>
                  <select id="fEstadoMisProcesos" class="form-select">
                    <option value="">Todos</option>
                    <option value="abierto">Abierto</option>
                    <option value="en proceso">En proceso</option>
                    <option value="cerrado">Cerrado</option>
                    <option value="archivado">Archivado</option>
                  </select>
                </div>

                <div class="col-6 col-md-3">
                  <label class="form-label">Fecha (desde)</label>
                  <input
                    id="fDesdeMisProcesos"
                    type="text"
                    class="form-control"
                    placeholder="Selecciona una fecha...">
                </div>

                <div class="col-6 col-md-3">
                  <label class="form-label">Fecha (hasta)</label>
                  <input
                    id="fHastaMisProcesos"
                    type="text"
                    class="form-control"
                    placeholder="Selecciona una fecha...">
                </div>

                <div class="col-6 col-md-1 d-grid">
                  <button
                    id="btnLimpiarMisProcesos"
                    class="btn btn-outline-secondary"
                    type="button">
                    <i class="bi bi-eraser"></i>
                  </button>
                </div>
              </div>

              <!-- Mensaje + total -->
              <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-2 gap-1">
                <small class="text-muted">
                  Haz clic en cualquier proceso para ver la línea de tiempo y responder a la decisión, si aplica.
                </small>
                <small class="fw-semibold text-muted">
                  Total: <span id="countTotalMisProcesos">0</span>
                </small>
              </div>

              <!-- Tabla -->
              <div class="table-responsive">
                <table class="table table-sm table-hover align-middle" id="tablaMisProcesos">
                  <thead>
                    <tr>
                      <th style="width:150px">Consecutivo</th>
                      <th style="width:150px">N° Cédula</th>
                      <th>Nombre</th>
                      <th>Proyecto</th>
                      <th style="width:130px">Fecha</th>
                      <th style="width:135px">Estado</th>
                      <th style="width:170px">Actualizado</th>
                    </tr>
                  </thead>
                  <tbody id="tbodyMisProcesos">
                    <tr>
                      <td colspan="7" class="text-center text-muted py-4">
                        No se han cargado procesos aún.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Paginador (se llena por JS) -->
              <div class="d-flex justify-content-end mt-2" id="pagerMisProcesos"></div>

              <small class="text-muted d-block mt-1">
                * Usa los filtros para limitar los procesos mostrados.
              </small>
            </div>


            <!-- ===================================
                 TAB 3: Línea de tiempo / Respuesta
            ==================================== -->
            <div
              class="tab-pane fade"
              id="tab-timeline"
              role="tabpanel"
              aria-labelledby="tab-timeline-tab">
              <div id="timelineHeader" class="mb-3 small text-muted">
                Selecciona un proceso en la pestaña <strong>Mis procesos</strong>.
              </div>

              <div id="timelineContent">
                <!-- aquí se renderizará la línea de tiempo por JS -->
              </div>
            </div>

          </div><!-- /.tab-content -->
        </div><!-- /.card-body -->
      </div><!-- /.card -->
    </div><!-- /.col-12 -->
  </div><!-- /.row g-4 -->

  <!-- Loader global (mismo que FURD principal) -->
  <div id="globalLoader" class="loader-overlay d-none">
    <div class="loader-content">
      <lottie-player
        class="loader-lottie"
        src="<?= base_url('assets/lottie/catloader.json') ?>"
        background="transparent"
        speed="1"
        style="width: 200px; height: 200px;"
        loop
        autoplay></lottie-player>
      <p
        class="loader-text mb-0 text-muted"
        data-default-text="Procesando solicitud, por favor espera...">
        Procesando solicitud, por favor espera...
      </p>
    </div>
  </div>

  <!-- Toasts -->
  <div id="portalToastContainer"
    class="toast-container position-fixed top-0 end-0 p-3"
    style="z-index: 3000;"></div>
</div><!-- /.page-furd -->

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


<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
<script>
  const PORTAL_BASE_URL = "<?= base_url('portal-cliente'); ?>";
  const FURD_STORE_URL = "<?= base_url('furd'); ?>";
  const PORTAL_EMAIL_INIT = "<?= esc($clienteEmail, 'js'); ?>";
</script>
<script defer src="<?= base_url('assets/js/pages/portal_cliente.js'); ?>"></script>
<?= $this->endSection(); ?>