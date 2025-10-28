<?php
namespace App\UseCases\Furd;

use App\Models\FurdModel;
use App\Models\FurdFaltaModel;
use App\Models\AdjuntoModel;

class DeleteFurd
{
    public function __construct(
        private FurdModel $furd,
        private FurdFaltaModel $ff,
        private AdjuntoModel $adj
    ){}

    public function __invoke(int $id): bool
    {
        $this->ff->where('furd_id',$id)->delete();
        $this->adj->where(['origen'=>'furd','origen_id'=>$id])->delete();
        return (bool)$this->furd->delete($id);
    }
}
