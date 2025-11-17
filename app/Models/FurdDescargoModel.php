<?php namespace App\Models;

use CodeIgniter\Model;

class FurdDescargoModel extends Model
{
    protected $table      = 'tbl_furd_descargos';
    protected $primaryKey = 'id';

    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    protected $allowedFields = [
        'furd_id',
        'fecha_evento',
        'hora',
        'medio',
        'observacion'
    ];

    /**
     * Obtiene el registro de descargos asociado a un FURD por ID numérico.
     */
    public function findByFurd(int $furdId): ?array
    {
        return $this->where('furd_id', $furdId)->first();
    }
}
