<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// ================ tbl_rit_faltas (catálogo de faltas según RIT) ================.
class CreateTblRitFaltas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'codigo'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'descripcion'     => ['type' => 'TEXT'],
            'gravedad'        => ['type' => 'ENUM', 'constraint' => ['leve', 'grave', 'gravísima'], 'default' => 'leve'],
            'activo'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'audit_created_by' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'audit_updated_by' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('codigo');
        $this->forge->createTable('tbl_rit_faltas', true);
    }

    public function down()
    {
        $this->forge->dropTable('tbl_rit_faltas', true);
    }
}
