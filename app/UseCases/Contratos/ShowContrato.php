<?php
namespace App\UseCases\Contratos;

use Config\Database;

class ShowContrato
{
    public function handle(int $id): ?array
    {
        $db = Database::connect();
        $row = $db->table('tbl_empleado_contratos AS ec')
            ->select([
                'ec.*',
                'e.numero_documento',
                'e.nombre_completo',
                'p.nombre AS proyecto_nombre'
            ])
            ->join('tbl_empleados AS e', 'e.id = ec.empleado_id', 'left')
            ->join('tbl_proyectos AS p', 'p.id = ec.proyecto_id', 'left')
            ->where('ec.id', $id)
            ->get()->getRowArray();

        return $row ?: null;
    }
}
