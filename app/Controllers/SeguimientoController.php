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
        $perPage = 10;

        $f->select("
            tbl_furd.id,
            tbl_furd.hecho         AS hecho_registro,
            cit.motivo             AS motivo_citacion,
            tbl_furd.estado,
            tbl_furd.fecha_evento,
            tbl_furd.created_at,
            tbl_furd.updated_at,
            e.numero_documento     AS cedula,
            e.nombre_completo      AS nombre,
            p.nombre               AS proyecto
        ")
            ->join('tbl_empleados e', 'e.id = tbl_furd.empleado_id', 'left')
            ->join(
                "(SELECT empleado_id, MAX(id) AS max_id
               FROM tbl_empleado_contratos
               WHERE (activo = 1 OR fecha_retiro IS NULL)
               GROUP BY empleado_id) cmax",
                'cmax.empleado_id = e.id',
                'left'
            )
            ->join('tbl_empleado_contratos c', 'c.id = cmax.max_id', 'left')
            ->join('tbl_proyectos p', 'p.id = c.proyecto_id', 'left')
            // 👇 último registro de citación por FURD
            ->join(
                "(SELECT furd_id, MAX(id) AS max_id
               FROM tbl_furd_citacion
               GROUP BY furd_id) citmax",
                'citmax.furd_id = tbl_furd.id',
                'left'
            )
            ->join('tbl_furd_citacion cit', 'cit.id = citmax.max_id', 'left');

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

        $rows = $f->groupBy('tbl_furd.id')
            ->orderBy('tbl_furd.created_at', 'DESC')
            ->paginate($perPage, 'seguimiento');

        $pager = $f->pager;

        $mapEstado = [
            'registro' => 'Abierto / Registro',
            'citacion' => 'En proceso / Citación',
            'descargos' => 'En proceso / Descargos',
            'soporte'  => 'En proceso / Soporte',
            'decision' => 'Cerrado / Decisión',
            'archivado' => 'Archivado',
        ];

        $registros = [];
        foreach ($rows as $r) {
            $created = Time::parse($r['created_at']);
            $updated = Time::parse($r['updated_at']);

            $consec = 'PD-' . str_pad((string)$r['id'], 6, '0', STR_PAD_LEFT);

            $hechoRegistro = (string)($r['hecho_registro'] ?? '');
            $hechoCitacion = (string)($r['motivo_citacion'] ?? '');

            // 👉 si hay motivo en citación, usarlo; si no, el del registro
            $hechoMostrar  = $hechoCitacion !== '' ? $hechoCitacion : $hechoRegistro;

            $registros[] = [
                'consecutivo'    => $consec,
                'cedula'         => (string)($r['cedula'] ?? ''),
                'nombre'         => (string)($r['nombre'] ?? ''),
                'proyecto'       => (string)($r['proyecto'] ?? ''),
                'fecha'          => $created->format('d/m/Y'),
                'creado_en_iso'  => $created->toDateString(),
                'hecho'          => $hechoMostrar,          // 👈 aquí ya viene el correcto
                'estado'         => $mapEstado[$r['estado']] ?? ucfirst((string)$r['estado']),
                'actualizado_en' => $updated->format('d/m/Y H:i'),
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
