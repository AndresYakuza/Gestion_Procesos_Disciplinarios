<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AdjuntoModel; // opcional, si ya lo tienes

class SoporteController extends BaseController
{
    /**
     * Mostrar formulario
     */
    public function create()
    {
        // si tienes usuario logueado, pásalo aquí
        $data = [
            'responsable' => '',
            'decisiones'  => $this->decisiones(),
        ];

        return view('soporte/index', $data);
    }

    /**
     * Guardar soportes y datos
     */
    public function store()
    {
        $rules = [
            'consecutivo' => 'required|is_natural_no_zero',
            'responsable' => 'permit_empty|max_length[120]',
            'decision'    => 'required|max_length[120]',
            // archivos se validan manualmente abajo (por ser múltiples)
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $consecutivo = (int) $this->request->getPost('consecutivo');
        $responsable = (string) $this->request->getPost('responsable');
        $decision    = (string) $this->request->getPost('decision');

        // Carpeta destino pública: public/uploads/furd/{id}/soporte
        $destDir = rtrim(FCPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'furd' . DIRECTORY_SEPARATOR . $consecutivo . DIRECTORY_SEPARATOR . 'soporte';
        if (! is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        // Procesar archivos múltiples (si hay)
        $moved = [];
        $files = $this->request->getFiles();

        if (! empty($files['soportes'])) {
            foreach ($files['soportes'] as $file) {
                if (! $file->isValid() || $file->hasMoved()) {
                    continue;
                }

                // Validación simple por tipo/tamaño
                $okMime  = in_array($file->getMimeType(), [
                    'application/pdf',
                    'image/jpeg', 'image/png', 'image/heic', 'image/heif', 'image/tiff',
                ], true);
                $okSize  = ($file->getSize() <= 20 * 1024 * 1024); // 20 MB

                if (! $okMime || ! $okSize) {
                    return redirect()->back()
                        ->withInput()
                        ->with('errors', ['soportes' => 'Alguno de los archivos no tiene un tipo permitido o excede el tamaño máximo (20 MB).']);
                }

                $newName = $file->getRandomName();
                $file->move($destDir, $newName);

                $publicUrl = base_url('uploads/furd/' . $consecutivo . '/soporte/' . $newName);
                $moved[] = [
                    'nombre_original' => $file->getClientName(),
                    'ruta'            => $publicUrl,
                    'mime'            => $file->getMimeType(),
                    'tamano_bytes'    => $file->getSize(),
                    'stored_path'     => $destDir . DIRECTORY_SEPARATOR . $newName, // por si necesitas
                ];
            }
        }

        // === (OPCIONAL) Persistir en BD los adjuntos, si tienes App\Models\AdjuntoModel ===
        if (! empty($moved) && class_exists(AdjuntoModel::class)) {
            try {
                $adj = new AdjuntoModel();
                foreach ($moved as $a) {
                    $adj->insert([
                        'origen'          => 'furd',                // ajusta si tu enum/campo difiere
                        'origen_id'       => $consecutivo,
                        'nombre_original' => $a['nombre_original'],
                        'ruta'            => $a['ruta'],
                        'mime'            => $a['mime'],
                        'tamano_bytes'    => $a['tamano_bytes'],
                        'audit_created_by'=> $responsable ?: 'sistema',
                    ]);
                }
            } catch (\Throwable $e) {
                // Si falla el registro, no impedimos la carga de archivos. Informamos.
                return redirect()->back()
                    ->with('errors', ['db' => 'Archivos subidos, pero ocurrió un error al registrar adjuntos: ' . $e->getMessage()]);
            }
        }

        // TODO: si necesitas registrar "responsable" y "decisión" en otra tabla,
        // hazlo aquí (p. ej., tabla soporte_furd o la misma FURD con columnas extra).

        return redirect()->to(site_url('soporte'))
            ->with('msg', 'Soportes guardados correctamente para el consecutivo #' . $consecutivo . '.');
    }

    /**
     * Catálogo simple de decisiones propuestas
     */
    private function decisiones(): array
    {
        return [
            'Llamado de atención',
            'Suspensión disciplinaria',
            'Terminación de contrato',
        ];
    }
}
