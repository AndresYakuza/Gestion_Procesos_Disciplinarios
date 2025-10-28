<?php
namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\FurdModel;
use App\Models\FurdFaltaModel;
use App\Models\AdjuntoModel;
use App\Requests\Furd\StoreFurdRequest;
use App\Requests\Furd\UpdateFurdRequest;
use App\UseCases\Furd\CreateFurd;
use App\UseCases\Furd\UpdateFurd as UpdateUC;
use App\UseCases\Furd\DeleteFurd as DeleteUC;
use App\UseCases\Furd\AttachFalta;
use App\UseCases\Furd\DetachFalta;
use App\UseCases\Furd\UploadAdjunto;
use App\UseCases\Furd\DeleteAdjunto;

/**
 * @property \CodeIgniter\HTTP\IncomingRequest $request
 */

class FurdController extends ResourceController
{
    protected $modelName = FurdModel::class;
    protected $format    = 'json';

    public function index()
    {
        $colabId = $this->request->getGet('colaborador_id');
        $q = $colabId ? $this->model->where('colaborador_id',$colabId) : $this->model;
        return $this->respond($q->orderBy('fecha_evento','DESC')->paginate(50));
    }

    public function show($id = null)
    {
        $row = $this->model->find($id);
        if (!$row) return $this->failNotFound();
        // faltas y adjuntos
        $ff = (new FurdFaltaModel())->where('furd_id',$id)->findAll();
        $ad = (new AdjuntoModel())->where(['origen'=>'furd','origen_id'=>$id])->findAll();
        $row['faltas'] = $ff;
        $row['adjuntos'] = $ad;
        return $this->respond($row);
    }

    public function create()
    {
        try {
            $data = StoreFurdRequest::validated($this->request, service('validation'));
            $uc   = new CreateFurd($this->model);
            return $this->respondCreated($uc($data));
        } catch (\Throwable $e) {
            return $this->failValidationErrors($e->getMessage());
        }
    }

    public function update($id = null)
    {
        try {
            $data = UpdateFurdRequest::validated($this->request, service('validation'));
            $uc   = new UpdateUC($this->model);
            return $this->respond($uc((int)$id,$data));
        } catch (\Throwable $e) {
            return $this->failValidationErrors($e->getMessage());
        }
    }

    public function delete($id = null)
    {
        $uc = new DeleteUC($this->model, new FurdFaltaModel(), new AdjuntoModel());
        $ok = $uc((int)$id);
        return $ok ? $this->respondDeleted(['id'=>(int)$id]) : $this->failServerError('No se pudo borrar');
    }

    public function attachFalta($id)
    {
        $faltaId = (int)($this->request->getJSON(true)['falta_id'] ?? 0);
        if (!$faltaId) return $this->failValidationErrors('falta_id requerido');
        $uc = new AttachFalta(new FurdFaltaModel());
        $uc((int)$id, $faltaId);
        return $this->respond(['ok'=>true]);
    }

    public function detachFalta($id,$faltaId)
    {
        $uc = new DetachFalta(new FurdFaltaModel());
        $uc((int)$id, (int)$faltaId);
        return $this->respond(['ok'=>true]);
    }

    public function uploadAdjunto($id)
    {
        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) return $this->failValidationErrors('Archivo inválido');
        $uc = new UploadAdjunto(new AdjuntoModel());
        $row = $uc((int)$id, $file, 'api');
        return $this->respondCreated($row);
    }

    public function deleteAdjunto($idAdj)
    {
        $uc = new DeleteAdjunto(new AdjuntoModel());
        $uc((int)$idAdj);
        return $this->respondDeleted(['id'=>(int)$idAdj]);
    }
}
