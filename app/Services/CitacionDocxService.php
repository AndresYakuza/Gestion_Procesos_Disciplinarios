<?php

namespace App\Services;

use PhpOffice\PhpWord\TemplateProcessor;
use App\Models\FurdAdjuntoModel;

class CitacionDocxService
{
    /**
     * Genera el DOCX de citación (RH-FO67) según el medio.
     *
     * @param array $furd       Datos del FURD.
     * @param array $citacion   Datos de la citación recién creada.
     * @param array $faltas     Faltas seleccionadas (como en FurdFormatoService).
     * @param array $adjuntos   Adjuntos/evidencias del FURD.
     * @return string|null      Ruta absoluta del archivo generado o null en caso de fallo.
     */
    public function generate(
        array $furd,
        array $citacion,
        array $faltas = [],
        array $adjuntos = []
    ): ?string {
        $medio  = strtolower((string) ($citacion['medio'] ?? ''));
        $furdId = (int)($furd['id'] ?? 0);

        // Log inicial
        log_message('debug', '[CITACION] generate() inicio - furd_id={id} faltas={cf} adjuntos={ca}', [
            'id' => $furdId,
            'cf' => is_array($faltas) ? count($faltas) : -1,
            'ca' => is_array($adjuntos) ? count($adjuntos) : -1,
        ]);

        /* =====================================================
         * 0) Fallbacks: si no me pasan faltas/adjuntos, los cargo
         *     directo desde BD (como en FurdFormatoService).
         * ===================================================== */

        // 0.1 Faltas
        if (empty($faltas) && $furdId > 0) {
            $faltas = $this->getFaltasByFurdId($furdId);
            log_message('debug', '[CITACION] Faltas cargadas desde BD: {n}', [
                'n' => count($faltas),
            ]);
        }

        // 0.2 Adjuntos
        if (empty($adjuntos) && $furdId > 0) {
            // Para la citación, normalmente quieres trasladar las pruebas del registro
            $adjuntos = (new FurdAdjuntoModel())->listByFase($furdId, 'registro');
            log_message('debug', '[CITACION] Adjuntos cargados desde BD (fase=registro): {n}', [
                'n' => is_array($adjuntos) ? count($adjuntos) : 0,
            ]);
        }

        /* =======================
         * 1) Selección de plantilla
         * ======================= */
        switch ($medio) {
            case 'virtual':
                $template = APPPATH . 'Resources/RH-FO67_CITACION_VIRTUAL.docx';
                break;
            case 'presencial':
                $template = APPPATH . 'Resources/RH-FO67_CITACION_PRESENCIAL.docx';
                break;
            case 'escrito':
                $template = APPPATH . 'Resources/RH-FO67_CITACION_ESCRITO.docx';
                break;
            default:
                log_message('error', '[CITACION] Medio no soportado: {medio}', ['medio' => $medio]);
                return null;
        }

        log_message('debug', '[CITACION] Usando plantilla: {tpl}', ['tpl' => $template]);

        if (!is_file($template)) {
            log_message('error', '[CITACION] Plantilla DOCX no encontrada: {tpl}', ['tpl' => $template]);
            return null;
        }

        try {
            $processor = new TemplateProcessor($template);
        } catch (\Throwable $e) {
            log_message('error', '[CITACION] Error creando TemplateProcessor: {msg}', [
                'msg' => $e->getMessage(),
            ]);
            return null;
        }

        /* =====================
         * 2) Datos base del FURD
         * ===================== */
        $consecutivo = (string) ($furd['consecutivo'] ?? '');
        $nombre      = (string) ($furd['nombre'] ?? $furd['nombre_completo'] ?? '');
        $cedula      = (string) ($furd['cedula'] ?? '');
        $proyecto    = (string) ($furd['proyecto'] ?? '');
        $empresa     = (string) ($furd['empresa_usuaria'] ?? '');
        $hechos      = (string) ($furd['hecho'] ?? '');

        $fechaEventoRaw = (string) ($citacion['fecha_evento'] ?? '');
        $horaRaw        = (string) ($citacion['hora'] ?? '');

        // 2.1 FECHA_CARTA = fecha actual (Bogotá) en español
        $nowBogota  = new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota'));
        $fechaCarta = $this->formatFechaLargaEs($nowBogota);        // ej. "02 de marzo del 2026"

        // 2.2 FECHA_DESCARGOS = fecha del evento, con mismo formato largo
        $fechaDesc = '';
        if ($fechaEventoRaw !== '') {
            try {
                $dtDesc    = new \DateTimeImmutable($fechaEventoRaw, new \DateTimeZone('America/Bogota'));
                $fechaDesc = $this->formatFechaLargaEs($dtDesc);      // ej. "09 de marzo del 2026"
            } catch (\Throwable $e) {
                $fechaDesc = $fechaEventoRaw; // fallback crudo
            }
        }

        // 2.3 HORA_DESCARGOS = hora abreviada tipo 9:30 AM / 2:00 PM
        $horaDesc = $this->formatHoraAmPm($horaRaw);

        /* =====================
         * 3) Texto de faltas y pruebas
         * ===================== */
        $textoFaltas  = $this->buildFaltasTexto($faltas, $furd['faltas'] ?? null);
        $textoPruebas = $this->buildPruebasTexto($adjuntos, $furd['pruebas'] ?? null);

        // Enlace Meet
        $enlaceMeet = $this->buildMeetLink($furd, $citacion);

        /* =============================
         * 4) Sustitución en la plantilla
         * ============================= */
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

        // Marcadores de pruebas/evidencias
        $processor->setValue('PRUEBAS_TRASLADO',  $textoPruebas);
        $processor->setValue('EVIDENCIAS',        $textoPruebas);
        $processor->setValue('ENLANCE',           $enlaceMeet);

        /* ========================
         * 5) Guardar archivo DOCX
         * ======================== */
        $safeConsec = preg_replace('/\W+/', '_', $consecutivo ?: 'PD-000000');
        $numero     = (int) ($citacion['numero'] ?? 1);

        $fileName = sprintf('CITACION_%s_N%02d.docx', $safeConsec, $numero);
        $folder   = WRITEPATH . 'citaciones';

        if (!is_dir($folder)) {
            if (!@mkdir($folder, 0775, true) && !is_dir($folder)) {
                log_message('error', '[CITACION] No se pudo crear carpeta: {dir}', ['dir' => $folder]);
                return null;
            }
        }

        $path = $folder . DIRECTORY_SEPARATOR . $fileName;

        try {
            $processor->saveAs($path);
        } catch (\Throwable $e) {
            log_message('error', '[CITACION] Error guardando DOCX en {path}: {msg}', [
                'path' => $path,
                'msg'  => $e->getMessage(),
            ]);
            return null;
        }

        log_message('debug', '[CITACION] DOCX generado correctamente: {path}', ['path' => $path]);

        return $path;
    }

    /**
     * Fecha larga en español: "02 de marzo del 2026".
     */
    private function formatFechaLargaEs(\DateTimeInterface $dt): string
    {
        $dia  = (int) $dt->format('d');
        $anio = (int) $dt->format('Y');
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

    /**
     * Hora en formato 12h: "9:30 AM", "2:00 PM".
     */
    private function formatHoraAmPm(string $horaRaw): string
    {
        $horaRaw = trim($horaRaw);
        if ($horaRaw === '') {
            return '';
        }

        $patterns = ['H:i:s', 'H:i']; // 24h con o sin segundos

        foreach ($patterns as $pat) {
            $dt = \DateTime::createFromFormat($pat, $horaRaw);
            if ($dt instanceof \DateTime) {
                return $dt->format('g:i A');
            }
        }

        // Si viene en otro formato, lo devolvemos tal cual
        return $horaRaw;
    }

    /**
     * Texto de lugar según el medio.
     */
    private function buildLugar(string $medio): string
    {
        $medio = strtolower($medio);

        if ($medio === 'virtual') {
            return 'Enlace de reunión virtual enviado al correo del trabajador.';
        }

        if ($medio === 'presencial') {
            return 'Oficinas administrativas de Contactamos de Colombia S.A.S.';
        }

        // escrito
        return 'Presentación de descargos por escrito dentro del término señalado.';
    }

    /**
     * Construye el texto de faltas:
     * - Si viene un arreglo de faltas, arma lista tipo "C01 (GRAVE): descripción".
     * - Si el arreglo está vacío, usa el texto plano de fallback.
     */
    private function buildFaltasTexto(array $faltas, ?string $fallback): string
    {
        $items = [];

        foreach ($faltas as $f) {
            if (!is_array($f)) {
                $line = trim((string) $f);
                if ($line !== '') {
                    $items[] = $line;
                }
                continue;
            }

            $codigo      = trim((string) ($f['codigo'] ?? ''));
            $gravedad    = trim((string) ($f['gravedad'] ?? ''));
            $descripcion = trim((string) (
                $f['descripcion'] ??
                $f['descripcion_falta'] ??
                ''
            ));

            $prefix = $codigo;
            if ($gravedad !== '') {
                $prefix .= $prefix !== '' ? " ({$gravedad})" : $gravedad;
            }

            $line = trim(
                ($prefix !== '' ? $prefix . ': ' : '') .
                $descripcion
            );

            if ($line !== '') {
                $items[] = $line;
            }
        }

        if (!empty($items)) {
            // Igual que en FurdFormato: doble salto para separarlas visualmente
            return implode("\n\n", $items);
        }

        $fallback = trim((string) $fallback);
        return $fallback !== '' ? $fallback : '';
    }

    /**
     * Construye el texto de pruebas trasladadas a partir de los adjuntos.
     */
    private function buildPruebasTexto(array $adjuntos, ?string $fallback): string
    {
        log_message('debug', '[CITACION] Adjuntos recibidos en buildPruebasTexto: {dump}', [
            'dump' => json_encode($adjuntos),
        ]);

        $nombres = [];

        foreach ($adjuntos as $a) {
            if (!is_array($a)) {
                $name = trim((string) $a);
            } else {
                $name = trim((string) (
                    $a['nombre_original'] ??
                    $a['nombre'] ??
                    $a['filename'] ??
                    ''
                ));
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

            log_message('debug', '[CITACION] Pruebas/evidencias usadas en DOCX: {lista}', [
                'lista' => implode(' | ', $nombres),
            ]);

            return implode("\n", $lines);
        }

        $fallback = trim((string) $fallback);
        return $fallback !== '' ? $fallback : '';
    }

    /**
     * Enlace Meet:
     * aquí no generamos códigos "inventados".
     * Se espera que el enlace venga desde BD o .env.
     */
    private function buildMeetLink(array $furd, array $citacion): string
    {
        if (!empty($citacion['link_meet'])) {
            return (string) $citacion['link_meet'];
        }

        if (!empty($furd['link_meet'])) {
            return (string) $furd['link_meet'];
        }

        $envLink = getenv('FURD_MEET_BASE_URL') ?: env('FURD_MEET_BASE_URL', '');
        return $envLink ?: '';
    }

    /**
     * Replica de getFaltasByFurdId() del FurdController,
     * para no depender de que nos pasen las faltas desde afuera.
     */
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