<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\HandlesAdjuntos;
use App\Models\FurdModel;
use App\Models\FurdDecisionModel;
use App\Requests\FurdDecisionRequest;
use App\Services\FurdWorkflow;

class DecisionController extends BaseController
{
    use HandlesAdjuntos;

    public function create()
    {
        return view('decision/create');
    }

    public function store()
    {
        // 0) Normalizar fecha_evento antes de validar
        $rawFecha = trim((string)$this->request->getPost('fecha_evento'));

        if ($rawFecha === '') {
            $errors = ['fecha_evento' => 'La fecha de la decisión es obligatoria.'];

            if ($this->request->isAJAX()) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON(['ok' => false, 'errors' => $errors]);
            }

            return redirect()->back()
                ->with('errors', $errors)
                ->withInput();
        }

        // Pasar a minúsculas y quitar tildes
        $fechaTexto = mb_strtolower($rawFecha, 'UTF-8');
        $fechaTexto = strtr($fechaTexto, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
        ]);
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

        // 14-11-2025 o 14/11/2025
        if (preg_match('~^\s*(\d{1,2})[-/](\d{1,2})[-/](\d{4})\s*$~', $fechaTexto, $m)) {
            $timestamp = strtotime(sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]));
        }

        // "14 noviembre 2025"
        if ($timestamp === false) {
            $fechaIngles = str_ireplace(array_keys($map), array_values($map), $fechaTexto);
            $timestamp   = strtotime($fechaIngles);
        }

        if ($timestamp === false) {
            $errors = ['fecha_evento' => 'La fecha de la decisión no es válida.'];

            if ($this->request->isAJAX()) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON(['ok' => false, 'errors' => $errors]);
            }

            return redirect()->back()
                ->with('errors', $errors)
                ->withInput();
        }

        $fechaConvertida = date('Y-m-d', $timestamp);

        // 1) Validar con Request
        $postData = $this->request->getPost();
        $postData['fecha_evento'] = $fechaConvertida;

        $validation = \Config\Services::validation();
        $validation->setRules(FurdDecisionRequest::rules(), FurdDecisionRequest::messages());

        if (!$validation->run($postData)) {
            $errors = $validation->getErrors();

            if ($this->request->isAJAX()) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON(['ok' => false, 'errors' => $errors]);
            }

            return redirect()->back()
                ->with('errors', $errors)
                ->withInput();
        }

        // 2) Buscar FURD por consecutivo (ya viene en formato PD-000123 del front)
        $consec = (string)$postData['consecutivo'];
        $furd   = (new FurdModel())->findByConsecutivo($consec);
        if (!$furd) {
            $errors = ['FURD no encontrado'];

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['ok' => false, 'errors' => $errors]);
            }

            return redirect()->back()
                ->with('errors', $errors)
                ->withInput();
        }

        // 3) Validar flujo (que ya exista soporte)
        $wf = new FurdWorkflow();
        if (!$wf->canStartDecision($furd)) {
            $errors = ['La fase previa (Soporte) no está completa o ya existe una decisión.'];

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['ok' => false, 'errors' => $errors]);
            }

            return redirect()->back()
                ->with('errors', $errors)
                ->withInput();
        }

        // 4) Evitar decisión duplicada para el mismo FURD
        $decisionModel = new FurdDecisionModel();
        $existing      = $decisionModel->findByFurd((int)$furd['id'] ?? 0);
        if ($existing) {
            $errors = ['Ya existe una decisión registrada para este proceso.'];

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['ok' => false, 'errors' => $errors]);
            }

            return redirect()->back()
                ->with('errors', $errors)
                ->withInput();
        }

        $db = db_connect();
        $db->transStart();

        try {
            // 5) Construir texto final: tipo + detalle
            $tipo    = (string)$postData['decision'];
            $detalle = trim((string)($postData['decision_text'] ?? ''));

            $decisionText = $tipo;
            if ($detalle !== '') {
                $decisionText .= "\n| Fundamento: " . $detalle;
            }

            $payload = [
                'furd_id'       => (int)$furd['id'],
                'fecha_evento'  => $fechaConvertida,
                'decision_text' => $decisionText,
            ];

            $id = (int)$decisionModel->insert($payload, true);

            // 6) Adjuntos fase decision -> Google Drive (via HandlesAdjuntos)
            $files = $this->request->getFiles()['adjuntos'] ?? [];
            if (!empty($files)) {
                $this->saveAdjuntos((int)$furd['id'], 'decision', is_array($files) ? $files : [$files]);
            }

            // Estado lo maneja el trigger en BD
            $db->transComplete();

            $mensajeOk = 'Decisión registrada. Proceso finalizado.';

            if ($this->request->isAJAX()) {
                // Flash para el siguiente GET
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

            $errors = [$e->getMessage()];

            if ($this->request->isAJAX()) {
                return $this->response
                    ->setStatusCode(500)
                    ->setJSON(['ok' => false, 'errors' => $errors]);
            }

            return redirect()->back()
                ->with('errors', $errors)
                ->withInput();
        }
    }


    public function update(int $id)
    {
        // misma normalización+validación de fecha que en store
        $rawFecha = trim((string)$this->request->getPost('fecha_evento'));

        if ($rawFecha === '') {
            return redirect()->back()
                ->with('errors', ['fecha_evento' => 'La fecha de la decisión es obligatoria.'])
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
            'diciembre'  => 'diciembre',
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
                ->with('errors', ['fecha_evento' => 'La fecha de la decisión no es válida.'])
                ->withInput();
        }

        $fechaConvertida = date('Y-m-d', $timestamp);

        $postData = $this->request->getPost();
        $postData['fecha_evento'] = $fechaConvertida;

        $validation = \Config\Services::validation();
        $validation->setRules(FurdDecisionRequest::rules(), FurdDecisionRequest::messages());

        if (!$validation->run($postData)) {
            return redirect()->back()
                ->with('errors', $validation->getErrors())
                ->withInput();
        }

        $d   = new FurdDecisionModel();
        $row = $d->find($id);
        if (!$row) {
            return redirect()->back()
                ->with('errors', ['Registro no existe']);
        }

        $tipo    = (string)$postData['decision'];
        $detalle = trim((string)($postData['decision_text'] ?? ''));

        $decisionText = $tipo;
        if ($detalle !== '') {
            $decisionText .= "\n\nDetalle: " . $detalle;
        }

        $payload = [
            'fecha_evento'  => $fechaConvertida,
            'decision_text' => $decisionText,
        ];
        $d->update($id, $payload);

        $files = $this->request->getFiles()['adjuntos'] ?? [];
        if (!empty($files)) {
            $this->saveAdjuntos((int)$row['furd_id'], 'decision', is_array($files) ? $files : [$files]);
        }

        return redirect()->back()->with('ok', 'Decisión actualizada');
    }

    public function find()
    {
        $consec = (string) $this->request->getGet('consecutivo');
        $furd   = (new FurdModel())->findByConsecutivo($consec);

        if (!$furd) {
            return $this->response->setJSON(['ok' => false]);
        }

        $am   = new \App\Models\FurdAdjuntoModel();
        $fid  = (int) $furd['id'];

        // Traemos adjuntos por fase, igual que en soporte, pero agregando soporte/decisión
        $prev = [
            'registro' => $am->listByFase($fid, 'registro'),
            'citacion' => $am->listByFase($fid, 'citacion'),
            'descargos' => $am->listByFase($fid, 'descargos'),
            'soporte'  => $am->listByFase($fid, 'soporte'),
            'decision' => $am->listByFase($fid, 'decision'),
        ];

        return $this->response->setJSON([
            'ok'      => true,
            'furd'    => $furd,
            'prevAdj' => $prev,
        ]);
    }
}
