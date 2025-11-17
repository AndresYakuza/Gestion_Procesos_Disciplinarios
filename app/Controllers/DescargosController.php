<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\HandlesAdjuntos;
use App\Models\FurdModel;
use App\Models\FurdDescargoModel;
use App\Models\FurdAdjuntoModel;
use App\Requests\FurdDescargosRequest;

class DescargosController extends BaseController
{
    use HandlesAdjuntos;

    public function create()
    {
        return view('descargos/create');
    }

    /** AJAX: trae adjuntos previos (citación) */
    public function find()
    {
        $consec = (string)$this->request->getGet('consecutivo');
        $furd = (new FurdModel())->findByConsecutivo($consec);
        if (!$furd) return $this->response->setJSON(['ok' => false]);

        $prevAdj = (new FurdAdjuntoModel())->listByFase((int)$furd['id'], 'citacion');
        return $this->response->setJSON(['ok' => true, 'furd' => $furd, 'adjuntos' => $prevAdj]);
    }

    public function store()
    {
        // 1) Normalizar fecha_evento ANTES de validar
        $rawFecha = trim((string)$this->request->getPost('fecha_evento'));

        if ($rawFecha === '') {
            return redirect()->back()
                ->with('errors', ['fecha_evento' => 'La fecha de descargos es obligatoria.'])
                ->withInput();
        }

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

        $timestamp = false;

        if (preg_match('~^\s*(\d{1,2})[-/](\d{1,2})[-/](\d{4})\s*$~', $fechaTexto, $m)) {
            $timestamp = strtotime(sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]));
        }

        if ($timestamp === false) {
            $fechaIngles = str_ireplace(array_keys($map), array_values($map), $fechaTexto);
            $timestamp   = strtotime($fechaIngles);
        }

        if ($timestamp === false) {
            return redirect()->back()
                ->with('errors', ['fecha_evento' => 'La fecha de descargos no es válida.'])
                ->withInput();
        }

        $fechaConvertida = date('Y-m-d', $timestamp);

        // 2) Preparar datos normalizados para validar
        $postData = $this->request->getPost();
        $postData['fecha_evento'] = $fechaConvertida;

        $validation = \Config\Services::validation();
        $validation->setRules(FurdDescargosRequest::rules(), FurdDescargosRequest::messages());

        if (!$validation->run($postData)) {
            return redirect()->back()
                ->with('errors', $validation->getErrors())
                ->withInput();
        }

        // 3) Buscar FURD por consecutivo
        $consec = (string)$postData['consecutivo'];
        $furd   = (new FurdModel())->findByConsecutivo($consec);
        if (!$furd) {
            return redirect()->back()
                ->with('errors', ['FURD no encontrado'])
                ->withInput();
        }

        // 4) Verificar que exista citación previa
        $citModel = new \App\Models\FurdCitacionModel();
        $citacion = $citModel->findByFurd((int)$furd['id']);
        if (!$citacion) {
            return redirect()->back()
                ->with('errors', ['Primero registra la citación.'])
                ->withInput();
        }

        // 4.bis) Verificar que NO exista ya un descargo para este FURD
        $descModel = new FurdDescargoModel();
        $existing  = $descModel->findByFurd((int)$furd['id']);
        if ($existing) {
            return redirect()->back()
                ->with('errors', ['Ya existen descargos registrados para este proceso. Si necesitas modificarlos, hazlo desde la opción de edición.'])
                ->withInput();
        }

        // 5) Guardar
        $db = db_connect();
        $db->transStart();

        try {
            $payload = [
                'furd_id'      => (int)$furd['id'],
                'fecha_evento' => $fechaConvertida,
                'hora'         => (string)($postData['hora'] ?? ''),
                'medio'        => (string)($postData['medio'] ?? ''),
                'observacion'  => (string)($postData['observacion'] ?? ''),
            ];
            $id = (int)$descModel->insert($payload, true);

            $files = $this->request->getFiles()['adjuntos'] ?? [];
            if (!empty($files)) {
                $this->saveAdjuntos((int)$furd['id'], 'descargos', is_array($files) ? $files : [$files]);
            }

            $db->transComplete();

            return redirect()
                ->to(site_url('soporte'))
                ->with('ok', 'Descargos registrados. Continúa con Soporte de citación y acta.');
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()
                ->with('errors', [$e->getMessage()])
                ->withInput();
        }
    }

    public function update(int $id)
    {
        $rules    = FurdDescargosRequest::rules();
        $messages = FurdDescargosRequest::messages();

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $desc = new FurdDescargoModel();
        $row  = $desc->find($id);
        if (!$row) return redirect()->back()->with('errors', ['Registro no existe']);

        $payload = [
            'fecha_evento' => (string)$this->request->getPost('fecha_evento'),
            'hora'         => (string)$this->request->getPost('hora'),
            'medio'        => (string)$this->request->getPost('medio'),
            'observacion'  => (string)$this->request->getPost('observacion'),
        ];
        $desc->update($id, $payload);

        $files = $this->request->getFiles()['adjuntos'] ?? [];
        if (!empty($files)) {
            $this->saveAdjuntos((int)$row['furd_id'], 'descargos', is_array($files) ? $files : [$files]);
        }

        return redirect()->back()->with('ok', 'Descargos actualizados');
    }
}
