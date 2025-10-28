<?php
namespace App\UseCases\Furd;

use App\Models\AdjuntoModel;

class DeleteAdjunto
{
    public function __construct(private AdjuntoModel $model) {}

    public function __invoke(int $id): bool
    {
        $row = $this->model->find($id);
        if (!$row) return true;
        $ok = (bool)$this->model->delete($id);
        $path = WRITEPATH . $row['ruta'];
        if ($ok && is_file($path)) @unlink($path);
        return $ok;
    }
}
