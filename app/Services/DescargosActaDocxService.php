<?php

namespace App\Services;

use App\Libraries\GDrive;
use App\Models\FurdAdjuntoModel;
use PhpOffice\PhpWord\TemplateProcessor;

class DescargosActaDocxService
{
    public function generateAndUpload(array $furd, array $descargo): ?array
    {
        $furdId      = (int)($furd['id'] ?? 0);
        $consecutivo = trim((string)($furd['consecutivo'] ?? ''));
        $medio       = strtolower(trim((string)($descargo['medio'] ?? '')));
        $fechaRaw    = trim((string)($descargo['fecha_evento'] ?? ''));
        $horaRaw     = trim((string)($descargo['hora'] ?? ''));

        if ($furdId <= 0 || $consecutivo === '') {
            log_message('error', '[DESCARGOS_DOCX] FURD inválido para generar acta.');
            return null;
        }

        $templateId = trim((string)env('GOOGLE_DOC_TEMPLATE_DESCARGOS', ''));
        if ($templateId === '') {
            log_message('error', '[DESCARGOS_DOCX] Falta GOOGLE_DOC_TEMPLATE_DESCARGOS en .env');
            return null;
        }

        $nombre  = trim((string)($furd['nombre_completo'] ?? $furd['nombre'] ?? ''));
        $cedula  = trim((string)($furd['cedula'] ?? ''));
        $correo  = trim((string)($furd['correo'] ?? ''));
        $empresa = trim((string)($furd['empresa_usuaria'] ?? ''));
        $hechos  = $this->resolveHechosDesdeCitacion($furdId, $furd);
        $cargo   = $this->resolveCargo($furd);

        $fechaDoc = $this->formatFechaLargaEs($fechaRaw);
        $horaDoc  = $this->formatHoraAmPm($horaRaw);

        $chkPresencial = $medio === 'presencial' ? 'X' : '';
        $chkVirtual    = $medio === 'virtual' ? 'X' : '';

        log_message('debug', '[DESCARGOS_DOCX] Datos a reemplazar: {data}', [
            'data' => json_encode([
                'RADICADO'       => $consecutivo,
                'NOMBRE'         => $nombre,
                'CEDULA'         => $cedula,
                'CORREO'         => $correo,
                'EMPRESA'        => $empresa,
                'CARGO'          => $cargo,
                'CHK_PRESENCIAL' => $chkPresencial,
                'CHK_VIRTUAL'    => $chkVirtual,
                'FECHA'          => $fechaDoc,
                'HORA'           => $horaDoc,
                'HECHOS'         => $hechos,
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $g = new GDrive();

        $tmpDir = WRITEPATH . 'tmp/descargos';
        if (!is_dir($tmpDir) && !@mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            log_message('error', '[DESCARGOS_DOCX] No se pudo crear carpeta temporal: {dir}', ['dir' => $tmpDir]);
            return null;
        }

        $tmpTemplatePath = null;
        $tmpOutputPath   = null;

        try {
            $templateMeta = $g->getFileMeta($templateId);
            $templateMime = (string)($templateMeta['mimeType'] ?? '');

            log_message('debug', '[DESCARGOS_DOCX] Plantilla detectada. mime={mime} name={name}', [
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

            $processor->setValue('RADICADO', $consecutivo);
            $processor->setValue('NOMBRE', $nombre);
            $processor->setValue('CEDULA', $cedula);
            $processor->setValue('CORREO', $correo);
            $processor->setValue('EMPRESA', $empresa);
            $processor->setValue('CARGO', $cargo);
            $processor->setValue('CHK_PRESENCIAL', $chkPresencial);
            $processor->setValue('CHK_VIRTUAL', $chkVirtual);
            $processor->setValue('FECHA', $fechaDoc);
            $processor->setValue('HORA', $horaDoc);
            $processor->setValue('HECHOS', $hechos);

            $safeConsec = preg_replace('/\W+/', '_', $consecutivo ?: 'PD_000000');
            $fileName   = 'RH-FO69_ACTA_CARGOS_DESCARGOS_' . $safeConsec . '.docx';

            $tmpOutputPath = $tmpDir . DIRECTORY_SEPARATOR . $fileName;
            $processor->saveAs($tmpOutputPath);

            if (!is_file($tmpOutputPath)) {
                throw new \RuntimeException('No se generó el DOCX temporal de salida.');
            }

            $year          = date('Y');
            $root          = trim((string)env('GDRIVE_ROOT', 'FURD'), '/');
            $procesoPath   = "{$root}/{$year}/{$consecutivo}";
            $descargosPath = "{$procesoPath}/Descargos";
            $parentId      = $g->ensurePath($descargosPath);

            $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            $up   = $g->upload($tmpOutputPath, $fileName, $mime, $parentId);

            $size = @filesize($tmpOutputPath) ?: null;
            $sha1 = @sha1_file($tmpOutputPath) ?: null;

            $adjuntoModel = new FurdAdjuntoModel();
            $adjuntoId = $adjuntoModel->insert([
                'origen'                 => 'furd',
                'origen_id'              => $furdId,
                'fase'                   => 'descargos',
                'nombre_original'        => $fileName,
                'ruta'                   => $descargosPath . '/' . $fileName,
                'mime'                   => $mime,
                'tamano_bytes'           => $size,
                'sha1'                   => $sha1,
                'storage_provider'       => 'gdrive',
                'drive_file_id'          => $up['id'] ?? null,
                'drive_web_view_link'    => $up['webViewLink'] ?? null,
                'drive_web_content_link' => $up['webContentLink'] ?? null,
                'created_at'             => date('Y-m-d H:i:s'),
            ], true);

            log_message('debug', '[DESCARGOS_DOCX] DOCX generado y subido a Drive: {id}', [
                'id' => $up['id'] ?? null,
            ]);

            return [
                'adjunto_id'          => $adjuntoId,
                'drive_file_id'       => $up['id'] ?? null,
                'view_link'           => $up['webViewLink'] ?? null,
                'download_link'       => $up['webContentLink'] ?? null,
                'docx_name'           => $up['name'] ?? $fileName,
                'descargos_folder_id' => $parentId,
                'descargos_path'      => $descargosPath,
            ];
        } catch (\Throwable $e) {
            log_message('error', '[DESCARGOS_DOCX] Error generando/subiendo acta: {msg}', [
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

    private function resolveHechosDesdeCitacion(int $furdId, array $furd): string
    {
        if ($furdId > 0) {
            try {
                $row = db_connect()->table('tbl_furd_citacion')
                    ->select('motivo')
                    ->where('furd_id', $furdId)
                    ->orderBy('numero', 'DESC')
                    ->orderBy('id', 'DESC')
                    ->get()
                    ->getRowArray();

                $motivo = trim((string)($row['motivo'] ?? ''));
                if ($motivo !== '') {
                    return $motivo;
                }
            } catch (\Throwable $e) {
                log_message('error', '[DESCARGOS_DOCX] Error obteniendo motivo de citación: {msg}', [
                    'msg' => $e->getMessage(),
                ]);
            }
        }

        return trim((string)($furd['hecho'] ?? ''));
    }

    private function resolveCargo(array $furd): string
    {
        $empleadoId = (int)($furd['empleado_id'] ?? 0);
        if ($empleadoId <= 0) {
            return '';
        }

        try {
            $row = db_connect()->table('vw_empleado_contrato_activo')
                ->select('cargo')
                ->where('empleado_id', $empleadoId)
                ->get()
                ->getRowArray();

            return trim((string)($row['cargo'] ?? ''));
        } catch (\Throwable $e) {
            log_message('error', '[DESCARGOS_DOCX] Error obteniendo cargo: {msg}', [
                'msg' => $e->getMessage(),
            ]);
            return '';
        }
    }

    private function formatFechaLargaEs(string $fecha): string
    {
        if ($fecha === '') {
            return '';
        }

        try {
            $dt = new \DateTimeImmutable($fecha, new \DateTimeZone('America/Bogota'));
        } catch (\Throwable $e) {
            return $fecha;
        }

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

        $mes = $meses[$dt->format('m')] ?? $dt->format('m');

        return sprintf('%02d de %s de %d', (int)$dt->format('d'), $mes, (int)$dt->format('Y'));
    }

    private function formatHoraAmPm(string $horaRaw): string
    {
        $horaRaw = trim($horaRaw);
        if ($horaRaw === '') {
            return '';
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            $dt = \DateTime::createFromFormat($format, $horaRaw);
            if ($dt instanceof \DateTime) {
                return $dt->format('g:i A');
            }
        }

        return $horaRaw;
    }
}