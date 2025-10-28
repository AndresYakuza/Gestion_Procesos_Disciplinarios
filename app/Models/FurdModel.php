<?php
namespace App\Models;

use CodeIgniter\Model;

class FurdModel extends Model
{
    protected $table      = 'tbl_furd';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'colaborador_id','fecha_evento','turno','hora_evento',
        'supervisor_id','hecho','estado',
        'audit_created_by','audit_updated_by','created_at','updated_at'
    ];
}
