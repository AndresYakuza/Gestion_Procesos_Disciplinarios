<?php

/** @var array $furd */
/** @var array $faltas */
/** @var array $adjuntos */
/** @var string $consecutivo */

use CodeIgniter\I18n\Time;

$fechaEvento = $furd['fecha_evento'] ?? null;
$fechaEventoFmt = $fechaEvento
  ? Time::parse($fechaEvento)->format('d/m/Y')
  : '—';

$horaEvento = $furd['hora_evento'] ?? '—';
$hecho      = $furd['hecho'] ?? '';
$empresa    = $furd['empresa_usuaria'] ?? '—';
$superior   = $furd['superior'] ?? '—';

$nombreTrab = $furd['nombre_trabajador'] ?? $furd['nombre_completo'] ?? '—';
$cedulaTrab = $furd['cedula_trabajador'] ?? $furd['cedula'] ?? '—';
$proyecto   = $furd['proyecto'] ?? '—';

$correoTrab = $furd['correo'] ?? $furd['correo_trabajador'] ?? '—';
$correoCli  = $furd['correo_cliente'] ?? '—';
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <title>FURD <?= esc($consecutivo) ?></title>
</head>

<body style="font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 14px; color: #333;">
  <h2 style="color:#198754; margin-bottom:4px;">
    Proceso disciplinario <?= esc($consecutivo) ?>
  </h2>
  <p style="margin-top:0;">
    Se ha registrado un nuevo FURD en el sistema de procesos disciplinarios.
  </p>

  <h3 style="margin-top:16px; font-size:15px;">1. Datos del trabajador</h3>
  <table cellspacing="0" cellpadding="4" border="0" style="border-collapse:collapse;">
    <tr>
      <td><strong>Nombre:</strong></td>
      <td><?= esc($nombreTrab) ?></td>
    </tr>
    <tr>
      <td><strong>Cédula:</strong></td>
      <td><?= esc($cedulaTrab) ?></td>
    </tr>
    <tr>
      <td><strong>Proyecto:</strong></td>
      <td><?= esc($proyecto) ?></td>
    </tr>
    <tr>
      <td><strong>Empresa usuaria:</strong></td>
      <td><?= esc($empresa) ?></td>
    </tr>
    <tr>
      <td><strong>Correo trabajador:</strong></td>
      <td><?= esc($correoTrab) ?></td>
    </tr>
    <tr>
      <td><strong>Correo del cliente que creó el FURD:</strong></td>
      <td><?= esc($correoCli) ?></td>
    </tr>
  </table>

  <h3 style="margin-top:16px; font-size:15px;">2. Datos del evento</h3>
  <table cellspacing="0" cellpadding="4" border="0" style="border-collapse:collapse;">
    <tr>
      <td><strong>Fecha del evento:</strong></td>
      <td><?= esc($fechaEventoFmt) ?></td>
    </tr>
    <tr>
      <td><strong>Hora del evento:</strong></td>
      <td><?= esc($horaEvento) ?></td>
    </tr>
    <tr>
      <td><strong>Superior que interviene:</strong></td>
      <td><?= esc($superior) ?></td>
    </tr>
  </table>

  <h3 style="margin-top:16px; font-size:15px;">3. Hecho o motivo de la intervención</h3>
  <p style="white-space:pre-line; margin-top:4px;">
    <?= esc($hecho) ?>
  </p>

  <?php if (!empty($faltas)): ?>
    <h3 style="margin-top:16px; font-size:15px;">4. Presuntas faltas al RIT</h3>
    <ul style="margin-top:4px;">
      <?php foreach ($faltas as $f): ?>
        <?php
        // Soportar tanto array asociativo como string plano
        $codigo      = '';
        $gravedad    = '';
        $descripcion = '';

        if (is_array($f)) {
          $codigo      = $f['codigo']      ?? '';
          $gravedad    = $f['gravedad']    ?? '';
          $descripcion = $f['descripcion']
            ?? ($f['descripcion_falta'] ?? '');
        } else {
          // Si solo viene un string, lo usamos como descripción
          $descripcion = (string) $f;
        }

        $prefix = trim(
          $codigo . ($gravedad !== '' ? ' (' . $gravedad . ')' : '')
        );
        ?>
        <li>
          <?php if ($prefix !== ''): ?>
            <strong><?= esc($prefix) ?></strong>
            <?php if ($descripcion !== ''): ?>:
            <?= esc($descripcion) ?>
          <?php endif; ?>
        <?php else: ?>
          <?= esc($descripcion) ?>
        <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>


  <?php if (!empty($adjuntos)): ?>
    <h3 style="margin-top:16px; font-size:15px;">5. Adjuntos (fase registro)</h3>
    <ul style="margin-top:4px;">
      <?php foreach ($adjuntos as $a): ?>
        <li>
          <?= esc($a['nombre_original'] ?? $a['nombre'] ?? 'Archivo') ?>
          <?php if (!empty($a['drive_web_view_link'])): ?>
            – <a href="<?= esc($a['drive_web_view_link']) ?>" target="_blank">Ver en Drive</a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <p style="margin-top:20px; font-size:12px; color:#666;">
    Este correo es informativo. Por favor, no lo respondas directamente si se envió desde una cuenta no monitoreada.
  </p>
</body>

</html>