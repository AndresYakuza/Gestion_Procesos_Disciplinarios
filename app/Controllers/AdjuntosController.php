<?php namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\FurdAdjuntoModel;

class AdjuntosController extends BaseController
{
    public function download(int $id)
    {
        $row = (new FurdAdjuntoModel())->find($id);
        if (!$row) {
            return redirect()->back()->with('errors', ['Archivo no disponible']);
        }

        if (($row['storage_provider'] ?? 'local') === 'gdrive') {
            $url = (string)($row['drive_web_content_link'] ?? $row['drive_web_view_link'] ?? '');
            if ($url !== '') {
                return redirect()->to($url); // descarga o vista en Drive
            }
            return redirect()->back()->with('errors', ['No hay enlace público configurado']);
        }

        // Local (compatibilidad)
        $abs = WRITEPATH . 'uploads/' . ltrim((string)($row['ruta'] ?? ''), '/\\');
        if (!is_file($abs)) {
            return redirect()->back()->with('errors', ['Archivo no disponible']);
        }

        return $this->response->download($abs, null)->setFileName($row['nombre_original'] ?: basename($abs));
    }

    public function delete(int $id)
    {
        $m = new FurdAdjuntoModel();
        if (! $m->deleteAndUnlink($id)) {
            return redirect()->back()->with('errors', ['No existe o no se pudo eliminar']);
        }
        return redirect()->back()->with('ok', 'Adjunto eliminado');
    }

     public function open(int $id)
    {
        $row = (new FurdAdjuntoModel())->find($id);
        if (!$row) {
            return redirect()->back()->with('errors', ['Archivo no disponible']);
        }

        // GDrive: abrir en visor
        if (($row['storage_provider'] ?? 'local') === 'gdrive') {
            $view = (string)($row['drive_web_view_link'] ?? '');
            // Fallback por si no quedó guardado el enlace (construimos uno válido)
            if ($view === '' && !empty($row['drive_file_id'])) {
                $view = 'https://drive.google.com/file/d/' . $row['drive_file_id'] . '/view?usp=sharing';
            }
            if ($view !== '') {
                return redirect()->to($view);
            }
            return redirect()->back()->with('errors', ['No hay enlace de vista disponible']);
        }

        // Local: intenta mostrar inline si es PDF/imagen, si no, descarga
        $abs = WRITEPATH . 'uploads/' . ltrim((string)($row['ruta'] ?? ''), '/\\');
        if (!is_file($abs)) {
            return redirect()->back()->with('errors', ['Archivo no disponible']);
        }

        $mime = (string)($row['mime'] ?? 'application/octet-stream');
        $name = $row['nombre_original'] ?: basename($abs);

        // Mostrar inline: PDFs e imágenes suelen abrirse en el navegador
        if (str_starts_with($mime, 'image/') || $mime === 'application/pdf') {
            return $this->response
                ->setHeader('Content-Type', $mime)
                ->setHeader('Content-Disposition', 'inline; filename="'.addslashes($name).'"')
                ->setBody(file_get_contents($abs));
        }

        // Otros tipos: descarga
        return $this->response->download($abs, null)->setFileName($name);
    }
}
