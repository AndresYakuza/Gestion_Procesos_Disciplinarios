<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\FurdModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Exceptions\PageNotFoundException;

class LineaTiempoController extends BaseController
{
    public function show(string $consecutivo)
    {
        $id = $this->decodeConsecutivo($consecutivo);
        if (!$id) {
            throw PageNotFoundException::forPageNotFound('Consecutivo inválido');
        }

        // 🧠 Carga principal del FURD con empleado y proyecto
        $furd = (new FurdModel())
            ->select("
            f.id,
            f.consecutivo,
            f.fecha_evento,
            f.hora_evento,
            f.estado,
            f.empresa_usuaria,
            f.superior,         
            f.hecho,
            f.correo_cliente,
            f.created_at,
            f.updated_at,
            e.numero_documento AS cedula,
            e.nombre_completo AS nombre,
            p.nombre AS proyecto
        ")
            ->from('tbl_furd f')
            ->join('tbl_empleados e', 'e.id = f.empleado_id', 'left')
            ->join('tbl_proyectos p', 'p.id = f.proyecto_id', 'left')
            ->where('f.id', $id)
            ->first();

        if (!$furd) {
            throw PageNotFoundException::forPageNotFound("No existe el proceso {$consecutivo}");
        }

        $proceso = [
            'consecutivo' => $furd['consecutivo'] ?? sprintf('PD-%06d', $furd['id']),
            'cedula'      => $furd['cedula'] ?? '',
            'nombre'      => $furd['nombre'] ?? '',
            'proyecto'    => $furd['proyecto'] ?? '',
            'estado'      => $furd['estado'] ?? '',
        ];

        // 🧱 Inicializamos las etapas
        $etapas = [];

        // 1️⃣ Registro
        $faltas = $this->getFaltas((int)$furd['id']);

        $hechoFull  = (string)($furd['hecho'] ?? '');
        $hechoShort = mb_strimwidth($hechoFull, 0, 220, '…', 'UTF-8');

        $etapas[] = [
            'clave'        => 'registro',
            'titulo'       => 'Registro',
            'fecha'        => Time::parse($furd['created_at'])->format('d/m/Y'),
            'detalle'      => $hechoShort,
            'detalle_full' => $hechoFull,
            'meta'    => [
                'Superior que interviene'  => (string)($furd['superior'] ?? '—'),
                'Email cliente'  => (string)($furd['correo_cliente'] ?? '—'),
                'Fecha del evento' => $furd['fecha_evento']
                    ? Time::parse($furd['fecha_evento'])->format('d/m/Y')
                    : '—',
                'Hora del evento'  => (string)($furd['hora_evento']    ?? '—'),
                'Empresa usuaria'  => (string)($furd['empresa_usuaria'] ?? '—'),
                'Faltas registradas' => (string)count($faltas),
            ],
            'faltas'   => $faltas,
            'adjuntos' => $this->getAdjuntos($furd['id'], 'registro'),
        ];


        // 2️⃣ Citación (con trazabilidad y citación vigente)
        $citacionesRows = db_connect()->table('tbl_furd_citacion')
            ->where('furd_id', $furd['id'])
            ->orderBy('numero', 'ASC')      // primero las antiguas
            ->orderBy('created_at', 'ASC')
            ->get()
            ->getResultArray();

        $citacion          = null; // importante para reutilizarlo después (descargos)
        $historialCitacion = [];

        if (!empty($citacionesRows)) {
            foreach ($citacionesRows as $row) {

                $notifRows = db_connect()->table('tbl_furd_citacion_notificacion')
                    ->where('citacion_id', (int)$row['id'])
                    ->orderBy('notificado_at', 'DESC')
                    ->orderBy('id', 'DESC')
                    ->get()
                    ->getResultArray();

                $ultimaNotif = $notifRows[0] ?? null;

                $historialCitacion[] = [
                    'numero'            => (int) ($row['numero'] ?? 1),
                    'fecha'             => !empty($row['fecha_evento']) ? Time::parse($row['fecha_evento'])->format('d/m/Y') : '',
                    'hora'              => $row['hora'] ?? '',
                    'medio'             => $row['medio'] ?? '',
                    'motivo'            => $row['motivo'] ?? '',
                    'motivo_recitacion' => $row['motivo_recitacion'] ?? '',
                    'ultima_notificacion' => $ultimaNotif ? [
                        'estado'       => $ultimaNotif['estado'] ?? '',
                        'fecha'        => !empty($ultimaNotif['notificado_at']) ? Time::parse($ultimaNotif['notificado_at'])->format('d/m/Y H:i') : '',
                        'destinatario' => $ultimaNotif['destinatario'] ?? '',
                    ] : null,
                    'notificaciones' => array_map(static function (array $n) {
                        return [
                            'estado'       => $n['estado'] ?? '',
                            'fecha'        => !empty($n['notificado_at']) ? Time::parse($n['notificado_at'])->format('d/m/Y H:i') : '',
                            'destinatario' => $n['destinatario'] ?? '',
                            'canal'        => $n['canal'] ?? 'email',
                            'error'        => $n['error'] ?? null,
                        ];
                    }, $notifRows),
                ];
            }

            $citacion = end($citacionesRows);


            $partesMotivo = [];
            if (!empty($citacion['motivo'])) {
                $partesMotivo[] = (string) $citacion['motivo'];
            }
            if (!empty($citacion['motivo_recitacion'])) {
                $partesMotivo[] = 'Motivo de la nueva citación vigente: ' . $citacion['motivo_recitacion'];
            }

            $motivoFull = trim(implode("\n\n", $partesMotivo));
            if ($motivoFull === '') {
                $motivoFull = 'Citación registrada para el proceso.';
            }

            $motivoShort = mb_strimwidth($motivoFull, 0, 220, '…', 'UTF-8');

            $metaCitacion = [
                'Fecha citación vigente' => !empty($citacion['fecha_evento'])
                    ? Time::parse($citacion['fecha_evento'])->format('d/m/Y')
                    : '—',
                'Hora citación vigente'  => $citacion['hora']  ?? '—',
                'Medio citación vigente' => $citacion['medio'] ?? '—',
                'Total de citaciones'    => (string) count($historialCitacion),
            ];

            $etapas[] = [
                'clave'        => 'citacion',
                'titulo'       => 'Citación',
                'fecha'        => !empty($citacion['created_at'])
                    ? Time::parse($citacion['created_at'])->format('d/m/Y')
                    : '',
                'detalle'      => $motivoShort,
                'detalle_full' => $motivoFull,
                'meta'         => $metaCitacion,
                'adjuntos'     => $this->getAdjuntos($furd['id'], 'citacion'),
                'citaciones'   => $historialCitacion, // 👈 trazabilidad completa
            ];
        } else {
            // Caso sin citaciones todavía
            $etapas[] = [
                'clave'        => 'citacion',
                'titulo'       => 'Citación',
                'fecha'        => '',
                'detalle'      => 'Sin citación registrada.',
                'detalle_full' => 'Sin citación registrada.',
                'meta'         => [],
                'adjuntos'     => [],
                'citaciones'   => [],
            ];
        }


        // 3️⃣ Descargos / Cargos y Descargos
        $descargos = db_connect()->table('tbl_furd_descargos')
            ->where('furd_id', $furd['id'])
            ->get()
            ->getRowArray();

        $descDetalle = '';
        $metaDesc    = [
            'Fecha del evento' => '—',
            'Hora'             => '—',
            'Medio'            => '—',
        ];

        // Caso normal: existe registro de descargos
        if ($descargos) {
            $descDetalle = 'Descargo realizado de manera ' . $descargos['medio'];

            $metaDesc = [
                'Fecha del evento' => isset($descargos['fecha_evento'])
                    ? Time::parse($descargos['fecha_evento'])->format('d/m/Y')
                    : '—',
                'Hora'  => $descargos['hora'] ?? '—',
                'Medio' => $descargos['medio'] ?? '—',
            ];
        }
        // ✅ Caso especial: NO hay descargos, pero la citación fue con descargo escrito
        elseif ($citacion && (($citacion['medio'] ?? null) === 'escrito')) {
            $descDetalle = 'No se realizó audiencia de cargos y descargos, porque el descargo fue presentado por escrito según la citación.';

            $metaDesc = [
                'Tipo de descargo'          => 'Escrito (se omite acta de cargos y descargos)',
                'Fecha del descargo escrito' => isset($citacion['fecha_evento'])
                    ? Time::parse($citacion['fecha_evento'])->format('d/m/Y')
                    : '—',
                'Hora citada'               => $citacion['hora'] ?? '—',
            ];
        }
        // Caso genérico: ni descargos ni citación especial
        else {
            $descDetalle = '— Sin audiencia de cargos y descargos registrada —';
        }

        $adjuntosDescargos = $this->getAdjuntos($furd['id'], 'descargos');
        $actaDescargos     = !empty($adjuntosDescargos) ? end($adjuntosDescargos) : null;

        $etapas[] = [
            'clave'        => 'descargos',
            'titulo'       => 'Cargos y Descargos',
            'fecha'        => isset($descargos['created_at'])
                ? Time::parse($descargos['created_at'])->format('d/m/Y')
                : '',
            'detalle'      => $descDetalle,
            'detalle_full' => $descDetalle,
            'meta'         => $metaDesc,
            'adjuntos'     => $adjuntosDescargos,
            'accion_editar_url' => $actaDescargos
                ? site_url('adjuntos/' . $actaDescargos['id'] . '/open')
                : null,
        ];

        // 4️⃣ Soporte (decisión propuesta + respuesta cliente)
        $soporte = db_connect()->table('tbl_furd_soporte')
            ->where('furd_id', $furd['id'])
            ->get()
            ->getRowArray();

        $soporteDetalleFull  = '';
        $soporteDetalleShort = '';

        $clienteEstado        = $soporte['cliente_estado']        ?? 'pendiente';
        $clienteRespondidoAt  = $soporte['cliente_respondido_at'] ?? null;
        $clienteDecision      = $soporte['cliente_decision']      ?? null;
        $clienteJustificacion = $soporte['cliente_justificacion'] ?? null;
        $clienteComentario    = $soporte['cliente_comentario']    ?? null;
        $clienteFechaSusp     = $soporte['cliente_fecha_inicio_suspension'] ?? null;
        $clienteFechaSuspFin  = $soporte['cliente_fecha_fin_suspension'] ?? null;


        $notificadoClienteAt   = $soporte['notificado_cliente_at']   ?? null;
        $recordatorioClienteAt = $soporte['recordatorio_cliente_at'] ?? null;
        $autoArchivadoAt       = $soporte['auto_archivado_at']       ?? null;

        if ($soporte) {
            $decisionPropuesta = (string) ($soporte['decision_propuesta'] ?? '—');
            $justOrigFull      = trim((string) ($soporte['justificacion'] ?? ''));
            $isSuspension      = strcasecmp($decisionPropuesta, 'Suspensión disciplinaria') === 0;

            // Resumen corto, mismo que en portal cliente
            if ($clienteEstado === 'pendiente') {
                $resumen = 'Decisión propuesta: ' . $decisionPropuesta . '. A la espera de respuesta del cliente.';
            } else {
                $estadoTxt = $clienteEstado === 'aprobado' ? 'APROBADA' : 'RECHAZADA';
                $resumen = 'Decisión propuesta: ' . $decisionPropuesta
                    . ". Cliente: {$estadoTxt}"
                    . ($clienteDecision ? ' · Ajuste sugerido: ' . $clienteDecision : '');
            }

            // Texto largo: justificación original + ajustes + comentario
            $partesFull = [];

            if ($justOrigFull !== '') {
                $partesFull[] = "Justificación original:\n" . $justOrigFull;
            }

            if ($clienteJustificacion && $clienteJustificacion !== $justOrigFull) {
                $partesFull[] = "Justificación ajustada por el cliente:\n" . $clienteJustificacion;
            }

            if ($clienteComentario) {
                $partesFull[] = "Comentario del cliente:\n" . $clienteComentario;
            }

            $soporteDetalleFull  = $partesFull ? implode("\n\n", $partesFull) : '— Sin información de soporte registrada —';
            $soporteDetalleShort = mb_strimwidth($resumen, 0, 220, '…', 'UTF-8');

            // META igual que en portal cliente
            $metaSoporte = [
                'Responsable'        => $soporte['responsable']        ?? '—',
                'Decisión propuesta' => $decisionPropuesta,
            ];

            if ($clienteEstado === 'pendiente') {
                $metaSoporte['Notificación inicial al cliente'] = $notificadoClienteAt
                    ? Time::parse($notificadoClienteAt)->format('d/m/Y H:i')
                    : '—';
                $metaSoporte['Recordatorio al cliente'] = $recordatorioClienteAt
                    ? Time::parse($recordatorioClienteAt)->format('d/m/Y H:i')
                    : '—';
            } elseif ($isSuspension) {
                $metaSoporte['Fecha inicio suspensión (cliente)'] = $clienteFechaSusp
                    ? Time::parse($clienteFechaSusp)->format('d/m/Y')
                    : '—';

                $metaSoporte['Fecha fin suspensión (cliente)'] = $clienteFechaSuspFin
                    ? Time::parse($clienteFechaSuspFin)->format('d/m/Y')
                    : '—';
            }
        } else {
            $soporteDetalleFull  = '— Sin soporte registrado —';
            $soporteDetalleShort = $soporteDetalleFull;
            $metaSoporte         = [];
        }

        $etapas[] = [
            'clave'        => 'soporte',
            'titulo'       => 'Soporte de Citación / Acta',
            'fecha'        => isset($soporte['created_at'])
                ? Time::parse($soporte['created_at'])->format('d/m/Y')
                : '',
            'detalle'      => $soporteDetalleShort,
            'detalle_full' => $soporteDetalleFull,
            'meta'         => $metaSoporte,

            // datos crudos para el bloque especial de soporte
            'decision_propuesta'      => $soporte['decision_propuesta']      ?? null,
            'justificacion_original'  => $soporte['justificacion']           ?? null,
            'cliente_estado'          => $clienteEstado,
            'cliente_respondido_at'   => $clienteRespondidoAt,
            'cliente_decision'        => $clienteDecision,
            'cliente_justificacion'   => $clienteJustificacion,
            'cliente_comentario'      => $clienteComentario,
            'adjuntos'                => $this->getAdjuntos($furd['id'], 'soporte'),
        ];

        // 5️⃣ Archivo automático (si aplica)
        if (!empty($autoArchivadoAt)) {
            $etapas[] = [
                'clave'        => 'archivado',
                'titulo'       => 'Archivo automático',
                'fecha'        => Time::parse($autoArchivadoAt)->format('d/m/Y'),
                'detalle'      => 'El proceso fue archivado automáticamente por falta de respuesta del cliente dentro del plazo de 10 días.',
                'detalle_full' => 'El proceso fue archivado automáticamente por falta de respuesta formal del cliente dentro del término de diez (10) días calendario previsto en el reglamento interno de trabajo.',
                'meta'         => [
                    'Fecha de auto-archivo' => Time::parse($autoArchivadoAt)->format('d/m/Y H:i'),
                ],
                'adjuntos'     => [],
            ];
        }

        // 6️⃣ Decisión
        $decision = db_connect()
            ->table('tbl_furd_decision')
            ->where('furd_id', $furd['id'])
            ->get()
            ->getRowArray();

        $detalle   = trim((string)($decision['decision_text'] ?? ''));
        $fundament = trim((string)($decision['fundamentacion'] ?? ($decision['detalle_text'] ?? '')));

        $partes = [];
        if ($detalle !== '')   $partes[] = $detalle;
        if ($fundament !== '') $partes[] = 'Fundamentación: ' . $fundament;

        $textoFull   = implode(' · ', $partes);
        $textoShort  = mb_strimwidth($textoFull, 0, 220, '…', 'UTF-8');

        $etapas[] = [
            'clave'        => 'decision',
            'titulo'       => 'Decisión',
            'fecha'        => isset($decision['created_at'])
                ? Time::parse($decision['created_at'])->format('d/m/Y')
                : '',
            'detalle'      => $textoShort ?: '— Sin decisión registrada —',
            'detalle_full' => $textoFull  ?: '— Sin decisión registrada —',
            'meta'    => [
                'Fecha de la decisión' => isset($decision['fecha_evento'])
                    ? Time::parse($decision['fecha_evento'])->format('d/m/Y')
                    : '—',
            ],
            'adjuntos' => $this->getAdjuntos($furd['id'], 'decision'),
        ];

        return view('linea_tiempo/show', compact('proceso', 'etapas'));
    }


    /**
     * Decodifica PD-#### o solo el número
     */
    private function decodeConsecutivo(string $s): ?int
    {
        if (preg_match('/^PD-0*([1-9]\d*)$/i', $s, $m)) {
            return (int)$m[1];
        }

        if (ctype_digit($s)) {
            $v = (int)$s;
            return $v > 0 ? $v : null;
        }

        return null;
    }


    /**
     * Obtiene adjuntos por fase
     */
    private function getAdjuntos(int $furdId, string $fase): array
    {
        $rows = db_connect()->table('tbl_adjuntos')
            ->select('id, nombre_original, ruta, storage_provider')
            ->where(['origen' => 'furd', 'origen_id' => $furdId, 'fase' => $fase])
            ->orderBy('created_at', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(static fn($a) => [
            'id'       => (int) $a['id'],
            'nombre'   => $a['nombre_original'] ?? basename((string)($a['ruta'] ?? '')),
            'provider' => $a['storage_provider'] ?? 'local',
        ], $rows);
    }


    private function getFaltas(int $furdId): array
    {
        $rows = db_connect()->table('tbl_furd_faltas ff')
            ->select('rf.codigo, rf.gravedad, rf.descripcion')
            ->join('tbl_rit_faltas rf', 'rf.id = COALESCE(ff.falta_id, ff.rit_falta_id)', 'left')
            ->where('ff.furd_id', $furdId)
            ->orderBy('rf.codigo', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(static fn($r) => [
            'codigo'   => (string)($r['codigo'] ?? ''),
            'gravedad' => (string)($r['gravedad'] ?? ''),
            'desc'     => (string)($r['descripcion'] ?? ''),
        ], $rows);
    }
}
