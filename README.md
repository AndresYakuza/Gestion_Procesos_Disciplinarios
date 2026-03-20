# Gestión de Procesos Disciplinarios (GPD)

Aplicación web construida sobre CodeIgniter 4 para gestionar el ciclo disciplinario completo de un colaborador, desde el registro inicial del FURD hasta la decisión final y el seguimiento por parte del cliente.

El sistema integra:

- Registro de procesos disciplinarios con consecutivo `PD-000001`.
- Gestión por fases: `registro`, `citación`, `descargos`, `soporte`, `decisión`.
- Generación automática de documentos en Google Drive, Google Docs y Google Sheets.
- Envío de correos y notificaciones.
- Creación de eventos de Google Calendar con enlace de Google Meet.
- Portal de consulta para cliente.
- Seguimiento interno con línea de tiempo del proceso.

## Flujo funcional

### 1. Registro FURD

- Se crea el proceso base en `tbl_furd`.
- Se genera el consecutivo con prefijo `PD-`.
- Se asocian faltas disciplinarias desde `tbl_rit_faltas`.
- Se suben evidencias a Google Drive.
- Se genera el formato RH-FO23 en Google Sheets y se exporta a PDF.
- Se envían correos de notificación al trabajador y al área de procesos.

### 2. Citación

- Permite generar una o varias citaciones mientras no existan descargos, soporte o decisión.
- Soporta medios `presencial`, `virtual` y `escrito`.
- Si la citación es virtual, puede crear evento en Google Calendar con enlace Meet.
- Genera el documento RH-FO67 desde plantilla en Google Docs/Drive.
- Envía la citación al trabajador por correo con el DOCX adjunto.

### 3. Descargos

- Requiere al menos una citación previa.
- Si la citación fue marcada como `escrito`, esta fase se omite.
- Genera el acta RH-FO69 y la sube a Google Drive.
- Permite adjuntar soportes complementarios de la fase.

### 4. Soporte

- Se habilita cuando ya existen descargos o cuando la citación fue con descargo escrito.
- Registra responsable, propuesta de decisión y justificación.
- Permite adjuntar archivos de soporte.
- Envía correo al cliente para revisión de la propuesta.
- Tiene proceso de recordatorio y auto-archivo por línea de comandos.

### 5. Decisión

- Requiere soporte previo.
- Registra la decisión final del proceso.
- Soporta validaciones adicionales para suspensión disciplinaria.
- Permite adjuntar documentos finales.

### 6. Seguimiento y portal cliente

- `Seguimiento` muestra el estado interno de todos los procesos.
- `Línea de tiempo` consolida las fases, adjuntos y respuesta del cliente.
- `Portal cliente` permite consultar procesos por `correo_cliente`, revisar estado y consultar historial.

## Reglas de negocio relevantes

Las reglas principales están centralizadas en `App\Domain\Furd\FurdWorkflow`:

- No se puede iniciar una fase si no existe la fase previa requerida.
- Sí se permite editar fases ya creadas.
- La fase `soporte` puede arrancar sin descargos solo si la citación fue con `medio = escrito`.
- No se permite duplicar descargos, soporte o decisión.
- Se permiten múltiples citaciones mientras el proceso no haya avanzado a fases posteriores.

## Stack técnico

- PHP 8.1+
- CodeIgniter 4
- MySQL o MariaDB
- Google API Client
- PhpSpreadsheet
- PhpWord
- mPDF

Dependencias principales declaradas en `composer.json`:

- `codeigniter4/framework`
- `google/apiclient`
- `phpoffice/phpspreadsheet`
- `phpoffice/phpword`
- `mpdf/mpdf`
- `luisplata/festivos-colombia`

## Estructura principal del proyecto

```text
app/
  Commands/        Comandos CLI de sincronización y recordatorios
  Config/          Rutas, filtros y configuración base
  Controllers/     Flujo web del proceso disciplinario
  Domain/Furd/     Reglas de negocio del workflow
  Filters/         Protección por PIN para ajustes
  Libraries/       Integraciones con Google y Sorttime
  Models/          Acceso a tablas principales
  Requests/        Reglas de validación de formularios
  Services/        Generación de documentos y correos
  Views/           Vistas del sistema, emails y portal cliente
public/
  assets/js/       JavaScript del flujo FURD y portal cliente
writable/
  logs/            Logs del sistema
  tmp/             Temporales de generación de archivos/correos
  keys/            JSON de service account de Google
```

## Módulos principales

| Módulo | Responsabilidad principal |
| --- | --- |
| `FurdController` | Registro inicial del proceso, faltas, evidencias y formato FURD |
| `CitacionController` | Registro y generación de citaciones |
| `DescargosController` | Registro de descargos y generación de acta |
| `SoporteController` | Propuesta al cliente, revisión pública y respuesta |
| `DecisionController` | Registro de decisión final |
| `SeguimientoController` | Listado interno de procesos |
| `LineaTiempoController` | Detalle cronológico del proceso |
| `PortalClienteController` | Consulta de procesos para cliente |
| `RitFaltaController` | Catálogo de faltas disciplinarias |
| `AdjuntosController` | Descarga, visualización y eliminación de adjuntos |
| `EmpleadoLookupController` | Consulta de empleado por cédula |

## Servicios e integraciones

### Google Drive

`App\Libraries\GDrive` se encarga de:

- Crear carpetas en una unidad compartida.
- Copiar plantillas.
- Subir archivos binarios.
- Exportar archivos de Google a DOCX o PDF.
- Descargar archivos para adjuntarlos por correo.

Variables clave:

- `GDRIVE_SA_JSON`
- `GDRIVE_SHARED_DRIVE_ID`
- `GDRIVE_ROOT`
- `GDRIVE_SHARE_DOMAIN` opcional
- `GDRIVE_IMPERSONATE` opcional

### Google Sheets

`App\Services\FurdGoogleFormatService`:

- Copia la plantilla RH-FO23.
- Reemplaza valores en celdas.
- Ajusta formato.
- Exporta a PDF.
- Guarda el resultado en Drive.

Variables clave:

- `GOOGLE_SHEETS_TEMPLATE_ID`
- `GOOGLE_SHEETS_TEMPLATE_SHEET_NAME`

### Google Docs / Word

Servicios que generan documentos DOCX a partir de plantillas:

- `App\Services\CitacionDocxService`
- `App\Services\DescargosActaDocxService`

Variables clave:

- `GOOGLE_DOC_TEMPLATE_CITACION_VIRTUAL`
- `GOOGLE_DOC_TEMPLATE_CITACION_ESCRITO`
- `GOOGLE_DOC_TEMPLATE_CITACION_PRESENCIAL`
- `GOOGLE_DOC_TEMPLATE_DESCARGOS`
- `GOOGLE_DOC_TEMPLATE_DECISION`

Nota:

- `GOOGLE_DOC_TEMPLATE_DECISION` actualmente se usa para descargar una plantilla de decisión, no para generar automáticamente un documento final de decisión.

### Google Calendar / Meet

`App\Libraries\GCalendar` crea eventos con enlace de Google Meet para citaciones virtuales.

Variables clave:

- `GCALENDAR_CALENDAR_ID`
- `GCALENDAR_IMPERSONATE`

### Correo

`App\Services\FurdMailService` administra:

- Notificación de citación al trabajador.
- Notificación de propuesta de soporte al cliente.
- Recordatorios de soporte.
- Auto-archivo por vencimiento.

Variables clave:

- `email.fromEmail`
- `email.fromName`
- `email.protocol`
- `email.SMTPHost`
- `email.SMTPUser`
- `email.SMTPPass`
- `email.SMTPPort`
- `email.SMTPCrypto`
- `gpd.correoGestionProcesos`

### Sorttime

`App\Libraries\SorttimeClient` consume el servicio externo de Sorttime para sincronización de empleados y contratos.

Variables clave:

- `sorttime.baseURI`
- `sorttime.nit`

## Variables de entorno usadas por el proyecto

Estas son las variables observadas actualmente en uso dentro del proyecto:

| Variable | Uso |
| --- | --- |
| `CI_ENVIRONMENT` | Ambiente de ejecución |
| `app.baseURL` | URL base del proyecto |
| `database.default.hostname` | Host de base de datos |
| `database.default.database` | Nombre de la base de datos |
| `database.default.username` | Usuario de base de datos |
| `database.default.password` | Contraseña de base de datos |
| `database.default.DBDriver` | Driver de BD |
| `database.default.charset` | Charset de conexión |
| `sorttime.baseURI` | Endpoint base del servicio Sorttime |
| `sorttime.nit` | NIT usado para autenticación Sorttime |
| `GDRIVE_SA_JSON` | Ruta al JSON de la service account |
| `GDRIVE_SHARED_DRIVE_ID` | ID de la unidad compartida en Drive |
| `GDRIVE_ROOT` | Carpeta raíz lógica en Drive |
| `GDRIVE_SHARE_DOMAIN` | Permisos por dominio, opcional |
| `GDRIVE_IMPERSONATE` | Impersonación de dominio para Drive, opcional |
| `GOOGLE_SHEETS_TEMPLATE_ID` | Plantilla base del formato FURD |
| `GOOGLE_SHEETS_TEMPLATE_SHEET_NAME` | Hoja a editar dentro de la plantilla |
| `GOOGLE_DOC_TEMPLATE_CITACION_VIRTUAL` | Plantilla de citación virtual |
| `GOOGLE_DOC_TEMPLATE_CITACION_ESCRITO` | Plantilla de citación escrita |
| `GOOGLE_DOC_TEMPLATE_CITACION_PRESENCIAL` | Plantilla de citación presencial |
| `GOOGLE_DOC_TEMPLATE_DESCARGOS` | Plantilla del acta de descargos |
| `GOOGLE_DOC_TEMPLATE_DECISION` | Plantilla descargable para decisión |
| `email.fromEmail` | Remitente del sistema |
| `email.fromName` | Nombre visible del remitente |
| `email.protocol` | Protocolo de correo |
| `email.SMTPHost` | Servidor SMTP |
| `email.SMTPUser` | Usuario SMTP |
| `email.SMTPPass` | Contraseña SMTP |
| `email.SMTPPort` | Puerto SMTP |
| `email.SMTPCrypto` | Cifrado SMTP |
| `gpd.correoGestionProcesos` | Correo de copia o gestión interna |
| `GCALENDAR_CALENDAR_ID` | ID del calendario de citaciones |
| `GCALENDAR_IMPERSONATE` | Usuario delegado para Calendar |
| `AJUSTES_PIN` | PIN de acceso al módulo de ajustes |

## Requisitos de infraestructura

- PHP 8.1 o superior
- Composer
- MySQL o MariaDB
- Extensiones PHP:
  - `intl`
  - `mbstring`
  - `json`
  - `curl`
  - `mysqlnd`
  - `zip`
  - `gd` o librerías compatibles con generación de documentos si aplica
- Acceso a Google Drive y, si se usa citación virtual, a Google Calendar
- JSON de service account con permisos suficientes

## Instalación local

### 1. Instalar dependencias PHP

```bash
composer install
```

### 2. Crear y ajustar el archivo de entorno

```bash
copy env .env
```

Luego configura los valores reales en [`.env`](./.env):

- Base URL
- Base de datos
- Credenciales SMTP
- IDs de plantillas Google
- IDs de Drive y Calendar

### 3. Ubicar el JSON de Google

La ruta esperada por defecto es:

```text
writable/keys/sa.json
```

También puedes cambiarla con `GDRIVE_SA_JSON`.

### 4. Base de datos

El proyecto depende de un esquema existente. En el árbol actual:

- La carpeta `app/Database/Migrations` existe, pero no contiene migraciones versionadas.
- Sí hay seeders para catálogos base:
  - `RitFaltasSeeder`
  - `ProyectosSeeder`
  - `ProyectoAliasSeeder`

Si ya tienes la base creada, solo ajusta la conexión en `.env`.

Si necesitas cargar catálogos:

```bash
php spark db:seed RitFaltasSeeder
php spark db:seed ProyectosSeeder
php spark db:seed ProyectoAliasSeeder
```

### 5. Servir la aplicación

Con el servidor embebido de CodeIgniter:

```bash
php spark serve --host 0.0.0.0 --port 8080
```

O configura Apache/Nginx apuntando a la carpeta `public/`.

## Tablas y vistas esperadas

El código hace referencia directa, como mínimo, a estas tablas y vistas:

- `tbl_furd`
- `tbl_furd_faltas`
- `tbl_furd_citacion`
- `tbl_furd_citacion_notificacion`
- `tbl_furd_descargos`
- `tbl_furd_soporte`
- `tbl_furd_decision`
- `tbl_adjuntos`
- `tbl_rit_faltas`
- `tbl_empleados`
- `tbl_empleado_contratos`
- `tbl_proyectos`
- `tbl_proyecto_alias` o equivalente asociado al modelo de alias
- `vw_empleado_contrato_activo`

## Rutas principales

| Método | Ruta | Descripción |
| --- | --- | --- |
| `GET` | `/` | Inicio del sistema |
| `GET` | `/furd` | Formulario de registro FURD |
| `POST` | `/furd` | Crear proceso disciplinario |
| `GET` | `/citacion` | Formulario de citación |
| `POST` | `/citacion` | Registrar citación |
| `GET` | `/citacion/docx/{id}` | Descargar DOCX de citación |
| `GET` | `/descargos` | Formulario de descargos |
| `POST` | `/descargos` | Registrar descargos |
| `GET` | `/soporte` | Formulario de soporte |
| `POST` | `/soporte` | Registrar soporte |
| `GET/POST` | `/soporte/revision-cliente/{consecutivo}` | Revisión pública del cliente |
| `GET` | `/decision` | Formulario de decisión |
| `POST` | `/decision` | Registrar decisión |
| `GET` | `/seguimiento` | Bandeja interna de procesos |
| `GET` | `/linea-tiempo/{consecutivo}` | Línea de tiempo interna |
| `GET` | `/portal-cliente` | Portal del cliente |
| `GET` | `/portal-cliente/mis-procesos` | Consulta AJAX de procesos por correo |
| `GET` | `/portal-cliente/furd/{consecutivo}/timeline` | Timeline AJAX del cliente |
| `GET/POST` | `/ajustes/acceso` | Acceso por PIN al módulo de ajustes |

## Comandos CLI disponibles en el proyecto

| Comando | Descripción |
| --- | --- |
| `php spark workers:peek [desde] [hasta]` | Inspecciona datos del reporte `masterWorkers` de Sorttime |
| `php spark workers:sync [desde] [hasta]` | Sincroniza empleados y contratos desde Sorttime |
| `php spark projects:backfill` | Resuelve `proyecto_id` en contratos usando alias de nómina |
| `php spark furd:recordatorios-soporte` | Envía recordatorios y auto-archiva soportes vencidos |

## Protección y acceso

- No hay un módulo de autenticación de usuarios implementado en este árbol.
- El módulo de ajustes usa un PIN vía `AjustesPinFilter`.
- El portal cliente filtra procesos por `correo_cliente`.

## Adjuntos y almacenamiento

Los adjuntos se registran en `tbl_adjuntos` y normalmente se almacenan en Google Drive con:

- `storage_provider = gdrive`
- `drive_file_id`
- `drive_web_view_link`
- `drive_web_content_link`

Rutas lógicas usadas actualmente:

- Registro FURD:
  - `FURD/{AÑO}/{CONSECUTIVO}/FURD/Adjuntos`
  - `FURD/{AÑO}/{CONSECUTIVO}/FURD/Formato del reporte disciplinario`
- Citación:
  - `FURD/{AÑO}/{CONSECUTIVO}/Citacion`
- Descargos:
  - `FURD/{AÑO}/{CONSECUTIVO}/Descargos`
- Adjuntos genéricos de fases posteriores vía trait:
  - `FURD/{AÑO}/{FURD_ID}/{FASE}`

## Frontend

El proyecto no depende de un bundler Node para el flujo principal observado. El comportamiento de interfaz se apoya sobre:

- `public/assets/js/app.js`
- `public/assets/js/pages/furd.js`
- `public/assets/js/pages/portal_cliente.js`

## Correos y temporales

Para enviar adjuntos por correo, algunos archivos se descargan o materializan temporalmente en:

```text
writable/tmp/
```

Eso es esperado y no implica que el documento final deje de existir en Google Drive.

## Observaciones operativas

- El proyecto ya no usa el README genérico de CodeIgniter; esta documentación está orientada al negocio real del sistema.
- Varias consultas dependen de datos consistentes entre empleados, contratos, proyectos y alias de nómina.
- Para citaciones virtuales, la delegación de dominio en Google Calendar debe estar correctamente configurada.
- Si faltan plantillas o IDs de Drive/Calendar en `.env`, la generación documental fallará.

## Estado actual de la documentación

Este README fue construido a partir del código existente en el repositorio local, incluyendo controladores, servicios, comandos, rutas, modelos y configuración actual del proyecto.

Andrés Aguirre Ramos - 2026 CONTACTAMOS >_< 


