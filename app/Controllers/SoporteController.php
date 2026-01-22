<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use App\Controllers\Traits\HandlesAdjuntos;
use App\Models\FurdModel;
use App\Models\FurdSoporteModel;
use App\Models\FurdAdjuntoModel;
use App\Requests\FurdSoporteRequest;
use App\Domain\Furd\FurdWorkflow;
use App\Models\FurdCitacionModel;
use App\Models\FurdDescargoModel;
use App\Models\FurdDecisionModel;
use App\Services\FurdMailService;

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
            'descargos' => $adjModel->listByFase((int)$furd['id'], 'descargos'),
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

        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'heic', 'doc', 'docx', 'xls', 'xlsx'];
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

            // datos completos de soporte para el correo
            $soporteData = $payload;
            $soporteData['id'] = $id;

            // Adjuntos fase soporte
            if (!empty($files)) {
                $this->saveAdjuntos((int)$furd['id'], 'soporte', $files);
            }

            // Actualizar estado del FURD
            (new FurdModel())->update((int)$furd['id'], ['estado' => 'soporte']);

            $db->transComplete();

            // ---------- 6) Notificar al cliente por correo ----------
            try {
                $mailer = new FurdMailService();
                $sent   = $mailer->notifySoportePropuesta($furd, $soporteData);

                if (!$sent) {
                    log_message('warning', 'No se pudo enviar correo de soporte para FURD {id}.', [
                        'id' => $furd['id'] ?? null,
                    ]);
                }
            } catch (\Throwable $mailEx) {
                // No tumbamos el proceso si falla el correo, solo registramos
                log_message('error', 'Excepción enviando correo de soporte FURD {id}: {msg}', [
                    'id'  => $furd['id'] ?? null,
                    'msg' => $mailEx->getMessage(),
                ]);
            }

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

    /**
     * Vista pública para que el cliente revise y responda la decisión propuesta.
     * URL esperada: /soporte/reviewCliente/PD-000123
     */
    public function reviewCliente(string $consecutivo)
    {

        // 1) Normalizar consecutivo (PD-000123)
        $consecNorm = $this->normalizeConsecutivo($consecutivo);
        if ($consecNorm === null) {
            throw PageNotFoundException::forPageNotFound('Consecutivo inválido');
        }

        $furdModel    = new FurdModel();
        $soporteModel = new FurdSoporteModel();

        // 2) Buscar FURD + soporte
        $furd = $furdModel->findByConsecutivo($consecNorm);
        if (!$furd) {
            throw PageNotFoundException::forPageNotFound('Proceso disciplinario no encontrado');
        }

        $soporte = $soporteModel->findByFurd((int) $furd['id']);
        if (!$soporte) {
            throw PageNotFoundException::forPageNotFound('No existe soporte registrado para este proceso');
        }

        // 2.1) Adjuntos de la fase SOPORTE
        $adjModel       = new FurdAdjuntoModel();
        $rawAdjuntos    = $adjModel->listByFase((int) $furd['id'], 'soporte');
        $adjuntosSoporte = array_map(static function (array $a) {
            $id   = (int) $a['id'];
            $name = $a['nombre_original'] ?? basename((string)($a['ruta'] ?? ''));

            return [
                'id'           => $id,
                'nombre'       => $name,
                'url_open'     => site_url('adjuntos/' . $id . '/open'),
                'url_download' => site_url('adjuntos/' . $id . '/download'),
            ];
        }, $rawAdjuntos);

        // 3) Si es GET ⇒ solo mostramos formulario
        if ($this->request->getMethod() === 'get') {
            return view('soporte/review_cliente', [
                'furd'            => $furd,
                'soporte'         => $soporte,
                'errors'          => [],
                'old'             => [],
                'adjuntosSoporte' => $adjuntosSoporte,
            ]);
        }

        // 4) Si es POST ⇒ procesar respuesta del cliente
        $post = [
            'cliente_estado'        => (string) $this->request->getPost('cliente_estado'),
            'cliente_decision'      => (string) $this->request->getPost('cliente_decision'),
            'cliente_justificacion' => (string) $this->request->getPost('cliente_justificacion'),
            'cliente_comentario'    => (string) $this->request->getPost('cliente_comentario'),
            'cliente_fecha_inicio_suspension' => (string) $this->request->getPost('cliente_fecha_inicio_suspension'),
        ];

        $post = array_map('trim', $post);

        $errors = [];

        if (!in_array($post['cliente_estado'], ['aprobado', 'rechazado'], true)) {
            $errors['cliente_estado'] = 'Debes indicar si apruebas o rechazas la decisión propuesta.';
        }

        // ¿La decisión propuesta es suspensión disciplinaria?
        $isSuspension = strcasecmp($soporte['decision_propuesta'] ?? '', 'Suspensión disciplinaria') === 0;

        // Si rechaza, exigir algo de texto
        if ($post['cliente_estado'] === 'rechazado') {
            if ($post['cliente_decision'] === '' && $post['cliente_justificacion'] === '') {
                $errors['cliente_decision'] = 'Si rechazas la decisión, describe qué cambio propones o una justificación.';
            }
        }

        // Si ES suspensión y APRUEBA, la fecha es obligatoria
        if ($isSuspension && $post['cliente_estado'] === 'aprobado' && $post['cliente_fecha_inicio_suspension'] === '') {
            $errors['cliente_fecha_inicio_suspension'] = 'Debes indicar la fecha de inicio de la suspensión disciplinaria.';
        }

        if (!empty($errors)) {
            $data = [
                'furd'            => $furd,
                'soporte'         => $soporte,
                'errors'          => $errors,
                'old'             => $post,
                'adjuntosSoporte' => $adjuntosSoporte,
            ];
            return view('soporte/review_cliente', $data);
        }

        // 5) Actualizar soporte con la respuesta del cliente
        $update = [
            'cliente_estado'                 => $post['cliente_estado'],
            'cliente_decision'               => $post['cliente_decision']      ?: null,
            'cliente_justificacion'         => $post['cliente_justificacion'] ?: null,
            'cliente_comentario'            => $post['cliente_comentario']    ?: null,
            'cliente_respondido_at'         => date('Y-m-d H:i:s'),
            'cliente_fecha_inicio_suspension' => $post['cliente_fecha_inicio_suspension'] ?: null,
        ];

        $soporteModel->update((int) $soporte['id'], $update);
        $soporte = array_merge($soporte, $update); // para el correo


        // 6) Notificar al emisor (por ahora: correo "fromEmail" del sistema)
        $emailConfig = config('Email');
        $to          = $emailConfig->fromEmail; // por ahora enviamos al emisor configurado

        if ($to) {
            $email = service('email');

            // Asunto según estado
            $estado     = $soporte['cliente_estado'] ?? 'pendiente';
            $estadoText = $estado === 'aprobado'
                ? 'APROBÓ la decisión'
                : ($estado === 'rechazado' ? 'SOLICITÓ AJUSTES' : 'respondió');

            $subject = sprintf(
                'Respuesta del cliente (%s) – %s',
                $estadoText,
                $furd['consecutivo'] ?? $consecNorm
            );

            // Datos del cliente
            $clienteNombre = $furd['empresa_usuaria'] ?? null;

            $clienteEmail = $furd['correo_cliente']
                ?? $furd['email_cliente']
                ?? $furd['correo_contacto']
                ?? null;

            // cuerpo HTML
            $body = view(
                'emails/furd/soporte_respuesta_cliente',
                [
                    'furd'          => $furd,
                    'soporte'       => $soporte,
                    'clienteNombre' => $clienteNombre,
                    'clienteEmail'  => $clienteEmail,
                ],
                ['debug' => false]
            );

            $email->setTo($to);
            $email->setSubject($subject);

            // 👇 ESTA LÍNEA ES LA CLAVE
            $email->setMailType('html');

            $email->setMessage($body);
            $email->send();
        }





        // 7) Vista de agradecimiento
        return view(
            'soporte/review_cliente_ok',
            [
                'furd'           => $furd,
                'soporte'        => $soporte,
                'cliente_estado' => $post['cliente_estado'],
            ],
            ['debug' => false]
        );
    }
}
