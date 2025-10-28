<?php
namespace App\UseCases\Furd;

use App\Models\AdjuntoModel;

class UploadAdjunto
{
    public function __construct(private AdjuntoModel $model) {}

    public function __invoke(int $furdId, \CodeIgniter\HTTP\Files\UploadedFile $file, string $user='system'): array
    {
        $dir = WRITEPATH . 'uploads/furd';
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $newName = $file->getRandomName();
        $file->move($dir, $newName);

        $id = $this->model->insert([
            'origen'          => 'furd',
            'origen_id'       => $furdId,
            'nombre_original' => $file->getClientName(),
            'ruta'            => 'uploads/furd/'.$newName,
            'mime'            => $file->getClientMimeType(),
            'tamano_bytes'    => $file->getSize(),
            'audit_created_by'=> $user,
            'created_at'      => date('Y-m-d H:i:s'),
        ], true);

        return $this->model->find($id);
    }
}
