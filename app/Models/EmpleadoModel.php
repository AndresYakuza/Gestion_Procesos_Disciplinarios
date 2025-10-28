<?php
namespace App\Models;

use CodeIgniter\Model;

class EmpleadoModel extends Model
{
    protected $table      = 'tbl_empleados';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'tipo_documento','numero_documento','nombre_completo',
        'nombre_1','nombre_2','apellido_1','apellido_2',
        'ciudad_expide','fecha_expide_cc',
        'fecha_nacimiento','ciudad_nac','dpto_nac',
        'sexo','estado_civil','grupo_sanguineo','grupo_social','mujer_cf',
        'eps','afp','fondo_cesantias','caja_compensacion','arl',
        'direccion_vive','barrio_vive','estrato','ciudad_vive','dpto_vive',
        'profesion','avecindad','libreta_militar','certificado_judicial','dto_lmil',
        'talla_camisa','talla_pantalon','talla_zapatos','peso','estatura',
        'correo','telefono','celular',
        'proyecto_id','activo',
        'audit_created_by','audit_updated_by','created_at','updated_at'
    ];

    protected $useTimestamps = true;
}
