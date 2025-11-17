<?php namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\RitFaltaModel;

class RitFaltaController extends BaseController
{
    public function index()
    {
        $m = new RitFaltaModel();
        $q = (string)$this->request->getGet('q');

        $builder = $m->builder();
        if ($q !== '') {
            $builder->groupStart()
                ->like('codigo', $q)
                ->orLike('descripcion', $q)
                ->groupEnd();
        }

        $rows = $builder->orderBy('codigo', 'ASC')->get(500)->getResultArray();

        // 🔹 Calcular el próximo código (para mostrarlo en el modal)
        $last = $m->orderBy('id', 'DESC')->first();
        $n = $last ? ((int)$last['id'] + 1) : 1;
        $next = 'RIT-' . str_pad((string)$n, 3, '0', STR_PAD_LEFT);

        // 🔹 Enviar las variables que la vista realmente usa
        return view('ajustes/faltas/index', [
            'faltas' => $rows,  // no 'rows'
            'q'      => $q,
            'next'   => $next
        ]);
    }


    public function create()
    {
        return view('ajustes/faltas/index');
    }

    public function store()
    {
        $m = new RitFaltaModel();

        // Autogenerar código si no llega
        $codigo = (string)$this->request->getPost('codigo');
        if ($codigo === '') {
            $last = $m->orderBy('id','DESC')->first();
            $n = $last ? ((int)$last['id'] + 1) : 1;
            $codigo = 'RIT-' . str_pad((string)$n, 3, '0', STR_PAD_LEFT);
        }

        $data = [
            'codigo'      => $codigo,
            'descripcion' => (string)$this->request->getPost('descripcion'),
            'gravedad'    => (string)$this->request->getPost('gravedad'),
            'activa'      => (int)$this->request->getPost('activa', FILTER_VALIDATE_INT) ? 1 : 0
        ];

        if (!$m->save($data)) {
            return redirect()->back()->with('errors', $m->errors())->withInput();
        }

        return redirect()->to(site_url('ajustes/faltas'))->with('ok','Falta creada');
    }

    public function edit(int $id)
    {
        $m = new RitFaltaModel();
        $row = $m->find($id);
        if (!$row) return redirect()->back()->with('errors',['No existe']);
        return view('ajustes/faltas/edit', ['falta'=>$row]);
    }

    public function update(int $id)
    {
        $m = new RitFaltaModel();
        $data = [
            'id'          => $id,
            'codigo'      => (string)$this->request->getPost('codigo'),
            'descripcion' => (string)$this->request->getPost('descripcion'),
            'gravedad'    => (string)$this->request->getPost('gravedad'),
            'activa'      => (int)$this->request->getPost('activa', FILTER_VALIDATE_INT) ? 1 : 0
        ];

        if (!$m->save($data)) {
            return redirect()->back()->with('errors', $m->errors())->withInput();
        }

        return redirect()->to(site_url('ajustes/faltas'))->with('ok','Falta actualizada');
    }

    public function delete(int $id)
    {
        $m = new RitFaltaModel();
        $m->delete($id, true);
        return redirect()->back()->with('ok','Falta eliminada');
    }
}
