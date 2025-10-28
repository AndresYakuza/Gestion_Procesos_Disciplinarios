<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// ================ tbl_furd (registro del evento disciplinario) ================.
class CreateTblFurd extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'empleado_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'fecha_evento'    => ['type' => 'DATE'],
            'turno'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'hora_evento'     => ['type' => 'TIME', 'null' => true],
            'supervisor_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'hecho'           => ['type' => 'TEXT'],
            'estado'          => ['type' => 'ENUM', 'constraint' => ['registrado', 'citacion_generada', 'acta_generada', 'decision_emitida'], 'default' => 'registrado'],
            'audit_created_by' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'audit_updated_by' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['empleado_id', 'supervisor_id']);
        $this->forge->addForeignKey('empleado_id',  'tbl_empleados', 'id', 'CASCADE', 'RESTRICT', 'fk_furd_empleado');
        $this->forge->addForeignKey('supervisor_id', 'tbl_empleados', 'id', 'CASCADE', 'SET NULL', 'fk_furd_supervisor');

        $this->forge->createTable('tbl_furd', true, [
            'ENGINE' => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci'
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('tbl_furd', true);
    }
}
