<?php
namespace App\Models;

use CodeIgniter\Model;

class AdjuntoModel extends Model
{
    protected $table      = 'tbl_adjuntos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'origen','origen_id','nombre_original','ruta','mime','tamano_bytes',
        'audit_created_by','created_at'
    ];
}
