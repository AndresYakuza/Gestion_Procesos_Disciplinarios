<?php
/**
 * Vista email: Notificación de citación al trabajador
 * Archivo: app/Views/emails/furd/citacion_trabajador.php
 *
 * Variables esperadas:
 * - $furd (array): consecutivo, nombre, cedula, proyecto, empresa_usuaria, superior, etc.
 * - $citacion (array): numero, fecha_evento, hora, medio, motivo, motivo_recitacion
 */

$consecutivo    = $furd['consecutivo'] ?? 'N/D';
$nombre         = $furd['nombre'] ?? $furd['nombre_completo'] ?? 'Trabajador';
$cedula         = $furd['cedula'] ?? 'N/D';
$empresa        = $furd['empresa_usuaria'] ?? $furd['empresa'] ?? '—';
$superior       = $furd['superior'] ?? $furd['jefe_inmediato'] ?? '—';

$numeroCit   = (string)($citacion['numero'] ?? '1');
$fechaEvento = !empty($citacion['fecha_evento'])
    ? date('d/m/Y', strtotime((string)$citacion['fecha_evento']))
    : 'N/D';
$horaEvento  = $citacion['hora'] ?? 'N/D';

$medioRaw     = trim((string)($citacion['medio'] ?? ''));
$medioKey     = strtolower($medioRaw);
$medioLegible = 'N/D';

switch ($medioKey) {
    case 'virtual':
        $medioLegible = 'Virtual (videollamada)';
        break;
    case 'presencial':
        $medioLegible = 'Presencial';
        break;
    case 'escrito':
        $medioLegible = 'Descargo escrito';
        break;
    default:
        $medioLegible = $medioRaw !== '' ? ucfirst($medioRaw) : 'N/D';
        break;
}

$motivo      = trim((string)($citacion['motivo'] ?? ''));
$motivoRecit = trim((string)($citacion['motivo_recitacion'] ?? ''));
$esEscrito   = ($medioKey === 'escrito');

$logoUrl   = 'https://drive.google.com/uc?export=view&id=1Vy4zS40rxWXwhwwHg9653xcf200gA1uq';
$bannerUrl = 'https://drive.google.com/uc?export=view&id=1F4qm94G8kkCYnH0ydVLJWHFvlVYcikis';

// $bannerUrl = $bannerUrl ?? base_url('assets/images/Logos/banner-2025.jpg');
// $logoUrl   = $logoUrl   ?? base_url('assets/images/Logos/logo-contactamos.png'); // ideal: versión “logo” con nombre corto

$brandGreen  = '#076633';
$accentGreen = '#198754';
$tealBadgeBg = '#D1FAE5';
$tealBadgeTx = '#065F46';

$cardStyle = "background:#F9FAFB; border:1px solid #E5E7EB; border-left:5px solid {$brandGreen}; border-radius:12px; overflow:hidden; margin:0 0 18px 0;";
$cardHead  = "padding:12px 14px; background:#FFFFFF; border-bottom:1px solid #E5E7EB;";
$cardBody  = "padding:12px 14px;";
$h3Style   = "font-size:14px; font-weight:800; color:#111827; margin:0;";
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="x-apple-disable-message-reformatting">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>Notificación de citación</title>
</head>

<body style="margin:0; padding:0; width:100%; background:#F3F5F7; font-family:Arial, Helvetica, sans-serif; color:#1F2937;">

  <!-- Preheader -->
  <div style="display:none; font-size:1px; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
    Proceso disciplinario <?= esc($consecutivo) ?> · Citación #<?= esc($numeroCit) ?>.
  </div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F3F5F7; margin:0; padding:0;">
    <tr>
      <td align="center" style="padding:24px 12px;">

        <!-- Container -->
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
          style="width:600px; max-width:600px; background:#FFFFFF; border-radius:16px; overflow:hidden; box-shadow:0 8px 30px rgba(15, 23, 42, 0.10);">

          <!-- Banner -->
          <tr>
            <td style="padding:0; margin:0;">
              <img
                src="<?= esc($bannerUrl) ?>"
                width="600"
                alt="CONTACTAMOS - Desafiando límites"
                style="display:block; width:100%; max-width:600px; height:auto; border:0; margin:0; padding:0;"
              >
            </td>
          </tr>

          <!-- Franja -->
          <tr>
            <td style="padding:0; height:6px; background:<?= $brandGreen ?>; line-height:6px; font-size:0;">&nbsp;</td>
          </tr>

          <!-- Header -->
          <tr>
            <td style="padding:18px 22px 8px 22px; font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="left" style="color:#111827;">
                    <div style="margin:0 0 8px 0;">
                      <span style="
                        display:inline-block;
                        font-size:12px;
                        font-weight:800;
                        color:<?= $tealBadgeTx ?>;
                        background:<?= $tealBadgeBg ?>;
                        border:1px solid #A7F3D0;
                        border-radius:999px;
                        padding:6px 10px;
                      ">
                        Acción requerida
                      </span>
                    </div>

                    <div style="font-size:12px; letter-spacing:.2px; color:#6B7280;">
                      CONTACTAMOS DE COLOMBIA S.A.S.
                    </div>
                    <div style="font-size:20px; font-weight:800; line-height:1.25; margin-top:4px; color:#111827;">
                      Notificación de citación disciplinaria
                    </div>
                    <div style="font-size:12px; color:#6B7280; margin-top:4px;">
                      Proceso <?= esc($consecutivo) ?> · Citación #<?= esc($numeroCit) ?>
                    </div>
                  </td>

                  <td align="right" style="vertical-align:top;">
                    <div style="display:inline-block; border-radius:999px; padding:8px 12px; font-size:12px; border:1px solid #D1D5DB; background:#F9FAFB; color:#111827;">
                      <strong style="font-weight:800; color:<?= $brandGreen ?>;">CIT</strong>
                      <span style="color:#6B7280;">&nbsp;<?= esc($numeroCit) ?></span>
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Divider -->
          <tr>
            <td style="height:1px; background:#E5E7EB; line-height:1px; font-size:0;">&nbsp;</td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:20px 22px 22px 22px; font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; color:#111827;">

              <p style="margin:0 0 12px 0; font-size:14px; line-height:1.75; color:#111827;">
                Hola <strong><?= esc($nombre) ?></strong>,
              </p>

              <p style="margin:0 0 12px 0; font-size:14px; line-height:1.75; color:#111827;">
                Te informamos que se ha programado una citación dentro del proceso disciplinario
                <strong><?= esc($consecutivo) ?></strong>.
              </p>

              <p style="margin:0 0 18px 0; font-size:14px; line-height:1.75; color:#4B5563;">
                A continuación encontrarás un resumen de los datos principales de la citación.
                El detalle completo se encuentra en el <strong>documento adjunto en formato Word</strong>,
                el cual hace parte integral de la comunicación formal de <strong>CONTACTAMOS DE COLOMBIA S.A.S.</strong>
                respecto de este proceso.
              </p>

              <!-- Datos de la citación -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="<?= $cardStyle ?>">
                <tr>
                  <td style="<?= $cardHead ?>">
                    <div style="<?= $h3Style ?>">Datos de la citación</div>
                  </td>
                </tr>
                <tr>
                  <td style="<?= $cardBody ?>">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                      <?php
                      $rows = [
                        ['Consecutivo', $consecutivo],
                        ['Citación N.º', $numeroCit],
                        ['Fecha', $fechaEvento],
                        ['Hora', $horaEvento],
                        ['Medio', $medioLegible],
                        ['Trabajador', $nombre . ' · CC ' . $cedula],
                        ['Empresa', $empresa],
                        ['Jefe / superior inmediato', $superior],
                      ];
                      foreach ($rows as $i => [$label, $value]): ?>
                        <tr>
                          <td valign="top" style="padding:8px 0; width:38%; font-size:13px; color:#374151;">
                            <strong style="color:#111827;"><?= esc($label) ?>:</strong>
                          </td>
                          <td valign="top" style="padding:8px 0; font-size:13px; color:#111827;">
                            <?= esc($value) ?>
                          </td>
                        </tr>
                        <?php if ($i < count($rows) - 1): ?>
                          <tr><td colspan="2" style="height:1px; background:#E5E7EB;"></td></tr>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </table>

                    <?php if ($motivo !== ''): ?>
                      <div style="margin-top:16px;">
                        <p style="margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:#6B7280; font-weight:800;">
                          Hecho o motivo objeto de análisis
                        </p>
                        <div style="font-size:13px; line-height:1.8; color:#111827; background:#FFFFFF; border:1px solid #E5E7EB; border-radius:10px; padding:12px; white-space:pre-line;">
                          <?= nl2br(esc($motivo)); ?>
                        </div>
                      </div>
                    <?php endif; ?>

                    <?php if ($motivoRecit !== ''): ?>
                      <div style="margin-top:16px;">
                        <p style="margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:#6B7280; font-weight:800;">
                          Motivo de nueva citación
                        </p>
                        <div style="font-size:13px; line-height:1.8; color:#111827; background:#FFFFFF; border:1px solid #E5E7EB; border-radius:10px; padding:12px; white-space:pre-line;">
                          <?= nl2br(esc($motivoRecit)); ?>
                        </div>
                      </div>
                    <?php endif; ?>
                  </td>
                </tr>
              </table>

              <!-- Documento adjunto -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="<?= $cardStyle ?>">
                <tr>
                  <td style="<?= $cardHead ?>">
                    <div style="<?= $h3Style ?>">Información contenida en el documento adjunto</div>
                  </td>
                </tr>
                <tr>
                  <td style="<?= $cardBody ?>">
                    <?php if ($esEscrito): ?>
                      <p style="margin:0 0 10px; font-size:13px; line-height:1.75; color:#111827;">
                        En el documento adjunto encontrarás el <strong>modelo para presentar tu descargo por escrito</strong>,
                        junto con:
                      </p>
                    <?php else: ?>
                      <p style="margin:0 0 10px; font-size:13px; line-height:1.75; color:#111827;">
                        En el documento adjunto encontrarás el <strong>texto completo de la citación</strong>, incluyendo:
                      </p>
                    <?php endif; ?>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                      <?php
                      $items = [
                        'La descripción detallada del hecho objeto de análisis disciplinario.',
                        'Las indicaciones sobre la fecha, hora y forma de realizar tu intervención (' . $medioLegible . ').',
                        'Las instrucciones para la presentación de tus descargos y de los medios de prueba que estimes pertinentes.',
                        'Información sobre tus derechos durante el proceso disciplinario.',
                      ];
                      foreach ($items as $item): ?>
                        <tr>
                          <td valign="top" style="width:18px; padding:6px 0; font-size:16px; line-height:1.6; color:<?= $accentGreen ?>;">•</td>
                          <td valign="top" style="padding:6px 0; font-size:13px; line-height:1.75; color:#111827;">
                            <?= esc($item) ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- Nota principal -->
              <div style="margin:0 0 16px; padding:14px 16px; background:#FFF8E6; border:1px solid #F5DF9B; border-radius:12px;">
                <p style="margin:0; font-size:13px; line-height:1.75; color:#7A5B00;">
                  Te invitamos a leer con atención todo el contenido del documento adjunto.
                  Es importante que <strong>respetes la fecha y hora indicadas</strong> y, en caso de no poder asistir
                  o diligenciar el descargo en los términos señalados, te comuniques previamente con
                  <strong>Gestión de Procesos Disciplinarios</strong> o con tu superior inmediato para reportar la novedad.
                </p>
              </div>

              <!-- Nota secundaria -->
              <div style="background:#F9FAFB; border:1px solid #E5E7EB; border-radius:12px; padding:14px 16px;">
                <p style="margin:0; font-size:13px; line-height:1.75; color:#4B5563;">
                  Este correo hace parte de la trazabilidad formal del proceso disciplinario y se envía
                  al correo registrado en tu información laboral. Si consideras que hay un error en los datos
                  o en la citación, por favor contacta al área de <strong>Gestión de Procesos Disciplinarios</strong>.
                </p>
              </div>

            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:<?= $brandGreen ?>; padding:16px 18px;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="left" style="vertical-align:middle;">
                    <img
                      src="<?= esc($logoUrl) ?>"
                      width="210"
                      alt="CONTACTAMOS - Desafiando límites"
                      style="display:block; width:210px; max-width:210px; height:auto; border:0;"
                    >
                  </td>
                  <td align="right" style="vertical-align:middle; font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
                    <div style="font-size:11px; color:#EAF7EF; line-height:1.5;">
                      <strong>Gestión de Procesos Disciplinarios</strong><br>
                      Mensaje generado automáticamente.<br>
                      <span style="opacity:.9;">Ref: <?= esc($consecutivo) ?> / CIT-<?= esc($numeroCit) ?></span>
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

        </table>

        <div style="height:18px; line-height:18px;">&nbsp;</div>
      </td>
    </tr>
  </table>
</body>
</html>