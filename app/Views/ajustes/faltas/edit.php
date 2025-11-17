<?= $this->extend('layouts/main'); ?>
<?= $this->section('styles'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pages/ajustes-faltas.css'); ?>">
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>

<div class="row g-4">
  <div class="col-12 col-lg-10 mx-auto">
    <div class="card animate-in">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">Editar falta</h5>
      </div>

      <form class="card-body" method="post" action="<?= base_url('ajustes/faltas/'.$falta['id']) ?>">
        <?= csrf_field(); ?>

        <?php if ($errors = session('errors')): ?>
          <div class="alert alert-danger">
            <?php foreach($errors as $e): ?><div><?= esc($e) ?></div><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="row g-3">
          <div class="col-12 col-md-4">
            <label class="form-label">Código</label>
            <input name="codigo" type="text" class="form-control" value="<?= esc($falta['codigo']) ?>" required>
          </div>
          <div class="col-12 col-md-8">
            <label class="form-label">Gravedad</label>
            <select name="gravedad" class="form-select" required>
              <?php
                $g = $falta['gravedad'];
                $opts = ['Leve','Grave','Gravísima'];
                foreach ($opts as $o):
              ?>
              <option value="<?= $o ?>" <?= $g===$o ? 'selected':'' ?>><?= $o ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Falta / Descripción</label>
            <textarea name="descripcion" class="form-control" rows="4" required><?= esc($falta['descripcion']) ?></textarea>
          </div>
        </div>

        <div class="sticky-actions bg-body border-top mt-4 pt-3 pb-3">
          <div class="d-flex justify-content-end gap-2">
            <a href="<?= base_url('ajustes/faltas') ?>" class="btn btn-outline-secondary">Cancelar</a>
            <button class="btn btn-success"><i class="bi bi-save me-1"></i>Guardar cambios</button>
          </div>
        </div>
      </form>

    </div>
  </div>
</div>

<?= $this->endSection(); ?>
