<?php
namespace App\Models;

use CodeIgniter\Model;

class ProyectoAliasModel extends Model
{
    protected $table         = 'tbl_proyecto_alias';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['proyecto_id','alias','alias_norm','created_at','updated_at'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
