<?php
namespace App\UseCases\Projects;

use App\Models\ProyectoAliasModel;

class UpdateAlias
{
    public function __construct(private ProyectoAliasModel $model) {}

    public function __invoke(int $id, array $data): array
    {
        $row = $this->model->find($id);
        if (!$row) throw new \RuntimeException('Alias no encontrado');

        if (isset($data['alias_norm'])) {
            $dup = $this->model->where('alias_norm',$data['alias_norm'])
                               ->where('id !=',$id)->first();
            if ($dup) throw new \RuntimeException('Otro alias ya usa ese valor normalizado');
        }

        $this->model->update($id, $data);
        return $this->model->find($id);
    }
}
