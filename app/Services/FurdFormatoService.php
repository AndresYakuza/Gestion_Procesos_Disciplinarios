<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as DrawingHelper;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class FurdFormatoService
{
    private string $outputDir;

    // IDs de tu Google Sheet
    private string $googleFileId = '1OAIX288CLT2uugoQDlxTAkeWJb962HiC_GMwXRkyETo';
    private string $googleGid    = '27165640';

    /**
     * Última fila real del formato
     */
    private int $printLastRow = 25;

    /**
     * Última columna real del formato
     */
    private string $printLastColumn = 'H';

    /**
     * Si quieres forzar escala fija, pon un número (ej 160).
     * Si lo dejas null, calcula escala automática para llenar la hoja.
     */
    private ?int $forcePdfScale = null;

    /**
     * Límite de escala automática (para evitar que se pase y cree 2 páginas)
     */
    private int $maxAutoScale = 190;

    public function __construct()
    {
        $this->outputDir = WRITEPATH . 'furd_formatos';

        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }
    }

    private function getGoogleExportUrl(): string
    {
        return sprintf(
            'https://docs.google.com/spreadsheets/d/%s/export?format=xlsx&gid=%s',
            $this->googleFileId,
            $this->googleGid
        );
    }

    private function getTemplateXlsxPath(): string
    {
        $tmpXlsx = $this->outputDir
            . DIRECTORY_SEPARATOR
            . 'RH_FO23_' . date('Ymd_His') . '_' . mt_rand(1000, 9999) . '.xlsx';

        $url = $this->getGoogleExportUrl();

        $context = stream_context_create([
            'http' => ['timeout' => 15],
        ]);

        $data = @file_get_contents($url, false, $context);

        if ($data === false || strlen($data) < 1024) {
            throw new \RuntimeException('No se pudo descargar la plantilla RH-FO23 desde Google Sheets.');
        }

        if (@file_put_contents($tmpXlsx, $data) === false) {
            throw new \RuntimeException('No se pudo escribir la plantilla RH-FO23 descargada.');
        }

        return $tmpXlsx;
    }

    /**
     * Aplica estilos "seguros" (sin tocar heights/widths del template).
     * Se enfoca en fuente y alineación para que se vea formal.
     */
    private function applySafeStyles(Worksheet $sheet): void
    {
        // Fuente base general
        $sheet->getParent()->getDefaultStyle()->getFont()
            ->setName('Arial')
            ->setSize(10);

        // Encabezado verde (como estaba al inicio: B1:F3)
        $headerGreen = 'B1:F3';
        $sheet->getStyle($headerGreen)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('0B5A2A');

        $sheet->getStyle($headerGreen)->getFont()
            ->setBold(true)
            ->setSize(11)
            ->getColor()->setRGB('FFFFFF');

        $sheet->getStyle($headerGreen)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        // Bloque derecho (como estaba al inicio)
        $sheet->getStyle('G1:G3')->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('G1:H3')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        // Fila 5 (Fecha/Consecutivo)
        $sheet->getStyle('A5:B5')->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('E5:F5')->getFont()->setBold(true)->setSize(9);

        $sheet->getStyle('A5:B5')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setIndent(1);

        $sheet->getStyle('E5:F5')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setIndent(1);

        // Fecha/hora en una sola línea y sin wrap (mejor aspecto)
        $sheet->getStyle('C5:D5')->getFont()->setSize(10);
        $sheet->getStyle('C5:D5')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(false)
            ->setIndent(0);

        $sheet->getStyle('G5:H5')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        // Encabezados de secciones
        foreach (['A7:D7', 'E7:H7'] as $rng) {
            $sheet->getStyle($rng)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle($rng)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }

        // Labels y valores en datos trabajador/evento
        $labelRanges = ['A8:B11', 'E8:F11', 'A15:B16', 'A17:B18'];
        foreach ($labelRanges as $rng) {
            $sheet->getStyle($rng)->getFont()->setBold(true)->setSize(9);
            $sheet->getStyle($rng)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true)
                ->setIndent(1);
        }

        $valueRanges = ['C8:D11', 'G8:H11', 'G11', 'C15:H16'];
        foreach ($valueRanges as $rng) {
            $sheet->getStyle($rng)->getFont()->setSize(9);
            $sheet->getStyle($rng)->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setWrapText(true);
        }


        // Título HECHO
        $sheet->getStyle('A12:H12')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A12:H12')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // HECHO: letra un poco más grande
        $sheet->getStyle('A13:H14')->getFont()->setSize(10);
        $sheet->getStyle('A13:H14')->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setIndent(1);

        // FALTAS: letra un poco más grande
        $sheet->getStyle('C15:H16')->getFont()->setSize(10);
        $sheet->getStyle('C15:H16')->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setIndent(1);

        // Título pruebas
        $sheet->getStyle('A16:H16')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A16:H16')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Adjuntos / pruebas
        $sheet->getStyle('A17:H18')->getFont()->setSize(8);
        $sheet->getStyle('A17:H18')->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setIndent(1);

        // Firma
        $sheet->getStyle('A19:H20')->getFont()->setBold(false)->setSize(10);
        $sheet->getStyle('A19:H20')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setIndent(1);

        // Pie
        $sheet->getStyle('A21:H25')->getFont()->setItalic(true)->setSize(7);
        $sheet->getStyle('A21:H25')->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /**
     * Ajusta el logo para que LLENE el rango combinado A1:A3.
     * No toca anchos/altos del sheet, solo el tamaño del Drawing.
     */
    private function fitLogoToMergedArea(Worksheet $sheet): void
    {
        $targetRange = 'A1:A3';

        $drawings = $sheet->getDrawingCollection();
        if (!$drawings || count($drawings) === 0) {
            return;
        }

        foreach ($drawings as $drawing) {
            $coord = method_exists($drawing, 'getCoordinates') ? $drawing->getCoordinates() : null;
            if (!$coord) continue;

            if (!$this->isCellInRange($coord, $targetRange)) {
                continue;
            }

            [$start, $end] = explode(':', $targetRange);
            [$startCol, $startRow] = Coordinate::coordinateFromString($start);
            [$endCol, $endRow]     = Coordinate::coordinateFromString($end);

            $startColIdx = Coordinate::columnIndexFromString($startCol);
            $endColIdx   = Coordinate::columnIndexFromString($endCol);

            $defaultFont = $sheet->getParent()->getDefaultStyle()->getFont();

            // Ancho total del rango
            $widthPx = 0;
            for ($c = $startColIdx; $c <= $endColIdx; $c++) {
                $colLetter = Coordinate::stringFromColumnIndex($c);
                $w = (float)$sheet->getColumnDimension($colLetter)->getWidth();
                if ($w <= 0) {
                    $w = (float)$sheet->getDefaultColumnDimension()->getWidth();
                }
                $widthPx += DrawingHelper::cellDimensionToPixels($w, $defaultFont);
            }

            // Alto total del rango
            $heightPx = 0;
            $defaultRowHeight = (float)$sheet->getDefaultRowDimension()->getRowHeight();
            if ($defaultRowHeight <= 0) $defaultRowHeight = 15.0;

            for ($r = (int)$startRow; $r <= (int)$endRow; $r++) {
                $h = (float)$sheet->getRowDimension($r)->getRowHeight();
                if ($h <= 0) $h = $defaultRowHeight;
                $heightPx += DrawingHelper::pointsToPixels($h);
            }

            if (method_exists($drawing, 'setResizeProportional')) {
                call_user_func([$drawing, 'setResizeProportional'], false);
            }

            if (method_exists($drawing, 'setCoordinates')) {
                $drawing->setCoordinates($start); // A1
            }
            if (method_exists($drawing, 'setOffsetX')) $drawing->setOffsetX(0);
            if (method_exists($drawing, 'setOffsetY')) $drawing->setOffsetY(0);

            // Ajuste por bordes
            $widthPx  = max(1, (int)($widthPx - 2));
            $heightPx = max(1, (int)($heightPx - 2));

            if (method_exists($drawing, 'setWidth'))  $drawing->setWidth($widthPx);
            if (method_exists($drawing, 'setHeight')) $drawing->setHeight($heightPx);

            break;
        }
    }

    private function isCellInRange(string $cell, string $range): bool
    {
        [$start, $end] = explode(':', $range);
        [$cellCol, $cellRow]   = Coordinate::coordinateFromString($cell);
        [$startCol, $startRow] = Coordinate::coordinateFromString($start);
        [$endCol, $endRow]     = Coordinate::coordinateFromString($end);

        $cellColIdx  = Coordinate::columnIndexFromString($cellCol);
        $startColIdx = Coordinate::columnIndexFromString($startCol);
        $endColIdx   = Coordinate::columnIndexFromString($endCol);

        return (
            (int)$cellRow >= (int)$startRow && (int)$cellRow <= (int)$endRow &&
            $cellColIdx >= $startColIdx && $cellColIdx <= $endColIdx
        );
    }

    /**
     * Calcula escala para aproximarse al alto útil de hoja CARTA.
     */
    private function calculateAutoScaleToFillPage(Worksheet $sheet): int
    {
        $paperHeightIn = 11.0;

        $margins = $sheet->getPageMargins();
        $top    = (float)$margins->getTop();
        $bottom = (float)$margins->getBottom();

        $usableHeightPt = ($paperHeightIn - $top - $bottom) * 72.0;
        if ($usableHeightPt <= 0) return 100;

        $defaultRowHeight = (float)$sheet->getDefaultRowDimension()->getRowHeight();
        if ($defaultRowHeight <= 0) $defaultRowHeight = 15.0;

        $contentHeightPt = 0.0;
        for ($r = 1; $r <= $this->printLastRow; $r++) {
            $h = (float)$sheet->getRowDimension($r)->getRowHeight();
            if ($h <= 0) $h = $defaultRowHeight;
            $contentHeightPt += $h;
        }

        if ($contentHeightPt <= 0) return 100;

        $scale = (int) round(($usableHeightPt / $contentHeightPt) * 100);

        if ($scale < 100) $scale = 100;
        if ($scale > $this->maxAutoScale) $scale = $this->maxAutoScale;

        return $scale;
    }

    private function configureSheetForPdf(Worksheet $sheet): void
    {
        foreach (array_keys($sheet->getBreaks()) as $cell) {
            $sheet->setBreak($cell, Worksheet::BREAK_NONE);
        }

        $pageSetup = $sheet->getPageSetup();

        if (method_exists($sheet, 'setShowGridlines')) {
            $sheet->setShowGridlines(false);
        }

        if (method_exists($sheet, 'setPrintGridlines')) {
            call_user_func([$sheet, 'setPrintGridlines'], false);
        }

        if (method_exists($sheet, 'setPrintHeadings')) {
            call_user_func([$sheet, 'setPrintHeadings'], false);
        } elseif (method_exists($sheet, 'setPrintRowColHeaders')) {
            call_user_func([$sheet, 'setPrintRowColHeaders'], false);
        }

        $printArea = sprintf('A1:%s%d', $this->printLastColumn, $this->printLastRow);
        $pageSetup->setPrintArea($printArea);

        $pageSetup->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_LETTER);

        $margins = $sheet->getPageMargins();
        $margins->setTop(0.2);
        $margins->setBottom(0.2);
        $margins->setLeft(0.2);
        $margins->setRight(0.2);
        $margins->setHeader(0);
        $margins->setFooter(0);

        $pageSetup->setFitToPage(false);
        $pageSetup->setFitToWidth(0);
        $pageSetup->setFitToHeight(0);

        $scale = $this->forcePdfScale ?? $this->calculateAutoScaleToFillPage($sheet);
        $pageSetup->setScale($scale);

        $pageSetup->setHorizontalCentered(true);

        $sheet->getHeaderFooter()->setOddHeader('');
        $sheet->getHeaderFooter()->setOddFooter('');
    }

    private function fillSheet(
        Spreadsheet $spreadsheet,
        array $furd,
        array $faltas,
        array $adjuntos = []
    ): void {
        $sheet = $spreadsheet->getActiveSheet();

        $consecutivo = $furd['consecutivo'] ?? sprintf('PD-%06d', $furd['id'] ?? 0);

        // Fecha + hora en una sola línea
        $fechaReporte = (new \DateTime('now', new \DateTimeZone('America/Bogota')))
            ->format('d/m/Y H:i');

        $fechaEvento    = $furd['fecha_evento'] ?? null;
        $fechaEventoFmt = $fechaEvento ? date('d/m/Y', strtotime($fechaEvento)) : '';
        $horaEvento     = $furd['hora_evento'] ?? '';
        $hecho          = $furd['hecho'] ?? '';

        $nombreTrab = $furd['nombre_trabajador'] ?? $furd['nombre_completo'] ?? '';
        $cedulaTrab = $furd['cedula_trabajador'] ?? $furd['cedula'] ?? '';
        $expedidaEn = $furd['expedida_en'] ?? '';
        $empresa    = $furd['empresa_usuaria'] ?? '';
        $superior   = $furd['superior'] ?? '';

        $sheet->setCellValue('C5', $fechaReporte);
        $sheet->setCellValue('G5', $consecutivo);

        $sheet->setCellValue('C8',  $nombreTrab);
        $sheet->setCellValue('C9',  $cedulaTrab);
        $sheet->setCellValue('C10', $expedidaEn);
        $sheet->setCellValue('C11', $empresa);

        $sheet->setCellValue('G8',  $fechaEventoFmt);
        $sheet->setCellValue('G9',  $horaEvento);
        $sheet->setCellValue('G10', $superior);

        // Adjuntos (cantidad)
        $nombresAdj = [];
        foreach ($adjuntos as $a) {
            $nombresAdj[] = $a['nombre_original'] ?? $a['nombre'] ?? $a['filename'] ?? null;
        }
        $nombresAdj = array_values(array_filter($nombresAdj));
        $sheet->setCellValue('G11', count($nombresAdj));

        // Hecho
        $maxHechoLen = 1200;
        $hechoClean  = trim((string)$hecho);
        $hechoCell   = (mb_strlen($hechoClean, 'UTF-8') > $maxHechoLen)
            ? mb_substr($hechoClean, 0, $maxHechoLen - 3, 'UTF-8') . '...'
            : $hechoClean;

        $sheet->setCellValue('A13', $hechoCell);

        // Faltas con línea en blanco entre cada una
        $items = [];
        foreach ($faltas as $f) {
            if (is_array($f)) {
                $codigo      = $f['codigo'] ?? '';
                $gravedad    = $f['gravedad'] ?? '';
                $descripcion = $f['descripcion'] ?? ($f['descripcion_falta'] ?? '');

                $prefix = trim($codigo . ($gravedad ? " ({$gravedad})" : ''));
                $line = trim(($prefix !== '' ? $prefix . ': ' : '') . (string)$descripcion);
                if ($line !== '') {
                    $items[] = $line;
                }
            } else {
                $line = trim((string)$f);
                if ($line !== '') {
                    $items[] = $line;
                }
            }
        }
        $sheet->setCellValue('C15', implode("\n\n", $items));

        // Lista adjuntos (hasta 4)
        $fmt = function (array $arr, int $pos): string {
            if (!isset($arr[$pos])) return '';
            return ($pos + 1) . '. ' . $arr[$pos];
        };

        $sheet->setCellValue('A17', $fmt($nombresAdj, 0));
        $sheet->setCellValue('A18', $fmt($nombresAdj, 1));
        $sheet->setCellValue('E17', $fmt($nombresAdj, 2));
        $sheet->setCellValue('E18', $fmt($nombresAdj, 3));
    }

    public function generar(array $furd, array $faltas, array $adjuntos = []): string
    {
        IOFactory::registerWriter('Pdf', \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf::class);

        $xlsxPath    = $this->getTemplateXlsxPath();
        $spreadsheet = IOFactory::load($xlsxPath);
        $sheet       = $spreadsheet->getActiveSheet();

        // Logo
        $this->fitLogoToMergedArea($sheet);

        // Estilos formales
        $this->applySafeStyles($sheet);

        // Llenar datos
        $this->fillSheet($spreadsheet, $furd, $faltas, $adjuntos);

        // Config PDF
        $this->configureSheetForPdf($sheet);

        $consecutivo = $furd['consecutivo'] ?? sprintf('PD-%06d', $furd['id'] ?? 0);
        $filename    = sprintf('RH_FO23_%s.pdf', $consecutivo);
        $fullPath    = $this->outputDir . DIRECTORY_SEPARATOR . $filename;

        $writer = IOFactory::createWriter($spreadsheet, 'Pdf');
        $writer->save($fullPath);

        if (file_exists($xlsxPath)) {
            unlink($xlsxPath);
        }

        return $fullPath;
    }

    public function generarPdf(array $furd, array $faltas, array $adjuntos = []): string
    {
        return $this->generar($furd, $faltas, $adjuntos);
    }
}