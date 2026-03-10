<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\HandlesAdjuntos;
use App\Domain\Furd\FurdWorkflow;
use App\Models\FurdCitacionModel;
use App\Models\FurdDecisionModel;
use App\Models\FurdDescargoModel;
use App\Models\FurdModel;
use App\Models\FurdSoporteModel;
use App\Requests\FurdDescargosRequest;
use App\Services\DescargosActaDocxService;

class DescargosController extends BaseController
{
    use HandlesAdjuntos;

    public function create()
    {
        return view('descargos/create');
    }

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

        $postData                 = $this->request->getPost();
        $postData['fecha_evento'] = $fechaConvertida;

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

        $wf = new FurdWorkflow(
            new FurdModel(),
            new FurdCitacionModel(),
            new FurdDescargoModel(),
            new FurdSoporteModel(),
            new FurdDecisionModel(),
        );

        if (!$wf->canStartDescargos($furd)) {
            $msg = 'No puedes generar el acta de cargos y descargos para este proceso. '
                . 'Verifica que exista citación previa, que no haya descargos registrados '
                . 'y que la citación no haya sido marcada con descargo escrito.';

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

        $db = db_connect();
        $db->transStart();

        try {
            $descModel = new FurdDescargoModel();

            $payload = [
                'furd_id'      => (int)$furd['id'],
                'fecha_evento' => $fechaConvertida,
                'hora'         => (string)($postData['hora'] ?? ''),
                'medio'        => (string)($postData['medio'] ?? ''),
                'observacion'  => (string)($postData['observacion'] ?? ''),
            ];

            $id = (int)$descModel->insert($payload, true);
            if ($id <= 0) {
                throw new \RuntimeException('No se pudo registrar la fase de descargos.');
            }

            $descargo = $descModel->find($id);
            if (!$descargo) {
                throw new \RuntimeException('No se pudo recuperar el registro de descargos recién creado.');
            }

            $docx = (new DescargosActaDocxService())->generateAndUpload($furd, $descargo);
            if (!$docx) {
                throw new \RuntimeException('Se registraron los descargos, pero no se pudo generar el acta en Drive.');
            }

            $files = $this->request->getFiles()['adjuntos'] ?? [];
            if (!empty($files)) {
                $this->saveAdjuntos((int)$furd['id'], 'descargos', is_array($files) ? $files : [$files]);
            }

            $db->transComplete();

            $mensajeOk = 'Descargos registrados y acta generada en Drive. Continúa el proceso desde Seguimiento.';

            if ($this->request->isAJAX()) {
                session()->setFlashdata('ok', $mensajeOk);
                session()->setFlashdata('consecutivo', $consec);

                return $this->response->setJSON([
                    'ok'         => true,
                    'redirectTo' => site_url('seguimiento'),
                    'openUrl'    => $docx['view_link'] ?? null,
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
        if (!$row) {
            return redirect()->back()->with('errors', ['Registro no existe']);
        }

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