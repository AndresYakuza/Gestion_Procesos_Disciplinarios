<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTimestampsToTblFurdFaltas extends Migration
{
    public function up()
    {
        $db = db_connect();
        $fields = $db->getFieldNames('tbl_furd_faltas');

        // Solo agrega si no existen
        $newFields = [];

        if (!in_array('created_at', $fields)) {
            $newFields['created_at'] = ['type' => 'DATETIME', 'null' => true];
        }

        if (!in_array('updated_at', $fields)) {
            $newFields['updated_at'] = ['type' => 'DATETIME', 'null' => true];
        }

        if (!empty($newFields)) {
            $this->forge->addColumn('tbl_furd_faltas', $newFields);
        }
    }

    public function down()
    {
        $db = db_connect();
        $fields = $db->getFieldNames('tbl_furd_faltas');

        if (in_array('created_at', $fields)) {
            $this->forge->dropColumn('tbl_furd_faltas', 'created_at');
        }
        if (in_array('updated_at', $fields)) {
            $this->forge->dropColumn('tbl_furd_faltas', 'updated_at');
        }
    }
}
