<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\HandlesAdjuntos;
use App\Models\FurdModel;
use App\Models\FurdCitacionModel;
use App\Models\FurdAdjuntoModel;
use App\Requests\FurdCitacionRequest;
use App\Services\FurdWorkflow;

class CitacionController extends BaseController
{
    use HandlesAdjuntos;

    public function create()
    {
        return view('citacion/create');
    }

    /** AJAX: busca por consecutivo y devuelve adjuntos del registro */
    public function find()
    {
        $consec = (string)$this->request->getGet('consecutivo');
        $furd = (new FurdModel())->findByConsecutivo($consec);
        if (!$furd) return $this->response->setJSON(['ok' => false]);

        $adj = (new FurdAdjuntoModel())->listByFase((int)$furd['id'], 'registro');
        return $this->response->setJSON(['ok' => true, 'furd' => $furd, 'adjuntos' => $adj]);
    }

    public function store()
    {

        $rawFecha = trim((string)$this->request->getPost('fecha_evento'));

        $fechaTexto = mb_strtolower($rawFecha, 'UTF-8');
        $fechaTexto = strtr($fechaTexto, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
        ]);

        // quitar días de la semana y la palabra "de"
        $fechaTexto = str_ireplace(
            ['lunes', 'martes', 'miercoles', 'miércoles', 'jueves', 'viernes', 'sabado', 'sábado', 'domingo', ' de '],
            ' ',
            $fechaTexto
        );

        // quitar comas dobles espacios, etc.
        $fechaTexto = str_replace(',', ' ', $fechaTexto);
        $fechaTexto = preg_replace('/\s+/', ' ', trim($fechaTexto));

        $map = [
            'enero' => 'january',
            'febrero' => 'february',
            'marzo' => 'march',
            'abril' => 'april',
            'mayo' => 'may',
            'junio' => 'june',
            'julio' => 'july',
            'agosto' => 'august',
            'septiembre' => 'september',
            'setiembre' => 'september',
            'octubre' => 'october',
            'noviembre' => 'november',
            'diciembre' => 'december',
        ];

        $fechaIngles = str_ireplace(array_keys($map), array_values($map), $fechaTexto);
        $timestamp   = strtotime($fechaIngles);

        if ($timestamp === false) {
            return redirect()->back()
                ->with('errors', ['fecha' => 'La fecha de citación no es válida.'])
                ->withInput();
        }

        $fechaConvertida = date('Y-m-d', $timestamp);
        $_POST['fecha_evento']  = $fechaConvertida;


        // 3) Obtener FURD
        $consec = (string)$this->request->getPost('consecutivo');
        $furd   = (new FurdModel())->findByConsecutivo($consec);
        if (!$furd) return redirect()->back()->with('errors', ['FURD no encontrado'])->withInput();

        $wf = new FurdWorkflow();
        if (!$wf->canStartCitacion($furd)) {
            return redirect()->back()->with('errors', ['La fase previa (registro) no está completa o ya existe citación.'])->withInput();
        }


        // 4) Guardar
        $db = db_connect();
        $db->transStart();

        try {
            $cit = new FurdCitacionModel();
            $payload = [
                'furd_id' => (int)$furd['id'],
                'fecha_evento'   => $fechaConvertida,
                'hora'    => (string)$this->request->getPost('hora'),
                'medio'   => (string)$this->request->getPost('medio'),
                'motivo'  => (string)$this->request->getPost('motivo'),
            ];
            $cit->insert($payload);

            // Adjuntos fase citación
            $files = $this->request->getFiles()['adjuntos'] ?? [];
            if (!empty($files)) {
                $this->saveAdjuntos((int)$furd['id'], 'citacion', is_array($files) ? $files : [$files]);
            }

            $db->transComplete();
            return redirect()->to(site_url('descargos'))->with('ok', 'Citación registrada. Continúa con Descargos.');
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->with('errors', [$e->getMessage()])->withInput();
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
}
