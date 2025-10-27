<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// ================ tbl_furd (registro del evento disciplinario) ================.
class CreateTblFurd extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                    => ['type'=>'INT','constraint'=>11,'unsigned'=>true,'auto_increment'=>true],
            'colaborador_id'        => ['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'fecha_evento'          => ['type'=>'DATE'],
            'turno'                 => ['type'=>'VARCHAR','constraint'=>50,'null'=>true],
            'hora_evento'           => ['type'=>'TIME','null'=>true],
            'supervisor_id'         => ['type'=>'INT','constraint'=>11,'unsigned'=>true,'null'=>true],
            'hecho'                 => ['type'=>'TEXT'], // detalle de lo sucedido
            'estado'                => ['type'=>'ENUM','constraint'=>['registrado','citacion_generada','acta_generada','decision_emitida'],'default'=>'registrado'],
            'audit_created_by'      => ['type'=>'VARCHAR','constraint'=>100,'null'=>true],
            'audit_updated_by'      => ['type'=>'VARCHAR','constraint'=>100,'null'=>true],
            'created_at'            => ['type'=>'DATETIME','null'=>true],
            'updated_at'            => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['colaborador_id','supervisor_id']);
        $this->forge->createTable('tbl_furd', true);

        $this->db->query('ALTER TABLE tbl_furd
            ADD CONSTRAINT fk_furd_colab FOREIGN KEY (colaborador_id) REFERENCES tbl_colaboradores(id)
              ON UPDATE CASCADE ON DELETE RESTRICT,
            ADD CONSTRAINT fk_furd_supervisor FOREIGN KEY (supervisor_id) REFERENCES tbl_colaboradores(id)
              ON UPDATE CASCADE ON DELETE SET NULL');
    }

    public function down()
    {
        $this->forge->dropTable('tbl_furd', true);
    }
}
