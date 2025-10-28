<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Http\Requests\Contratos\ListContratosRequest;
use App\Models\EmpleadoContratoModel;
use App\UseCases\Contratos\ListContratos;
use App\UseCases\Contratos\ShowContrato;
use CodeIgniter\HTTP\ResponseInterface;

class ContratosController extends BaseController
{
    public function index(): ResponseInterface
    {
        $req = ListContratosRequest::from($this->request);

        $use  = new ListContratos(new EmpleadoContratoModel());
        $out  = $use->handle($req);

        return $this->response->setJSON($out);
    }

    public function show(int $id): ResponseInterface
    {
        $use = new ShowContrato();
        $row = $use->handle($id);

        if (!$row) {
            return $this->response->setStatusCode(404)->setJSON([
                'error' => 'No encontrado'
            ]);
        }

        return $this->response->setJSON(['data' => $row]);
    }
}
