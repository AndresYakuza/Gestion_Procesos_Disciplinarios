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

    // ✅ Vista HTML
    public function form()
    {
        return view('furd/create');
    }

    // ✅ Listado (JSON)
    public function index()
    {
        // Traer catálogo de faltas activas (ordenadas por gravedad y nombre)
        $faltas = model(\App\Models\RitFaltaModel::class)
            ->where('activo', 1)
            ->orderBy('gravedad', 'DESC')
            ->orderBy('codigo', 'ASC')
            ->findAll();

        return view('furd/index', [
            'faltas' => $faltas,
            'title'  => 'Registrar Proceso Disciplinario'
        ]);
    }

    // ✅ Mostrar uno (JSON)
    public function show($id = null)
    {
        $row = $this->model->find($id);
        if (!$row) return $this->failNotFound();

        $row['faltas'] = (new FurdFaltaModel())->where('furd_id', $id)->findAll();
        $row['adjuntos'] = (new AdjuntoModel())->where(['origen' => 'furd', 'origen_id' => $id])->findAll();
        return $this->respond($row);
    }

    // ✅ Crear (desde formulario o API)
    public function create()
    {
        try {
            // Detectar si la solicitud viene desde un formulario o JSON
            $input = $this->request->getPost() ?: $this->request->getJSON(true);

            // Validación básica (por si no usas StoreFurdRequest en formularios)
            if (empty($input['colaborador_id']) || empty($input['fecha_evento']) || empty($input['hecho'])) {
                return $this->failValidationErrors('Faltan datos requeridos.');
            }

            // Si usas tu request custom:
            // $data = StoreFurdRequest::validated($this->request, service('validation'));
            $uc = new CreateFurd($this->model);
            $result = $uc($input);

            // Si viene de un formulario HTML -> redirigir
            if ($this->request->is('web')) {
                return redirect()->to('/furd')->with('success', 'Registro creado correctamente');
            }

            // Si viene de una API -> responder JSON
            return $this->respondCreated($result);

        } catch (\Throwable $e) {
            return $this->failValidationErrors($e->getMessage());
        }
    }

    // ✅ Actualizar (solo API)
    public function update($id = null)
    {
        try {
            $data = UpdateFurdRequest::validated($this->request, service('validation'));
            $uc = new UpdateUC($this->model);
            return $this->respond($uc((int)$id, $data));
        } catch (\Throwable $e) {
            return $this->failValidationErrors($e->getMessage());
        }
    }

    // ✅ Eliminar (solo API)
    public function delete($id = null)
    {
        $uc = new DeleteUC($this->model, new FurdFaltaModel(), new AdjuntoModel());
        $ok = $uc((int)$id);
        return $ok
            ? $this->respondDeleted(['id' => (int)$id])
            : $this->failServerError('No se pudo borrar');
    }

    // ✅ Métodos extra (API)
    public function attachFalta($id)
    {
        $faltaId = (int)($this->request->getJSON(true)['falta_id'] ?? 0);
        if (!$faltaId) return $this->failValidationErrors('falta_id requerido');
        $uc = new AttachFalta(new FurdFaltaModel());
        $uc((int)$id, $faltaId);
        return $this->respond(['ok' => true]);
    }

    public function detachFalta($id, $faltaId)
    {
        $uc = new DetachFalta(new FurdFaltaModel());
        $uc((int)$id, (int)$faltaId);
        return $this->respond(['ok' => true]);
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
        return $this->respondDeleted(['id' => (int)$idAdj]);
    }
}
