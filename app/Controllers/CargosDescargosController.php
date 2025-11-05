<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class CargosDescargosController extends BaseController
{
    public function __construct()
    {
        helper(['form', 'url']);
    }

    /**
     * GET /cargos-descargos
     * Renderiza la vista del formulario.
     */
    public function create(): string
    {
        return view('cargos_descargos/index');
    }

    /**
     * POST /cargos-descargos
     * Valida el formulario y (por ahora) solo confirma la operación.
     * La persistencia real la implementamos al definir la tabla/modelo.
     */
    public function store()
    {
        $rules = [
            'consecutivo' => 'required|max_length[50]',
            'fecha'       => 'required|valid_date[Y-m-d]',
            'hora'        => ['label' => 'hora', 'rules' => 'required|regex_match[/^\d{2}:\d{2}$/]'],
            'medio'       => 'required|in_list[PRESENCIAL,VIRTUAL]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        // TODO: guardar en BD cuando definamos el modelo de acta
        // $model->insert([...]);

        return redirect()->to(site_url('cargos-descargos'))
            ->with('toast', [
                'type'    => 'success',
                'message' => 'Acta de cargos y descargos registrada correctamente.',
            ]);
    }
}
