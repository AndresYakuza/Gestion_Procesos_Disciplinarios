<?php
namespace App\UseCases\Furd;

use App\Models\FurdFaltaModel;

class DetachFalta
{
    public function __construct(private FurdFaltaModel $model) {}

    public function __invoke(int $furdId, int $faltaId): bool
    {
        return (bool)$this->model->where(compact('furd_id','falta_id'))->delete();
    }
}
