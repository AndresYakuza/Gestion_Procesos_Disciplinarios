<?php namespace App\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\HandlesAdjuntos;
use App\Models\FurdModel;
use App\Models\FurdSoporteModel;
use App\Models\FurdAdjuntoModel;
use App\Requests\FurdSoporteRequest;
use App\Services\FurdWorkflow;

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
        $consec = (string)$this->request->getGet('consecutivo');
        $furd = (new FurdModel())->findByConsecutivo($consec);
        if (!$furd) return $this->response->setJSON(['ok'=>false]);
        $prev = [
            'registro' => (new FurdAdjuntoModel())->listByFase((int)$furd['id'], 'registro'),
            'citacion' => (new FurdAdjuntoModel())->listByFase((int)$furd['id'], 'citacion'),
            'descargos'=> (new FurdAdjuntoModel())->listByFase((int)$furd['id'], 'descargos'),
        ];
        return $this->response->setJSON(['ok'=>true,'furd'=>$furd,'prevAdj'=>$prev]);
    }

    public function store()
    {
        $rules    = FurdSoporteRequest::rules();
        $messages = FurdSoporteRequest::messages();

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        // 1) Buscar FURD por consecutivo
        $consec = (string)$this->request->getPost('consecutivo');
        $furd   = (new FurdModel())->findByConsecutivo($consec);
        if (!$furd) {
            return redirect()->back()
                ->with('errors', ['FURD no encontrado'])
                ->withInput();
        }

        // 2) Validar flujo (que ya existan descargos, etc.)
        $wf = new FurdWorkflow();
        if (!$wf->canStartSoporte($furd)) {
            return redirect()->back()
                ->with('errors', ['Primero registra los descargos.'])
                ->withInput();
        }

        // 3) Evitar soporte duplicado para el mismo FURD
        $soporteModel = new FurdSoporteModel();
        $existing     = $soporteModel->findByFurd((int)$furd['id']);
        if ($existing) {
            return redirect()->back()
                ->with('errors', ['Ya existe un soporte registrado para este proceso. Si necesitas modificarlo, hazlo desde la edición.'])
                ->withInput();
        }

        // 4) Guardar
        $db = db_connect();
        $db->transStart();

        try {
            $payload = [
                'furd_id'            => (int)$furd['id'],
                'responsable'        => (string)$this->request->getPost('responsable'),
                'decision_propuesta' => (string)$this->request->getPost('decision_propuesta'),
            ];
            $id = (int)$soporteModel->insert($payload, true);

            // Adjuntos fase soporte
            $files = $this->request->getFiles()['adjuntos'] ?? [];
            if (!empty($files)) {
                $this->saveAdjuntos((int)$furd['id'], 'soporte', is_array($files) ? $files : [$files]);
            }

            // Actualizar estado del FURD
            (new FurdModel())->update((int)$furd['id'], ['estado' => 'soporte']);

            $db->transComplete();

            return redirect()
                ->to(site_url('decision'))
                ->with('ok', 'Soporte registrado. Continúa con Decisión.');
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()
                ->with('errors', [$e->getMessage()])
                ->withInput();
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
        ];
        $s->update($id, $payload);

        $files = $this->request->getFiles()['adjuntos'] ?? [];
        if (!empty($files)) {
            $this->saveAdjuntos((int)$row['furd_id'], 'soporte', is_array($files) ? $files : [$files]);
        }

        return redirect()->back()->with('ok', 'Soporte actualizado');
    }

}
