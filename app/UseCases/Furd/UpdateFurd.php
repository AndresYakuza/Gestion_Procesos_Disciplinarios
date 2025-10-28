<?php
namespace App\UseCases\Furd;

use App\Models\FurdModel;

class UpdateFurd
{
    public function __construct(private FurdModel $model) {}

    public function __invoke(int $id, array $data): array
    {
        $row = $this->model->find($id);
        if (!$row) throw new \RuntimeException('FURD no encontrado');
        $this->model->update($id, $data);
        return $this->model->find($id);
    }
}
