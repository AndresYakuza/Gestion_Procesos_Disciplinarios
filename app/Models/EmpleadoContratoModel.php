<?php
namespace App\Models;

use CodeIgniter\Model;

class EmpleadoContratoModel extends Model
{
    protected $table      = 'tbl_empleado_contratos';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'empleado_id','contrato','cod_nomina','proyecto_id','sueldo',
        'cargo_sige','cargo','categoria','codigo',
        'fecha_ingreso','fecha_retiro','activo',
        'audit_created_by','audit_updated_by','created_at','updated_at'
    ];
    protected $useTimestamps = true;
}
