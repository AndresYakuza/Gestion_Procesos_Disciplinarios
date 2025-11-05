<?= $this->extend('layouts/main'); ?>

<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/seguimiento.css'); ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<?php
// Espera un arreglo $registros desde el controlador con esta forma:
/*
$registros = [
  [
    'consecutivo'     => 'FURD-2025-0001',
    'cedula'          => '1049536932',
    'nombre'          => 'Karina Blanco',
    'proyecto'        => 'CLARO WHATSAPP',
    'fecha'           => '2025-10-21',
    'hecho'           => 'Hecho resumido...',
    'estado'          => 'En proceso', // Abierto | En proceso | Cerrado | Archivado
    'actualizado_en'  => '2025-10-22 09:30'
  ],
  // ...
];
*/
$registros = $registros ?? [];
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="mb-0">
    <i class="bi bi-list-check me-2 text-success"></i>
    Seguimiento de Solicitudes
  </h5>
  <div class="small text-muted">Total: <span id="countTotal"><?= count($registros) ?></span></div>
</div>

<div class="card animate-in">
  <div class="card-body">
    <!-- Filtros -->
    <div class="row g-2 align-items-end mb-3">
      <div class="col-12 col-md-3">
        <label class="form-label">Buscar</label>
        <div class="input-group">
          <input id="q" type="search" class="form-control" placeholder="Consecutivo, cédula, nombre, proyecto...">
          <button id="btnBuscar" class="btn btn-outline-success" type="button"><i class="bi bi-search"></i></button>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label">Estado</label>
        <select id="fEstado" class="form-select">
          <option value="">Todos</option>
          <option>Abierto</option>
          <option>En proceso</option>
          <option>Cerrado</option>
          <option>Archivado</option>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label">Fecha (desde)</label>
        <input id="fDesde" type="date" class="form-control">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label">Fecha (hasta)</label>
        <input id="fHasta" type="date" class="form-control">
      </div>
      <div class="col-6 col-md-1 d-grid">
        <button id="btnLimpiar" class="btn btn-outline-secondary"><i class="bi bi-eraser"></i></button>
      </div>
    </div>

    <!-- Tabla -->
    <div class="table-responsive">
      <table class="table align-middle table-hover table-seg">
        <thead>
          <tr>
            <th style="width:150px">Consecutivo</th>
            <th style="width:150px">N° Cédula</th>
            <th>Nombre</th>
            <th>Proyecto</th>
            <th style="width:130px">Fecha</th>
            <th>Hecho</th>
            <th style="width:135px">Estado</th>
            <th style="width:170px">Actualizado</th>
            <th style="width:160px" class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody id="tbodySeg">
          <?php if (empty($registros)): ?>
            <tr>
              <td colspan="9" class="text-center text-muted py-4">Sin registros</td>
            </tr>
          <?php else: ?>
            <?php foreach ($registros as $r): ?>
            <?php
            $badgeClass = match (strtolower($r['estado'])) {
              'decisión'           => 'badge bg-primary-subtle text-primary fw-semibold px-3 py-2',
              'cargos y descargos' => 'badge bg-warning-subtle text-warning fw-semibold px-3 py-2',
              'registro'           => 'badge bg-info-subtle text-info fw-semibold px-3 py-2',
              'abierto'            => 'badge bg-success-subtle text-success fw-semibold px-3 py-2',
              'en proceso'         => 'badge bg-warning-subtle text-warning fw-semibold px-3 py-2',
              'cerrado'            => 'badge bg-secondary-subtle text-secondary fw-semibold px-3 py-2',
              'archivado'          => 'badge bg-danger-subtle text-danger fw-semibold px-3 py-2',
              default              => 'badge bg-light text-dark fw-semibold px-3 py-2'
            };
            ?>
              <tr data-row>
                <td data-key="consecutivo"><?= esc($r['consecutivo']) ?></td>
                <td data-key="cedula" class="text-mono"><?= esc($r['cedula']) ?></td>
                <td data-key="nombre"><?= esc($r['nombre']) ?></td>
                <td data-key="proyecto"><?= esc($r['proyecto']) ?></td>
                <td data-key="fecha"><?= esc($r['fecha']) ?></td>
                <td data-key="hecho" class="text-center">
                  <button 
                    type="button"
                    class="btn btn-sm btn-outline-success btn-hecho-detalle"
                    data-hecho="<?= esc($r['hecho']) ?>"
                    data-nombre="<?= esc($r['nombre']) ?>"
                    data-consecutivo="<?= esc($r['consecutivo']) ?>"
                    title="Ver detalle del hecho">
                    <i class="bi bi-eye me-1"></i> Ver detalle
                  </button>
                </td>
                <td data-key="estado">
                  <span class="<?= $badgeClass ?>"><?= esc(strtoupper($r['estado'])) ?></span>
                </td>
                <td data-key="actualizado"><?= esc($r['actualizado_en']) ?></td>
                <td class="text-end">
                  <a href="<?= site_url('linea-tiempo/' . urlencode($r['consecutivo'])) ?>"
                    class="btn btn-sm btn-outline-success btn-linea-tiempo"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="Ver la línea de tiempo del proceso">
                    <i class="bi bi-activity me-1"></i> Línea temporal
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- Modal Detalle de Hecho -->
<div class="modal fade" id="modalHecho" tabindex="-1" aria-labelledby="modalHechoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg border-0 rounded-3">
      <div class="modal-header bg-success-subtle">
        <h5 class="modal-title fw-bold text-success" id="modalHechoLabel">
          <i class="bi bi-info-circle me-2"></i>Detalle del Hecho
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1 text-muted small"><strong>Consecutivo:</strong> <span id="hechoConsecutivo"></span></p>
        <p class="mb-1 text-muted small"><strong>Colaborador:</strong> <span id="hechoNombre"></span></p>
        <hr>
        <p id="hechoTexto" class="fs-6" style="white-space: pre-line"></p>
      </div>
      <div class="modal-footer">
        <button id="btnCopiarHecho" type="button" class="btn btn-outline-primary">
          <i class="bi bi-clipboard me-1"></i> Copiar texto
        </button>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-lg me-1"></i> Cerrar
        </button>
      </div>
    </div>
  </div>
</div>


<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>
<script>
(() => {
  const q = document.getElementById('q');
  const fEstado = document.getElementById('fEstado');
  const fDesde = document.getElementById('fDesde');
  const fHasta = document.getElementById('fHasta');
  const btnBuscar = document.getElementById('btnBuscar');
  const btnLimpiar = document.getElementById('btnLimpiar');
  const rows = [...document.querySelectorAll('tr[data-row]')];
  const countTotal = document.getElementById('countTotal');

  function matchDateRange(valueDate, d1, d2){
    if(!valueDate) return true;
    if(!d1 && !d2) return true;
    const v = new Date(valueDate);
    if(d1 && v < new Date(d1)) return false;
    if(d2 && v > new Date(d2)) return false;
    return true;
  }

  function apply(){
    const text = (q.value || '').toLowerCase().trim();
    const est  = (fEstado.value || '').toLowerCase().trim();
    const d1   = fDesde.value || '';
    const d2   = fHasta.value || '';

    let visible = 0;
    rows.forEach(tr => {
      const data = {
        consecutivo: tr.querySelector('[data-key="consecutivo"]')?.textContent.toLowerCase() || '',
        cedula:      tr.querySelector('[data-key="cedula"]')?.textContent.toLowerCase() || '',
        nombre:      tr.querySelector('[data-key="nombre"]')?.textContent.toLowerCase() || '',
        proyecto:    tr.querySelector('[data-key="proyecto"]')?.textContent.toLowerCase() || '',
        fecha:       tr.querySelector('[data-key="fecha"]')?.textContent || '',
        hecho:       tr.querySelector('[data-key="hecho"]')?.textContent.toLowerCase() || '',
        estado:      tr.querySelector('[data-key="estado"] .badge')?.textContent.toLowerCase() || '',
      };

      const textok = !text || Object.values(data).join(' ').includes(text);
      const estok  = !est || data.estado === est;
      const dateok = matchDateRange(data.fecha, d1, d2);

      const show = textok && estok && dateok;
      tr.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    if (countTotal) countTotal.textContent = visible;
  }

  btnBuscar?.addEventListener('click', apply);
  q?.addEventListener('keyup', e => (e.key === 'Enter') && apply());
  fEstado?.addEventListener('change', apply);
  fDesde?.addEventListener('change', apply);
  fHasta?.addEventListener('change', apply);
  btnLimpiar?.addEventListener('click', () => {
    q.value = ''; fEstado.value = ''; fDesde.value = ''; fHasta.value = '';
    apply();
  });
})();

document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
  new bootstrap.Tooltip(el);
});

// === Mostrar modal de detalle del hecho ===
const modalElement = document.getElementById('modalHecho');
const modal = new bootstrap.Modal(modalElement);
let hechoActual = '';

document.querySelectorAll('.btn-hecho-detalle').forEach(btn => {
  btn.addEventListener('click', () => {
    const hecho = btn.getAttribute('data-hecho') || '(Sin descripción)';
    const nombre = btn.getAttribute('data-nombre') || '';
    const consecutivo = btn.getAttribute('data-consecutivo') || '';

    hechoActual = hecho;
    document.getElementById('hechoTexto').textContent = hecho;
    document.getElementById('hechoNombre').textContent = nombre;
    document.getElementById('hechoConsecutivo').textContent = consecutivo;

    modal.show();
  });
});

// === Copiar texto del hecho ===
document.getElementById('btnCopiarHecho').addEventListener('click', async () => {
  try {
    await navigator.clipboard.writeText(hechoActual);
    const btn = document.getElementById('btnCopiarHecho');
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check2 me-1"></i> Copiado!';
    btn.classList.replace('btn-outline-primary', 'btn-success');
    setTimeout(() => {
      btn.innerHTML = original;
      btn.classList.replace('btn-success', 'btn-outline-primary');
    }, 2000);
  } catch (err) {
    alert('No se pudo copiar el texto.');
  }
});

</script>
<?= $this->endSection(); ?>
