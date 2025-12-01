<?php namespace App\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\HandlesAdjuntos;
use App\Models\FurdModel;
use App\Models\FurdSoporteModel;
use App\Models\FurdAdjuntoModel;
use App\Requests\FurdSoporteRequest;
use App\Domain\Furd\FurdWorkflow;
use App\Models\FurdCitacionModel;
use App\Models\FurdDescargoModel;
use App\Models\FurdDecisionModel;

class SoporteController extends BaseController
{
    use HandlesAdjuntos;

    public function create()
    {
        return view('soporte/create');
    }

    /** AJAX: trae adjuntos previos (registro, citación, descargos) */
    public function find()
    {
        $raw    = (string)$this->request->getGet('consecutivo');
        $consec = $this->normalizeConsecutivo($raw);

        if ($consec === null) {
            return $this->response->setJSON(['ok' => false]);
        }

        $furd = (new FurdModel())->findByConsecutivo($consec);
        if (!$furd) {
            return $this->response->setJSON(['ok' => false]);
        }

        $adjModel = new FurdAdjuntoModel();
        $prev = [
            'registro' => $adjModel->listByFase((int)$furd['id'], 'registro'),
            'citacion' => $adjModel->listByFase((int)$furd['id'], 'citacion'),
            'descargos'=> $adjModel->listByFase((int)$furd['id'], 'descargos'),
        ];

        return $this->response->setJSON([
            'ok'          => true,
            'consecutivo' => $consec,
            'furd'        => $furd,
            'prevAdj'     => $prev,
        ]);
    }

    public function store()
    {
        // ---------- 0) Normalizar consecutivo ----------
        $consecNorm = $this->normalizeConsecutivo(
            (string)$this->request->getPost('consecutivo')
        );

        if ($consecNorm === null) {
            $errors = ['consecutivo' => 'El consecutivo es obligatorio y debe tener formato PD-000123.'];

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['ok' => false, 'errors' => $errors]);
            }

            return redirect()->back()->with('errors', $errors)->withInput();
        }

        // ---------- 1) Validar datos de formulario ----------
        $postData                 = $this->request->getPost();
        $postData['consecutivo']  = $consecNorm;

        $validation = \Config\Services::validation();
        $validation->setRules(FurdSoporteRequest::rules(), FurdSoporteRequest::messages());

        if (!$validation->run($postData)) {
            $errors = $validation->getErrors();

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['ok' => false, 'errors' => $errors]);
            }

            return redirect()->back()->with('errors', $errors)->withInput();
        }

        // ---------- 2) Validar adjuntos (extensión + tamaño) ----------
        $files = $this->request->getFiles()['adjuntos'] ?? [];
        $files = is_array($files) ? $files : [$files];

        $allowedExt = ['pdf','jpg','jpeg','png','heic','doc','docx','xls','xlsx'];
        $maxSize    = 16 * 1024 * 1024; // 16 MB
        $fileError  = null;

        foreach ($files as $file) {
            if (!$file || !$file->isValid() || $file->hasMoved()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, $allowedExt, true)) {
                $fileError = 'Uno o más archivos tienen un formato no permitido. Solo se permiten PDF, imágenes (JPG, PNG, HEIC) y archivos de Office (DOC, DOCX, XLS, XLSX).';
                break;
            }

            if ($file->getSize() > $maxSize) {
                $fileError = 'Uno o más archivos superan el tamaño máximo de 16 MB.';
                break;
            }
        }

        if ($fileError !== null) {
            $errors = ['adjuntos' => $fileError];

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['ok' => false, 'errors' => $errors]);
            }

            return redirect()->back()->with('errors', $errors)->withInput();
        }

        // ---------- 3) Buscar FURD por consecutivo ----------
        $furd = (new FurdModel())->findByConsecutivo($consecNorm);
        if (!$furd) {
            $errors = ['consecutivo' => 'El consecutivo no existe.'];

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['ok' => false, 'errors' => $errors]);
            }

            return redirect()->back()->with('errors', $errors)->withInput();
        }

// ---------- 4) Validar flujo (descargos o descargo escrito) ----------
$wf = new FurdWorkflow(
    new FurdModel(),
    new FurdCitacionModel(),
    new FurdDescargoModel(),
    new FurdSoporteModel(),
    new FurdDecisionModel(),
);

if (!$wf->canStartSoporte($furd)) {
    $errors = ['La fase previa no está completa. '
        . 'Debes contar con acta de descargos o haber registrado la citación con descargo escrito, '
        . 'y no debe existir un soporte previo para este proceso.'];

    if ($this->request->isAJAX()) {
        return $this->response->setJSON(['ok' => false, 'errors' => $errors]);
    }

    return redirect()->back()->with('errors', $errors)->withInput();
}

        // Evitar soporte duplicado para el mismo FURD
        $soporteModel = new FurdSoporteModel();
        $existing     = $soporteModel->findByFurd((int)$furd['id']);
        if ($existing) {
            $errors = ['Ya existe un soporte registrado para este proceso. Si necesitas modificarlo, hazlo desde la edición.'];

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['ok' => false, 'errors' => $errors]);
            }

            return redirect()->back()->with('errors', $errors)->withInput();
        }

        // ---------- 5) Guardar ----------
        $db = db_connect();
        $db->transStart();

        try {
            $payload = [
                'furd_id'            => (int)$furd['id'],
                'responsable'        => (string)$postData['responsable'],
                'decision_propuesta' => (string)$postData['decision_propuesta'],
                'justificacion'      => (string)$postData['justificacion'], 

            ];
            $id = (int)$soporteModel->insert($payload, true);

            // Adjuntos fase soporte
            if (!empty($files)) {
                $this->saveAdjuntos((int)$furd['id'], 'soporte', $files);
            }

            // Actualizar estado del FURD
            (new FurdModel())->update((int)$furd['id'], ['estado' => 'soporte']);

            $db->transComplete();

            $mensajeOk = 'Soporte registrado. Continúa con Decisión.';

            if ($this->request->isAJAX()) {
                session()->setFlashdata('ok', $mensajeOk);
                session()->setFlashdata('consecutivo', $consecNorm);

                return $this->response->setJSON([
                    'ok'         => true,
                    'redirectTo' => site_url('seguimiento'),
                ]);
            }

            return redirect()
                ->to(site_url('seguimiento'))
                ->with('ok', $mensajeOk)
                ->with('consecutivo', $consecNorm);
        } catch (\Throwable $e) {
            $db->transRollback();

            $errors = [$e->getMessage()];

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['ok' => false, 'errors' => $errors]);
            }

            return redirect()->back()->with('errors', $errors)->withInput();
        }
    }

    public function update(int $id)
    {
        $rules    = FurdSoporteRequest::rules();
        $messages = FurdSoporteRequest::messages();

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        $s   = new FurdSoporteModel();
        $row = $s->find($id);
        if (!$row) {
            return redirect()->back()
                ->with('errors', ['Registro no existe']);
        }

        $payload = [
            'responsable'        => (string)$this->request->getPost('responsable'),
            'decision_propuesta' => (string)$this->request->getPost('decision_propuesta'),
            'justificacion'      => (string)$this->request->getPost('justificacion')
        ];
        $s->update($id, $payload);

        $files = $this->request->getFiles()['adjuntos'] ?? [];
        if (!empty($files)) {
            $this->saveAdjuntos((int)$row['furd_id'], 'soporte', is_array($files) ? $files : [$files]);
        }

        return redirect()->back()->with('ok', 'Soporte actualizado');
    }

    /**
     * Normaliza un consecutivo a formato PD-000123.
     * Devuelve null si es inválido.
     */
    private function normalizeConsecutivo(?string $value): ?string
    {
        $v = strtoupper(trim((string)$value));
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
