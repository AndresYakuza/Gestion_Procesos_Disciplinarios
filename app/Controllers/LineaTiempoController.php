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
            f.hecho,
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

        $faltas = $this->getFaltas((int)$furd['id']);

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


        // 2️⃣ Citación
        $citacion = db_connect()->table('tbl_furd_citacion')
            ->where('furd_id', $furd['id'])
            ->get()
            ->getRowArray();

        $motivoFull  = $citacion ? (string)$citacion['motivo'] : '';
        $motivoShort = mb_strimwidth($motivoFull, 0, 220, '…', 'UTF-8');

        $etapas[] = [
            'clave'        => 'citacion',
            'titulo'       => 'Citación',
            'fecha'        => isset($citacion['created_at'])
                ? Time::parse($citacion['created_at'])->format('d/m/Y')
                : '',
            'detalle'      => $motivoShort,
            'detalle_full' => $motivoFull,
            'meta'    => [
                'Fecha del evento (Descargo)' => isset($citacion['fecha_evento'])
                    ? Time::parse($citacion['fecha_evento'])->format('d/m/Y')
                    : '—',
                'Hora'  => $citacion['hora'] ?? '—',
                'Medio' => $citacion['medio'] ?? '—',
            ],
            'adjuntos' => $this->getAdjuntos($furd['id'], 'citacion'),
        ];


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

        $etapas[] = [
            'clave'        => 'descargos',
            'titulo'       => 'Cargos y Descargos',
            'fecha'        => isset($descargos['created_at'])
                ? Time::parse($descargos['created_at'])->format('d/m/Y')
                : '',
            'detalle'      => $descDetalle,
            'detalle_full' => $descDetalle,
            'meta'         => $metaDesc,
            'adjuntos'     => $this->getAdjuntos($furd['id'], 'descargos'),
        ];



        // 4️⃣ Soporte (con decisión propuesta + justificación)
        $soporte = db_connect()->table('tbl_furd_soporte')
            ->where('furd_id', $furd['id'])
            ->get()
            ->getRowArray();

        $soporteDetalleFull  = '';
        $soporteDetalleShort = '';

        if ($soporte) {

            $justFull     = trim((string)($soporte['justificacion'] ?? ''));

            $partes = [];

            if ($justFull !== '') {
                $partes[] = 'Justificación: ' . $justFull;
            }

            $soporteDetalleFull = $partes
                ? implode("\n\n", $partes)
                : '— Sin información de soporte registrada —';

            $soporteDetalleShort = mb_strimwidth($soporteDetalleFull, 0, 220, '…', 'UTF-8');
        } else {
            $soporteDetalleFull  = '— Sin soporte registrado —';
            $soporteDetalleShort = $soporteDetalleFull;
        }

        $etapas[] = [
            'clave'        => 'soporte',
            'titulo'       => 'Soporte de Citación / Acta',
            'fecha'        => isset($soporte['created_at'])
                ? Time::parse($soporte['created_at'])->format('d/m/Y')
                : '',
            'detalle'      => $soporteDetalleShort,
            'detalle_full' => $soporteDetalleFull,
            'meta'    => [
                'Responsable'        => $soporte['responsable']        ?? '—',
                'Decisión propuesta' => $soporte['decision_propuesta'] ?? '—',
            ],
            'adjuntos' => $this->getAdjuntos($furd['id'], 'soporte'),
        ];



        // 5️⃣ Decisión
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
