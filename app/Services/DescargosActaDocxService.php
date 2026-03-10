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

        $template = APPPATH . 'Resources/RH-FO69_FORMATO_ACTA_DE_CARGOS_Y_DESCARGOS_COLOMBIA.docx';
        if (!is_file($template)) {
            log_message('error', '[DESCARGOS_DOCX] Plantilla no encontrada: {tpl}', ['tpl' => $template]);
            return null;
        }

        $nombre  = trim((string)($furd['nombre_completo'] ?? $furd['nombre'] ?? ''));
        $cedula  = trim((string)($furd['cedula'] ?? ''));
        $correo  = trim((string)($furd['correo'] ?? ''));
        $empresa = trim((string)($furd['empresa_usuaria'] ?? ''));
        $hechos = $this->resolveHechosDesdeCitacion($furdId, $furd);
        $cargo   = $this->resolveCargo($furd);

        $fechaDoc = $this->formatFechaLargaEs($fechaRaw);
        $horaDoc  = $this->formatHoraAmPm($horaRaw);

        $chkPresencial = $medio === 'presencial' ? 'X' : '';
        $chkVirtual    = $medio === 'virtual' ? 'X' : '';

        log_message('debug', '[DESCARGOS_DOCX] Datos a reemplazar: {data}', [
            'data' => json_encode([
                'RADICADO'        => $consecutivo,
                'NOMBRE'          => $nombre,
                'CEDULA'          => $cedula,
                'CORREO'          => $correo,
                'EMPRESA'         => $empresa,
                'CARGO'           => $cargo,
                'CHK_PRESENCIAL'  => $chkPresencial,
                'CHK_VIRTUAL'     => $chkVirtual,
                'FECHA'           => $fechaDoc,
                'HORA'            => $horaDoc,
                'HECHOS'          => $hechos,
            ], JSON_UNESCAPED_UNICODE),
        ]);

        try {
            $processor = new TemplateProcessor($template);

            // NO configurar macros << >> porque tu plantilla usa ${CAMPO}
            // PhpWord por defecto trabaja con ${CAMPO}

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
            $fileName   = 'ACTA_CARGOS_DESCARGOS_' . $safeConsec . '.docx';

            $localDir = WRITEPATH . 'descargos';
            if (!is_dir($localDir)) {
                if (!@mkdir($localDir, 0775, true) && !is_dir($localDir)) {
                    log_message('error', '[DESCARGOS_DOCX] No se pudo crear carpeta local: {dir}', ['dir' => $localDir]);
                    return null;
                }
            }

            $localPath = $localDir . DIRECTORY_SEPARATOR . $fileName;
            $processor->saveAs($localPath);

            if (!is_file($localPath)) {
                log_message('error', '[DESCARGOS_DOCX] No se generó el archivo local: {path}', ['path' => $localPath]);
                return null;
            }

            $g = new GDrive();

            $root       = (string) env('GDRIVE_ROOT', 'FURD');
            $folderPath = rtrim($root, '/') . '/' . date('Y') . '/' . $furdId . '/descargos';
            $parentId   = $g->ensurePath($folderPath);

            $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            $up   = $g->upload($localPath, $fileName, $mime, $parentId);

            $viewLink = $up['webViewLink'] ?? null;
            if (!$viewLink && !empty($up['id'])) {
                $viewLink = 'https://drive.google.com/file/d/' . $up['id'] . '/view?usp=sharing';
            }

            $size = @filesize($localPath) ?: null;
            $sha1 = @sha1_file($localPath) ?: null;

            $adjuntoModel = new FurdAdjuntoModel();
            $adjuntoId = $adjuntoModel->insert([
                'origen'                 => 'furd',
                'origen_id'              => $furdId,
                'fase'                   => 'descargos',
                'nombre_original'        => $fileName,
                'ruta'                   => $folderPath . '/' . $fileName,
                'mime'                   => $mime,
                'tamano_bytes'           => $size,
                'sha1'                   => $sha1,
                'storage_provider'       => 'gdrive',
                'drive_file_id'          => $up['id'] ?? null,
                'drive_web_view_link'    => $viewLink,
                'drive_web_content_link' => $up['webContentLink'] ?? null,
                'created_at'             => date('Y-m-d H:i:s'),
            ], true);

            @unlink($localPath);

            return [
                'adjunto_id'    => $adjuntoId,
                'drive_file_id' => $up['id'] ?? null,
                'view_link'     => $viewLink,
                'download_link' => $up['webContentLink'] ?? null,
            ];
        } catch (\Throwable $e) {
            log_message('error', '[DESCARGOS_DOCX] Error generando/subiendo acta: {msg}', [
                'msg' => $e->getMessage(),
            ]);

            if (isset($localPath) && is_file($localPath)) {
                @unlink($localPath);
            }

            return null;
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

        // fallback por si no existe citación o viene vacío
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