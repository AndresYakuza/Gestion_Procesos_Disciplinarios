<p>
  Estimado(a) cliente <strong><?= esc($furd['empresa_usuaria'] ?? '') ?></strong>,
  Y 
  Estimado(a) trabajador(a) <strong><?= esc($furd['nombre_completo'] ?? '') ?></strong>,
</p>

<p>
  De manera atenta informamos que el proceso disciplinario
  <strong><?= esc($furd['consecutivo'] ?? '') ?></strong>,
  adelantado respecto de su vínculo laboral con
  <strong><?= esc($furd['empresa_usuaria'] ?? 'la compañía') ?></strong>,
  ha sido <strong>archivado automáticamente</strong>.
</p>

<p>
  Lo anterior, en razón a que no se obtuvo respuesta por parte del cliente interno
  frente a la decisión propuesta dentro del término máximo de
  <strong>diez (10) días calendario</strong>, de conformidad con lo previsto
  en el reglamento interno de trabajo y demás normas aplicables.
</p>

<p>
  Para cualquier inquietud adicional sobre este cierre, puede comunicarse con
  el área de Gestión de Procesos Disciplinarios.
</p>

<p>Cordialmente,<br>Gestión de Procesos Disciplinarios</p>
