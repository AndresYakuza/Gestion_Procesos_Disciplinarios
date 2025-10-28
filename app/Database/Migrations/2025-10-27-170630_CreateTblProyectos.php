<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;


// ================ tbl_proyectos (catálogo de proyectos) ================.
class CreateTblProyectos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nombre'                => ['type' => 'VARCHAR', 'constraint' => 150],
            'codigo'                => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'activo'                => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'audit_created_by'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'audit_updated_by'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('codigo');
        $this->forge->createTable('tbl_proyectos', true);
    }

    public function down()
    {
        $this->forge->dropTable('tbl_proyectos', true);
    }
}
