<?php namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProyectoAliasModel;
use App\Models\ProyectoModel;

class ProyectoAliasController extends BaseController
{
    public function index()
    {
        $q = (string)$this->request->getGet('q');
        $m = new ProyectoAliasModel();
        $builder = $m->builder()->select('proyecto_aliases.*, proyectos.nombre AS proyecto')
                   ->join('proyectos','proyectos.id=proyecto_aliases.proyecto_id','left');

        if ($q !== '') {
            $builder->groupStart()
               ->like('alias', $q)
               ->orLike('proyectos.nombre', $q)
               ->groupEnd();
        }

        $rows = $builder->orderBy('proyectos.nombre','ASC')->orderBy('alias','ASC')->get(500)->getResultArray();
        $proyectos = (new ProyectoModel())->orderBy('nombre','ASC')->findAll();

        return view('proyecto_alias/index', ['rows'=>$rows, 'proyectos'=>$proyectos, 'q'=>$q]);
    }

    public function store()
    {
        $projId = (int)$this->request->getPost('proyecto_id');
        $alias  = (string)$this->request->getPost('alias');

        if ($projId <= 0 || $alias === '') {
            return redirect()->back()->with('errors',['Proyecto y alias son obligatorios'])->withInput();
        }

        $m = new ProyectoAliasModel();
        $data = [
            'proyecto_id' => $projId,
            'alias'       => $alias,
            'alias_norm'  => ProyectoAliasModel::norm($alias)
        ];

        $m->insert($data);
        return redirect()->back()->with('ok','Alias creado');
    }

    public function update(int $id)
    {
        $projId = (int)$this->request->getPost('proyecto_id');
        $alias  = (string)$this->request->getPost('alias');

        if ($projId <= 0 || $alias === '') {
            return redirect()->back()->with('errors',['Proyecto y alias son obligatorios'])->withInput();
        }

        $m = new ProyectoAliasModel();
        $m->update($id, [
            'proyecto_id' => $projId,
            'alias'       => $alias,
            'alias_norm'  => ProyectoAliasModel::norm($alias)
        ]);

        return redirect()->back()->with('ok','Alias actualizado');
    }

    public function delete(int $id)
    {
        (new ProyectoAliasModel())->delete($id, true);
        return redirect()->back()->with('ok','Alias eliminado');
    }
}
