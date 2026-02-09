<?php

namespace App\Models;

use CodeIgniter\Model;

class FurdCitacionNotificacionModel extends Model
{
    protected $table         = 'tbl_furd_citacion_notificacion';
    protected $primaryKey    = 'id';

    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    protected $allowedFields = [
        'citacion_id',
        'canal',
        'destinatario',
        'estado',
        'mensaje_id',
        'error',
        'notificado_at',
    ];

    public function listByCitacion(int $citacionId): array
    {
        return $this->where('citacion_id', $citacionId)
            ->orderBy('notificado_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    public function latestByCitacion(int $citacionId): ?array
    {
        return $this->where('citacion_id', $citacionId)
            ->orderBy('notificado_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();
    }
}
