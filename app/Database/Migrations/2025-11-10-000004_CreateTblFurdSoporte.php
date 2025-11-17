<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTblFurdSoporte extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type'=>'INT','constraint'=>10,'unsigned'=>true,'auto_increment'=>true],
            'furd_id' => ['type'=>'INT','constraint'=>10,'unsigned'=>true],
            'responsable'   => ['type'=>'VARCHAR','constraint'=>150,'null'=>false],
            'decision_prop' => ['type'=>'TEXT','null'=>true],
            'audit_created_by' => ['type'=>'VARCHAR','constraint'=>100,'null'=>true],
            'audit_updated_by' => ['type'=>'VARCHAR','constraint'=>100,'null'=>true],
            'created_at' => ['type'=>'DATETIME','null'=>true],
            'updated_at' => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('furd_id');
        $this->forge->addForeignKey('furd_id','tbl_furd','id','CASCADE','CASCADE');
        $this->forge->createTable('tbl_furd_soporte', true);

        // Trigger: actualizar estado de FURD al crear soporte
        $this->db->query("DROP TRIGGER IF EXISTS trg_soporte_ai_estado");
        $this->db->query("
            CREATE TRIGGER trg_soporte_ai_estado
            AFTER INSERT ON tbl_furd_soporte
            FOR EACH ROW
            UPDATE tbl_furd SET estado='soporte', updated_at=NOW() WHERE id=NEW.furd_id
        ");
    }

    public function down()
    {
        $this->db->query("DROP TRIGGER IF EXISTS trg_soporte_ai_estado");
        $this->forge->dropTable('tbl_furd_soporte', true);
    }
}
