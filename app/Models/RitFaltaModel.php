<?php
namespace App\Models;

use CodeIgniter\Model;

class RitFaltaModel extends Model
{
    protected $table      = 'tbl_rit_faltas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $allowedFields = [
        'codigo','descripcion','gravedad','activo',
        'audit_created_by','audit_updated_by','created_at','updated_at'
    ];
}
