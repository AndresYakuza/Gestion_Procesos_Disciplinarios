<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTblFurdDecision extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','constraint'=>10,'unsigned'=>true,'auto_increment'=>true],
            'furd_id' => ['type'=>'INT','constraint'=>10,'unsigned'=>true],
            'fecha'   => ['type'=>'DATE','null'=>false],
            'decision_text' => ['type'=>'TEXT','null'=>false],
            'audit_created_by' => ['type'=>'VARCHAR','constraint'=>100,'null'=>true],
            'audit_updated_by' => ['type'=>'VARCHAR','constraint'=>100,'null'=>true],
            'created_at' => ['type'=>'DATETIME','null'=>true],
            'updated_at' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('furd_id');
        $this->forge->addForeignKey('furd_id','tbl_furd','id','CASCADE','CASCADE');
        $this->forge->createTable('tbl_furd_decision', true);

        // Trigger: actualizar estado de FURD al crear decisión
        $this->db->query("DROP TRIGGER IF EXISTS trg_decision_ai_estado");
        $this->db->query("
            CREATE TRIGGER trg_decision_ai_estado
            AFTER INSERT ON tbl_furd_decision
            FOR EACH ROW
            UPDATE tbl_furd SET estado='decision', updated_at=NOW() WHERE id=NEW.furd_id
        ");
    }

    public function down()
    {
        $this->db->query("DROP TRIGGER IF EXISTS trg_decision_ai_estado");
        $this->forge->dropTable('tbl_furd_decision', true);
    }
}
