<?php namespace App\Models;

use CodeIgniter\Model;

class RitFaltaModel extends Model
{
    protected $table      = 'tbl_rit_faltas';
    protected $primaryKey = 'id';

    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    protected $allowedFields = [
        'codigo', 'descripcion', 'gravedad', 'activa'
    ];

    protected $validationRules = [
        'codigo'      => 'required|min_length[3]|max_length[30]|is_unique[tbl_rit_faltas.codigo,id,{id}]',
        'descripcion' => 'required|min_length[5]',
        'gravedad'    => 'required|in_list[Leve,Grave,Gravísima,Gravisima,GRAVE,LEVE,GRAVISIMA]'
    ];
}
