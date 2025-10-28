<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// ================ tbl_furd_faltas (N–M entre FURD y faltas). ================
// Un FURD puede estar asociado a varias faltas del RIT.
// Una falta del RIT puede aparecer en muchos FURD.
class CreateTblFurdFaltas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'furd_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'falta_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['furd_id', 'falta_id']);
        $this->forge->createTable('tbl_furd_faltas', true);

        $this->db->query('ALTER TABLE tbl_furd_faltas
            ADD CONSTRAINT fk_ff_furd  FOREIGN KEY (furd_id)  REFERENCES tbl_furd(id)
              ON UPDATE CASCADE ON DELETE CASCADE,
            ADD CONSTRAINT fk_ff_falta FOREIGN KEY (falta_id) REFERENCES tbl_rit_faltas(id)
              ON UPDATE CASCADE ON DELETE RESTRICT');
    }

    public function down()
    {
        $this->forge->dropTable('tbl_furd_faltas', true);
    }
}
