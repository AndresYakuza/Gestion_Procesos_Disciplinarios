<?php
/**
 * Vista email: Notificación de citación al trabajador
 * Archivo: app/Views/emails/furd/citacion_trabajador.php
 *
 * Variables esperadas:
 * - $furd (array): consecutivo, nombre, cedula, proyecto, empresa_usuaria, superior
 * - $citacion (array): numero, fecha_evento, hora, medio, motivo, motivo_recitacion
 */

$consecutivo = esc($furd['consecutivo'] ?? 'N/D');
$nombre      = esc($furd['nombre'] ?? $furd['nombre_completo'] ?? 'Trabajador');
$cedula      = esc($furd['cedula'] ?? 'N/D');
$proyecto    = esc($furd['proyecto'] ?? 'N/D');

$numeroCit   = esc((string)($citacion['numero'] ?? '1'));
$fechaEvento = !empty($citacion['fecha_evento'])
    ? esc(date('d/m/Y', strtotime((string)$citacion['fecha_evento'])))
    : 'N/D';
$horaEvento  = esc($citacion['hora'] ?? 'N/D');
$medio       = esc($citacion['medio'] ?? 'N/D');

$motivo      = trim((string)($citacion['motivo'] ?? ''));
$motivoRecit = trim((string)($citacion['motivo_recitacion'] ?? ''));

// Opcional: si luego quieres link a portal de detalle
$urlProceso = site_url('linea-tiempo/' . ($furd['consecutivo'] ?? ''));
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Notificación de citación</title>
  <style>
    /* Reset básico para email */
    body, table, td, p, a { -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }
    table, td { mso-table-lspace:0pt; mso-table-rspace:0pt; }
    img { border:0; outline:none; text-decoration:none; -ms-interpolation-mode:bicubic; }
    table { border-collapse:collapse !important; }

    body {
      margin:0 !important;
      padding:0 !important;
      width:100% !important;
      background:#f4f6f8;
      font-family: Arial, Helvetica, sans-serif;
      color:#1f2937;
    }

    .wrapper {
      width:100%;
      background:#f4f6f8;
      padding:24px 0;
    }

    .container {
      width:100%;
      max-width:680px;
      margin:0 auto;
      background:#ffffff;
      border:1px solid #e5e7eb;
      border-radius:12px;
      overflow:hidden;
    }

    .header {
      background:linear-gradient(135deg,#0f766e,#0ea5a6);
      color:#ffffff;
      padding:20px 24px;
    }

    .header h1 {
      margin:0;
      font-size:20px;
      line-height:1.3;
      font-weight:700;
    }

    .header p {
      margin:6px 0 0 0;
      font-size:13px;
      opacity:0.95;
    }

    .content {
      padding:24px;
    }

    .badge {
      display:inline-block;
      font-size:12px;
      font-weight:700;
      color:#065f46;
      background:#d1fae5;
      border:1px solid #a7f3d0;
      border-radius:999px;
      padding:6px 10px;
      margin-bottom:14px;
    }

    .intro {
      margin:0 0 14px 0;
      font-size:14px;
      line-height:1.6;
    }

    .card {
      border:1px solid #e5e7eb;
      border-radius:10px;
      background:#fafafa;
      padding:14px;
      margin:14px 0;
    }

    .card h3 {
      margin:0 0 10px 0;
      font-size:14px;
      color:#111827;
    }

    .meta-table {
      width:100%;
      border-collapse:collapse;
      font-size:13px;
    }

    .meta-table td {
      padding:8px 0;
      border-bottom:1px dashed #e5e7eb;
      vertical-align:top;
    }

    .meta-table tr:last-child td {
      border-bottom:none;
    }

    .label {
      width:38%;
      color:#6b7280;
      font-weight:600;
      padding-right:10px;
    }

    .value {
      width:62%;
      color:#111827;
      font-weight:500;
    }

    .motivo {
      margin-top:10px;
      font-size:13px;
      line-height:1.6;
      white-space:pre-line;
      color:#374151;
    }

    .cta-wrap {
      text-align:center;
      margin:22px 0 8px;
    }

    .btn {
      display:inline-block;
      background:#0f766e;
      color:#ffffff !important;
      text-decoration:none;
      font-size:14px;
      font-weight:700;
      padding:11px 18px;
      border-radius:8px;
    }

    .note {
      margin-top:14px;
      font-size:12px;
      color:#6b7280;
      line-height:1.5;
    }

    .footer {
      padding:16px 24px 22px;
      border-top:1px solid #e5e7eb;
      background:#fcfcfd;
      font-size:12px;
      color:#6b7280;
      line-height:1.6;
    }

    .mono {
      font-family: Consolas, Monaco, 'Courier New', monospace;
      font-size:12px;
      color:#374151;
    }

    @media screen and (max-width:600px){
      .content, .header, .footer { padding:18px !important; }
      .label, .value { display:block; width:100%; }
      .label { padding-bottom:2px; }
    }
  </style>
</head>
<body>
  <table role="presentation" class="wrapper" width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td align="center">

        <table role="presentation" class="container" width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td class="header">
              <h1>Notificación de citación disciplinaria</h1>
              <p>Proceso <?= $consecutivo; ?> · Citación #<?= $numeroCit; ?></p>
            </td>
          </tr>

          <tr>
            <td class="content">
              <span class="badge">Acción requerida</span>

              <p class="intro">
                Hola <strong><?= $nombre; ?></strong>, se ha registrado una citación dentro del proceso disciplinario
                <strong><?= $consecutivo; ?></strong>.
              </p>

              <div class="card">
                <h3>Datos de la citación</h3>

                <table role="presentation" class="meta-table" width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td class="label">Consecutivo</td>
                    <td class="value"><?= $consecutivo; ?></td>
                  </tr>
                  <tr>
                    <td class="label">Citación N.º</td>
                    <td class="value"><?= $numeroCit; ?></td>
                  </tr>
                  <tr>
                    <td class="label">Fecha</td>
                    <td class="value"><?= $fechaEvento; ?></td>
                  </tr>
                  <tr>
                    <td class="label">Hora</td>
                    <td class="value"><?= $horaEvento; ?></td>
                  </tr>
                  <tr>
                    <td class="label">Medio</td>
                    <td class="value"><?= $medio; ?></td>
                  </tr>
                  <tr>
                    <td class="label">Trabajador</td>
                    <td class="value"><?= $nombre; ?> · CC <?= $cedula; ?></td>
                  </tr>
                  <tr>
                    <td class="label">Proyecto</td>
                    <td class="value"><?= $proyecto; ?></td>
                  </tr>
                </table>

                <?php if ($motivo !== ''): ?>
                  <div class="motivo">
                    <strong>Motivo:</strong><br>
                    <?= nl2br(esc($motivo)); ?>
                  </div>
                <?php endif; ?>

                <?php if ($motivoRecit !== ''): ?>
                  <div class="motivo">
                    <strong>Motivo de nueva citación:</strong><br>
                    <?= nl2br(esc($motivoRecit)); ?>
                  </div>
                <?php endif; ?>
              </div>

              <div class="cta-wrap">
                <a href="<?= esc($urlProceso); ?>" class="btn" target="_blank" rel="noopener">
                  Ver detalle del proceso
                </a>
              </div>

              <p class="note">
                Este correo es informativo y hace parte de la trazabilidad del proceso.
                Si consideras que hay un error en la información, comunícate con Gestión de Procesos Disciplinarios.
              </p>
            </td>
          </tr>

          <tr>
            <td class="footer">
              <strong>Gestión de Procesos Disciplinarios</strong><br>
              Mensaje generado automáticamente.<br>
              <span class="mono">Ref: <?= $consecutivo; ?> / CIT-<?= $numeroCit; ?></span>
            </td>
          </tr>
        </table>

      </td>
    </tr>
  </table>
</body>
</html>
