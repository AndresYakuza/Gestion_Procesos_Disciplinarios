<?php
namespace App\UseCases\Furd;

use App\Models\FurdFaltaModel;

class AttachFalta
{
    public function __construct(private FurdFaltaModel $model) {}

    public function __invoke(int $furdId, int $faltaId): bool
    {
        $exists = $this->model->where(compact('furd_id','falta_id'))->first();
        if ($exists) return true;
        return (bool)$this->model->insert(['furd_id'=>$furdId,'falta_id'=>$faltaId]);
    }
}
