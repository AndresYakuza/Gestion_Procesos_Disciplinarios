<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RitFaltaModel;

class RitFaltaController extends BaseController
{
    public function index()
    {
        $m = new RitFaltaModel();
        $q = (string) $this->request->getGet('q'); // lo puedes dejar si quieres, ya no se usará
        $perPage = 10; // si quieres ver todas en una sola página

        // 🔸 ya no filtramos aquí por q, solo paginamos
        $faltas = $m->orderBy('codigo', 'DESC')
            ->paginate($perPage, 'faltas');

        $pager = $m->pager;

        // Modelo limpio para calcular el siguiente código
        $m2   = new RitFaltaModel();
        $last = $m2->orderBy('id', 'DESC')->first();
        $n    = $last ? ((int) $last['id'] + 1) : 1;
        $next = 'RIT-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT);

        return view('ajustes/faltas/index', [
            'faltas' => $faltas,
            'q'      => $q,
            'next'   => $next,
            'pager'  => $pager,
        ]);
    }


    public function create()
    {
        // No usamos vista aparte, siempre listamos en index
        return redirect()->to(site_url('ajustes/faltas'));
    }

    public function store()
    {
        $m = new RitFaltaModel();

        // Autogenerar código si no llegó (aunque en la vista viene readonly)
        $codigo = (string) $this->request->getPost('codigo');
        if ($codigo === '') {
            $last = $m->orderBy('id', 'DESC')->first();
            $n    = $last ? ((int) $last['id'] + 1) : 1;
            $codigo = 'RIT-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
        }

        $data = [
            'codigo'      => $codigo,
            'descripcion' => (string) $this->request->getPost('descripcion'),
            'gravedad'    => (string) $this->request->getPost('gravedad'),
            'activo'      => 1, // por defecto activa
        ];

        // 1) Chequeo manual de unicidad de código
        $existe = $m->where('codigo', $data['codigo'])->first();
        if ($existe) {
            return redirect()->back()
                ->with('errors', ['Ya existe una falta con ese código.'])
                ->withInput();
        }

        // 2) Validación del modelo
        if (!$m->save($data)) {
            // Si algo falla, mostramos claramente los errores del modelo
            return redirect()->back()
                ->with('errors', $m->errors())
                ->withInput();
        }

        return redirect()
            ->to(site_url('ajustes/faltas'))
            ->with('ok', 'Falta creada');
    }

    public function edit(int $id)
    {
        $m   = new RitFaltaModel();
        $row = $m->find($id);

        if (!$row) {
            return redirect()->back()
                ->with('errors', ['No existe la falta seleccionada.']);
        }

        return view('ajustes/faltas/edit', ['falta' => $row]);
    }

    public function update(int $id)
    {
        $m = new RitFaltaModel();

        $data = [
            'id'          => $id,
            'codigo'      => (string) $this->request->getPost('codigo'),
            'descripcion' => (string) $this->request->getPost('descripcion'),
            'gravedad'    => (string) $this->request->getPost('gravedad'),
            'activo'      => 1, // si en el futuro pones un switch, aquí se ajusta
        ];

        // 1) Chequeo manual de unicidad de código (excluyendo este id)
        $otro = $m->where('codigo', $data['codigo'])
            ->where('id !=', $id)
            ->first();

        if ($otro) {
            return redirect()->back()
                ->with('errors', ['Ya existe otra falta con ese código.'])
                ->withInput();
        }

        // 2) Validación normal
        if (!$m->save($data)) {
            return redirect()->back()
                ->with('errors', $m->errors())
                ->withInput();
        }

        return redirect()
            ->to(site_url('ajustes/faltas'))
            ->with('ok', 'Falta actualizada');
    }

    public function delete(int $id)
    {
        $m = new RitFaltaModel();
        $m->delete($id, true);

        return redirect()
            ->back()
            ->with('ok', 'Falta eliminada');
    }
}
