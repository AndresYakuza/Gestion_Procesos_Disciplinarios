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

$logoUrl   = 'https://drive.google.com/uc?export=view&id=1Vy4zS40rxWXwhwwHg9653xcf200gA1uq';
$bannerUrl = 'https://drive.google.com/uc?export=view&id=1F4qm94G8kkCYnH0ydVLJWHFvlVYcikis';


/**
 * IMPORTANTE:
 * - En correos, las imágenes deben ser URL públicas (HTTPS) o embebidas por CID.
 * - Recomendado: súbelas a /public/assets/email/ (sin espacios en el nombre).
//  */
// $bannerUrl = $bannerUrl ?? base_url('assets/images/Logos/banner-2025.jpg');
// $logoUrl   = $logoUrl   ?? base_url('assets/images/Logos/logo-contactamos.png'); // ideal: versión “logo” con nombre corto

$brandGreen = '#076633'; // color real del fondo del logo
$accentGreen = '#198754'; // acento (botones, bullets)
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>FURD <?= esc($consecutivo) ?></title>
</head>

<body style="margin:0; padding:0; background:#F3F5F7;">
  <!-- Preheader -->
  <div style="display:none; font-size:1px; line-height:1px; max-height:0px; max-width:0px; opacity:0; overflow:hidden;">
    Proceso disciplinario <?= esc($consecutivo) ?> – Notificación de recepción de FURD.
  </div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F3F5F7; margin:0; padding:0;">
    <tr>
      <td align="center" style="padding:24px 12px;">

        <!-- Container -->
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
          style="width:600px; max-width:600px; background:#FFFFFF; border-radius:16px; overflow:hidden; box-shadow:0 8px 30px rgba(15, 23, 42, 0.10);">

          <!-- Banner (top) -->
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

          <!-- Brand accent line -->
          <tr>
            <td style="padding:0; height:6px; background:<?= $brandGreen ?>; line-height:6px; font-size:0;">&nbsp;</td>
          </tr>

          <!-- Header (title + badge) -->
          <tr>
            <td style="padding:18px 22px 8px 22px; font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="left" style="color:#111827;">
                    <div style="font-size:12px; letter-spacing:.2px; color:#6B7280;">
                      CONTACTAMOS DE COLOMBIA S.A.S.
                    </div>
                    <div style="font-size:18px; font-weight:800; line-height:1.2; margin-top:4px; color:#111827;">
                      Proceso disciplinario <?= esc($consecutivo) ?>
                    </div>
                    <div style="font-size:12px; color:#6B7280; margin-top:4px;">
                      Formato Único de Reporte Disciplinario (FURD)
                    </div>
                  </td>

                  <td align="right" style="vertical-align:top;">
                    <div style="display:inline-block; border-radius:999px; padding:8px 12px; font-size:12px; border:1px solid #D1D5DB; background:#F9FAFB; color:#111827;">
                      <strong style="font-weight:800; color:<?= $brandGreen ?>;">FURD</strong>
                      <span style="color:#6B7280;">&nbsp;<?= esc($consecutivo) ?></span>
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

              <!-- Encabezado tipo carta -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 14px 0;">
                <tr>
                  <td style="font-size:14px; line-height:1.55; color:#111827;">
                    Señor<br>
                    <strong style="font-size:15px; font-weight:800;"><?= esc($nombreTrab) ?></strong><br>
                    CC Nº <strong style="font-weight:800;"><?= esc($cedulaTrab) ?></strong>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 12px 0; font-size:14px; line-height:1.7; color:#111827;">
                Cordial saludo,
              </p>

              <p style="margin:0 0 10px 0; font-size:14px; line-height:1.75; color:#111827;">
                CONTACTAMOS DE COLOMBIA S.A.S. se permite informarle que hemos recibido por parte de la empresa usuaria
                <strong><?= esc($proyecto) ?></strong> una solicitud de apertura de proceso disciplinario en su contra.
              </p>

              <p style="margin:0 0 14px 0; font-size:14px; line-height:1.75; color:#111827;">
                En consecuencia, adjuntamos el Formato Único de Reporte Disciplinario (FURD), en el cual podrá consultar de manera
                detallada los hechos objeto de investigación, las pruebas que los sustentan, las presuntas faltas atribuidas y las
                posibles consecuencias derivadas de las mismas.
              </p>

              <p style="margin:0 0 18px 0; font-size:14px; line-height:1.75; color:#111827;">
                Agradecemos su atención y quedamos atentos a cualquier manifestación o aclaración que considere pertinente dentro del proceso.
              </p>

              <!-- Divider -->
              <div style="height:1px; background:#E5E7EB; margin:18px 0;"></div>

              <!-- Card base style: left accent -->
              <?php
                $cardStyle = "background:#F9FAFB; border:1px solid #E5E7EB; border-left:5px solid {$brandGreen}; border-radius:12px; overflow:hidden; margin:0 0 14px 0;";
                $cardHead  = "padding:12px 14px; background:#FFFFFF; border-bottom:1px solid #E5E7EB;";
                $cardBody  = "padding:12px 14px;";
                $h3Style   = "font-size:14px; font-weight:800; color:#111827; margin:0;";
              ?>

              <!-- 1. Datos del trabajador -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="<?= $cardStyle ?>">
                <tr><td style="<?= $cardHead ?>"><div style="<?= $h3Style ?>">1. Datos del trabajador</div></td></tr>
                <tr>
                  <td style="<?= $cardBody ?>">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                      <?php
                      $rowsTrab = [
                        ['Nombre:', $nombreTrab],
                        ['Cédula:', $cedulaTrab],
                        ['Empresa usuaria:', $proyecto],
                        ['Correo trabajador:', $correoTrab],
                        ['Correo del cliente que creó el FURD:', $correoCli],
                      ];
                      foreach ($rowsTrab as $i => [$label, $value]): ?>
                        <tr>
                          <td valign="top" style="padding:8px 0; width:40%; font-size:13px; color:#374151;">
                            <strong style="color:#111827;"><?= esc($label) ?></strong>
                          </td>
                          <td valign="top" style="padding:8px 0; font-size:13px; color:#111827;">
                            <?= esc($value) ?>
                          </td>
                        </tr>
                        <?php if ($i < count($rowsTrab) - 1): ?>
                          <tr><td colspan="2" style="height:1px; background:#E5E7EB;"></td></tr>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- 2. Datos del evento -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="<?= $cardStyle ?>">
                <tr><td style="<?= $cardHead ?>"><div style="<?= $h3Style ?>">2. Datos del evento</div></td></tr>
                <tr>
                  <td style="<?= $cardBody ?>">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                      <?php
                      $rowsEvento = [
                        ['Fecha del evento:', $fechaEventoFmt],
                        ['Hora del evento:', $horaEvento],
                        ['Superior que interviene:', $superior],
                      ];
                      foreach ($rowsEvento as $i => [$label, $value]): ?>
                        <tr>
                          <td valign="top" style="padding:8px 0; width:40%; font-size:13px; color:#374151;">
                            <strong style="color:#111827;"><?= esc($label) ?></strong>
                          </td>
                          <td valign="top" style="padding:8px 0; font-size:13px; color:#111827;">
                            <?= esc($value) ?>
                          </td>
                        </tr>
                        <?php if ($i < count($rowsEvento) - 1): ?>
                          <tr><td colspan="2" style="height:1px; background:#E5E7EB;"></td></tr>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- 3. Hecho -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="<?= $cardStyle ?>">
                <tr><td style="<?= $cardHead ?>"><div style="<?= $h3Style ?>">3. Hecho o motivo de la intervención</div></td></tr>
                <tr>
                  <td style="<?= $cardBody ?>">
                    <div style="font-size:13px; line-height:1.8; color:#111827; background:#FFFFFF; border:1px solid #E5E7EB; border-radius:10px; padding:12px; white-space:pre-line;">
                      <?= esc($hecho) ?>
                    </div>
                  </td>
                </tr>
              </table>

              <!-- 4. Faltas -->
              <?php if (!empty($faltas)): ?>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="<?= $cardStyle ?>">
                  <tr><td style="<?= $cardHead ?>"><div style="<?= $h3Style ?>">4. Presuntas faltas al RIT</div></td></tr>
                  <tr>
                    <td style="<?= $cardBody ?>">
                      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                        <?php foreach ($faltas as $idx => $f): ?>
                          <?php
                            $codigo      = '';
                            $gravedad    = '';
                            $descripcion = '';

                            if (is_array($f)) {
                              $codigo      = $f['codigo']      ?? '';
                              $gravedad    = $f['gravedad']    ?? '';
                              $descripcion = $f['descripcion'] ?? ($f['descripcion_falta'] ?? '');
                            } else {
                              $descripcion = (string) $f;
                            }

                            $prefix = trim($codigo . ($gravedad !== '' ? ' (' . $gravedad . ')' : ''));
                          ?>
                          <tr>
                            <td valign="top" style="width:18px; padding:8px 0; font-size:16px; line-height:1.6; color:<?= $accentGreen ?>;">
                              •
                            </td>
                            <td valign="top" style="padding:8px 0; font-size:13px; line-height:1.75; color:#111827;">
                              <?php if ($prefix !== ''): ?>
                                <strong style="color:#111827;"><?= esc($prefix) ?></strong>
                                <?php if ($descripcion !== ''): ?>:
                                  <?= esc($descripcion) ?>
                                <?php endif; ?>
                              <?php else: ?>
                                <?= esc($descripcion) ?>
                              <?php endif; ?>
                            </td>
                          </tr>
                          <tr><td colspan="2" style="height:1px; background:#E5E7EB;"></td></tr>
                        <?php endforeach; ?>
                      </table>
                    </td>
                  </tr>
                </table>
              <?php endif; ?>

              <!-- 5. Adjuntos -->
              <?php if (!empty($adjuntos)): ?>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="<?= $cardStyle ?>">
                  <tr><td style="<?= $cardHead ?>"><div style="<?= $h3Style ?>">5. Adjuntos (fase registro)</div></td></tr>
                  <tr>
                    <td style="<?= $cardBody ?>">
                      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                        <?php foreach ($adjuntos as $a): ?>
                          <?php $nombreArchivo = $a['nombre_original'] ?? $a['nombre'] ?? 'Archivo'; ?>
                          <tr>
                            <td valign="top" style="width:18px; padding:10px 0; font-size:16px; line-height:1.6; color:<?= $accentGreen ?>;">
                              •
                            </td>
                            <td valign="top" style="padding:10px 0; font-size:13px; line-height:1.75; color:#111827;">
                              <?= esc($nombreArchivo) ?>
                              <?php if (!empty($a['drive_web_view_link'])): ?>
                                <span style="color:#9CA3AF;">&nbsp;–&nbsp;</span>
                                <a href="<?= esc($a['drive_web_view_link']) ?>" target="_blank"
                                  style="color:<?= $accentGreen ?>; text-decoration:none; font-weight:700;">
                                  Ver en Drive
                                </a>
                              <?php endif; ?>
                            </td>
                          </tr>
                          <tr><td colspan="2" style="height:1px; background:#E5E7EB;"></td></tr>
                        <?php endforeach; ?>
                      </table>
                    </td>
                  </tr>
                </table>
              <?php endif; ?>

              <!-- Nota final -->
              <div style="background:#FFF7ED; border:1px solid #FED7AA; border-radius:12px; padding:12px 14px; margin-top:6px;">
                <div style="font-size:12px; line-height:1.6; color:#7C2D12;">
                  Este correo es informativo. Por favor, no lo respondas directamente si se envió desde una cuenta no monitoreada.
                </div>
              </div>

            </td>
          </tr>

          <!-- Footer corporativo con logo -->
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
                      © <?= date('Y') ?> CONTACTAMOS DE COLOMBIA S.A.S.<br>
                      <span style="opacity:.9;">FURD <?= esc($consecutivo) ?></span>
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