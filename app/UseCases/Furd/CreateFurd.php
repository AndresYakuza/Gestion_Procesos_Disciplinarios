<?php
namespace App\UseCases\Furd;

use App\Models\FurdModel;

class CreateFurd
{
    public function __construct(private FurdModel $model) {}

    public function __invoke(array $data): array
    {
        $id = $this->model->insert($data, true);
        return $this->model->find($id);
    }
}
