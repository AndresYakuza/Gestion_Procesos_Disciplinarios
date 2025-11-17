<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\FurdModel;
use CodeIgniter\I18n\Time;

class SeguimientoController extends BaseController
{
    public function index()
    {
        $f = new FurdModel();

        // Filtros desde la URL (?estado=xxx&q=xxx)
        $estado = (string) $this->request->getGet('estado');
        $q      = (string) $this->request->getGet('q');

        // Construir la consulta base (evitar duplicados por múltiples contratos)
        $builder = $f->select("
                tbl_furd.id,
                tbl_furd.hecho,
                tbl_furd.estado,
                tbl_furd.fecha_evento,
                tbl_furd.created_at,
                tbl_furd.updated_at,
                e.numero_documento  AS cedula,
                e.nombre_completo   AS nombre,
                p.nombre            AS proyecto
            ")
            ->join('tbl_empleados e', 'e.id = tbl_furd.empleado_id', 'left')

            // Subconsulta: tomar un solo contrato (el activo o el más reciente) por empleado
            ->join("(SELECT empleado_id, MAX(id) AS max_id
                     FROM tbl_empleado_contratos
                     WHERE (activo = 1 OR fecha_retiro IS NULL)
                     GROUP BY empleado_id) cmax", 'cmax.empleado_id = e.id', 'left')

            // Ya con un único contrato, relacionar proyecto
            ->join('tbl_empleado_contratos c', 'c.id = cmax.max_id', 'left')
            ->join('tbl_proyectos p', 'p.id = c.proyecto_id', 'left');

        // Aplicar filtros dinámicos si existen
        if ($estado !== '') {
            $builder->where('tbl_furd.estado', $estado);
        }

        if ($q !== '') {
            $builder->groupStart()
                ->like('tbl_furd.consecutivo', $q)
                ->orLike('e.numero_documento', $q)
                ->orLike('e.nombre_completo', $q)
                ->orLike('p.nombre', $q)
                ->groupEnd();
        }

        // Obtener los últimos 500 registros (forzando una fila por FURD)
        $rows = $builder
                    ->groupBy('tbl_furd.id')
                    ->orderBy('tbl_furd.created_at', 'DESC')
                    ->findAll(500);

        // Mapear estados a textos legibles
        $mapEstado = [
            'registrado' => 'Abierto',
            'citacion'   => 'En proceso',
            'descargos'  => 'En proceso',
            'soporte'    => 'En proceso',
            'decision'   => 'Cerrado',
        ];

        // Construir arreglo final para la vista
        $registros = [];
        foreach ($rows as $r) {
            $anio   = Time::parse($r['created_at'])->getYear();
            $consec = 'PD-' . str_pad((string)$r['id'], 6, '0', STR_PAD_LEFT);

            $registros[] = [
                'consecutivo'    => $consec,
                'cedula'         => (string)($r['cedula'] ?? ''),
                'nombre'         => (string)($r['nombre'] ?? ''),
                'proyecto'       => (string)($r['proyecto'] ?? ''),
                'fecha' => Time::parse($r['created_at'])->format('d/m/Y'),
                'hecho'          => (string)($r['hecho'] ?? ''),
                'estado'         => $mapEstado[$r['estado']] ?? ucfirst((string)$r['estado']),
                'actualizado_en' => date('d-m-Y (H:i)', strtotime($r['updated_at'])),
            ];
        }

        // Renderizar la vista con los datos
        return view('seguimiento/index', [
            'registros' => $registros,
            'estado'    => $estado,
            'q'         => $q
        ]);
    }

    public function show(string $consecutivo)
    {
        // Redirige a la línea temporal correspondiente
        return redirect()->to(site_url('linea-temporal/' . $consecutivo));
    }
}
