<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// ================ tbl_adjuntos (evidencias/soportes del FURD) ================
class CreateTblAdjuntos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'origen'           => ['type' => 'ENUM', 'constraint' => ['furd'], 'default' => 'furd'],
            'origen_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'nombre_original'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'ruta'             => ['type' => 'VARCHAR', 'constraint' => 255], // path en storage
            'mime'             => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'tamano_bytes'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'audit_created_by' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['origen', 'origen_id']);
        $this->forge->createTable('tbl_adjuntos', true);

        // FK polimórfica: para FURD
        $this->db->query('ALTER TABLE tbl_adjuntos
            ADD CONSTRAINT fk_adj_furd FOREIGN KEY (origen_id) REFERENCES tbl_furd(id)
              ON UPDATE CASCADE ON DELETE CASCADE');
    }

    public function down()
    {
        $this->forge->dropTable('tbl_adjuntos', true);
    }
}
