<?php
/** @var array       $furd */
/** @var array       $soporte */
/** @var string|null $clienteNombre */
/** @var string|null $clienteEmail */

$estado = $soporte['cliente_estado'] ?? 'pendiente';

$badge = [
    'aprobado'  => 'Aprobado',
    'rechazado' => 'Rechazado',
    'pendiente' => 'Pendiente',
][$estado] ?? 'Pendiente';

// Fallbacks por si no llegaron desde el controlador
$clienteNombre = $clienteNombre ?? ($furd['empresa_usuaria'] ?? '');
$clienteEmail  = $clienteEmail
    ?? ($furd['correo_cliente'] ?? $furd['email_cliente'] ?? $furd['correo_contacto'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Respuesta del cliente a la decisión propuesta</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; color:#333;">

  <?php if ($clienteNombre !== '' || $clienteEmail !== ''): ?>
    <p>
      <strong>Cliente que registró la respuesta:</strong>
      <?php if ($clienteNombre !== '' && $clienteEmail !== ''): ?>
        <?= ' ' . esc($clienteNombre) . ' – ' . esc($clienteEmail) ?>
      <?php elseif ($clienteNombre !== ''): ?>
        <?= ' ' . esc($clienteNombre) ?>
      <?php else: ?>
        <?= ' ' . esc($clienteEmail) ?>
      <?php endif; ?>
    </p>
  <?php endif; ?>

  <p>Hola,</p>

  <p>
    El cliente ha registrado una respuesta a la <strong>decisión propuesta</strong> del
    proceso disciplinario con consecutivo
    <strong><?= esc($furd['consecutivo'] ?? '') ?></strong>.
  </p>

  <p><strong>Estado de la respuesta:</strong> <?= esc($badge) ?></p>

  <p><strong>Decisión propuesta originalmente:</strong> <?= esc($soporte['decision_propuesta'] ?? '') ?></p>

  <?php if (!empty($soporte['cliente_decision'])): ?>
    <p><strong>Decisión ajustada por el cliente:</strong> <?= esc($soporte['cliente_decision']) ?></p>
  <?php endif; ?>

  <p><strong>Justificación original:</strong></p>
  <p style="white-space: pre-line;"><?= nl2br(esc($soporte['justificacion'] ?? '')); ?></p>

  <?php if (!empty($soporte['cliente_justificacion'])): ?>
    <p><strong>Justificación ajustada por el cliente:</strong></p>
    <p style="white-space: pre-line;"><?= nl2br(esc($soporte['cliente_justificacion'])); ?></p>
  <?php endif; ?>

  <?php if (!empty($soporte['cliente_comentario'])): ?>
    <p><strong>Comentario adicional del cliente:</strong></p>
    <p style="white-space: pre-line;"><?= nl2br(esc($soporte['cliente_comentario'])); ?></p>
  <?php endif; ?>

  <p>
    Fecha y hora de respuesta:
    <?= !empty($soporte['cliente_respondido_at'])
        ? esc(date('d/m/Y H:i', strtotime($soporte['cliente_respondido_at'])))
        : 'No disponible' ?>
  </p>

  <p>Atentamente,<br>
    <strong><?= esc(config('Email')->fromName ?? 'Gestión de Procesos Disciplinarios'); ?></strong>
  </p>
</body>
</html>
