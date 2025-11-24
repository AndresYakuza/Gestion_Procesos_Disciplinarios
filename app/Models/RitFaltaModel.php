<?php namespace App\Models;

use CodeIgniter\Model;

class RitFaltaModel extends Model
{
    protected $table      = 'tbl_rit_faltas';
    protected $primaryKey = 'id';

    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    // Solo campos reales de la tabla que vamos a escribir
    protected $allowedFields = [
        'codigo',
        'descripcion',
        'gravedad',
        'activo',
    ];

    protected $validationRules = [
        // id solo por si algún día lo usas en formularios; no afecta al insert
        'id'          => 'permit_empty|is_natural_no_zero',
        'codigo'      => 'required|min_length[3]|max_length[50]',
        'descripcion' => 'required|min_length[5]|max_length[500]|max_word_length[120]',
        'gravedad'    => 'required|in_list[Leve,Grave,Gravísima,Gravisima,GRAVE,LEVE,GRAVISIMA]',
        // activo no es obligatorio porque en BD tiene default 1
        'activo'      => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'codigo' => [
            'required'   => 'El código es obligatorio.',
            'min_length' => 'El código debe tener al menos 3 caracteres.',
            'max_length' => 'El código no puede superar los 50 caracteres.',
        ],
        'descripcion' => [
            'required'        => 'La descripción de la falta es obligatoria.',
            'min_length'      => 'La descripción es muy corta.',
            'max_length'      => 'La descripción es demasiado larga (máximo 500 caracteres).',
            'max_word_length' => 'La descripción contiene una palabra demasiado larga sin espacios. Divide el texto en frases o agrega espacios.',
        ],
        'gravedad' => [
            'required' => 'La gravedad es obligatoria.',
            'in_list'  => 'La gravedad seleccionada no es válida.',
        ],
    ];
}
