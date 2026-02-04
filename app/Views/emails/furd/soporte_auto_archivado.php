<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Archivo proceso disciplinario <?= esc($furd['consecutivo'] ?? '') ?></title>
</head>
<body style="font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 14px; color: #333; background-color:#f5f5f5; margin:0; padding:24px 0;">
  <div style="max-width:720px; margin:0 auto; background:#ffffff; padding:24px 28px; border-radius:8px; border:1px solid #e0e0e0;">
    
    <!-- Encabezado -->
    <h2 style="margin:0 0 8px 0; font-size:18px; color:#198754;">
      Archivo de proceso disciplinario <?= esc($furd['consecutivo'] ?? '') ?>
    </h2>
    <p style="margin:0 0 20px 0; color:#555;">
      Comunicación formal de cierre del proceso disciplinario.
    </p>

    <!-- Saludo -->
    <p style="margin:0 0 16px 0;">
      Estimado cliente,
      <strong><?= esc($furd['empresa_usuaria'] ?? '') ?></strong>,<br>
      Estimado(a) trabajador(a),
      <strong><?= esc($furd['nombre_completo'] ?? '') ?></strong>,
    </p>

    <!-- Cuerpo principal -->
    <p style="margin:0 0 12px 0; line-height:1.6;">
      De manera atenta informamos que el proceso disciplinario
      <strong><?= esc($furd['consecutivo'] ?? '') ?></strong>,
      adelantado respecto de su vínculo laboral con
      <strong><?= esc($furd['empresa_usuaria'] ?? 'la compañía') ?></strong>,
      ha sido <strong>archivado automáticamente</strong>.
    </p>

    <p style="margin:0 0 12px 0; line-height:1.6;">
      Lo anterior, en razón a que no se obtuvo respuesta por parte del cliente interno
      frente a la decisión propuesta dentro del término máximo de
      <strong>diez (10) días calendario</strong>, de conformidad con lo previsto
      en el reglamento interno de trabajo y demás normas aplicables.
    </p>

    <p style="margin:0 0 16px 0; line-height:1.6;">
      Para cualquier inquietud adicional sobre este cierre, pueden comunicarse con el área de
      <strong>Gestión de Procesos Disciplinarios</strong>.
    </p>

    <!-- Despedida -->
    <p style="margin:0; line-height:1.5;">
      Cordialmente,<br>
      <strong>Gestión de Procesos Disciplinarios</strong>
    </p>
  </div>

  <p style="max-width:720px; margin:12px auto 0 auto; font-size:11px; color:#888; text-align:center;">
    Este correo es informativo. Por favor, no lo responda directamente si se envió desde una cuenta no monitoreada.
  </p>
</body>
</html>
