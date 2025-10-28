<?php
namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ProyectoAliasModel;
use App\Requests\Projects\StoreAliasRequest;
use App\Requests\Projects\UpdateAliasRequest;
use App\UseCases\Projects\CreateAlias;
use App\UseCases\Projects\UpdateAlias as UpdateUC;
use App\UseCases\Projects\DeleteAlias;

/**
 * @property \CodeIgniter\HTTP\IncomingRequest $request
 */

class ProyectoAliasController extends ResourceController
{
    protected $modelName = ProyectoAliasModel::class;
    protected $format    = 'json';

    public function index()
    {
        $proyectoId = $this->request->getGet('proyecto_id');
        $q = $proyectoId ? $this->model->where('proyecto_id',$proyectoId) : $this->model;
        return $this->respond($q->orderBy('alias','ASC')->findAll(500));
    }

    public function create()
    {
        try {
            $data = StoreAliasRequest::validated($this->request, service('validation'));
            $uc   = new CreateAlias($this->model);
            return $this->respondCreated($uc($data));
        } catch (\Throwable $e) {
            return $this->failValidationErrors($e->getMessage());
        }
    }

    public function update($id = null)
    {
        try {
            $data = UpdateAliasRequest::validated($this->request, service('validation'));
            $uc   = new UpdateUC($this->model);
            return $this->respond($uc((int)$id, $data));
        } catch (\Throwable $e) {
            return $this->failValidationErrors($e->getMessage());
        }
    }

    public function delete($id = null)
    {
        $uc = new DeleteAlias($this->model);
        $ok = $uc((int)$id);
        return $ok ? $this->respondDeleted(['id'=>(int)$id]) : $this->failServerError('No se pudo borrar');
    }
}
