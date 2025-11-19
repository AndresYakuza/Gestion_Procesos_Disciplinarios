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

        $estado  = (string) $this->request->getGet('estado');
        $q       = (string) $this->request->getGet('q');
        $perPage = 10; // o lo que quieras

        // 👇 IMPORTANTE: armamos la query SOBRE EL MODELO ($f), no sobre builder aparte
        $f->select("
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
          ->join("(SELECT empleado_id, MAX(id) AS max_id
                   FROM tbl_empleado_contratos
                   WHERE (activo = 1 OR fecha_retiro IS NULL)
                   GROUP BY empleado_id) cmax", 'cmax.empleado_id = e.id', 'left')
          ->join('tbl_empleado_contratos c', 'c.id = cmax.max_id', 'left')
          ->join('tbl_proyectos p', 'p.id = c.proyecto_id', 'left');

        if ($estado !== '') {
            $f->where('tbl_furd.estado', $estado);
        }

        if ($q !== '') {
            $f->groupStart()
              ->like('tbl_furd.consecutivo', $q)
              ->orLike('e.numero_documento', $q)
              ->orLike('e.nombre_completo', $q)
              ->orLike('p.nombre', $q)
              ->groupEnd();
        }

        // 🔹 AQUÍ paginamos usando un grupo propio: 'seguimiento'
        $rows = $f->groupBy('tbl_furd.id')
                  ->orderBy('tbl_furd.created_at', 'DESC')
                  ->paginate($perPage, 'seguimiento');

        $pager = $f->pager; // lo enviamos a la vista

        $mapEstado = [
            'registrado' => 'Abierto',
            'citacion'   => 'En proceso',
            'descargos'  => 'En proceso',
            'soporte'    => 'En proceso',
            'decision'   => 'Cerrado',
            'archivado'  => 'Archivado',    
        ];

        $registros = [];
        foreach ($rows as $r) {
            $created = Time::parse($r['created_at']);
            $updated = Time::parse($r['updated_at']);

            $consec = 'PD-' . str_pad((string)$r['id'], 6, '0', STR_PAD_LEFT);

            $registros[] = [
                'consecutivo'     => $consec,
                'cedula'          => (string)($r['cedula'] ?? ''),
                'nombre'          => (string)($r['nombre'] ?? ''),
                'proyecto'        => (string)($r['proyecto'] ?? ''),
                'fecha'           => $created->format('d/m/Y'),
                'creado_en_iso'   => $created->toDateString(),
                'hecho'           => (string)($r['hecho'] ?? ''),
                'estado'          => $mapEstado[$r['estado']] ?? ucfirst((string)$r['estado']),
                'actualizado_en'  => $updated->format('d/m/Y H:i'),
            ];
        }

        return view('seguimiento/index', [
            'registros' => $registros,
            'estado'    => $estado,
            'q'         => $q,
            'pager'     => $pager,
        ]);
    }

    public function show(string $consecutivo)
    {
        return redirect()->to(site_url('linea-temporal/' . $consecutivo));
    }
}
