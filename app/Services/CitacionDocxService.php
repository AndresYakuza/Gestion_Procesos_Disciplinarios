<?php

namespace App\Services;

use PhpOffice\PhpWord\TemplateProcessor;
use App\Models\FurdAdjuntoModel;
use App\Libraries\GDrive;

class CitacionDocxService
{
    /**
     * Genera el DOCX de citación desde Google Drive/Docs,
     * lo sube a la unidad compartida y devuelve metadata del archivo en Drive.
     *
     * @return array|null
     */
    public function generate(
        array $furd,
        array $citacion,
        array $faltas = [],
        array $adjuntos = []
    ): ?array {
        $medio  = strtolower((string)($citacion['medio'] ?? ''));
        $furdId = (int)($furd['id'] ?? 0);

        log_message('debug', '[CITACION] generate() inicio - furd_id={id} faltas={cf} adjuntos={ca}', [
            'id' => $furdId,
            'cf' => is_array($faltas) ? count($faltas) : -1,
            'ca' => is_array($adjuntos) ? count($adjuntos) : -1,
        ]);

        // =====================================================
        // 0) Fallbacks BD
        // =====================================================
        if (empty($faltas) && $furdId > 0) {
            $faltas = $this->getFaltasByFurdId($furdId);
            log_message('debug', '[CITACION] Faltas cargadas desde BD: {n}', [
                'n' => count($faltas),
            ]);
        }

        if (empty($adjuntos) && $furdId > 0) {
            $adjuntos = (new FurdAdjuntoModel())->listByFase($furdId, 'registro');
            log_message('debug', '[CITACION] Adjuntos cargados desde BD (fase=registro): {n}', [
                'n' => is_array($adjuntos) ? count($adjuntos) : 0,
            ]);
        }

        // =====================================================
        // 1) Selección de plantilla desde Drive
        // =====================================================
        switch ($medio) {
            case 'virtual':
                $templateId = (string) env('GOOGLE_DOC_TEMPLATE_CITACION_VIRTUAL', '');
                break;
            case 'presencial':
                $templateId = (string) env('GOOGLE_DOC_TEMPLATE_CITACION_PRESENCIAL', '');
                break;
            case 'escrito':
                $templateId = (string) env('GOOGLE_DOC_TEMPLATE_CITACION_ESCRITO', '');
                break;
            default:
                log_message('error', '[CITACION] Medio no soportado: {medio}', ['medio' => $medio]);
                return null;
        }

        if ($templateId === '') {
            log_message('error', '[CITACION] No hay templateId configurado para medio: {medio}', [
                'medio' => $medio,
            ]);
            return null;
        }

        $g = new GDrive();

        $tmpDir = WRITEPATH . 'tmp/citacion';
        if (!is_dir($tmpDir) && !@mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            log_message('error', '[CITACION] No se pudo crear carpeta temporal: {dir}', ['dir' => $tmpDir]);
            return null;
        }

        $tmpTemplatePath = null;
        $tmpOutputPath   = null;

        try {
            $templateMeta = $g->getFileMeta($templateId);
            $templateMime = (string)($templateMeta['mimeType'] ?? '');

            log_message('debug', '[CITACION] Plantilla detectada. mime={mime} name={name}', [
                'mime' => $templateMime,
                'name' => $templateMeta['name'] ?? '',
            ]);

            if ($templateMime === 'application/vnd.google-apps.document') {
                $templateBinary = $g->exportGoogleFile(
                    $templateId,
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                );
            } else {
                $templateBinary = $g->downloadFile($templateId);
            }

            $tmpTemplatePath = $tmpDir . DIRECTORY_SEPARATOR . 'tpl_' . uniqid('', true) . '.docx';
            file_put_contents($tmpTemplatePath, $templateBinary);

            if (!is_file($tmpTemplatePath) || filesize($tmpTemplatePath) <= 0) {
                throw new \RuntimeException('No se pudo materializar la plantilla DOCX temporal.');
            }

            $processor = new TemplateProcessor($tmpTemplatePath);

            // =====================================================
            // 2) Datos base del FURD
            // =====================================================
            $consecutivo = (string)($furd['consecutivo'] ?? '');
            $nombre      = (string)($furd['nombre'] ?? $furd['nombre_completo'] ?? '');
            $cedula      = (string)($furd['cedula'] ?? '');
            $proyecto    = (string)($furd['proyecto'] ?? '');
            $empresa     = (string)($furd['empresa_usuaria'] ?? '');

            $hechos = trim((string)($citacion['motivo'] ?? ''));
            if ($hechos === '') {
                $hechos = trim((string)($furd['hecho'] ?? ''));
            }

            $fechaEventoRaw = (string)($citacion['fecha_evento'] ?? '');
            $horaRaw        = (string)($citacion['hora'] ?? '');

            $nowBogota  = new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota'));
            $fechaCarta = $this->formatFechaLargaEs($nowBogota);

            $fechaDesc = '';
            if ($fechaEventoRaw !== '') {
                try {
                    $dtDesc    = new \DateTimeImmutable($fechaEventoRaw, new \DateTimeZone('America/Bogota'));
                    $fechaDesc = $this->formatFechaLargaEs($dtDesc);
                } catch (\Throwable $e) {
                    $fechaDesc = $fechaEventoRaw;
                }
            }

            $horaDesc = $this->formatHoraAmPm($horaRaw);

            // =====================================================
            // 3) Texto de faltas y pruebas
            // =====================================================
            $textoFaltas  = $this->buildFaltasTexto($faltas, $furd['faltas'] ?? null);
            $textoPruebas = $this->buildPruebasTexto($adjuntos, $furd['pruebas'] ?? null);
            $enlaceMeet   = $this->buildMeetLink($furd, $citacion);

            // =====================================================
            // 4) Reemplazo marcadores
            // =====================================================
            $processor->setValue('FECHA_CARTA',       $fechaCarta);
            $processor->setValue('NOMBRE_TRABAJADOR', $nombre);
            $processor->setValue('CEDULA',            $cedula);
            $processor->setValue('EMPRESA_USUARIA',   $empresa);
            $processor->setValue('PROCESO_RAD',       $consecutivo);
            $processor->setValue('PROYECTO',          $proyecto);

            $processor->setValue('HECHOS',            $hechos);
            $processor->setValue('FALTAS',            $textoFaltas);

            $processor->setValue('FECHA_DESCARGOS',   $fechaDesc);
            $processor->setValue('HORA_DESCARGOS',    $horaDesc);
            $processor->setValue('MEDIO_DESCARGOS',   ucfirst($medio));
            $processor->setValue('LUGAR_DESCARGOS',   $this->buildLugar($medio));

            $processor->setValue('PRUEBAS_TRASLADO',  $textoPruebas);
            $processor->setValue('EVIDENCIAS',        $textoPruebas);
            $processor->setValue('ENLANCE',           $enlaceMeet);

            // =====================================================
            // 5) Guardar temporal y subir a Drive
            // =====================================================
            $safeConsec = preg_replace('/\W+/', '_', $consecutivo ?: 'PD-000000');
            $numero     = (int)($citacion['numero'] ?? 1);
            $fileName   = sprintf('RH-FO67_CITACION_%s_N%02d.docx', $safeConsec, $numero);

            $tmpOutputPath = $tmpDir . DIRECTORY_SEPARATOR . $fileName;
            $processor->saveAs($tmpOutputPath);

            if (!is_file($tmpOutputPath)) {
                throw new \RuntimeException('No se generó el DOCX temporal de salida.');
            }

            $year         = date('Y');
            $root         = trim((string)env('GDRIVE_ROOT', 'FURD'), '/');
            $procesoPath  = "{$root}/{$year}/{$consecutivo}";
            $citacionPath = "{$procesoPath}/Citacion";
            $parentId     = $g->ensurePath($citacionPath);

            $up = $g->upload(
                $tmpOutputPath,
                $fileName,
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                $parentId
            );

            log_message('debug', '[CITACION] DOCX generado y subido a Drive: {id}', [
                'id' => $up['id'] ?? null,
            ]);

            return [
                'consecutivo_folder_path' => $procesoPath,
                'citacion_folder_path'    => $citacionPath,
                'citacion_folder_id'      => $parentId,
                'docx_file_id'            => $up['id'] ?? null,
                'docx_name'               => $up['name'] ?? $fileName,
                'docx_mime'               => $up['mimeType'] ?? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'docx_web_view_link'      => $up['webViewLink'] ?? null,
                'docx_web_content_link'   => $up['webContentLink'] ?? null,
            ];
        } catch (\Throwable $e) {
            log_message('error', '[CITACION] Error generando DOCX desde Drive: {msg}', [
                'msg' => $e->getMessage(),
            ]);
            return null;
        } finally {
            if ($tmpTemplatePath && is_file($tmpTemplatePath)) {
                @unlink($tmpTemplatePath);
            }
            if ($tmpOutputPath && is_file($tmpOutputPath)) {
                @unlink($tmpOutputPath);
            }
        }
    }

    private function formatFechaLargaEs(\DateTimeInterface $dt): string
    {
        $dia  = (int)$dt->format('d');
        $anio = (int)$dt->format('Y');
        $mesN = $dt->format('m');

        $meses = [
            '01' => 'enero',
            '02' => 'febrero',
            '03' => 'marzo',
            '04' => 'abril',
            '05' => 'mayo',
            '06' => 'junio',
            '07' => 'julio',
            '08' => 'agosto',
            '09' => 'septiembre',
            '10' => 'octubre',
            '11' => 'noviembre',
            '12' => 'diciembre',
        ];

        $mes = $meses[$mesN] ?? $mesN;

        return sprintf('%02d de %s del %d', $dia, $mes, $anio);
    }

    private function formatHoraAmPm(string $horaRaw): string
    {
        $horaRaw = trim($horaRaw);
        if ($horaRaw === '') {
            return '';
        }

        foreach (['H:i:s', 'H:i'] as $pat) {
            $dt = \DateTime::createFromFormat($pat, $horaRaw);
            if ($dt instanceof \DateTime) {
                return $dt->format('g:i A');
            }
        }

        return $horaRaw;
    }

    private function buildLugar(string $medio): string
    {
        $medio = strtolower($medio);

        if ($medio === 'virtual') {
            return 'Enlace de reunión virtual enviado al correo del trabajador.';
        }

        if ($medio === 'presencial') {
            return 'Oficinas administrativas de Contactamos de Colombia S.A.S.';
        }

        return 'Presentación de descargos por escrito dentro del término señalado.';
    }

    private function buildFaltasTexto(array $faltas, ?string $fallback): string
    {
        $items = [];

        foreach ($faltas as $f) {
            if (!is_array($f)) {
                $line = trim((string)$f);
                if ($line !== '') {
                    $items[] = $line;
                }
                continue;
            }

            $codigo      = trim((string)($f['codigo'] ?? ''));
            $gravedad    = trim((string)($f['gravedad'] ?? ''));
            $descripcion = trim((string)($f['descripcion'] ?? $f['descripcion_falta'] ?? ''));

            $prefix = $codigo;
            if ($gravedad !== '') {
                $prefix .= $prefix !== '' ? " ({$gravedad})" : $gravedad;
            }

            $line = trim(($prefix !== '' ? $prefix . ': ' : '') . $descripcion);

            if ($line !== '') {
                $items[] = $line;
            }
        }

        if (!empty($items)) {
            return implode("\n\n", $items);
        }

        $fallback = trim((string)$fallback);
        return $fallback !== '' ? $fallback : '';
    }

    private function buildPruebasTexto(array $adjuntos, ?string $fallback): string
    {
        log_message('debug', '[CITACION] Adjuntos recibidos en buildPruebasTexto: {dump}', [
            'dump' => json_encode($adjuntos),
        ]);

        $nombres = [];

        foreach ($adjuntos as $a) {
            if (!is_array($a)) {
                $name = trim((string)$a);
            } else {
                $name = trim((string)($a['nombre_original'] ?? $a['nombre'] ?? $a['filename'] ?? ''));
            }

            if ($name !== '') {
                $nombres[] = $name;
            }
        }

        if (!empty($nombres)) {
            $lines = [];
            foreach ($nombres as $i => $name) {
                $lines[] = sprintf('%d. %s', $i + 1, $name);
            }

            return implode("\n", $lines);
        }

        $fallback = trim((string)$fallback);
        return $fallback !== '' ? $fallback : '';
    }

    private function buildMeetLink(array $furd, array $citacion): string
    {
        if (!empty($citacion['link_meet'])) {
            return (string)$citacion['link_meet'];
        }

        if (!empty($furd['link_meet'])) {
            return (string)$furd['link_meet'];
        }

        $envLink = getenv('FURD_MEET_BASE_URL') ?: env('FURD_MEET_BASE_URL', '');
        return $envLink ?: '';
    }

    private function getFaltasByFurdId(int $furdId): array
    {
        if ($furdId <= 0) {
            return [];
        }

        return db_connect()->table('tbl_furd_faltas ff')
            ->select('rf.codigo, rf.gravedad, rf.descripcion')
            ->join(
                'tbl_rit_faltas rf',
                'rf.id = COALESCE(ff.falta_id, ff.rit_falta_id)',
                'left'
            )
            ->where('ff.furd_id', $furdId)
            ->orderBy('rf.codigo', 'ASC')
            ->get()
            ->getResultArray();
    }
}
