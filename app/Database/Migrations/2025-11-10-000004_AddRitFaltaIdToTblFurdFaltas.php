<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRitFaltaIdToTblFurdFaltas extends Migration
{
    public function up()
    {
        $db = db_connect();
        $fields = $db->getFieldNames('tbl_furd_faltas');

        // Solo agregar si no existe
        if (!in_array('rit_falta_id', $fields)) {
            $this->forge->addColumn('tbl_furd_faltas', [
                'rit_falta_id' => [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'furd_id',
                ],
            ]);
        }
    }

    public function down()
    {
        $db = db_connect();
        $fields = $db->getFieldNames('tbl_furd_faltas');

        if (in_array('rit_falta_id', $fields)) {
            $this->forge->dropColumn('tbl_furd_faltas', 'rit_falta_id');
        }
    }
}
