<?php namespace App\Models;

use CodeIgniter\Model;

class FurdDecisionModel extends Model
{
    protected $table      = 'tbl_furd_decision';
    protected $primaryKey = 'id';

    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    protected $allowedFields = [
        'furd_id',
        'fecha_evento',
        'decision_text',
    ];

    public function findByFurd(int $furdId): ?array
    {
        return $this->where('furd_id', $furdId)->first();
    }
}
