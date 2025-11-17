<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;

class FileController extends Controller
{
    public function furd($id = null, $filename = null)
    {
        // Ruta física dentro de writable
        $path = WRITEPATH . "uploads/furd/{$id}/{$filename}";

        if (!is_file($path)) {
            throw PageNotFoundException::forPageNotFound("Archivo no encontrado");
        }

        // Devuelve el archivo como descarga o visualización
        return $this->response->download($path, null)->setFileName($filename);
    }
}
