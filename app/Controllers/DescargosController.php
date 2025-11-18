<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\HandlesAdjuntos;
use App\Models\FurdModel;
use App\Models\FurdDescargoModel;
// use App\Models\FurdAdjuntoModel;  // ← ya no se usa aquí
use App\Requests\FurdDescargosRequest;

class DescargosController extends BaseController
{
    use HandlesAdjuntos;

    public function create()
    {
        return view('descargos/create');
    }

    /** AJAX: validar consecutivo (ya no devolvemos adjuntos) */
    public function find()
    {
        $raw    = (string) $this->request->getGet('consecutivo');
        $consec = $this->normalizeConsecutivo($raw);

        if ($consec === null) {
            return $this->response->setJSON(['ok' => false]);
        }

        $furd = (new FurdModel())->findByConsecutivo($consec);
        if (!$furd) {
            return $this->response->setJSON(['ok' => false]);
        }

        return $this->response->setJSON([
            'ok'   => true,
            'furd' => $furd,
        ]);
    }

public function store()
{
    // 1) Normalizar fecha_evento ANTES de validar
    $rawFecha = trim((string)$this->request->getPost('fecha_evento'));

    if ($rawFecha === '') {
        $msg = 'La fecha de descargos es obligatoria.';

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
        $msg = 'La fecha de descargos no es válida.';

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

    $fechaConvertida = date('Y-m-d', $timestamp);

    // 2) Preparar datos normalizados para validar
    $postData                 = $this->request->getPost();
    $postData['fecha_evento'] = $fechaConvertida;

    // Normalizar consecutivo a PD-000000
    $consecNorm = $this->normalizeConsecutivo((string)($postData['consecutivo'] ?? ''));
    if ($consecNorm === null) {
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
    $postData['consecutivo'] = $consecNorm;

    $validation = \Config\Services::validation();
    $validation->setRules(FurdDescargosRequest::rules(), FurdDescargosRequest::messages());

    if (!$validation->run($postData)) {
        $errs = $validation->getErrors();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'ok'     => false,
                'errors' => $errs,
            ]);
        }

        return redirect()->back()
            ->with('errors', $errs)
            ->withInput();
    }

    // 3) Buscar FURD por consecutivo
    $consec = (string)$postData['consecutivo'];
    $furd   = (new FurdModel())->findByConsecutivo($consec);
    if (!$furd) {
        $msg = 'El consecutivo no existe.';

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

    // 4) Verificar que exista citación previa
    $citModel = new \App\Models\FurdCitacionModel();
    $citacion = $citModel->findByFurd((int)$furd['id']);
    if (!$citacion) {
        $msg = 'Ups! Fase previa sin completar para este consecutivo. Primero registra la citación.';
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

    // 4.bis) Verificar que NO exista ya un descargo para este FURD
    $descModel = new FurdDescargoModel();
    $existing  = $descModel->findByFurd((int)$furd['id']);
    if ($existing) {
        $msg = 'Ya existen descargos registrados para este proceso. Si necesitas modificarlos, hazlo desde la opción de edición.';

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

        // Adjuntos de descargos (por si en otra vista se suben)
        $files = $this->request->getFiles()['adjuntos'] ?? [];
        if (!empty($files)) {
            $this->saveAdjuntos((int)$furd['id'], 'descargos', is_array($files) ? $files : [$files]);
        }

        $db->transComplete();

        $mensajeOk = 'Descargos registrados. Continúa el proceso desde Seguimiento.';

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
}
