<?php

namespace App\Models;

use CodeIgniter\Model;

class FurdSoporteModel extends Model
{
    protected $table      = 'tbl_furd_soporte';
    protected $primaryKey = 'id';

    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    protected $allowedFields = [
        'furd_id',
        'responsable',
        'decision_propuesta',
        'justificacion',
        'cliente_estado',
        'cliente_decision',
        'cliente_justificacion',
        'cliente_comentario',
        'cliente_respondido_at'
    ];

    public function findByFurd(int $furdId): ?array
    {
        return $this->where('furd_id', $furdId)->first();
    }
}
