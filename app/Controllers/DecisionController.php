<?php namespace App\Controllers;

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
        // 1) Normalizar fecha_evento antes de validar
        $rawFecha = trim((string)$this->request->getPost('fecha_evento'));

        if ($rawFecha === '') {
            return redirect()->back()
                ->with('errors', ['fecha_evento' => 'La fecha de la decisión es obligatoria.'])
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
            return redirect()->back()
                ->with('errors', ['fecha_evento' => 'La fecha de la decisión no es válida.'])
                ->withInput();
        }

        $fechaConvertida = date('Y-m-d', $timestamp);

        // 2) Validar con Request
        $postData = $this->request->getPost();
        $postData['fecha_evento'] = $fechaConvertida;

        $validation = \Config\Services::validation();
        $validation->setRules(FurdDecisionRequest::rules(), FurdDecisionRequest::messages());

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

        // 4) Validar flujo (que ya exista soporte)
        $wf = new FurdWorkflow();
        if (!$wf->canStartDecision($furd)) {
            return redirect()->back()
                ->with('errors', ['Primero registra el soporte.'])
                ->withInput();
        }

        // 5) Evitar decisión duplicada para el mismo FURD
        $decisionModel = new FurdDecisionModel();
        $existing      = $decisionModel->findByFurd((int)$furd['id'] ?? 0);
        if ($existing) {
            return redirect()->back()
                ->with('errors', ['Ya existe una decisión registrada para este proceso.'])
                ->withInput();
        }

        $db = db_connect();
        $db->transStart();

        try {
            // Construir texto final: tipo + detalle
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

            // Adjuntos fase decision -> Google Drive (via HandlesAdjuntos)
            $files = $this->request->getFiles()['adjuntos'] ?? [];
            if (!empty($files)) {
                $this->saveAdjuntos((int)$furd['id'], 'decision', is_array($files) ? $files : [$files]);
            }

            // No es necesario actualizar estado aquí: lo hace el trigger
            // trg_decision_ai_estado en la BD

            $db->transComplete();

            return redirect()
                ->to(site_url('seguimiento'))
                ->with('ok', 'Decisión registrada. Proceso finalizado.');
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()
                ->with('errors', [$e->getMessage()])
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
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
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
            'descargos'=> $am->listByFase($fid, 'descargos'),
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
