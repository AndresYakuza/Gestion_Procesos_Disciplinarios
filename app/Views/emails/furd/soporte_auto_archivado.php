<?php
/**
 * Vista email: Archivo automático de proceso disciplinario
 *
 * Variables esperadas:
 * - $furd (array): consecutivo, empresa_usuaria, nombre_completo, etc.
 */

$consecutivo = $furd['consecutivo'] ?? '';
$empresa     = $furd['empresa_usuaria'] ?? 'la compañía';
$trabajador  = $furd['nombre_completo'] ?? 'Trabajador';

$logoUrl   = 'https://drive.google.com/uc?export=view&id=1Vy4zS40rxWXwhwwHg9653xcf200gA1uq';
$bannerUrl = 'https://drive.google.com/uc?export=view&id=1F4qm94G8kkCYnH0ydVLJWHFvlVYcikis';

// $bannerUrl = $bannerUrl ?? base_url('assets/images/Logos/banner-2025.jpg');
// $logoUrl   = $logoUrl   ?? base_url('assets/images/Logos/logo-contactamos.png'); // ideal: versión “logo” con nombre corto

$brandGreen  = '#076633';
$accentGreen = '#198754';

$cardStyle = "background:#F9FAFB; border:1px solid #E5E7EB; border-left:5px solid {$brandGreen}; border-radius:12px; overflow:hidden; margin:0 0 18px 0;";
$cardHead  = "padding:12px 14px; background:#FFFFFF; border-bottom:1px solid #E5E7EB;";
$cardBody  = "padding:12px 14px;";
$h3Style   = "font-size:14px; font-weight:800; color:#111827; margin:0;";
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>Archivo proceso disciplinario <?= esc($consecutivo) ?></title>
</head>

<body style="margin:0; padding:0; background:#F3F5F7; font-family:Arial, Helvetica, sans-serif; color:#333333;">

  <!-- Preheader -->
  <div style="display:none; font-size:1px; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
    Comunicación formal de cierre del proceso disciplinario <?= esc($consecutivo) ?>.
  </div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F3F5F7; margin:0; padding:0;">
    <tr>
      <td align="center" style="padding:24px 12px;">

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
                    <div style="font-size:12px; letter-spacing:.2px; color:#6B7280;">
                      CONTACTAMOS DE COLOMBIA S.A.S.
                    </div>
                    <div style="font-size:20px; font-weight:800; line-height:1.25; margin-top:4px; color:#111827;">
                      Archivo de proceso disciplinario
                    </div>
                    <div style="font-size:12px; color:#6B7280; margin-top:4px;">
                      Comunicación formal de cierre
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

              <p style="margin:0 0 12px 0; font-size:14px; line-height:1.75; color:#111827;">
                Estimado cliente, <strong><?= esc($empresa) ?></strong>,<br>
                Estimado(a) trabajador(a), <strong><?= esc($trabajador) ?></strong>,
              </p>

              <p style="margin:0 0 14px 0; font-size:14px; line-height:1.75; color:#111827;">
                De manera atenta informamos que el proceso disciplinario
                <strong><?= esc($consecutivo) ?></strong>, adelantado respecto de su vínculo laboral con
                <strong><?= esc($empresa) ?></strong>, ha sido <strong>archivado automáticamente</strong>.
              </p>

              <!-- Resumen -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="<?= $cardStyle ?>">
                <tr>
                  <td style="<?= $cardHead ?>">
                    <div style="<?= $h3Style ?>">Resumen del cierre</div>
                  </td>
                </tr>
                <tr>
                  <td style="<?= $cardBody ?>">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                      <tr>
                        <td valign="top" style="padding:8px 0; width:38%; font-size:13px; color:#374151;">
                          <strong style="color:#111827;">Consecutivo:</strong>
                        </td>
                        <td valign="top" style="padding:8px 0; font-size:13px; color:#111827;">
                          <?= esc($consecutivo) ?>
                        </td>
                      </tr>
                      <tr><td colspan="2" style="height:1px; background:#E5E7EB;"></td></tr>
                      <tr>
                        <td valign="top" style="padding:8px 0; width:38%; font-size:13px; color:#374151;">
                          <strong style="color:#111827;">Estado final:</strong>
                        </td>
                        <td valign="top" style="padding:8px 0; font-size:13px; color:#111827;">
                          Archivo automático del proceso
                        </td>
                      </tr>
                      <tr><td colspan="2" style="height:1px; background:#E5E7EB;"></td></tr>
                      <tr>
                        <td valign="top" style="padding:8px 0; width:38%; font-size:13px; color:#374151;">
                          <strong style="color:#111827;">Empresa usuaria:</strong>
                        </td>
                        <td valign="top" style="padding:8px 0; font-size:13px; color:#111827;">
                          <?= esc($empresa) ?>
                        </td>
                      </tr>
                      <tr><td colspan="2" style="height:1px; background:#E5E7EB;"></td></tr>
                      <tr>
                        <td valign="top" style="padding:8px 0; width:38%; font-size:13px; color:#374151;">
                          <strong style="color:#111827;">Trabajador:</strong>
                        </td>
                        <td valign="top" style="padding:8px 0; font-size:13px; color:#111827;">
                          <?= esc($trabajador) ?>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- Motivo -->
              <div style="margin:0 0 18px; padding:14px 16px; background:#FFF8E6; border:1px solid #F5DF9B; border-radius:12px;">
                <p style="margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:#7A5B00; font-weight:800;">
                  Motivo del archivo
                </p>
                <p style="margin:0; font-size:13px; line-height:1.8; color:#7A5B00;">
                  No se obtuvo respuesta por parte del cliente interno frente a la decisión propuesta
                  dentro del término máximo de <strong>diez (10) días calendario</strong>, de conformidad con
                  lo previsto en el reglamento interno de trabajo y demás normas aplicables.
                </p>
              </div>

              <p style="margin:0 0 16px 0; font-size:14px; line-height:1.75; color:#111827;">
                Para cualquier inquietud adicional sobre este cierre, pueden comunicarse con el área de
                <strong>Gestión de Procesos Disciplinarios</strong>.
              </p>

              <p style="margin:0; font-size:14px; line-height:1.75; color:#111827;">
                Cordialmente,<br>
                <strong>Gestión de Procesos Disciplinarios</strong>
              </p>

              <!-- Nota -->
              <div style="background:#F9FAFB; border:1px solid #E5E7EB; border-radius:12px; padding:12px 14px; margin-top:18px;">
                <div style="font-size:12px; line-height:1.6; color:#6B7280;">
                  Este correo es informativo. Por favor, no lo respondas directamente si fue enviado desde una cuenta no monitoreada.
                </div>
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
                      © <?= date('Y') ?> CONTACTAMOS DE COLOMBIA S.A.S.<br>
                      <span style="opacity:.9;">Archivo proceso <?= esc($consecutivo) ?></span>
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