<?php
namespace App\UseCases\Projects;

use App\Models\ProyectoAliasModel;

class CreateAlias
{
    public function __construct(private ProyectoAliasModel $model) {}

    public function __invoke(array $data): array
    {
        // único por alias_norm
        $dup = $this->model->where('alias_norm',$data['alias_norm'])->first();
        if ($dup) throw new \RuntimeException('El alias ya existe');

        $id = $this->model->insert($data, true);
        return $this->model->find($id);
    }
}
