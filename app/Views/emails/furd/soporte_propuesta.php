<?php
/**
 * Email: Notificación de decisión propuesta (fase Soporte) al cliente.
 *
 * @var array  $furd
 * @var array  $soporte
 * @var string $urlAprobacion
 */

$nombreCliente     = trim($furd['empresa_usuaria'] ?? 'Cliente');
$consecutivo       = $furd['consecutivo'] ?? '';
$decisionPropuesta = $soporte['decision_propuesta'] ?? '';
$justificacion     = $soporte['justificacion'] ?? '';
$remitente         = config('Email')->fromName ?? 'Gestión de Procesos Disciplinarios';

$logoUrl   = 'https://drive.google.com/uc?export=view&id=1Vy4zS40rxWXwhwwHg9653xcf200gA1uq';
$bannerUrl = 'https://drive.google.com/uc?export=view&id=1F4qm94G8kkCYnH0ydVLJWHFvlVYcikis';

// $bannerUrl = $bannerUrl ?? base_url('assets/images/Logos/banner-2025.jpg');
// $logoUrl   = $logoUrl   ?? base_url('assets/images/Logos/logo-contactamos.png'); // ideal: versión “logo” con nombre corto

$brandGreen  = '#076633';
$accentGreen = '#198754';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <title>Revisión de decisión propuesta</title>
</head>

<body style="margin:0; padding:0; background:#F3F5F7; font-family:Arial, Helvetica, sans-serif; color:#24323f;">

  <!-- Preheader -->
  <div style="display:none; font-size:1px; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
    Proceso disciplinario <?= esc($consecutivo) ?> – Revisión de decisión propuesta.
  </div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F3F5F7; margin:0; padding:0;">
    <tr>
      <td align="center" style="padding:24px 12px;">

        <!-- Contenedor -->
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
          style="width:600px; max-width:600px; background:#FFFFFF; border-radius:16px; overflow:hidden; box-shadow:0 8px 30px rgba(15, 23, 42, 0.10);">

          <!-- Banner superior -->
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

          <!-- Línea de marca -->
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
                      Revisión de decisión propuesta
                    </div>
                    <div style="font-size:12px; color:#6B7280; margin-top:4px;">
                      Proceso disciplinario <?= esc($consecutivo) ?>
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

          <!-- Cuerpo -->
          <tr>
            <td style="padding:20px 22px 22px 22px; font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; color:#111827;">

              <p style="margin:0 0 18px; font-size:14px; line-height:1.75; color:#111827;">
                Hola, cliente <strong><?= esc($nombreCliente); ?></strong>:
              </p>

              <p style="margin:0 0 18px; font-size:14px; line-height:1.75; color:#111827;">
                Te informamos que se ha registrado una <strong>decisión propuesta</strong> dentro del proceso disciplinario con consecutivo
                <strong>#<?= esc($consecutivo); ?></strong>.
              </p>

              <?php
                $cardStyle = "background:#F9FAFB; border:1px solid #E5E7EB; border-left:5px solid {$brandGreen}; border-radius:12px; overflow:hidden; margin:0 0 18px 0;";
                $cardHead  = "padding:12px 14px; background:#FFFFFF; border-bottom:1px solid #E5E7EB;";
                $cardBody  = "padding:12px 14px;";
                $h3Style   = "font-size:14px; font-weight:800; color:#111827; margin:0;";
              ?>

              <!-- Resumen -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="<?= $cardStyle ?>">
                <tr>
                  <td style="<?= $cardHead ?>">
                    <div style="<?= $h3Style ?>">Resumen de la decisión propuesta</div>
                  </td>
                </tr>
                <tr>
                  <td style="<?= $cardBody ?>">

                    <p style="margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; color:#6B7280; font-weight:800;">
                      Decisión sugerida
                    </p>
                    <div style="margin:0 0 18px; font-size:15px; line-height:1.7; color:#111827; font-weight:800; background:#FFFFFF; border:1px solid #E5E7EB; border-radius:10px; padding:12px;">
                      <?= esc($decisionPropuesta); ?>
                    </div>

                    <p style="margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:0.5px; color:#6B7280; font-weight:800;">
                      Justificación
                    </p>
                    <div style="margin:0; font-size:13px; line-height:1.8; color:#111827; background:#FFFFFF; border:1px solid #E5E7EB; border-radius:10px; padding:12px; white-space:pre-line;">
                      <?= nl2br(esc($justificacion)); ?>
                    </div>

                  </td>
                </tr>
              </table>

              <!-- Instrucción -->
              <p style="margin:0 0 12px; font-size:14px; line-height:1.75; color:#111827;">
                Por favor revisa la propuesta e indícanos una de las siguientes opciones:
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                style="background:#F9FAFB; border:1px solid #E5E7EB; border-radius:12px; margin:0 0 22px 0;">
                <tr>
                  <td style="padding:14px 16px;">

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                      <tr>
                        <td valign="top" style="width:18px; padding:6px 0; font-size:16px; line-height:1.6; color:<?= $accentGreen ?>;">•</td>
                        <td valign="top" style="padding:6px 0; font-size:13px; line-height:1.75; color:#111827;">
                          Estás de acuerdo con la propuesta tal como está.
                        </td>
                      </tr>
                      <tr><td colspan="2" style="height:1px; background:#E5E7EB;"></td></tr>

                      <tr>
                        <td valign="top" style="width:18px; padding:6px 0; font-size:16px; line-height:1.6; color:<?= $accentGreen ?>;">•</td>
                        <td valign="top" style="padding:6px 0; font-size:13px; line-height:1.75; color:#111827;">
                          Deseas realizar ajustes.
                        </td>
                      </tr>
                      <tr><td colspan="2" style="height:1px; background:#E5E7EB;"></td></tr>

                      <tr>
                        <td valign="top" style="width:18px; padding:6px 0; font-size:16px; line-height:1.6; color:<?= $accentGreen ?>;">•</td>
                        <td valign="top" style="padding:6px 0; font-size:13px; line-height:1.75; color:#111827;">
                          No estás de acuerdo con la decisión propuesta.
                        </td>
                      </tr>
                    </table>

                  </td>
                </tr>
              </table>

              <?php if (!empty($urlAprobacion)): ?>
                <!-- Botón -->
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px;">
                  <tr>
                    <td align="center" style="border-radius:10px; background-color:<?= $accentGreen ?>;">
                      <a href="<?= esc($urlAprobacion) ?>"
                         style="display:inline-block; padding:14px 24px; font-size:14px; font-weight:800; color:#ffffff; text-decoration:none; border-radius:10px;">
                        Revisar y responder
                      </a>
                    </td>
                  </tr>
                </table>

                <!-- URL alterna -->
                <div style="margin:0 0 22px; padding:14px 16px; background:#FFF8E6; border:1px solid #F5DF9B; border-radius:12px;">
                  <p style="margin:0 0 8px; font-size:13px; font-weight:800; color:#7A5B00;">
                    ¿El botón no funciona?
                  </p>
                  <p style="margin:0; font-size:13px; line-height:1.7; color:#6B7280;">
                    Copia y pega este enlace en tu navegador:
                    <br>
                    <span style="color:#0D6EFD; word-break:break-all;"><?= esc($urlAprobacion) ?></span>
                  </p>
                </div>
              <?php endif; ?>

              <p style="margin:0 0 16px; font-size:14px; line-height:1.75; color:#111827;">
                Agradecemos tu pronta revisión.
              </p>

              <!-- Nota -->
              <div style="background:#FFF7ED; border:1px solid #FED7AA; border-radius:12px; padding:12px 14px; margin-top:6px;">
                <div style="font-size:12px; line-height:1.6; color:#7C2D12;">
                  Este es un mensaje informativo generado automáticamente. Por favor, no respondas directamente a este correo.
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
                      <span style="opacity:.9;"><?= esc($remitente); ?></span>
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