<?php
/**
 * Email: Notificación de decisión propuesta (fase Soporte) al cliente.
 *
 * @var array  $furd
 * @var array  $soporte
 * @var string $urlAprobacion
 */

$nombreCliente = trim($furd['empresa_usuaria'] ?? 'cliente');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Revisión de decisión propuesta</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color:#333;">
  <p>Estimado(a) cliente <?= esc($nombreCliente); ?>,</p>

  <p>
    Se ha registrado una <strong>decisión propuesta</strong> en el proceso disciplinario
    con consecutivo <strong><?= esc($furd['consecutivo'] ?? '') ?></strong>.
  </p>

  <p><strong>Decisión sugerida:</strong> <?= esc($soporte['decision_propuesta'] ?? '') ?></p>

  <p><strong>Justificación:</strong></p>
  <p style="white-space: pre-line;"><?= nl2br(esc($soporte['justificacion'] ?? '')); ?></p>

  <p>Por favor revisa esta propuesta y dinos si:</p>
  <ul>
    <li>La apruebas tal como está.</li>
    <li>Deseas hacer ajustes.</li>
    <li>No estás de acuerdo con la decisión propuesta.</li>
  </ul>

  <?php if (!empty($urlAprobacion)): ?>
    <p style="margin: 20px 0;">
      <a href="<?= esc($urlAprobacion) ?>"
         style="background:#198754;color:#fff;text-decoration:none;padding:10px 16px;border-radius:4px;display:inline-block;">
        Revisar y responder a la propuesta
      </a>
    </p>

    <p>
      Si el botón anterior no funciona, copia y pega en tu navegador el siguiente enlace:<br>
      <span style="color:#555;"><?= esc($urlAprobacion) ?></span>
    </p>
  <?php endif; ?>

  <p>Atentamente,<br>
    <strong><?= esc(config('Email')->fromName ?? 'Gestión de Procesos Disciplinarios'); ?></strong>
  </p>
</body>
</html>
