<?php
namespace App\UseCases\Contratos;

use App\Models\EmpleadoContratoModel;
use App\Http\Requests\Contratos\ListContratosRequest;
use CodeIgniter\Database\BaseBuilder;
use Config\Database;

class ListContratos
{
    public function __construct(private EmpleadoContratoModel $model)
    {
    }

    private function baseBuilder(): BaseBuilder
    {
        $db = Database::connect();
        return $db->table('tbl_empleado_contratos AS ec')
            ->select([
                'ec.*',
                'e.numero_documento',
                'e.nombre_completo',
                'p.nombre AS proyecto_nombre'
            ])
            ->join('tbl_empleados AS e', 'e.id = ec.empleado_id', 'left')
            ->join('tbl_proyectos AS p', 'p.id = ec.proyecto_id', 'left');
    }

    public function handle(ListContratosRequest $req): array
    {
        $b = $this->baseBuilder();

        // Filtros
        if ($req->empleado_id !== null)  $b->where('ec.empleado_id', $req->empleado_id);
        if ($req->proyecto_id !== null)  $b->where('ec.proyecto_id', $req->proyecto_id);
        if ($req->activo !== null)       $b->where('ec.activo', $req->activo);
        if ($req->estado_contrato)       $b->where('ec.estado_contrato', $req->estado_contrato);
        if ($req->contrato)              $b->where('ec.contrato', $req->contrato);
        if ($req->numero_documento)      $b->where('e.numero_documento', $req->numero_documento);
        if ($req->nomina_like)           $b->like('ec.nomina', $req->nomina_like, 'both');

        if ($req->desde_ingreso)         $b->where('ec.fecha_ingreso >=', $req->desde_ingreso);
        if ($req->hasta_ingreso)         $b->where('ec.fecha_ingreso <=', $req->hasta_ingreso);
        if ($req->desde_retiro)          $b->where('ec.fecha_retiro >=', $req->desde_retiro);
        if ($req->hasta_retiro)          $b->where('ec.fecha_retiro <=', $req->hasta_retiro);

        if ($req->q) {
            $b->groupStart()
                ->like('ec.contrato', $req->q, 'both')
                ->orLike('ec.cod_nomina', $req->q, 'both')
                ->orLike('e.numero_documento', $req->q, 'both')
                ->orLike('e.nombre_completo', $req->q, 'both')
                ->groupEnd();
        }

        // Conteo
        $total = (clone $b)->countAllResults();

        // Orden + paginación
        $b->orderBy('ec.' . $req->sortBy, $req->sortDir);
        $offset = ($req->page - 1) * $req->perPage;
        $rows = $b->get($req->perPage, $offset)->getResultArray();

        $lastPage = (int)ceil($total / max(1, $req->perPage));

        return [
            'data' => $rows,
            'meta' => [
                'total'        => $total,
                'per_page'     => $req->perPage,
                'current_page' => $req->page,
                'last_page'    => $lastPage
            ]
        ];
    }
}
