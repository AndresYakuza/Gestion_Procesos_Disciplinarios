<?php namespace App\Models;

use CodeIgniter\Model;

class EmpleadoContratoModel extends Model
{
    protected $table      = 'tbl_empleado_contratos';
    protected $primaryKey = 'id';

    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'empleado_id','contrato','cod_nomina','nomina','proyecto_id',
        'sueldo','cargo_sige','cargo','categoria','codigo',
        'fecha_ingreso','fecha_retiro','activo',
        'tipo_contrato','duracion','nivel','fecha_sige',
        'centro_costo','dpto','division','centro_trabajo',
        'tipo_ingreso','periodo_pago',
        'tipo_cuenta','banco','cuenta','porcentaje_arl',
        'primera_vez','usuario_contrato','ultimo_cambio',
        'estado_contrato','cno','nombre_cno'
    ];

    public function findActiveByEmpleado(int $empleadoId): ?array
    {
        return $this->where('empleado_id', $empleadoId)
                    ->where('activo', 1)
                    ->orderBy('fecha_ingreso', 'DESC')
                    ->first();
    }
}
 