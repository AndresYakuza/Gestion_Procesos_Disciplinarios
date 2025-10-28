<?php
namespace App\Models;

use CodeIgniter\Model;

class EmpleadoModel extends Model
{
    protected $DBGroup      = 'default';           
    protected $table        = 'tbl_empleados';
    protected $primaryKey   = 'id';
    protected $useAutoIncrement = true;

    protected $returnType   = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'tipo_documento','numero_documento','nombre_completo',
        'nombre_1','nombre_2','apellido_1','apellido_2',
        'ciudad_expide','fecha_expide_cc',
        'eps','afp','fondo_cesantias','caja_compensacion','arl',
        'correo','telefono','celular',
        'direccion_vive','barrio_vive','estrato','ciudad_vive','dpto_vive',
        'profesion','ciudad_nac','dpto_nac','fecha_nacimiento','sexo',
        'estado_civil','grupo_sanguineo','grupo_social','mujer_cf',
        'libreta_militar','certificado_judicial','avecindad',
        'proyecto_id','activo',
        'audit_created_by','audit_updated_by',
        'created_at','updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
