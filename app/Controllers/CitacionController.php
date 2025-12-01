<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\HandlesAdjuntos;
use App\Models\FurdModel;
use App\Models\FurdCitacionModel;
use App\Models\FurdAdjuntoModel;
use App\Requests\FurdCitacionRequest;
use App\Domain\Furd\FurdWorkflow;
use App\Models\FurdDescargoModel;
use App\Models\FurdSoporteModel;
use App\Models\FurdDecisionModel;
use luisplata\FestivosColombia\Festivos;

class CitacionController extends BaseController
{
    use HandlesAdjuntos;

    public function create()
    {
        $fechasHabilitadas = $this->calcularFechasHabilitadas();

        return view('citacion/create', [
            'fechasHabilitadas' => $fechasHabilitadas,
        ]);
    }


    public function find()
    {
        $raw = (string) $this->request->getGet('consecutivo');

        $consec = $this->normalizeConsecutivo($raw);
        if ($consec === null) {
            return $this->response->setJSON(['ok' => false]);
        }

        $fm   = new FurdModel();
        $furd = $fm->findByConsecutivo($consec);
        if (!$furd) {
            return $this->response->setJSON(['ok' => false]);
        }

        $fase = (string) $this->request->getGet('fase') ?: 'registro';

        $rows = (new FurdAdjuntoModel())->listByFase((int) $furd['id'], $fase);

        $adjuntos = array_map(static function (array $row) {
            $id = (int) ($row['id'] ?? 0);

            return [
                'id'     => $id,
                'nombre' => $row['nombre']
                    ?? $row['nombre_original']
                    ?? $row['filename']
                    ?? 'archivo',
                'mime'   => $row['mime']
                    ?? $row['mimetype']
                    ?? '',
                'tamano' => $row['tamano']
                    ?? $row['tamano_bytes']
                    ?? $row['size']
                    ?? null,
                // mismo visor que usas en la línea de tiempo
                'url'    => base_url('adjuntos/' . $id . '/open'),
            ];
        }, $rows ?? []);

        return $this->response->setJSON([
            'ok'          => true,
            'consecutivo' => $consec,
            'furd'        => $furd,
            'adjuntos'    => $adjuntos,
        ]);
    }


    public function store()
    {
        // --- 1) Normalizar fecha (lo que ya tenías) ---
        $rawFecha = trim((string)$this->request->getPost('fecha_evento'));

        $fechaTexto = mb_strtolower($rawFecha, 'UTF-8');
        $fechaTexto = strtr($fechaTexto, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
        ]);

        $fechaTexto = str_ireplace(
            ['lunes', 'martes', 'miercoles', 'miércoles', 'jueves', 'viernes', 'sabado', 'sábado', 'domingo', ' de '],
            ' ',
            $fechaTexto
        );

        $fechaTexto = str_replace(',', ' ', $fechaTexto);
        $fechaTexto = preg_replace('/\s+/', ' ', trim($fechaTexto));

        $map = [
            'enero'      => 'january',
            'febrero'    => 'february',
            'marzo'      => 'march',
            'abril'      => 'april',
            'mayo'       => 'may',
            'junio'      => 'june',
            'julio'      => 'july',
            'agosto'     => 'august',
            'septiembre' => 'september',
            'setiembre'  => 'september',
            'octubre'    => 'october',
            'noviembre'  => 'november',
            'diciembre'  => 'december',
        ];

        $fechaIngles = str_ireplace(array_keys($map), array_values($map), $fechaTexto);
        $timestamp   = strtotime($fechaIngles);

        if ($timestamp === false) {
            $msg = 'La fecha de citación no es válida.';

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'ok'     => false,
                    'errors' => ['fecha_evento' => $msg],
                ]);
            }

            return redirect()->back()
                ->with('errors', ['fecha_evento' => $msg])
                ->withInput();
        }

        $fechaConvertida       = date('Y-m-d', $timestamp);
        $_POST['fecha_evento'] = $fechaConvertida;

        // --- 1.2 bis) Validar que la fecha esté entre el 5° y 7° día hábil contado desde mañana ---
        $fechasHabilitadas = $this->calcularFechasHabilitadas();

        if (!in_array($fechaConvertida, $fechasHabilitadas, true)) {
            $msg = 'La fecha del descargo debe estar entre el 5° y el 7° día hábil contado desde mañana, contando de lunes a sábado y excluyendo domingos y festivos en Colombia.';


            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'ok'     => false,
                    'errors' => ['fecha_evento' => $msg],
                ]);
            }

            return redirect()->back()
                ->with('errors', ['fecha_evento' => $msg])
                ->withInput();
        }

        // --- 2) Obtener FURD por consecutivo normalizado ---
        $consecRaw = (string) $this->request->getPost('consecutivo');
        $consec    = $this->normalizeConsecutivo($consecRaw);

        if ($consec === null) {
            $msg = 'El consecutivo es obligatorio y debe tener formato PD-000123.';

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'ok'     => false,
                    'errors' => ['consecutivo' => $msg],
                ]);
            }

            return redirect()->back()
                ->with('errors', ['consecutivo' => $msg])
                ->withInput();
        }

        $furd = (new FurdModel())->findByConsecutivo($consec);
        if (!$furd) {
            $msg = 'FURD no encontrado';

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'ok'     => false,
                    'errors' => ['consecutivo' => $msg],
                ]);
            }

            return redirect()->back()
                ->with('errors', ['consecutivo' => $msg])
                ->withInput();
        }

        // --- 3) Validar workflow ---
        $wf = new FurdWorkflow(
            new FurdModel(),
            new FurdCitacionModel(),
            new FurdDescargoModel(),
            new FurdSoporteModel(),
            new FurdDecisionModel(),
        );

        if (!$wf->canStartCitacion($furd)) {
            $msg = 'La fase previa (registro) no está completa o ya existe citación.';

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'ok'     => false,
                    'errors' => [$msg],
                ]);
            }

            return redirect()->back()
                ->with('errors', [$msg])
                ->withInput();
        }

        // --- 4) Guardar citación ---
        $db = db_connect();
        $db->transStart();

        try {
            $cit = new FurdCitacionModel();

            $medio = (string) $this->request->getPost('medio');

            $payload = [
                'furd_id'      => (int) $furd['id'],
                'fecha_evento' => $fechaConvertida,
                'hora'         => (string) $this->request->getPost('hora'),
                'medio'        => $medio,
                'motivo'       => (string) $this->request->getPost('motivo'),
            ];

            $cit->insert($payload);

            $files = $this->request->getFiles()['adjuntos'] ?? [];
            if (!empty($files)) {
                $this->saveAdjuntos((int) $furd['id'], 'citacion', is_array($files) ? $files : [$files]);
            }

            $db->transComplete();

            if ($medio === 'escrito') {
                $mensajeOk = 'Citación registrada con descargo escrito. Continúa directamente con Soporte desde Seguimiento (no se genera acta de cargos y descargos).';
            } else {
                $mensajeOk = 'Citación registrada. Continúa con Descargos desde Seguimiento.';
            }

            if ($this->request->isAJAX()) {
                session()->setFlashdata('ok', $mensajeOk);
                session()->setFlashdata('consecutivo', $consec);

                return $this->response->setJSON([
                    'ok'         => true,
                    'redirectTo' => site_url('seguimiento'),
                ]);
            }

            return redirect()
                ->to(site_url('seguimiento'))
                ->with('ok', $mensajeOk)
                ->with('consecutivo', $consec);
        } catch (\Throwable $e) {
            $db->transRollback();

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'ok'     => false,
                    'errors' => [$e->getMessage()],
                ]);
            }

            return redirect()->back()
                ->with('errors', [$e->getMessage()])
                ->withInput();
        }
    }




    public function update(int $id)
    {
        $rules = FurdCitacionRequest::rules();
        $messages = FurdCitacionRequest::messages();

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $cit = new FurdCitacionModel();
        $row = $cit->find($id);
        if (!$row) return redirect()->back()->with('errors', ['Registro no existe']);

        $payload = [
            'fecha_evento'   => (string)$this->request->getPost('fecha_evento'),
            'hora'    => (string)$this->request->getPost('hora'),
            'medio'   => (string)$this->request->getPost('medio'),
            'motivo'  => (string)$this->request->getPost('motivo'),
        ];
        $cit->update($id, $payload);

        // Adjuntos adicionales
        $files = $this->request->getFiles()['adjuntos'] ?? [];
        if (!empty($files)) {
            $this->saveAdjuntos((int)$row['furd_id'], 'citacion', is_array($files) ? $files : [$files]);
        }

        return redirect()->back()->with('ok', 'Citación actualizada');
    }

    /**
     * Normaliza un consecutivo a formato PD-000123.
     * Devuelve null si es inválido.
     */
    private function normalizeConsecutivo(?string $value): ?string
    {
        $v = strtoupper(trim((string) $value));
        if ($v === '') {
            return null;
        }

        // Si no empieza por PD-, añadimos el prefijo
        if (substr($v, 0, 3) !== 'PD-') {
            $v = 'PD-' . preg_replace('/\D+/', '', $v);
        }

        if (!preg_match('~^PD-(\d+)~', $v, $m)) {
            return null;
        }

        $num = preg_replace('/\D+/', '', $m[1]);
        if ($num === '') {
            return null;
        }

        return 'PD-' . str_pad($num, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Calcula las fechas hábiles permitidas (5°, 6° y 7° día hábil desde mañana),
     * contando de lunes a sábado y excluyendo domingos y festivos no laborables
     * en Colombia.
     * Retorna un array de strings 'Y-m-d'.
     */
    private function calcularFechasHabilitadas(): array
    {
        $tz  = new \DateTimeZone('America/Bogota');
        $hoy = new \DateTimeImmutable('today', $tz);

        $anioActual    = (int) $hoy->format('Y');
        $anioSiguiente = $anioActual + 1;

        // Inicializamos festivos del año actual y del siguiente
        $festivosActual = new Festivos();
        $festivosActual->festivos($anioActual);

        $festivosSiguiente = new Festivos();
        $festivosSiguiente->festivos($anioSiguiente);

        $esDiaHabil = function (\DateTimeImmutable $fecha) use ($festivosActual, $festivosSiguiente, $anioActual): bool {
            // 1 = lunes ... 7 = domingo
            $dow = (int) $fecha->format('N');

            // Ahora SÍ contamos el sábado como hábil (solo se excluye domingo)
            if ($dow === 7) { // domingo
                return false;
            }

            $anio = (int) $fecha->format('Y');
            $dia  = (int) $fecha->format('d');
            $mes  = (int) $fecha->format('m');

            if ($anio === $anioActual) {
                return !$festivosActual->esFestivo($dia, $mes);
            }

            return !$festivosSiguiente->esFestivo($dia, $mes);
        };

        $fechas          = [];
        $contadorHabiles = 0;
        $fecha           = $hoy;

        // queremos el 5°, 6° y 7° día hábil → 3 fechas
        while (count($fechas) < 3) {
            // empezamos a contar desde mañana
            $fecha = $fecha->modify('+1 day');

            if ($esDiaHabil($fecha)) {
                $contadorHabiles++;

                if ($contadorHabiles >= 5 && $contadorHabiles <= 7) {
                    $fechas[] = $fecha->format('Y-m-d');
                }
            }
        }

        return $fechas;
    }
}
