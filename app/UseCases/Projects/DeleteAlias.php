<?php
namespace App\UseCases\Projects;

use App\Models\ProyectoAliasModel;

class DeleteAlias
{
    public function __construct(private ProyectoAliasModel $model) {}

    public function __invoke(int $id): bool
    {
        $row = $this->model->find($id);
        if (!$row) return true;
        return (bool)$this->model->delete($id);
    }
}
