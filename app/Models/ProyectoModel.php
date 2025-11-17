<?php namespace App\Models;

use CodeIgniter\Model;

class ProyectoModel extends Model
{
    protected $table      = 'proyectos';
    protected $primaryKey = 'id';

    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = ['nombre', 'descripcion', 'activa'];
}
