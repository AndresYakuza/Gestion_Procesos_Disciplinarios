<?php

namespace App\Controllers;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use App\Models\EmpleadoModel;

class EmpleadoLookupController extends Controller
{
    use ResponseTrait;

    /**
     * GET /empleados/lookup/{cedula}
     * Devuelve datos básicos del empleado y su contrato activo (vista vw_empleado_contrato_activo).
     */
    public function getByCedula(string $cedula)
    {
        $db = \Config\Database::connect();

        $emp = (new EmpleadoModel())->where('numero_documento', $cedula)->first();
        if (!$emp) {
            return $this->respond(['found' => false, 'message' => 'No encontrado'], 404);
        }

        $contrato = $db->table('vw_empleado_contrato_activo')
            ->where('empleado_id', $emp['id'])
            ->get()->getRowArray();

        // empresa_usuaria: COALESCE(centro_trabajo, nomina) ya viene en la vista
        $payload = [
            'found'            => true,
            'empleado'         => [
                'id'               => (int)$emp['id'],
                'numero_documento' => $emp['numero_documento'],
                'nombre_completo'  => $emp['nombre_completo'],
                'correo'           => $emp['correo'],
                'ciudad_expide'    => $emp['ciudad_expide'],
            ],
            'contrato_activo'  => $contrato ? [
                'contrato'        => $contrato['contrato'],
                'empresa_usuaria' => $contrato['empresa_usuaria'],
                'fecha_ingreso'   => $contrato['fecha_ingreso'],
                'sueldo'          => $contrato['sueldo'],
                'tipo_contrato'   => $contrato['tipo_contrato'],
                'cargo'           => $contrato['cargo'],
            ] : null,
        ];

        return $this->respond($payload);
    }
}
