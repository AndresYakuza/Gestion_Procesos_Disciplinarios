<?php

/** @var array       $furd */
/** @var array       $soporte */
/** @var string|null $clienteNombre */
/** @var string|null $clienteEmail */

$estado            = $soporte['cliente_estado'] ?? 'pendiente';
$isSuspension      = strcasecmp($soporte['decision_propuesta'] ?? '', 'Suspensión disciplinaria') === 0;
$fechaInicioSusp   = $soporte['cliente_fecha_inicio_suspension'] ?? null;
$fechaFinSusp      = $soporte['cliente_fecha_fin_suspension'] ?? null;
$consecutivo       = $furd['consecutivo'] ?? '';
$decisionPropuesta = $soporte['decision_propuesta'] ?? '';
$justificacion     = $soporte['justificacion'] ?? '';
$remitente         = config('Email')->fromName ?? 'Gestión de Procesos Disciplinarios';

$badgeText = [
  'aprobado'  => 'Aprobado',
  'rechazado' => 'Rechazado',
  'pendiente' => 'Pendiente',
][$estado] ?? 'Pendiente';

$badgeColor = [
  'aprobado'  => '#198754',
  'rechazado' => '#dc3545',
  'pendiente' => '#f59e0b',
][$estado] ?? '#6b7280';

// Fallbacks por si no llegaron desde el controlador
$clienteNombre = $clienteNombre ?? ($furd['empresa_usuaria'] ?? '');
$clienteEmail  = $clienteEmail
  ?? ($furd['correo_cliente'] ?? $furd['email_cliente'] ?? $furd['correo_contacto'] ?? '');

$iniTxt = $fechaInicioSusp ? date('d/m/Y', strtotime($fechaInicioSusp)) : '—';
$finTxt = $fechaFinSusp ? date('d/m/Y', strtotime($fechaFinSusp)) : '—';

$respuestaFecha = !empty($soporte['cliente_respondido_at'])
  ? date('d/m/Y H:i', strtotime($soporte['cliente_respondido_at']))
  : 'No disponible';

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
  <title>Respuesta del cliente a la decisión propuesta</title>
</head>

<body style="margin:0; padding:0; background:#F3F5F7; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">

  <!-- Preheader -->
  <div style="display:none; font-size:1px; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
    Proceso disciplinario <?= esc($consecutivo) ?> – Respuesta del cliente frente a la decisión propuesta.
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

          <!-- Brand accent -->
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
                        padding:6px 12px;
                        border-radius:999px;
                        font-size:11px;
                        font-weight:800;
                        text-transform:uppercase;
                        letter-spacing:.05em;
                        background:<?= esc($badgeColor) ?>15;
                        color:<?= esc($badgeColor) ?>;
                        border:1px solid <?= esc($badgeColor) ?>33;
                      ">
                        <?= esc($badgeText) ?>
                      </span>
                    </div>

                    <div style="font-size:12px; letter-spacing:.2px; color:#6B7280;">
                      CONTACTAMOS DE COLOMBIA S.A.S.
                    </div>
                    <div style="font-size:20px; font-weight:800; line-height:1.25; margin-top:4px; color:#111827;">
                      Respuesta del cliente
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

          <!-- Body -->
          <tr>
            <td style="padding:20px 22px 22px 22px; font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; color:#111827;">

              <p style="margin:0 0 16px 0; font-size:14px; line-height:1.75; color:#111827;">
                Hola,
              </p>

              <p style="margin:0 0 18px 0; font-size:14px; line-height:1.75; color:#111827;">
                El cliente ha registrado una respuesta frente a la <strong>decisión propuesta</strong>. A continuación encontrarás el resumen de la información reportada.
              </p>

              <?php
                $cardStyle = "background:#F9FAFB; border:1px solid #E5E7EB; border-left:5px solid {$brandGreen}; border-radius:12px; overflow:hidden; margin:0 0 18px 0;";
                $cardHead  = "padding:12px 14px; background:#FFFFFF; border-bottom:1px solid #E5E7EB;";
                $cardBody  = "padding:12px 14px;";
                $h3Style   = "font-size:14px; font-weight:800; color:#111827; margin:0;";
              ?>

              <!-- Proceso -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="<?= $cardStyle ?>">
                <tr>
                  <td style="<?= $cardHead ?>">
                    <div style="<?= $h3Style ?>">Información del proceso</div>
                  </td>
                </tr>
                <tr>
                  <td style="<?= $cardBody ?>">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                      <tr>
                        <td valign="top" style="padding:8px 0; width:40%; font-size:13px; color:#374151;">
                          <strong style="color:#111827;">Consecutivo:</strong>
                        </td>
                        <td valign="top" style="padding:8px 0; font-size:13px; color:#111827;">
                          #<?= esc($consecutivo) ?>
                        </td>
                      </tr>
                      <tr><td colspan="2" style="height:1px; background:#E5E7EB;"></td></tr>
                      <tr>
                        <td valign="top" style="padding:8px 0; width:40%; font-size:13px; color:#374151;">
                          <strong style="color:#111827;">Estado de respuesta:</strong>
                        </td>
                        <td valign="top" style="padding:8px 0; font-size:13px; color:#111827;">
                          <span style="
                            display:inline-block;
                            padding:4px 10px;
                            border-radius:999px;
                            font-size:11px;
                            font-weight:800;
                            text-transform:uppercase;
                            letter-spacing:.05em;
                            background:<?= esc($badgeColor) ?>15;
                            color:<?= esc($badgeColor) ?>;
                            border:1px solid <?= esc($badgeColor) ?>33;
                          ">
                            <?= esc($badgeText) ?>
                          </span>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- Cliente -->
              <?php if ($clienteNombre !== '' || $clienteEmail !== ''): ?>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="<?= $cardStyle ?>">
                  <tr>
                    <td style="<?= $cardHead ?>">
                      <div style="<?= $h3Style ?>">Cliente que registró la respuesta</div>
                    </td>
                  </tr>
                  <tr>
                    <td style="<?= $cardBody ?>">
                      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">
                        <?php if ($clienteNombre !== ''): ?>
                          <tr>
                            <td valign="top" style="padding:8px 0; width:40%; font-size:13px; color:#374151;">
                              <strong style="color:#111827;">Nombre:</strong>
                            </td>
                            <td valign="top" style="padding:8px 0; font-size:13px; color:#111827;">
                              <?= esc($clienteNombre) ?>
                            </td>
                          </tr>
                        <?php endif; ?>

                        <?php if ($clienteNombre !== '' && $clienteEmail !== ''): ?>
                          <tr><td colspan="2" style="height:1px; background:#E5E7EB;"></td></tr>
                        <?php endif; ?>

                        <?php if ($clienteEmail !== ''): ?>
                          <tr>
                            <td valign="top" style="padding:8px 0; width:40%; font-size:13px; color:#374151;">
                              <strong style="color:#111827;">Correo:</strong>
                            </td>
                            <td valign="top" style="padding:8px 0; font-size:13px; color:#111827;">
                              <a href="mailto:<?= esc($clienteEmail) ?>" style="color:#2563eb; text-decoration:none;">
                                <?= esc($clienteEmail) ?>
                              </a>
                            </td>
                          </tr>
                        <?php endif; ?>
                      </table>
                    </td>
                  </tr>
                </table>
              <?php endif; ?>

              <!-- Resumen principal -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="<?= $cardStyle ?>">
                <tr>
                  <td style="<?= $cardHead ?>">
                    <div style="<?= $h3Style ?>">Resumen de la respuesta</div>
                  </td>
                </tr>
                <tr>
                  <td style="<?= $cardBody ?>">

                    <p style="margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:#6B7280; font-weight:800;">
                      Decisión propuesta originalmente
                    </p>
                    <div style="margin:0 0 16px; font-size:14px; line-height:1.75; color:#111827; font-weight:700; background:#FFFFFF; border:1px solid #E5E7EB; border-radius:10px; padding:12px;">
                      <?= esc($decisionPropuesta) ?>
                    </div>

                    <?php if (!empty($soporte['cliente_decision'])): ?>
                      <p style="margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:#6B7280; font-weight:800;">
                        Decisión o ajuste del cliente
                      </p>
                      <div style="margin:0 0 16px; font-size:13px; line-height:1.8; color:#111827; background:#FFFFFF; border:1px solid #E5E7EB; border-radius:10px; padding:12px; white-space:pre-line;">
                        <?= esc($soporte['cliente_decision']) ?>
                      </div>
                    <?php endif; ?>

                    <?php if ($isSuspension && ($fechaInicioSusp || $fechaFinSusp)): ?>
                      <p style="margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:#6B7280; font-weight:800;">
                        Período de suspensión reportado
                      </p>
                      <div style="margin:0; font-size:13px; line-height:1.8; color:#111827; background:#FFFFFF; border:1px solid #E5E7EB; border-radius:10px; padding:12px;">
                        Del <strong><?= esc($iniTxt) ?></strong> al <strong><?= esc($finTxt) ?></strong>
                      </div>
                    <?php endif; ?>

                  </td>
                </tr>
              </table>

              <!-- Justificación original -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="<?= $cardStyle ?>">
                <tr>
                  <td style="<?= $cardHead ?>">
                    <div style="<?= $h3Style ?>">Justificación original</div>
                  </td>
                </tr>
                <tr>
                  <td style="<?= $cardBody ?>">
                    <div style="font-size:13px; line-height:1.8; color:#111827; background:#FFFFFF; border:1px solid #E5E7EB; border-radius:10px; padding:12px; white-space:pre-line;">
                      <?= esc($justificacion) ?>
                    </div>
                  </td>
                </tr>
              </table>

              <!-- Justificación del cliente -->
              <?php if (!empty($soporte['cliente_justificacion'])): ?>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="<?= $cardStyle ?>">
                  <tr>
                    <td style="<?= $cardHead ?>">
                      <div style="<?= $h3Style ?>">Justificación del cliente</div>
                    </td>
                  </tr>
                  <tr>
                    <td style="<?= $cardBody ?>">
                      <div style="font-size:13px; line-height:1.8; color:#111827; background:#FFFFFF; border:1px solid #E5E7EB; border-radius:10px; padding:12px; white-space:pre-line;">
                        <?= esc($soporte['cliente_justificacion']) ?>
                      </div>
                    </td>
                  </tr>
                </table>
              <?php endif; ?>

              <!-- Comentario adicional -->
              <?php if (!empty($soporte['cliente_comentario'])): ?>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="<?= $cardStyle ?>">
                  <tr>
                    <td style="<?= $cardHead ?>">
                      <div style="<?= $h3Style ?>">Comentario adicional del cliente</div>
                    </td>
                  </tr>
                  <tr>
                    <td style="<?= $cardBody ?>">
                      <div style="font-size:13px; line-height:1.8; color:#111827; background:#FFFFFF; border:1px solid #E5E7EB; border-radius:10px; padding:12px; white-space:pre-line;">
                        <?= esc($soporte['cliente_comentario']) ?>
                      </div>
                    </td>
                  </tr>
                </table>
              <?php endif; ?>

              <!-- Fecha -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                style="background:#F9FAFB; border:1px solid #E5E7EB; border-radius:12px; margin:0 0 8px 0;">
                <tr>
                  <td style="padding:14px 16px; font-size:13px; line-height:1.7; color:#374151;">
                    <strong style="color:#111827;">Fecha y hora de respuesta:</strong>
                    <?= esc($respuestaFecha) ?>
                  </td>
                </tr>
              </table>

              <!-- Nota -->
              <div style="background:#FFF7ED; border:1px solid #FED7AA; border-radius:12px; padding:12px 14px; margin-top:6px;">
                <div style="font-size:12px; line-height:1.6; color:#7C2D12;">
                  Este correo fue generado automáticamente como parte del seguimiento del proceso disciplinario.
                </div>
              </div>

            </td>
          </tr>

          <!-- Footer corporativo -->
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