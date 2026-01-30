<?php

/** @var array       $furd */
/** @var array       $soporte */
/** @var string|null $clienteNombre */
/** @var string|null $clienteEmail */

$estado = $soporte['cliente_estado'] ?? 'pendiente';
$isSuspension    = strcasecmp($soporte['decision_propuesta'] ?? '', 'Suspensión disciplinaria') === 0;
$fechaInicioSusp = $soporte['cliente_fecha_inicio_suspension'] ?? null;
$fechaFinSusp    = $soporte['cliente_fecha_fin_suspension'] ?? null;

$badgeText = [
  'aprobado'  => 'Aprobado',
  'rechazado' => 'Rechazado',
  'pendiente' => 'Pendiente',
][$estado] ?? 'Pendiente';

$badgeColor = [
  'aprobado'  => '#198754',  // verde
  'rechazado' => '#dc3545',  // rojo
  'pendiente' => '#ffc107',  // amarillo
][$estado] ?? '#6c757d';

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

<body style="margin:0; padding:24px; background-color:#f5f7fb; font-family:Arial, Helvetica, sans-serif; font-size:14px; color:#1f2933;">

  <div style="max-width:720px; margin:0 auto;">
    <!-- Encabezado superior -->
    <div style="margin-bottom:16px; text-align:left; color:#6b7280; font-size:12px;">
      Gestión de Procesos Disciplinarios
    </div>

    <!-- Card principal -->
    <div style="background-color:#ffffff; border:1px solid #e5e7eb; border-radius:10px; box-shadow:0 8px 24px rgba(15,23,42,0.06); overflow:hidden;">

      <!-- Header del card -->
      <div style="padding:16px 20px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; gap:12px;">
        <div>
          <span style="
            display:inline-block;
            padding:4px 14px;
            border-radius:999px;
            font-size:11px;
            font-weight:600;
            text-transform:uppercase;
            letter-spacing:.05em;
            background-color:<?= esc($badgeColor) ?>1A;
            color:<?= esc($badgeColor) ?>;
            white-space:nowrap;
          ">
            <?= esc($badgeText) ?>
          </span>
          <div style="font-weight:600; font-size:15px; margin-bottom:4px; color:#111827;">
            <br>
            Respuesta a la decisión propuesta
          </div>
          <div style="font-size:12px; color:#6b7280;">
            Proceso disciplinario:
            <strong><?= esc($furd['consecutivo'] ?? '') ?></strong>
          </div>
        </div>
      </div>

      <!-- Cuerpo -->
      <div style="padding:20px; line-height:1.6;">

        <?php if ($clienteNombre !== '' || $clienteEmail !== ''): ?>
          <p style="margin:0 0 12px 0;">
            <span style="font-size:13px; color:#374151; font-weight:600;">Cliente que registró la respuesta:</span><br>
            <span style="font-size:13px; color:#111827;">
              <?php if ($clienteNombre !== '' && $clienteEmail !== ''): ?>
                <?= esc($clienteNombre) ?> – <a href="mailto:<?= esc($clienteEmail) ?>" style="color:#2563eb; text-decoration:none;"><?= esc($clienteEmail) ?></a>
              <?php elseif ($clienteNombre !== ''): ?>
                <?= esc($clienteNombre) ?>
              <?php else: ?>
                <a href="mailto:<?= esc($clienteEmail) ?>" style="color:#2563eb; text-decoration:none;"><?= esc($clienteEmail) ?></a>
              <?php endif; ?>
            </span>
          </p>
        <?php endif; ?>

        <p style="margin:0 0 12px 0; font-size:14px;">
          Hola,
        </p>

        <p style="margin:0 0 16px 0; font-size:14px;">
          El cliente ha registrado una respuesta frente a la
          <strong>decisión propuesta</strong> del proceso disciplinario indicado.
        </p>

        <!-- Bloque resumen rápido -->
        <div style="margin:0 0 20px 0; padding:12px 14px; border-radius:10px; border:1px dashed #d1d5db; background-color:#f9fafb;">
          <div style="font-size:13px; margin-bottom:6px;">
            <strong style="color:#374151;">Decisión propuesta originalmente:</strong><br>
            <span><?= esc($soporte['decision_propuesta'] ?? '') ?></span>
          </div>

          <?php if (!empty($soporte['cliente_decision'])): ?>
            <div style="font-size:13px; margin-top:8px;">
              <strong style="color:#374151;">Decisión / ajuste del cliente:</strong><br>
              <span><?= esc($soporte['cliente_decision']) ?></span>
            </div>
          <?php endif; ?>

          <?php if ($isSuspension && ($fechaInicioSusp || $fechaFinSusp)): ?>
            <?php
            $iniTxt = $fechaInicioSusp ? date('d/m/Y', strtotime($fechaInicioSusp)) : '—';
            $finTxt = $fechaFinSusp   ? date('d/m/Y', strtotime($fechaFinSusp))   : '—';
            ?>
            <div style="font-size:13px; margin-top:8px;">
              <strong style="color:#374151;">Período de suspensión reportado por el cliente:</strong><br>
              <span>Del <?= esc($iniTxt) ?> al <?= esc($finTxt) ?></span>
            </div>
          <?php endif; ?>
        </div>

        <!-- Justificación original -->
        <h4 style="font-size:13px; margin:0 0 4px 0; color:#374151;">Justificación original</h4>
        <p style="margin:0 0 14px 0; font-size:13px; line-height:1.6; white-space:pre-line; color:#111827;">
          <?= esc($soporte['justificacion'] ?? '') ?>
        </p>

        <!-- Justificación del cliente -->
        <?php if (!empty($soporte['cliente_justificacion'])): ?>
          <h4 style="font-size:13px; margin:10px 0 4px 0; color:#374151;">Justificación del cliente</h4>
          <p style="margin:0 0 14px 0; font-size:13px; line-height:1.6; white-space:pre-line; color:#111827;">
            <?= esc($soporte['cliente_justificacion']) ?>
          </p>
        <?php endif; ?>

        <!-- Comentario adicional -->
        <?php if (!empty($soporte['cliente_comentario'])): ?>
          <h4 style="font-size:13px; margin:10px 0 4px 0; color:#374151;">Comentario adicional del cliente</h4>
          <p style="margin:0 0 14px 0; font-size:13px; line-height:1.6; white-space:pre-line; color:#111827;">
            <?= esc($soporte['cliente_comentario']) ?>
          </p>
        <?php endif; ?>

        <!-- Fecha -->
        <p style="margin:16px 0 0 0; font-size:12px; color:#6b7280;">
          <strong>Fecha y hora de respuesta:</strong>
          <?php if (!empty($soporte['cliente_respondido_at'])): ?>
            <?= esc(date('d/m/Y H:i', strtotime($soporte['cliente_respondido_at']))) ?>
          <?php else: ?>
            No disponible
          <?php endif; ?>
        </p>

      </div>

      <!-- Footer del card -->
      <div style="padding:12px 20px; border-top:1px solid #e5e7eb; background-color:#f9fafb; font-size:12px; color:#6b7280;">
        Atentamente,<br>
        <strong style="color:#111827;"><?= esc(config('Email')->fromName ?? 'Gestión de Procesos Disciplinarios'); ?></strong>
      </div>

    </div>
  </div>

</body>

</html>