<p>
  Estimado(a) cliente
  <strong><?= esc($furd['empresa_usuaria'] ?? '') ?></strong>,
</p>

<p>
  Hace cinco (5) días se le remitió la decisión propuesta dentro del proceso disciplinario
  <strong><?= esc($furd['consecutivo'] ?? '') ?></strong>,
  correspondiente al trabajador
  <strong><?= esc($furd['nombre_completo'] ?? '') ?></strong>
  (CC <?= esc($furd['cedula'] ?? '') ?>).
</p>

<p>
  De acuerdo con el reglamento interno de trabajo, cuenta con un plazo total de
  <strong>diez (10) días calendario</strong> para aprobar o rechazar formalmente la propuesta.
  En caso de no recibir respuesta dentro de los próximos cinco (5) días,
  el proceso será archivado automáticamente por vencimiento del término.
</p>

<p>
  Para registrar su decisión, por favor ingrese al enlace remitido previamente para la
  aprobación o rechazo del proceso.
</p>

<p>Cordialmente,<br>Gestión de Procesos Disciplinarios</p>
