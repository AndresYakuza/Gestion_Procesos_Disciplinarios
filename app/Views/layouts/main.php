<!doctype html>
<html lang="es" data-bs-theme="light">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title ?? 'Procesos Disciplinarios') ?></title>

  <!-- ===================== CSS PRINCIPAL ===================== -->
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Pixel UI Kit -->
  <link href="https://cdn.jsdelivr.net/npm/@themesberg/pixel-bootstrap@5.0.0/dist/css/pixel.css" rel="stylesheet">
  <!-- Fuente -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <!-- Tu CSS -->
  <link rel="stylesheet" href="<?= base_url('assets/css/global.css'); ?>">
  <!-- Flatpickr CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <!-- Choices CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">

  <!-- SweetAlert2 (en head para que esté disponible en todo momento) -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  

  <!-- Estilos adicionales por vista -->
  <?= $this->renderSection('styles') ?>



</head>

<body class="theme-rich app-shell">
<div id="appTop"></div>

  <?= $this->include('partials/navbar'); ?>

  <main class="container py-4">
    <?= $this->renderSection('content'); ?>
  </main>

    <?= $this->include('partials/footer'); ?>


  <!-- ===================== SCRIPTS PRINCIPALES ===================== -->
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <!-- Pixel UI Kit JS -->
  <script src="https://cdn.jsdelivr.net/npm/@themesberg/pixel-bootstrap@5.0.0/dist/js/pixel.js"></script>
  <!-- Tu JS -->
  <script src="<?= base_url('assets/js/app.js'); ?>"></script>
  <!-- Flatpickr JS -->
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
  <!-- Choices JS -->
  <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

  <!-- =================== Helpers/Init global (ANTES de scripts de vista) =================== -->
  <script>
    const TOAST_ICONS = {
      success: '<i class="bi bi-check-lg"></i>',
      info:    '<i class="bi bi-info-lg"></i>',
      warning: '<i class="bi bi-exclamation-lg"></i>',
      error:   '<i class="bi bi-x-lg"></i>'
    };

    function showToast(message, type='success', delay=4500) {
      const iconHtml = `<span class="swal2-toast-icon-custom">${TOAST_ICONS[type] || TOAST_ICONS.success}</span>`;
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',         
        iconHtml: iconHtml,      
        title: message,
        showConfirmButton: false,
        timer: delay,
        timerProgressBar: true,
        customClass: { popup: `swal2-toast-modern toast-${type}` },
        didOpen: (t)=>{ t.addEventListener('mouseenter', Swal.stopTimer); t.addEventListener('mouseleave', Swal.resumeTimer); }
      });
    }


    // Inicializaciones suaves (no fallan si no existen los elementos)
    document.addEventListener('DOMContentLoaded', () => {
      // Flatpickr fecha (solo si NO tiene data-no-global-flatpickr)
      const fechaGlobal = document.querySelector('#fecha:not([data-no-global-flatpickr])');
      if (fechaGlobal) {
        flatpickr(fechaGlobal, {
          dateFormat: 'd-m-Y',
          altInput: true,
          altFormat: 'j F Y',
          locale: 'es',
          disableMobile: true,
          position: 'below left',
          defaultDate: 'today',
          monthSelectorType: 'static',
          yearSelectorType: 'dropdown',
          onReady: (selectedDates, dateStr, instance) => {
            const yearEl = instance.calendarContainer.querySelector('.numInputWrapper');
            if (yearEl) yearEl.style.display = 'inline-flex';
          }
        });
      }

      // Flatpickr hora
      if (document.querySelector('#hora')) {
        flatpickr('#hora', {
          enableTime: true,
          noCalendar: true,
          dateFormat: 'H:i',
          time_24hr: false,
          altInput: true,
          altFormat: 'h:i K',
          locale: 'es',
          disableMobile: false,
          allowInput: false,
          minuteIncrement: 15,
          static: false
        });
      }

      // Choices para todos los selects .form-select
      document.querySelectorAll('select.form-select').forEach(select => {
        if (select.dataset.choicesApplied) return;
        new Choices(select, {
          searchEnabled: false,
          itemSelectText: '',
          shouldSort: false,
          position: 'bottom',
        });
        select.dataset.choicesApplied = true;
      });
    });
  </script>



  <!-- ===================== SCRIPTS POR VISTA ===================== -->
  <?= $this->renderSection('scripts'); ?>

  <!-- ===================== FLASH TOASTS (sesión) ===================== -->
  <?php if ($msg = session('ok')): ?>
    <script>
      window.addEventListener('load', () => {
        showToast(<?= json_encode($msg) ?>, 'success', 4500);
      });
    </script>
  <?php endif; ?>

  <?php if ($errs = session('errors')): ?>
    <script>
      window.addEventListener('load', () => {
        const msg = <?= json_encode(is_array($errs) ? reset($errs) : $errs) ?>;
        showToast(msg || 'Ocurrió un error', 'error', 6000);
      });
    </script>
  <?php endif; ?>

  <?php if ($info = session('info')): ?>
    <script>
      window.addEventListener('load', () => {
        showToast(<?= json_encode($info) ?>, 'info', 5000);
      });
    </script>
  <?php endif; ?>

  <?php if ($warn = session('warn')): ?>
    <script>
      window.addEventListener('load', () => {
        showToast(<?= json_encode($warn) ?>, 'warning', 5500);
      });
    </script>
  <?php endif; ?>
</body>

</html>