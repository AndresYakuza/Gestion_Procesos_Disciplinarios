<?php

namespace App\Models;

use CodeIgniter\Model;

class EmpleadoModel extends Model
{
    protected $table      = 'tbl_empleados';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'numero_documento',
        'tipo_documento',
        'expedida_en',
        'nombre_completo',
        'barrio_vive',
        'estrato',
        'celular',
        'ciudad_vive',
        'dpto_vive',
        'profesion',
        'correo',
        'telefono',
        'proyecto_id',
        'activo',
        'audit_created_by',
        'audit_updated_by',
        'created_at',
        'updated_at'
    ];
    protected $useTimestamps = true;
}
