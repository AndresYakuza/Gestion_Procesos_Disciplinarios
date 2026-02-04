<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f5f7; padding:24px 0;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; border-radius:8px; box-shadow:0 2px 6px rgba(15,23,42,0.08); overflow:hidden; font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; color:#111827;">
        <tr>
          <td style="padding:24px 28px 20px 28px;">
            <!-- Encabezado -->
            <p style="margin:0 0 6px 0; font-size:13px; color:#6b7280;">
              Notificación de vencimiento de término
            </p>
            <h2 style="margin:0 0 16px 0; font-size:20px; color:#111827;">
              Proceso disciplinario
              <span style="color:#16a34a;"><?= esc($furd['consecutivo'] ?? '') ?></span>
            </h2>

            <!-- Saludo -->
            <p style="margin:0 0 16px 0; font-size:14px; line-height:1.6;">
              Estimado cliente,
              <strong><?= esc($furd['empresa_usuaria'] ?? '') ?></strong>,
            </p>

            <!-- Bloque resumen -->
            <div style="margin:0 0 18px 0; padding:12px 14px; background-color:#f9fafb; border-radius:6px; border-left:4px solid #16a34a;">
              <p style="margin:0 0 6px 0; font-size:13px; font-weight:600; color:#111827;">
                Resumen del proceso
              </p>
              <p style="margin:0; font-size:13px; line-height:1.6; color:#4b5563;">
                Trabajador:
                <strong><?= esc($furd['nombre_completo'] ?? '') ?></strong><br>
                Cédula:
                <strong><?= esc($furd['cedula'] ?? '') ?></strong>
              </p>
            </div>

            <!-- Cuerpo principal -->
            <p style="margin:0 0 12px 0; font-size:14px; line-height:1.7; color:#374151;">
              Hace cinco (5) días se le remitió la decisión propuesta dentro del proceso disciplinario
              <strong><?= esc($furd['consecutivo'] ?? '') ?></strong>,
              correspondiente al trabajador mencionado.
            </p>

            <p style="margin:0 0 12px 0; font-size:14px; line-height:1.7; color:#374151;">
              De acuerdo con el reglamento interno de trabajo, cuenta con un plazo total de
              <strong>diez (10) días calendario</strong> para aprobar o rechazar formalmente la propuesta.
            </p>

            <p style="margin:0 0 16px 0; font-size:14px; line-height:1.7; color:#374151;">
              En caso de no recibir respuesta dentro de los próximos cinco (5) días,
              el proceso será archivado automáticamente por vencimiento del término.
            </p>

            <!-- Recordatorio de acción -->
            <div style="margin:0 0 20px 0; padding:10px 14px; background-color:#eff6ff; border-radius:6px; border:1px solid #bfdbfe;">
              <p style="margin:0; font-size:13px; line-height:1.6; color:#1f2937;">
                Para registrar su decisión, ingrese al enlace remitido previamente para la
                aprobación o rechazo del proceso disciplinario.
              </p>
            </div>

            <!-- Firma -->
            <p style="margin:0; font-size:14px; line-height:1.6; color:#374151;">
              Cordialmente,<br>
              <strong>Gestión de Procesos Disciplinarios</strong>
            </p>

            <!-- Nota inferior -->
            <p style="margin:20px 0 0 0; font-size:11px; line-height:1.5; color:#9ca3af; border-top:1px solid #e5e7eb; padding-top:10px;">
              Este correo es informativo. Por favor, no lo respondas directamente si se envió desde una cuenta no monitoreada.
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
