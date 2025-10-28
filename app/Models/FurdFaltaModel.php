<?php
namespace App\Models;

use CodeIgniter\Model;

class FurdFaltaModel extends Model
{
    protected $table      = 'tbl_furd_faltas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    public $useTimestamps = false;
    protected $allowedFields = ['furd_id','falta_id'];
}
