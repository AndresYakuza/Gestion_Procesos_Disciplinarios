<?php

namespace App\Services;

use App\Libraries\GDrive;
use Google\Service\Sheets;
use Google\Service\Sheets\BatchUpdateValuesRequest;
use Google\Service\Sheets\ValueRange;

class FurdGoogleFormatService
{
    protected GDrive $drive;
    protected Sheets $sheets;

    public function __construct()
    {
        $this->drive  = new GDrive();
        $this->sheets = new Sheets($this->drive->getClient());
    }

    public function generar(array $furd, array $faltas = [], array $adjuntos = []): array
    {
        $furdId       = (int)($furd['id'] ?? 0);
        $consecutivo  = (string)($furd['consecutivo'] ?? sprintf('PD-%06d', $furdId));
        $year         = date('Y');
        $root         = trim((string)env('GDRIVE_ROOT', 'FURD'), '/');
        $templateId   = (string)env('GOOGLE_SHEETS_TEMPLATE_ID', '');
        $sheetName    = (string)env('GOOGLE_SHEETS_TEMPLATE_SHEET_NAME', 'RH-FO23');

        if ($templateId === '') {
            throw new \RuntimeException('Falta GOOGLE_SHEETS_TEMPLATE_ID en .env');
        }

        $basePath   = "{$root}/{$year}/{$consecutivo}";
        $furdFolder = $this->drive->ensurePath($basePath . '/FURD');
        $adjFolder  = $this->drive->createFolderInParent('Adjuntos', $furdFolder);
        $fmtFolder  = $this->drive->createFolderInParent('Formato del reporte disciplinario', $furdFolder);

        $sheetCopyName = "RH-FO23 {$consecutivo}";
        $copy = $this->drive->copyFile($templateId, $sheetCopyName, $fmtFolder);
        $spreadsheetId = $copy['id'];

        $values = $this->buildSheetValues($sheetName, $furd, $faltas, $adjuntos);

        $body = new BatchUpdateValuesRequest([
            'valueInputOption' => 'USER_ENTERED',
            'data' => $values,
        ]);

        $this->sheets->spreadsheets_values->batchUpdate($spreadsheetId, $body);

        $this->applyLayoutFormatting($spreadsheetId, $sheetName);

        $pdfBinary = $this->drive->exportGoogleFile($spreadsheetId, 'application/pdf');
        $pdfName   = "RH-FO23 {$consecutivo}.pdf";

        $pdf = $this->drive->uploadContent(
            $pdfBinary,
            $pdfName,
            'application/pdf',
            $fmtFolder
        );

        return [
            'consecutivo_folder_path' => $basePath,
            'furd_folder_id'          => $furdFolder,
            'adjuntos_folder_id'      => $adjFolder,
            'formato_folder_id'       => $fmtFolder,
            'sheet_file_id'           => $spreadsheetId,
            'sheet_web_view_link'     => $copy['webViewLink'] ?? null,
            'pdf_file_id'             => $pdf['id'],
            'pdf_name'                => $pdf['name'],
            'pdf_web_view_link'       => $pdf['webViewLink'] ?? null,
            'pdf_web_content_link'    => $pdf['webContentLink'] ?? null,
        ];
    }

    protected function buildSheetValues(string $sheetName, array $furd, array $faltas, array $adjuntos): array
    {
        $consecutivo = (string)($furd['consecutivo'] ?? sprintf('PD-%06d', $furd['id'] ?? 0));
        $nowBogota   = new \DateTimeImmutable('now', new \DateTimeZone('America/Bogota'));

        $fechaReporte = $nowBogota->format('d/m/Y H:i');
        $nombre       = (string)($furd['nombre_trabajador'] ?? $furd['nombre_completo'] ?? $furd['nombre'] ?? '');
        $cedula       = (string)($furd['cedula_trabajador'] ?? $furd['cedula'] ?? '');
        $expedidaEn   = (string)($furd['expedida_en'] ?? '');
        $empresa      = (string)($furd['empresa_usuaria'] ?? '');
        $superior     = trim((string)($furd['superior'] ?? ''));
        $hecho        = $this->normalizeMultilineText((string)($furd['hecho'] ?? ''));

        $fechaEvento = '';
        if (!empty($furd['fecha_evento'])) {
            try {
                $fechaEvento = (new \DateTimeImmutable((string)$furd['fecha_evento']))->format('d/m/Y');
            } catch (\Throwable $e) {
                $fechaEvento = (string)$furd['fecha_evento'];
            }
        }

        $horaEvento    = $this->formatHoraAmPm((string)($furd['hora_evento'] ?? ''));
        $textoFaltas   = $this->buildFaltasTexto($faltas, $furd['faltas'] ?? null);
        $pruebas       = $this->buildPruebasSlots($adjuntos);
        $nroEvidencias = $this->countAdjuntos($adjuntos);

        return [
            new ValueRange(['range' => "{$sheetName}!C5",  'values' => [[$fechaReporte]]]),
            new ValueRange(['range' => "{$sheetName}!G5",  'values' => [[$consecutivo]]]),
            new ValueRange(['range' => "{$sheetName}!C8",  'values' => [[$nombre]]]),
            new ValueRange(['range' => "{$sheetName}!C9",  'values' => [[$cedula]]]),
            new ValueRange(['range' => "{$sheetName}!C10", 'values' => [[$expedidaEn]]]),
            new ValueRange(['range' => "{$sheetName}!C11", 'values' => [[$empresa]]]),
            new ValueRange(['range' => "{$sheetName}!G8",  'values' => [[$fechaEvento]]]),
            new ValueRange(['range' => "{$sheetName}!G9",  'values' => [[$horaEvento]]]),
            new ValueRange(['range' => "{$sheetName}!G10", 'values' => [[$superior]]]),
            new ValueRange(['range' => "{$sheetName}!G11", 'values' => [[(string)$nroEvidencias]]]),
            new ValueRange(['range' => "{$sheetName}!A13", 'values' => [[$hecho]]]),
            new ValueRange(['range' => "{$sheetName}!C15", 'values' => [[$textoFaltas]]]),
            new ValueRange(['range' => "{$sheetName}!A17", 'values' => [[$pruebas[0]]]]),
            new ValueRange(['range' => "{$sheetName}!E17", 'values' => [[$pruebas[1]]]]),
            new ValueRange(['range' => "{$sheetName}!A18", 'values' => [[$pruebas[2]]]]),
            new ValueRange(['range' => "{$sheetName}!E18", 'values' => [[$pruebas[3]]]]),
        ];
    }

    protected function formatHoraAmPm(string $horaRaw): string
    {
        $horaRaw = trim($horaRaw);
        if ($horaRaw === '') {
            return '';
        }

        foreach (['H:i:s', 'H:i'] as $pattern) {
            $dt = \DateTime::createFromFormat($pattern, $horaRaw);
            if ($dt instanceof \DateTime) {
                return $dt->format('g:i A');
            }
        }

        return $horaRaw;
    }

    protected function buildFaltasTexto(array $faltas, ?string $fallback): string
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

    protected function buildPruebasSlots(array $adjuntos): array
    {
        $nombres = [];

        foreach ($adjuntos as $a) {
            $rawName = is_array($a)
                ? trim((string)($a['nombre_original'] ?? $a['nombre'] ?? $a['filename'] ?? ''))
                : trim((string)$a);

            if ($rawName !== '') {
                $nombres[] = $this->formatAttachmentLabel($rawName);
            }

            if (count($nombres) >= 4) {
                break;
            }
        }

        return [
            isset($nombres[0]) ? '1. ' . $nombres[0] : '',
            isset($nombres[1]) ? '2. ' . $nombres[1] : '',
            isset($nombres[2]) ? '3. ' . $nombres[2] : '',
            isset($nombres[3]) ? '4. ' . $nombres[3] : '',
        ];
    }

    protected function formatAttachmentLabel(string $filename, int $max = 38): string
    {
        $filename = trim($filename);
        if ($filename === '') {
            return '';
        }

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // mejora cortes naturales
        $name = str_replace(['_', '-'], ' ', $name);
        $name = preg_replace('/\s+/u', ' ', $name);

        if (mb_strlen($name) > $max) {
            $name = mb_substr($name, 0, $max - 3) . '...';
        }

        return $ext !== ''
            ? $name . '.' . strtolower($ext)
            : $name;
    }

    protected function countAdjuntos(array $adjuntos): int
    {
        $count = 0;

        foreach ($adjuntos as $a) {
            $name = is_array($a)
                ? trim((string)($a['nombre_original'] ?? $a['nombre'] ?? $a['filename'] ?? ''))
                : trim((string)$a);

            if ($name !== '') {
                $count++;
            }
        }

        return $count;
    }

    protected function normalizeMultilineText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $text = preg_replace("/\r\n|\r/u", "\n", $text);
        $text = preg_replace("/\n{3,}/u", "\n\n", $text);

        return $text;
    }

    protected function applyLayoutFormatting(string $spreadsheetId, string $sheetName): void
    {
        $spreadsheet = $this->sheets->spreadsheets->get($spreadsheetId);
        $sheetId = null;

        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $sheetName) {
                $sheetId = $sheet->getProperties()->getSheetId();
                break;
            }
        }

        if ($sheetId === null) {
            return;
        }

        $requests = [
            // Limpiar fondo y formato en celdas de valores lado derecho
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => 7,
                        'endRowIndex' => 11,
                        'startColumnIndex' => 6,
                        'endColumnIndex' => 7,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'backgroundColorStyle' => [
                                'rgbColor' => [
                                    'red' => 1,
                                    'green' => 1,
                                    'blue' => 1,
                                ],
                            ],
                            'horizontalAlignment' => 'CENTER',
                            'verticalAlignment' => 'MIDDLE',
                            'wrapStrategy' => 'WRAP',
                        ],
                    ],
                    'fields' => 'userEnteredFormat.backgroundColorStyle,userEnteredFormat.horizontalAlignment,userEnteredFormat.verticalAlignment,userEnteredFormat.wrapStrategy',
                ],
            ],

            // Hecho / motivo
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => 12,
                        'endRowIndex' => 13,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 8,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'wrapStrategy' => 'WRAP',
                            'verticalAlignment' => 'TOP',
                            'horizontalAlignment' => 'LEFT',
                            'textFormat' => [
                                'fontSize' => 10,
                            ],
                        ],
                    ],
                    'fields' => 'userEnteredFormat.wrapStrategy,userEnteredFormat.verticalAlignment,userEnteredFormat.horizontalAlignment,userEnteredFormat.textFormat.fontSize',
                ],
            ],

            // Faltas
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => 14,
                        'endRowIndex' => 15,
                        'startColumnIndex' => 2,
                        'endColumnIndex' => 8,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'wrapStrategy' => 'WRAP',
                            'verticalAlignment' => 'TOP',
                            'horizontalAlignment' => 'LEFT',
                            'textFormat' => [
                                'fontSize' => 9,
                            ],
                        ],
                    ],
                    'fields' => 'userEnteredFormat.wrapStrategy,userEnteredFormat.verticalAlignment,userEnteredFormat.horizontalAlignment,userEnteredFormat.textFormat.fontSize',
                ],
            ],

            // Pruebas
            [
                'repeatCell' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'startRowIndex' => 16,
                        'endRowIndex' => 18,
                        'startColumnIndex' => 0,
                        'endColumnIndex' => 8,
                    ],
                    'cell' => [
                        'userEnteredFormat' => [
                            'wrapStrategy' => 'WRAP',
                            'verticalAlignment' => 'MIDDLE',
                            'horizontalAlignment' => 'LEFT',
                            'textFormat' => [
                                'fontSize' => 9,
                            ],
                        ],
                    ],
                    'fields' => 'userEnteredFormat.wrapStrategy,userEnteredFormat.verticalAlignment,userEnteredFormat.horizontalAlignment,userEnteredFormat.textFormat.fontSize',
                ],
            ],

            [
                'autoResizeDimensions' => [
                    'dimensions' => [
                        'sheetId' => $sheetId,
                        'dimension' => 'ROWS',
                        'startIndex' => 12,
                        'endIndex' => 13,
                    ],
                ],
            ],

            [
                'autoResizeDimensions' => [
                    'dimensions' => [
                        'sheetId' => $sheetId,
                        'dimension' => 'ROWS',
                        'startIndex' => 14,
                        'endIndex' => 15,
                    ],
                ],
            ],

            // Altura filas pruebas
            [
                'updateDimensionProperties' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'dimension' => 'ROWS',
                        'startIndex' => 16,
                        'endIndex' => 18,
                    ],
                    'properties' => [
                        'pixelSize' => 28,
                    ],
                    'fields' => 'pixelSize',
                ],
            ],
        ];

        $this->sheets->spreadsheets->batchUpdate(
            $spreadsheetId,
            new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                'requests' => $requests,
            ])
        );
    }
}
