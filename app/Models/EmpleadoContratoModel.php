<?php
namespace App\Models;

use CodeIgniter\Model;

class EmpleadoContratoModel extends Model
{
    protected $DBGroup      = 'default';            
    protected $table        = 'tbl_empleado_contratos';
    protected $primaryKey   = 'id';
    protected $useAutoIncrement = true;

    protected $returnType   = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'empleado_id','contrato','cod_nomina','nomina','proyecto_id',
        'sueldo','cargo_sige','cargo','categoria','codigo',
        'fecha_ingreso','fecha_retiro','activo',
        'tipo_contrato','duracion','nivel','fecha_sige',
        'centro_costo','dpto','division','centro_trabajo','tipo_ingreso',
        'periodo_pago','tipo_cuenta','banco','cuenta',
        'porcentaje_arl','primera_vez','usuario_contrato',
        'ultimo_cambio','estado_contrato','cno','nombre_cno',
        'audit_created_by','audit_updated_by','created_at','updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
