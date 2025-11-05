<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\FurdModel;
use CodeIgniter\I18n\Time;

class SeguimientoController extends BaseController
{
     public function index()
    {

        // === DEMO: dataset estático ===
        $rows = [
            [
                'id'             => 101,
                'consecutivo'    => 'FURD-2025-0001',
                'cedula'         => '1049536932',
                'nombre'         => 'Karina Blanco',
                'proyecto'       => 'CLARO WHATSAPP',
                'fecha'          => '2025-10-21',
                'hecho'          => 'Incumplimiento de instrucción operativa en turno.',
                'estado'         => 'DECISIÓN',
                'actualizado_en' => '2025-10-21 14:35:00',
            ],
            [
                'id'             => 102,
                'consecutivo'    => 'FURD-2025-0002',
                'cedula'         => '100200300',
                'nombre'         => 'Andrés Yakuza',
                'proyecto'       => 'EXTRACCION_NOTAS_RR',
                'fecha'          => '2025-10-20',
                'hecho'          => 'Retraso reiterado en el inicio de sesión (3 días).',
                'estado'         => 'CARGOS Y DESCARGOS',
                'actualizado_en' => '2025-10-21 11:10:00',
            ],
            [
                'id'             => 103,
                'consecutivo'    => 'FURD-2025-0003',
                'cedula'         => '800700600',
                'nombre'         => 'David Rojas',
                'proyecto'       => 'FIJA LECTURABILIDAD',
                'fecha'          => '2025-10-19',
                'hecho'          => 'Uso indebido de credenciales compartidas.',
                'estado'         => 'REGISTRO',
                'actualizado_en' => '2025-10-19 17:45:12',
            ],
            [
                'id'             => 104,
                'consecutivo'    => 'FURD-2025-0004',
                'cedula'         => '1092837465',
                'nombre'         => 'Lina Hurtado',
                'proyecto'       => 'CALL CENTER FIJA',
                'fecha'          => '2025-10-22',
                'hecho'          => 'Ausencia sin justificación el 20 y 21 de octubre.',
                'estado'         => 'SOPORTE',
                'actualizado_en' => '2025-10-24 15:00:00',
            ],
            [
                'id'             => 105,
                'consecutivo'    => 'FURD-2025-0005',
                'cedula'         => '1029384756',
                'nombre'         => 'Carlos Montoya',
                'proyecto'       => 'VENTAS EMPRESARIALES',
                'fecha'          => '2025-10-10',
                'hecho'          => 'Reporte de conducta inadecuada, no confirmado.',
                'estado'         => 'ARCHIVADO',
                'actualizado_en' => '2025-10-12 17:00:00',
            ],
        ];

return view('seguimiento/index', ['registros' => $rows]);

    }
    // public function index()
    // {
    //     $f = new FurdModel();

    //     // Trae últimos 500 procesos con datos de empleado y proyecto (si existe)
    //     $rows = $f->select("
    //             tbl_furd.id,
    //             tbl_furd.hecho,
    //             tbl_furd.estado,
    //             tbl_furd.created_at,
    //             tbl_furd.updated_at,
    //             e.numero_documento  AS cedula,
    //             e.nombre_completo   AS nombre,
    //             p.nombre            AS proyecto
    //         ")
    //         ->join('tbl_empleados e', 'e.id = tbl_furd.colaborador_id', 'left')
    //         ->join('tbl_empleado_contratos c', 'c.empleado_id = e.id AND (c.activo=1 OR c.fecha_retiro IS NULL)', 'left')
    //         ->join('tbl_proyectos p', 'p.id = c.proyecto_id', 'left')
    //         ->orderBy('tbl_furd.created_at', 'DESC')
    //         ->findAll(500);

    //     $mapEstado = [
    //         'registrado'        => 'Abierto',
    //         'citacion_generada' => 'En proceso',
    //         'acta_generada'     => 'En proceso',
    //         'decision_emitida'  => 'Cerrado',
    //     ];

    //     $registros = [];
    //     foreach ($rows as $r) {
    //         $anio  = Time::parse($r['created_at'])->getYear();
    //         $consec = 'FURD-' . $anio . '-' . str_pad((string) $r['id'], 4, '0', STR_PAD_LEFT);

    //         $registros[] = [
    //             'consecutivo'    => $consec,
    //             'cedula'         => (string)($r['cedula'] ?? ''),
    //             'nombre'         => (string)($r['nombre'] ?? ''),
    //             'proyecto'       => (string)($r['proyecto'] ?? ''),
    //             'fecha'          => substr((string)$r['created_at'], 0, 10),
    //             'hecho'          => (string)($r['hecho'] ?? ''),
    //             'estado'         => $mapEstado[$r['estado']] ?? ucfirst((string)$r['estado']),
    //             'actualizado_en' => (string)$r['updated_at'],
    //         ];
    //     }

    //     return view('seguimiento/index', compact('registros'));
    // }
}
