<?php namespace App\Controllers\Traits;

use App\Models\FurdAdjuntoModel;
use App\Libraries\GDrive;

trait HandlesAdjuntos
{
    /**
     * Sube archivos a Google Drive y registra en tbl_adjuntos
     * para la fase indicada (registro, citacion, descargos, soporte, decision)
     */
    protected function saveAdjuntos(int $furdId, string $fase, $files): void
    {
        helper(['filesystem']);

        // Normalizar: puede venir un solo UploadedFile o un array
        if (!is_array($files)) {
            $files = [$files];
        }

        // Filtrar nulos / inválidos
        $files = array_filter($files, static fn($f) => $f && $f->isValid());
        if (!$files) {
            return;
        }

        $tmpDir = WRITEPATH . 'uploads/tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $root = (string) env('GDRIVE_ROOT', 'FURD');   // mismo root que usas en registro
        $g    = new GDrive();

        // Carpeta: FURD/AAAA/{furd_id}/{fase}
        $folderPath = rtrim($root, '/') . '/' . date('Y') . '/' . $furdId . '/' . $fase;
        $parentId   = $g->ensurePath($folderPath);

        $am = new FurdAdjuntoModel();

        foreach ($files as $file) {
            if (!$file->isValid()) {
                continue;
            }

            $newName  = $file->getRandomName();
            $original = $file->getClientName();
            $mime     = $file->getClientMimeType();
            $size     = (int) $file->getSize();

            // Mover a tmp
            $file->move($tmpDir, $newName);
            $src = $tmpDir . '/' . $newName;
            if (!is_file($src)) {
                continue;
            }

            $sha1 = @sha1_file($src) ?: null;

            // Subir a Drive
            $up = $g->upload($src, $original, $mime, $parentId);

            // Borrar tmp local
            @unlink($src);

            // Registrar en tbl_adjuntos (misma estructura que en registro)
            $am->insert([
                'origen'                  => 'furd',
                'origen_id'               => $furdId,
                'fase'                    => $fase,
                'nombre_original'         => $original,
                'ruta'                    => $folderPath . '/' . $original, // ruta "lógica"
                'mime'                    => $mime,
                'tamano_bytes'            => $size,
                'sha1'                    => $sha1,
                'storage_provider'        => 'gdrive',
                'drive_file_id'           => $up['id']            ?? null,
                'drive_web_view_link'     => $up['webViewLink']   ?? null,
                'drive_web_content_link'  => $up['webContentLink']?? null,
                'created_at'              => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
