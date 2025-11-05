<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\FurdModel;
use App\Models\AdjuntoModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Exceptions\PageNotFoundException;

class LineaTiempoController extends BaseController
{

    // public function show(string $consecutivo)
    // {
    //     $id = $this->decodeConsecutivo($consecutivo);
    //     if (!$id) {
    //         throw PageNotFoundException::forPageNotFound('Consecutivo inválido');
    //     }

    //     $furd = (new FurdModel())
    //         ->select("
    //             tbl_furd.*,
    //             e.numero_documento AS cedula,
    //             e.nombre_completo  AS nombre,
    //             p.nombre           AS proyecto
    //         ")
    //         ->join('tbl_empleados e', 'e.id = tbl_furd.colaborador_id', 'left')
    //         ->join('tbl_empleado_contratos c', 'c.empleado_id = e.id AND (c.activo=1 OR c.fecha_retiro IS NULL)', 'left')
    //         ->join('tbl_proyectos p', 'p.id = c.proyecto_id', 'left')
    //         ->where('tbl_furd.id', $id)
    //         ->first();

    //     if (!$furd) {
    //         throw PageNotFoundException::forPageNotFound("No existe el proceso {$consecutivo}");
    //     }

    //     $anio = Time::parse($furd['created_at'])->getYear();
    //     $proceso = [
    //         'consecutivo' => 'FURD-' . $anio . '-' . str_pad((string)$furd['id'], 4, '0', STR_PAD_LEFT),
    //         'cedula'      => (string)($furd['cedula'] ?? ''),
    //         'nombre'      => (string)($furd['nombre'] ?? ''),
    //         'proyecto'    => (string)($furd['proyecto'] ?? ''),
    //         'estado'      => (string)($furd['estado'] ?? ''),
    //     ];

    //     // Etapas
    //     $etapas = [];

    //     // 1) Registro
    //     $etapas[] = [
    //         'clave'   => 'registro',
    //         'titulo'  => 'Registro',
    //         'fecha'   => substr((string)$furd['created_at'], 0, 10),
    //         'resumen' => mb_strimwidth((string)($furd['hecho'] ?? ''), 0, 220, '…', 'UTF-8'),
    //         'meta'    => [
    //             'Turno'      => (string)($furd['turno'] ?? '—'),
    //             'Supervisor' => $furd['supervisor_id'] ? ('#'.$furd['supervisor_id']) : '—',
    //         ],
    //         'adjuntos'=> [],
    //     ];

    //     // 2) Citación (usa campos propios si existen)
    //     $etapas[] = [
    //         'clave'   => 'citacion',
    //         'titulo'  => 'Citación',
    //         'fecha'   => (string)($furd['fecha_evento'] ?? ''),
    //         'resumen' => !empty($furd['fecha_evento']) ? 'Citación programada' : '',
    //         'meta'    => [
    //             'Fecha' => (string)($furd['fecha_evento'] ?? '—'),
    //             'Hora'  => (string)($furd['hora_evento'] ?? '—'),
    //             'Turno' => (string)($furd['turno'] ?? '—'),
    //         ],
    //         'adjuntos'=> [],
    //     ];

    //     // 3) Cargos y Descargos (consideramos done si estado >= acta_generada)
    //     $etapas[] = [
    //         'clave'   => 'cargos_descargos',
    //         'titulo'  => 'Cargos y Descargos',
    //         'fecha'   => in_array($furd['estado'], ['acta_generada','decision_emitida'], true)
    //                      ? substr((string)$furd['updated_at'], 0, 10) : '',
    //         'resumen' => 'Acta de cargos/descargos',
    //         'meta'    => [],
    //         'adjuntos'=> [],
    //     ];

    //     // 4) Soporte de citación y acta (adjuntos sobre el FURD)
    //     $adj = (new AdjuntoModel())
    //         ->where(['origen' => 'furd', 'origen_id' => (int)$furd['id']])
    //         ->orderBy('created_at', 'ASC')
    //         ->findAll();

    //     $etapas[] = [
    //         'clave'   => 'soporte',
    //         'titulo'  => 'Soporte de citación y acta',
    //         'fecha'   => !empty($adj) ? substr((string)$adj[0]['created_at'], 0, 10) : '',
    //         'resumen' => !empty($adj) ? 'Se cargaron soportes de citación/acta.' : '',
    //         'meta'    => ['Adjuntos' => (string)count($adj)],
    //         'adjuntos'=> array_map(
    //             fn($a) => ['nombre' => ($a['nombre_original'] ?? basename((string)$a['ruta'])),
    //                        'url'    => site_url((string)$a['ruta'])],
    //             $adj
    //         ),
    //     ];

    //     // 5) Decisión
    //     $etapas[] = [
    //         'clave'   => 'decision',
    //         'titulo'  => 'Decisión',
    //         'fecha'   => $furd['estado'] === 'decision_emitida'
    //                      ? substr((string)$furd['updated_at'], 0, 10) : '',
    //         'resumen' => $furd['estado'] === 'decision_emitida' ? 'Decisión emitida.' : '',
    //         'meta'    => [],
    //         'adjuntos'=> [],
    //     ];

    //     return view('linea_tiempo/index', compact('proceso', 'etapas'));
    // }

public function show(string $consecutivo)
{
    // === DEMO: base extendida con todos los estados posibles ===
    $db = [

        // 🔹 Caso 1: proceso COMPLETO (todas las etapas finalizadas)
        'FURD-2025-0001' => [
            'consecutivo' => 'FURD-2025-0001',
            'empleado' => [
                'cedula'   => '1049536932',
                'nombre'   => 'Karina Blanco',
                'proyecto' => 'CLARO WHATSAPP',
            ],
            'estado' => 'DECISIÓN',
            'events' => [
                [
                    'tipo'    => 'registro',
                    'fecha'   => '2025-10-21 09:20',
                    'detalle' => 'Incumplimiento de instrucción operativa en turno.',
                    'faltas'  => [
                        ['codigo' => 'FLT-003', 'gravedad' => 'Gravísima', 'desc' => 'Entrega de documentos falsos como soporte'],
                        ['codigo' => 'FLT-001', 'gravedad' => 'Grave', 'desc' => 'Pérdida de calidades exigidas para el cargo'],
                    ],
                    'adjuntos' => ['evidencia_turno.jpg', 'registro_sistema.pdf'],
                ],
                [
                    'tipo'    => 'citacion',
                    'fecha'   => '2025-10-21 10:00',
                    'medio'   => 'PRESENCIAL',
                    'adjuntos'=> ['carta_citacion.pdf'],
                ],
                [
                    'tipo'    => 'cargos_descargos',
                    'fecha'   => '2025-10-21 11:30',
                    'medio'   => 'PRESENCIAL',
                    'adjuntos'=> ['acta_cyd.pdf'],
                ],
                [
                    'tipo'    => 'soporte',
                    'fecha'   => '2025-10-21 12:40',
                    'adjuntos'=> ['citacion_firmada.pdf', 'acta_firmada.pdf'],
                ],
                [
                    'tipo'     => 'decision',
                    'fecha'    => '2025-10-21 13:30',
                    'decision' => 'Llamado de atención',
                    'adjuntos' => ['comunicado_decision.pdf'],
                ],
            ],
        ],

        // 🔹 Caso 2: proceso EN PROCESO (hasta cargos y descargos)
        'FURD-2025-0002' => [
            'consecutivo' => 'FURD-2025-0002',
            'empleado' => [
                'cedula'   => '100200300',
                'nombre'   => 'Andrés Yakuza',
                'proyecto' => 'EXTRACCION_NOTAS_RR',
            ],
            'estado' => 'CARGOS Y DESCARGOS',
            'events' => [
                [
                    'tipo'    => 'registro',
                    'fecha'   => '2025-10-20 08:15',
                    'detalle' => 'Retraso reiterado en el inicio de sesión (3 días).',
                    'faltas'  => [
                        ['codigo' => 'FLT-020', 'gravedad' => 'Leve', 'desc' => 'Incumplimiento horario'],
                    ],
                    'adjuntos' => ['pantallazo_asistencia.xlsx'],
                ],
                [
                    'tipo'    => 'citacion',
                    'fecha'   => '2025-10-21 09:30',
                    'medio'   => 'VIRTUAL',
                    'adjuntos'=> ['carta_citacion_virtual.pdf'],
                ],
                [
                    'tipo'    => 'cargos_descargos',
                    'fecha'   => '2025-10-21 11:00',
                    'medio'   => 'PRESENCIAL',
                    'adjuntos'=> [],
                ],
                // aún sin soporte ni decisión
            ],
        ],

        // 🔹 Caso 3: proceso INICIADO (solo registro)
        'FURD-2025-0003' => [
            'consecutivo' => 'FURD-2025-0003',
            'empleado' => [
                'cedula'   => '800700600',
                'nombre'   => 'David Rojas',
                'proyecto' => 'FIJA LECTURABILIDAD',
            ],
            'estado' => 'REGISTRO',
            'events' => [
                [
                    'tipo'    => 'registro',
                    'fecha'   => '2025-10-19 16:40',
                    'detalle' => 'Uso indebido de credenciales compartidas.',
                    'faltas'  => [
                        ['codigo' => 'FLT-011', 'gravedad' => 'Grave', 'desc' => 'Compartir credenciales corporativas'],
                    ],
                    'adjuntos' => ['log_accesos.pdf'],
                ],
            ],
        ],

        // 🔹 Caso 4: proceso con soporte pero sin decisión
        'FURD-2025-0004' => [
            'consecutivo' => 'FURD-2025-0004',
            'empleado' => [
                'cedula'   => '1092837465',
                'nombre'   => 'Lina Hurtado',
                'proyecto' => 'CALL CENTER FIJA',
            ],
            'estado' => 'SOPORTE',
            'events' => [
                [
                    'tipo'    => 'registro',
                    'fecha'   => '2025-10-22 08:00',
                    'detalle' => 'Ausencia sin justificación el 20 y 21 de octubre.',
                    'faltas'  => [['codigo' => 'FLT-021', 'gravedad' => 'Grave', 'desc' => 'Inasistencia injustificada']],
                    'adjuntos'=> ['pantallazo_turnos.pdf'],
                ],
                [
                    'tipo'    => 'citacion',
                    'fecha'   => '2025-10-23 09:30',
                    'medio'   => 'VIRTUAL',
                    'adjuntos'=> ['citacion_inasistencia.pdf'],
                ],
                [
                    'tipo'    => 'cargos_descargos',
                    'fecha'   => '2025-10-24 11:00',
                    'medio'   => 'PRESENCIAL',
                    'adjuntos'=> ['acta_inasistencia.pdf'],
                ],
                [
                    'tipo'    => 'soporte',
                    'fecha'   => '2025-10-24 15:00',
                    'adjuntos'=> ['acta_firmada.pdf'],
                ],
                // sin decisión aún
            ],
        ],

        // 🔹 Caso 5: archivado (proceso cerrado sin decisión)
        'FURD-2025-0005' => [
            'consecutivo' => 'FURD-2025-0005',
            'empleado' => [
                'cedula'   => '1029384756',
                'nombre'   => 'Carlos Montoya',
                'proyecto' => 'VENTAS EMPRESARIALES',
            ],
            'estado' => 'ARCHIVADO',
            'events' => [
                [
                    'tipo'    => 'registro',
                    'fecha'   => '2025-10-10 09:00',
                    'detalle' => 'Reporte de conducta inadecuada, no confirmado.',
                    'faltas'  => [],
                    'adjuntos'=> [],
                ],
                [
                    'tipo'    => 'citacion',
                    'fecha'   => '2025-10-11 10:00',
                    'medio'   => 'VIRTUAL',
                    'adjuntos'=> [],
                ],
                [
                    'tipo'    => 'decision',
                    'fecha'   => '2025-10-12 17:00',
                    'decision' => 'Proceso archivado por falta de pruebas.',
                    'adjuntos'=> ['acta_archivo.pdf'],
                ],
            ],
        ],
    ];

    $data = $db[$consecutivo] ?? null;
    if (!$data) {
        throw PageNotFoundException::forPageNotFound('Consecutivo no existe: ' . $consecutivo);
    }

    return view('linea_tiempo/show', $data);
}


    /** Acepta FURD-YYYY-#### o solo el número */
    private function decodeConsecutivo(string $s): ?int
    {
        if (ctype_digit($s)) return (int)$s;
        if (preg_match('/^FURD-\d{4}-0*([1-9]\d*)$/i', $s, $m)) return (int)$m[1];
        return null;
    }
}
