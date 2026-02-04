<?= $this->extend('layouts/main'); ?>
<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/ajustes-faltas.css'); ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<div class="card animate-in shadow-sm">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h5 class="mb-0 text-success"><i class="bi bi-sliders me-2"></i>Ajustes · Faltas del RIT</h5>
    <div class="d-flex gap-2 align-items-center">
      <form class="d-flex" method="get" action="<?= base_url('ajustes/faltas') ?>">
        <div class="position-relative w-100">
          <input
            id="searchFaltas"
            type="search"
            class="form-control form-control-sm pe-5"
            name="q"
            value="<?= esc($q ?? '') ?>"
            placeholder="Buscar código o descripción...">

          <!-- Spinner dentro del input -->
          <div
            id="searchSpinner"
            class="search-spinner position-absolute top-50 end-0 translate-middle-y me-2 d-none">
            <div class="spinner-border spinner-border-sm text-success" role="status"></div>
          </div>
        </div>
      </form>
      <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalNueva">
        <i class="bi bi-plus-lg me-1"></i> Nueva falta
      </button>
    </div>
  </div>

  <div class="card-body">
    <?php if (session('ok')): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i><?= esc(session('ok')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php if ($errors = session('errors')): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="table-responsive mt-3">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:120px">Código</th>
            <th>Falta / Descripción</th>
            <th style="width:140px">Gravedad</th>
            <th style="width:120px" class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody id="tbodyFaltas">
          <?php if (empty($faltas)): ?>
            <tr>
              <td colspan="4" class="text-center text-muted py-4">No hay registros.</td>
            </tr>
            <?php else: foreach ($faltas as $f): ?>
              <?php
              $sev = ucfirst(strtolower($f['gravedad']));
              $badge = match ($sev) {
                'Gravísima' => 'badge-soft-danger',
                'Grave'     => 'badge-soft-warning',
                default     => 'badge-soft-success',
              };
              ?>
              <tr>
                <td><span class="text-mono"><?= esc($f['codigo']) ?></span></td>
                <td><?= esc($f['descripcion']) ?></td>
                <td><span class="badge <?= $badge ?>"><?= esc($sev) ?></span></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="<?= base_url('ajustes/faltas/' . $f['id'] . '/edit') ?>">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <button type="button"
                    class="btn btn-sm btn-outline-danger btn-delete"
                    data-id="<?= $f['id'] ?>"
                    data-descripcion="<?= esc($f['descripcion']) ?>">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
          <?php endforeach;
          endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Paginador -->
    <?php if (!empty($pager)): ?>
      <nav aria-label="Page navigation" class="mt-3">
        <?= $pager->only(['q'])->links('faltas', 'bootstrap_full') ?>
      </nav>
    <?php endif; ?>
  </div>
</div>

<!-- Modal Nueva Falta -->
<div class="modal fade" id="modalNueva" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form class="modal-content shadow-lg border-0 rounded-3" method="post" action="<?= base_url('ajustes/faltas') ?>">
      <?= csrf_field(); ?>
      <div class="modal-header bg-success-subtle">
        <h5 class="modal-title fw-semibold text-success">
          <i class="bi bi-plus-circle me-1"></i> Nueva falta
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-3">
        <div class="col-12 col-md-4">
          <label class="form-label fw-semibold">Código</label>
          <input name="codigo" type="text" class="form-control" value="<?= esc($next) ?>" readonly>
          <div class="form-text">Se autogenera</div>
        </div>
        <div class="col-12 col-md-8">
          <label class="form-label fw-semibold">Gravedad</label>
          <select name="gravedad" class="form-select" required>
            <option value="">Seleccione…</option>
            <option>Leve</option>
            <option>Grave</option>
            <option>Gravísima</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Descripción</label>
          <textarea
            id="nuevaDescripcion"
            name="descripcion"
            class="form-control"
            rows="4"
            required
            placeholder="Describe la falta…"
            maxlength="1500"></textarea>

          <div class="form-text d-flex justify-content-between small">
            <span>Máximo 1500 caracteres.</span>
            <span id="nuevaDescCounter">0 / 1500</span>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancelar</button>
        <button class="btn btn-success"><i class="bi bi-save me-1"></i>Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Confirmar Eliminación -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-danger-subtle">
        <h5 class="modal-title text-danger"><i class="bi bi-trash me-1"></i>Eliminar falta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0">¿Estás seguro de eliminar la siguiente falta?</p>
        <p class="fw-semibold text-danger mt-2" id="faltaDescripcion"></p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <form id="formEliminar" method="post" action="">
          <?= csrf_field(); ?>
          <button class="btn btn-danger"><i class="bi bi-trash me-1"></i>Eliminar</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Loader global para crear / eliminar faltas -->
<div id="globalLoader" class="loader-overlay d-none">
  <div class="loader-content">
    <lottie-player
      class="loader-lottie"
      src="<?= base_url('assets/lottie/confetti-animation.json') ?>"
      background="transparent"
      speed="1"
      style="width: 220px; height: 220px;"
      loop
      autoplay>
    </lottie-player>
    <p class="loader-text mb-0 text-muted">
      Procesando cambios en las faltas del RIT…
    </p>
  </div>
</div>


<?= $this->endSection(); ?>

<?= $this->section('scripts'); ?>

<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    // ====== BUSCADOR ULTRA RÁPIDO (carga 1 vez y filtra en JS) ======
    const searchInput = document.getElementById('searchFaltas');
    const searchSpinner = document.getElementById('searchSpinner');
    const tbody = document.getElementById('tbodyFaltas');

    // nav del paginador (tu <nav aria-label="Page navigation"...>)
    const pagerNav = document.querySelector('nav[aria-label="Page navigation"]');

    if (searchInput && tbody) {
      const searchForm = searchInput.closest('form');
      const originalHTML = tbody.innerHTML;

      let allFaltas = null;
      let t = null;

      const showSpinner = () => searchSpinner?.classList.remove('d-none');
      const hideSpinner = () => searchSpinner?.classList.add('d-none');

      const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, (m) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      } [m]));

      const formatSev = (s) => {
        const v = String(s ?? '').trim();
        if (!v) return '';
        return v.charAt(0).toUpperCase() + v.slice(1).toLowerCase();
      };

      const badgeClass = (sev) => {
        const n = String(sev ?? '').toLowerCase();
        if (n.includes('gravísima') || n.includes('gravisima')) return 'badge-soft-danger';
        if (n.includes('grave')) return 'badge-soft-warning';
        return 'badge-soft-success';
      };

      const renderRows = (list) => {
        if (!list || list.length === 0) {
          tbody.innerHTML = `
          <tr>
            <td colspan="4" class="text-center text-muted py-4">Sin resultados.</td>
          </tr>`;
          return;
        }

        const base = `<?= base_url('ajustes/faltas') ?>`;

        tbody.innerHTML = list.map(f => {
          const sev = formatSev(f.gravedad);
          const badge = badgeClass(sev);

          return `
          <tr>
            <td><span class="text-mono">${escapeHtml(f.codigo)}</span></td>
            <td>${escapeHtml(f.descripcion)}</td>
            <td><span class="badge ${badge}">${escapeHtml(sev)}</span></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="${base}/${f.id}/edit">
                <i class="bi bi-pencil"></i>
              </a>
              <button type="button"
                class="btn btn-sm btn-outline-danger btn-delete"
                data-id="${escapeHtml(f.id)}"
                data-descripcion="${escapeHtml(f.descripcion)}">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>`;
        }).join('');
      };

      const ensureAll = async () => {
        if (allFaltas) return allFaltas;

        showSpinner();
        const res = await fetch(`<?= base_url('ajustes/faltas/all') ?>`, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        allFaltas = await res.json();
        hideSpinner();
        return allFaltas;
      };

      const doFilter = async () => {
        const term = searchInput.value.trim().toLowerCase();

        // si está vacío, volvemos a la tabla paginada original
        if (!term) {
          tbody.innerHTML = originalHTML;
          pagerNav?.classList.remove('d-none');
          hideSpinner();
          return;
        }

        pagerNav?.classList.add('d-none');

        const data = await ensureAll();
        const filtered = data.filter(f => {
          const hay = `${f.codigo ?? ''} ${f.descripcion ?? ''}`.toLowerCase();
          return hay.includes(term);
        });

        renderRows(filtered);
      };

      // No recargar al dar Enter
      searchForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        doFilter();
      });

      // Debounce corto para que se sienta inmediato
      searchInput.addEventListener('input', () => {
        showSpinner();
        clearTimeout(t);
        t = setTimeout(doFilter, 120);
      });
    }

    // ✅ IMPORTANTE: tu lógica de eliminar debe funcionar con filas nuevas (delegación)
    const modalEliminarEl = document.getElementById('modalEliminar');
    const modalEliminar = modalEliminarEl ? new bootstrap.Modal(modalEliminarEl) : null;
    const formEliminar = document.getElementById('formEliminar');
    const desc = document.getElementById('faltaDescripcion');

    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.btn-delete');
      if (!btn) return;

      const id = btn.dataset.id;
      const descripcion = btn.dataset.descripcion;

      if (formEliminar) formEliminar.action = `<?= base_url('ajustes/faltas') ?>/${id}/delete`;
      if (desc) desc.textContent = descripcion;
      modalEliminar?.show();
    });

    // (De aquí para abajo puedes dejar tus loaders / contador como ya los tienes)
  });
</script>

<?= $this->endSection(); ?>