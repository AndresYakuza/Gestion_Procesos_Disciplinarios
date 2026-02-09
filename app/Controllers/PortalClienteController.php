<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\FurdModel;
use App\Models\FurdSoporteModel;
use App\Models\RitFaltaModel;
use CodeIgniter\I18n\Time;

class PortalClienteController extends BaseController
{
    /**
     * Portal one-page para el cliente (embebible).
     * Lee opcionalmente:
     *  - ?email=cliente@empresa.com  (o ?correo_cliente=...)
     *  - ?furd=PD-000123             (para abrir un proceso de una vez)
     */
    public function index()
    {
        $clienteEmail = (string) (
            $this->request->getGet('email')
            ?? $this->request->getGet('correo_cliente')
            ?? ''
        );

        $consecutivoInicial = (string) $this->request->getGet('furd');

        // ⬇️ MISMO código que el FURD principal
        $faltas = model(RitFaltaModel::class)
            ->orderBy('codigo', 'ASC')
            ->findAll();

        return view('portal_cliente/index', [
            'clienteEmail'       => $clienteEmail,
            'consecutivoInicial' => $consecutivoInicial,
            'faltas'             => $faltas,
        ]);
    }


    /**
     * AJAX: lista de FURD asociados a un correo_cliente.
     * GET /portal-cliente/mis-procesos?email=...&consecutivo=PD-000123 (opcional)
     */
    public function misProcesos()
    {
        $email = trim((string) $this->request->getGet('email'));
        if ($email === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'ok'       => false,
                    'msg'      => 'El correo del cliente es obligatorio.',
                    'procesos' => [],
                ]);
        }

        $consecutivo = trim((string) $this->request->getGet('consecutivo'));

        $f = new FurdModel();

        $f->select("
                tbl_furd.id,
                tbl_furd.consecutivo,
                tbl_furd.estado,
                tbl_furd.created_at,
                tbl_furd.updated_at,
                e.numero_documento AS cedula,
                e.nombre_completo  AS nombre,
                p.nombre           AS proyecto
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
            ->where('tbl_furd.correo_cliente', $email)
            ->orderBy('tbl_furd.created_at', 'DESC');

        if ($consecutivo !== '') {
            // Permitimos tanto PD-000123 como solo el número
            $idFromConsec = $this->decodeConsecutivo($consecutivo);
            if ($idFromConsec) {
                $f->where('tbl_furd.id', $idFromConsec);
            } else {
                $f->where('tbl_furd.consecutivo', $consecutivo);
            }
        }

        $rows = $f->findAll();

        $mapEstado = [
            'registro'  => 'Abierto / Registro',
            'citacion'  => 'En proceso / Citación',
            'descargos' => 'En proceso / Descargos',
            'soporte'   => 'En proceso / Soporte',
            'decision'  => 'Cerrado / Decisión',
            'archivado' => 'Archivado',
        ];

        $procesos = [];
        foreach ($rows as $r) {
            $created = $r['created_at'] ? Time::parse($r['created_at']) : null;
            $updated = $r['updated_at'] ? Time::parse($r['updated_at']) : null;

            $consec = $r['consecutivo']
                ?: ('PD-' . str_pad((string) $r['id'], 6, '0', STR_PAD_LEFT));

            $procesos[] = [
                'consecutivo'    => $consec,
                'cedula'         => (string) ($r['cedula']   ?? ''),
                'nombre'         => (string) ($r['nombre']   ?? ''),
                'proyecto'       => (string) ($r['proyecto'] ?? ''),
                'estado'         => $mapEstado[$r['estado']] ?? ucfirst((string) $r['estado']),
                'estado_raw'     => (string) ($r['estado'] ?? ''),
                'fecha'          => $created ? $created->format('d/m/Y')       : '',
                'actualizado_en' => $updated ? $updated->format('d/m/Y H:i')   : '',
            ];
        }

        return $this->response->setJSON([
            'ok'       => true,
            'procesos' => $procesos,
        ]);
    }

    /**
     * AJAX: línea de tiempo simplificada para el cliente.
     * GET /portal-cliente/furd/PD-000123/timeline?email=...
     */
    public function timeline(string $consecutivo)
    {
        $email = trim((string) $this->request->getGet('email'));
        if ($email === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'ok'    => false,
                    'msg'   => 'El correo del cliente es obligatorio.',
                    'items' => [],
                ]);
        }

        $id = $this->decodeConsecutivo($consecutivo);
        if (!$id) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'ok'    => false,
                    'msg'   => 'Consecutivo inválido.',
                    'items' => [],
                ]);
        }

        $db = db_connect();

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
                e.nombre_completo  AS nombre,
                p.nombre           AS proyecto
            ")
            ->from('tbl_furd f')
            ->join('tbl_empleados e', 'e.id = f.empleado_id', 'left')
            ->join('tbl_proyectos p', 'p.id = f.proyecto_id', 'left')
            ->where('f.id', $id)
            ->where('f.correo_cliente', $email) // 🔒 solo procesos de ese correo
            ->first();

        if (!$furd) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'ok'    => false,
                    'msg'   => 'No se encontró el proceso para este correo.',
                    'items' => [],
                ]);
        }

        $proceso = [
            'consecutivo' => $furd['consecutivo'] ?? sprintf('PD-%06d', $furd['id']),
            'cedula'      => $furd['cedula']   ?? '',
            'nombre'      => $furd['nombre']   ?? '',
            'proyecto'    => $furd['proyecto'] ?? '',
            'estado_raw'  => $furd['estado']   ?? '',
            'estado'      => $this->mapEstado($furd['estado'] ?? ''),
        ];

        $items = $this->buildTimelineItems($furd);

        return $this->response->setJSON([
            'ok'      => true,
            'proceso' => $proceso,
            'items'   => $items,
        ]);
    }

    /**
     * AJAX: el cliente aprueba o solicita ajustes sobre la decisión.
     * POST /portal-cliente/furd/PD-000123/respuesta
     *
     * Campos esperados:
     *  - correo_cliente
     *  - cliente_estado        (aprobado|rechazado)
     *  - cliente_decision      (opcional si aprobado)
     *  - cliente_justificacion (obligatorio si rechazado y no hay decisión)
     *  - cliente_comentario    (opcional)
     */
    // public function responderDecision(string $consecutivo)
    // {
    //     if ($this->request->getMethod() !== 'post') {
    //         return $this->response
    //             ->setStatusCode(405)
    //             ->setJSON(['ok' => false, 'msg' => 'Método no permitido.']);
    //     }

    //     $email = trim((string) $this->request->getPost('correo_cliente'));
    //     if ($email === '') {
    //         return $this->response
    //             ->setStatusCode(400)
    //             ->setJSON(['ok' => false, 'msg' => 'El correo del cliente es obligatorio.']);
    //     }

    //     $id = $this->decodeConsecutivo($consecutivo);
    //     if (!$id) {
    //         return $this->response
    //             ->setStatusCode(404)
    //             ->setJSON(['ok' => false, 'msg' => 'Consecutivo inválido.']);
    //     }

    //     $furdModel = new FurdModel();

    //     $furd = $furdModel
    //         ->where('id', $id)
    //         ->where('correo_cliente', $email) // 🔒 seguridad
    //         ->first();

    //     if (!$furd) {
    //         return $this->response
    //             ->setStatusCode(404)
    //             ->setJSON(['ok' => false, 'msg' => 'Proceso no encontrado para este correo.']);
    //     }

    //     $soporteModel = new FurdSoporteModel();

    //     $soporte = $soporteModel
    //         ->where('furd_id', (int) $furd['id'])
    //         ->first();

    //     if (!$soporte) {
    //         return $this->response
    //             ->setStatusCode(404)
    //             ->setJSON(['ok' => false, 'msg' => 'No existe soporte registrado para este proceso.']);
    //     }

    //     $post = [
    //         'cliente_estado'        => trim((string) $this->request->getPost('cliente_estado')),
    //         'cliente_decision'      => trim((string) $this->request->getPost('cliente_decision')),
    //         'cliente_justificacion' => trim((string) $this->request->getPost('cliente_justificacion')),
    //         'cliente_comentario'    => trim((string) $this->request->getPost('cliente_comentario')),
    //     ];

    //     $errors = [];

    //     if (!in_array($post['cliente_estado'], ['aprobado', 'rechazado'], true)) {
    //         $errors['cliente_estado'] = 'Debes indicar si apruebas o rechazas la decisión propuesta.';
    //     }

    //     $isSuspension = strcasecmp($soporte['decision_propuesta'] ?? '', 'Suspensión disciplinaria') === 0;


    //     if ($post['cliente_estado'] === 'rechazado') {
    //         if ($post['cliente_decision'] === '' && $post['cliente_justificacion'] === '') {
    //             $errors['cliente_decision'] = 'Si rechazas la decisión, describe el cambio propuesto o una justificación.';
    //         }
    //     }

    //     if ($isSuspension && $post['cliente_estado'] === 'aprobado' && $post['cliente_fecha_inicio_suspension'] === '') {
    //         $errors['cliente_fecha_inicio_suspension'] = 'Debes indicar la fecha de inicio de la suspensión disciplinaria.';
    //     }

    //     if (!empty($errors)) {
    //         return $this->response
    //             ->setStatusCode(422)
    //             ->setJSON([
    //                 'ok'     => false,
    //                 'errors' => $errors,
    //             ]);
    //     }

    //     $update = [
    //         'cliente_estado'        => $post['cliente_estado'],
    //         'cliente_decision'      => $post['cliente_decision']      ?: null,
    //         'cliente_justificacion' => $post['cliente_justificacion'] ?: null,
    //         'cliente_comentario'    => $post['cliente_comentario']    ?: null,
    //         'cliente_respondido_at' => date('Y-m-d H:i:s'),
    //         'cliente_fecha_inicio_suspension' => $post['cliente_fecha_inicio_suspension'] ?: null,

    //     ];

    //     $soporteModel->update((int) $soporte['id'], $update);

    //     // Aquí puedes llamar al mismo servicio de correo que uses en SoporteController::reviewCliente

    //     return $this->response->setJSON([
    //         'ok'     => true,
    //         'estado' => $post['cliente_estado'],
    //     ]);
    // }

    // ---------------------------------------------------------
    // Helpers privados
    // ---------------------------------------------------------

    /**
     * Decodifica PD-000123 → 123
     */
    private function decodeConsecutivo(string $s): ?int
    {
        if (preg_match('/^PD-0*([1-9]\d*)$/i', $s, $m)) {
            return (int) $m[1];
        }

        // Si mandan solo el número
        if (ctype_digit($s)) {
            $val = (int) $s;
            return $val > 0 ? $val : null;
        }

        return null;
    }

    /**
     * Mapea estado técnico a etiqueta de negocio.
     */
    private function mapEstado(string $estado): string
    {
        $map = [
            'registro'  => 'Abierto / Registro',
            'citacion'  => 'En proceso / Citación',
            'descargos' => 'En proceso / Descargos',
            'soporte'   => 'En proceso / Soporte',
            'decision'  => 'Cerrado / Decisión',
            'archivado' => 'Archivado',
        ];

        return $map[$estado] ?? ucfirst($estado);
    }

    /**
     * Construye la línea de tiempo simplificada que verá el cliente.
     */
    /**
     * Construye la línea de tiempo simplificada que verá el cliente.
     * (Mismo esquema que la línea de tiempo administrativa,
     *  pero filtrada por correo_cliente).
     */
    private function buildTimelineItems(array $furd): array
    {
        $db = db_connect();
        $id = (int) $furd['id'];

        $items  = [];
        $faltas = $this->getFaltas($id);

        // 1️⃣ Registro
        $hechoFull  = (string) ($furd['hecho'] ?? '');
        $hechoShort = $hechoFull !== ''
            ? mb_strimwidth($hechoFull, 0, 220, '…', 'UTF-8')
            : 'Proceso registrado.';

        $items[] = [
            'clave'        => 'registro',
            'titulo'       => 'Registro',
            'fecha'        => $furd['created_at']
                ? Time::parse($furd['created_at'])->format('d/m/Y')
                : '',
            'detalle'      => $hechoShort,
            'detalle_full' => $hechoFull,
            'estado'       => 'completado',
            'meta'         => [
                'Superior que interviene'  => (string) ($furd['superior']        ?? '—'),
                'Email cliente'            => (string) ($furd['correo_cliente']  ?? '—'),
                'Fecha del evento'         => $furd['fecha_evento']
                    ? Time::parse($furd['fecha_evento'])->format('d/m/Y')
                    : '—',
                'Hora del evento'          => (string) ($furd['hora_evento']     ?? '—'),
                'Empresa usuaria'          => (string) ($furd['empresa_usuaria'] ?? '—'),
                'Faltas registradas'       => (string) count($faltas),
            ],
            'faltas'   => $faltas,
            'adjuntos' => $this->getAdjuntos($id, 'registro'),

        ];

        // 2️⃣ Citación (cliente: resumen + trazabilidad)
        $citacionesRows = $db->table('tbl_furd_citacion')
            ->where('furd_id', $id)
            ->orderBy('numero', 'ASC')
            ->orderBy('created_at', 'ASC')
            ->get()
            ->getResultArray();

        // La usaremos luego para el caso de descargo escrito
        $citacion          = null;
        $historialCitacion = [];

        if (!empty($citacionesRows)) {
            // Historial estructurado (igual que en línea de tiempo admin)
            // Historial estructurado (igual que en línea de tiempo admin) + notificaciones
            foreach ($citacionesRows as $row) {
                $notifRows = $db->table('tbl_furd_citacion_notificacion')
                    ->where('citacion_id', (int)($row['id'] ?? 0))
                    ->orderBy('notificado_at', 'DESC')
                    ->orderBy('id', 'DESC')
                    ->get()
                    ->getResultArray();

                $ultimaNotif = $notifRows[0] ?? null;

                $historialCitacion[] = [
                    'numero'            => (int) ($row['numero'] ?? 1),
                    'fecha'             => !empty($row['fecha_evento'])
                        ? Time::parse($row['fecha_evento'])->format('d/m/Y')
                        : '',
                    'hora'              => $row['hora']   ?? '',
                    'medio'             => $row['medio']  ?? '',
                    'motivo'            => $row['motivo'] ?? '',
                    'motivo_recitacion' => $row['motivo_recitacion'] ?? '',

                    // 👇 NUEVO: última notificación resumida
                    'ultima_notificacion' => $ultimaNotif ? [
                        'estado'       => $ultimaNotif['estado'] ?? '',
                        'fecha'        => !empty($ultimaNotif['notificado_at'])
                            ? Time::parse($ultimaNotif['notificado_at'])->format('d/m/Y H:i')
                            : '',
                        'destinatario' => $ultimaNotif['destinatario'] ?? '',
                    ] : null,

                    // 👇 NUEVO: histórico completo
                    'notificaciones' => array_map(static function (array $n) {
                        return [
                            'estado'       => $n['estado'] ?? '',
                            'fecha'        => !empty($n['notificado_at'])
                                ? Time::parse($n['notificado_at'])->format('d/m/Y H:i')
                                : '',
                            'destinatario' => $n['destinatario'] ?? '',
                            'canal'        => $n['canal'] ?? 'email',
                            'error'        => $n['error'] ?? null,
                        ];
                    }, $notifRows),
                ];
            }


            $citacion = end($citacionesRows); // vigente

            // Texto base con citación vigente
            $vigenteFecha = !empty($citacion['fecha_evento'])
                ? Time::parse($citacion['fecha_evento'])->format('d/m/Y')
                : '—';

            $vigenteHora  = $citacion['hora']  ?? '—';
            $vigenteMedio = $citacion['medio'] ?? '—';
            $vigenteNum   = (int) ($citacion['numero'] ?? 1);

            $lineaVigente = "Citación vigente #{$vigenteNum} el {$vigenteFecha}"
                . ($vigenteHora !== '—' ? " a las {$vigenteHora}" : '')
                . " por medio {$vigenteMedio}.";

            $motivoVigente = trim((string) ($citacion['motivo'] ?? ''));
            if ($motivoVigente !== '') {
                $lineaVigente .= " Motivo: {$motivoVigente}.";
            }

            if (!empty($citacion['motivo_recitacion'])) {
                $lineaVigente .= ' Motivo de la nueva citación vigente: '
                    . $citacion['motivo_recitacion'] . '.';
            }

            // Resumen plano en texto (por si se usa en otros lados)
            $historialText = '';
            if (count($citacionesRows) > 1) {
                $partsHist = [];
                foreach ($citacionesRows as $cRow) {
                    $num   = (int) ($cRow['numero'] ?? 1);
                    $fecha = !empty($cRow['fecha_evento'])
                        ? Time::parse($cRow['fecha_evento'])->format('d/m/Y')
                        : '—';
                    $medio = $cRow['medio'] ?? '—';

                    $txt = "Citación #{$num} ({$fecha}, medio {$medio})";
                    if (!empty($cRow['motivo_recitacion'])) {
                        $txt .= ' · Motivo de nueva citación: ' . $cRow['motivo_recitacion'];
                    }
                    $partsHist[] = $txt;
                }

                $historialText = " Historial de citaciones: " . implode(' | ', $partsHist) . '.';
            }

            $detalleFull  = $lineaVigente . $historialText;
            $detalleShort = mb_strimwidth($detalleFull, 0, 220, '…', 'UTF-8');

            $metaCitacion = [
                'Fecha citación vigente' => $vigenteFecha,
                'Hora citación vigente'  => $vigenteHora,
                'Medio citación vigente' => $vigenteMedio,
                'Total de citaciones'    => (string) count($citacionesRows),
            ];

            $items[] = [
                'clave'        => 'citacion',
                'titulo'       => 'Citación',
                'fecha'        => !empty($citacion['created_at'])
                    ? Time::parse($citacion['created_at'])->format('d/m/Y')
                    : '',
                'detalle'      => $detalleShort,
                'detalle_full' => $detalleFull,
                'estado'       => 'completado',
                'meta'         => $metaCitacion,
                'adjuntos'     => $this->getAdjuntos($id, 'citacion'),

                // 👈 NUEVO: historial para el Portal Cliente
                'citaciones'   => $historialCitacion,
            ];
        } else {
            $items[] = [
                'clave'        => 'citacion',
                'titulo'       => 'Citación',
                'fecha'        => '',
                'detalle'      => 'Sin citación registrada.',
                'detalle_full' => 'Sin citación registrada.',
                'estado'       => 'pendiente',
            ];
        }



        // 3️⃣ Descargos / Cargos y Descargos
        $descargos = $db->table('tbl_furd_descargos')
            ->where('furd_id', $id)
            ->get()
            ->getRowArray();

        $descDetalle = '';
        $metaDesc    = [
            'Fecha del evento' => '—',
            'Hora'             => '—',
            'Medio'            => '—',
        ];

        if ($descargos) {
            $descDetalle = 'Descargo realizado de manera ' . ($descargos['medio'] ?? '—');

            $metaDesc = [
                'Fecha del evento' => isset($descargos['fecha_evento'])
                    ? Time::parse($descargos['fecha_evento'])->format('d/m/Y')
                    : '—',
                'Hora'  => $descargos['hora']  ?? '—',
                'Medio' => $descargos['medio'] ?? '—',
            ];
        } elseif ($citacion && (($citacion['medio'] ?? null) === 'escrito')) {
            $descDetalle = 'No se realizó audiencia de cargos y descargos, porque el descargo fue presentado por escrito según la citación.';

            $metaDesc = [
                'Tipo de descargo'           => 'Escrito (se omite acta de cargos y descargos)',
                'Fecha del descargo escrito' => isset($citacion['fecha_evento'])
                    ? Time::parse($citacion['fecha_evento'])->format('d/m/Y')
                    : '—',
                'Hora citada'                => $citacion['hora'] ?? '—',
            ];
        } else {
            $descDetalle = '— Sin audiencia de cargos y descargos registrada —';
        }

        $items[] = [
            'clave'        => 'descargos',
            'titulo'       => 'Cargos y Descargos',
            'fecha'        => isset($descargos['created_at'])
                ? Time::parse($descargos['created_at'])->format('d/m/Y')
                : '',
            'detalle'      => $descDetalle,
            'detalle_full' => $descDetalle,
            'estado'       => $descargos ? 'completado' : 'pendiente',
            'meta'         => $metaDesc,
            'adjuntos'     => $this->getAdjuntos($id, 'descargos'),
        ];

        // 4️⃣ Soporte de citación / acta
        $soporte = $db->table('tbl_furd_soporte')
            ->where('furd_id', $id)
            ->get()
            ->getRowArray();

        $clienteEstado        = $soporte['cliente_estado']        ?? 'pendiente';
        $clienteRespondidoAt  = $soporte['cliente_respondido_at'] ?? null;
        $clienteDecision      = $soporte['cliente_decision']      ?? null;
        $clienteJustificacion = $soporte['cliente_justificacion'] ?? null;
        $clienteComentario    = $soporte['cliente_comentario']    ?? null;
        $clienteFechaSusp    = $soporte['cliente_fecha_inicio_suspension'] ?? null;
        $clienteFechaSuspFin = $soporte['cliente_fecha_fin_suspension']    ?? null;


        $notificadoClienteAt   = $soporte['notificado_cliente_at']   ?? null;
        $recordatorioClienteAt = $soporte['recordatorio_cliente_at'] ?? null;
        $autoArchivadoAt       = $soporte['auto_archivado_at']       ?? null;

        $decisionPropuesta = (string) ($soporte['decision_propuesta'] ?? '—');
        $isSuspension      = strcasecmp($decisionPropuesta, 'Suspensión disciplinaria') === 0;

        /** 🔗 La revisión de cliente siempre va por CONSECUTIVO (PD-000xxx), 
         *  igual que en la vista administrativa.
         */
        $consecutivo = $furd['consecutivo'] ?? sprintf('PD-%06d', $furd['id']);
        /**
         * El cliente solo puede responder si:
         *  - el proceso sigue en estado técnico "soporte"
         *  - y el estado del cliente está pendiente.
         * Si el proceso está "archivado" (o ya pasó a "decision"), no se expone el enlace.
         */
        $puedeResponder = ($furd['estado'] === 'soporte') && ($clienteEstado === 'pendiente');

        $urlRevision = $puedeResponder
            ? base_url('soporte/revision-cliente/' . $consecutivo)
            : null;

        if ($soporte) {
            if ($clienteEstado === 'pendiente') {
                $resumen = 'Decisión propuesta: ' . $decisionPropuesta . '. A la espera de respuesta del cliente.';
            } else {
                $estadoTxt = $clienteEstado === 'aprobado' ? 'APROBADA' : 'RECHAZADA';
                $resumen = 'Decisión propuesta: ' . $decisionPropuesta
                    . ". Cliente: {$estadoTxt}"
                    . ($clienteDecision ? ' · Ajuste sugerido: ' . $clienteDecision : '');
            }

            // 👇 meta con el comportamiento solicitado
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
                // aquí NO se muestra aún la fecha de inicio suspensión
            } elseif ($isSuspension) {
                // cliente ya tomó decisión → mostramos solo la fecha de inicio (si aplica)
                $metaSoporte['Fecha inicio suspensión (cliente)'] = $clienteFechaSusp
                    ? Time::parse($clienteFechaSusp)->format('d/m/Y')
                    : '—';

                $metaSoporte['Fecha fin suspensión (cliente)'] = $clienteFechaSuspFin
                    ? Time::parse($clienteFechaSuspFin)->format('d/m/Y')
                    : '—';
            }

            $items[] = [
                'clave'                  => 'soporte',
                'titulo'                 => 'Soporte de citación / acta',
                'fecha'                  => $soporte['created_at']
                    ? Time::parse($soporte['created_at'])->format('d/m/Y')
                    : '',
                'detalle'                => mb_strimwidth($resumen, 0, 220, '…', 'UTF-8'),
                'detalle_full'           => $resumen,
                'estado'                 => 'completado',
                'meta'                   => $metaSoporte,
                'cliente_estado'         => $clienteEstado,
                'cliente_respondido'     => $clienteRespondidoAt,
                'cliente_decision'       => $clienteDecision,
                'cliente_comentario'     => $clienteComentario,
                'justificacion_original' => $soporte['justificacion'] ?? null,
                'cliente_justificacion'  => $clienteJustificacion,
                'decision_propuesta'     => $decisionPropuesta,
                'cliente_fecha_inicio_suspension' => $clienteFechaSusp
                    ? Time::parse($clienteFechaSusp)->format('Y-m-d')
                    : null,
                'cliente_fecha_fin_suspension' => $clienteFechaSuspFin
                    ? Time::parse($clienteFechaSuspFin)->format('Y-m-d')
                    : null,

                'url_revision'           => $urlRevision,
                'adjuntos'               => $this->getAdjuntos($id, 'soporte'),
            ];
        } else {
            $items[] = [
                'clave'          => 'soporte',
                'titulo'         => 'Soporte de citación / acta',
                'fecha'          => '',
                'detalle'        => 'Sin información de soporte registrada.',
                'detalle_full'   => 'Sin información de soporte registrada.',
                'estado'         => 'pendiente',
                'cliente_estado' => 'pendiente',
                'url_revision'   => null,
            ];
        }

        if (!empty($autoArchivadoAt)) {
            $items[] = [
                'clave'        => 'archivado',
                'titulo'       => 'Archivo automático',
                'fecha'        => Time::parse($autoArchivadoAt)->format('d/m/Y'),
                'detalle'      => 'El proceso fue archivado automáticamente por falta de respuesta del cliente dentro del plazo de 10 días.',
                'detalle_full' => 'El proceso fue archivado automáticamente por falta de respuesta formal dentro del plazo de diez (10) días calendario establecido para la aprobación o rechazo de la decisión propuesta.',
                'estado'       => 'completado',
            ];
        }

        // 5️⃣ Decisión
        $decision = $db->table('tbl_furd_decision')
            ->where('furd_id', $id)
            ->get()
            ->getRowArray();

        if ($decision) {
            $detalle   = trim((string) ($decision['decision_text'] ?? ''));
            $fundament = trim((string) ($decision['fundamentacion'] ?? ($decision['detalle_text'] ?? '')));

            $partes = [];
            if ($detalle !== '')   $partes[] = $detalle;
            if ($fundament !== '') $partes[] = 'Fundamentación: ' . $fundament;

            $textoFull  = implode(' · ', $partes);
            $textoShort = mb_strimwidth($textoFull, 0, 220, '…', 'UTF-8');

            $items[] = [
                'clave'        => 'decision',
                'titulo'       => 'Decisión',
                'fecha'        => isset($decision['created_at'])
                    ? Time::parse($decision['created_at'])->format('d/m/Y')
                    : '',
                'detalle'      => $textoShort ?: '— Sin decisión registrada —',
                'detalle_full' => $textoFull  ?: '— Sin decisión registrada —',
                'estado'       => 'completado',
                'meta'         => [
                    'Fecha de la decisión' => isset($decision['fecha_evento'])
                        ? Time::parse($decision['fecha_evento'])->format('d/m/Y')
                        : '—',
                ],
                'adjuntos'     => $this->getAdjuntos($id, 'decision'),
            ];
        } else {
            $items[] = [
                'clave'        => 'decision',
                'titulo'       => 'Decisión',
                'fecha'        => '',
                'detalle'      => '— Sin decisión registrada —',
                'detalle_full' => '— Sin decisión registrada —',
                'estado'       => 'pendiente',
            ];
        }

        return $items;
    }

    /**
     * Obtiene adjuntos por fase (mismo esquema que en LineaTiempoController)
     */
    private function getAdjuntos(int $furdId, string $fase): array
    {
        $rows = db_connect()->table('tbl_adjuntos')
            ->select('id, nombre_original, ruta, storage_provider')
            ->where([
                'origen'    => 'furd',
                'origen_id' => $furdId,
                'fase'      => $fase,
            ])
            ->orderBy('created_at', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(static fn($a) => [
            'id'           => (int) $a['id'],
            'nombre'       => $a['nombre_original'] ?? basename((string) ($a['ruta'] ?? '')),
            'provider'     => $a['storage_provider'] ?? 'local',
            'url_open'     => site_url('adjuntos/' . (int) $a['id'] . '/open'),
            'url_download' => site_url('adjuntos/' . (int) $a['id'] . '/download'),
        ], $rows);
    }


    /**
     * Obtiene faltas asociadas al FURD (igual que en LineaTiempoController)
     */
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
            'codigo'   => (string) ($r['codigo']   ?? ''),
            'gravedad' => (string) ($r['gravedad'] ?? ''),
            'desc'     => (string) ($r['descripcion'] ?? ''),
        ], $rows);
    }
}
