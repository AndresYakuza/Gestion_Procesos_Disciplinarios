<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;


// ================ tbl_empleados (máster mínimo: cédula, expedida_en, nombre, proyecto ================.
class CreateTblEmpleados extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type'=>'INT','constraint'=>11,'unsigned'=>true,'auto_increment'=>true],
            'cedula'           => ['type'=>'VARCHAR','constraint'=>32],
            'tipo_documento'   => ['type'=>'VARCHAR','constraint'=>10,'null'=>true],
            'expedida_en'      => ['type'=>'VARCHAR','constraint'=>100,'null'=>true],
            'nombre_completo'  => ['type'=>'VARCHAR','constraint'=>255],
            'proyecto_id'      => ['type'=>'INT','constraint'=>11,'unsigned'=>true,'null'=>true],
            'email'            => ['type'=>'VARCHAR','constraint'=>150,'null'=>true],
            'telefono'         => ['type'=>'VARCHAR','constraint'=>50,'null'=>true],
            'activo'           => ['type'=>'TINYINT','constraint'=>1,'default'=>1],
            'audit_created_by' => ['type'=>'VARCHAR','constraint'=>100,'null'=>true],
            'audit_updated_by' => ['type'=>'VARCHAR','constraint'=>100,'null'=>true],
            'created_at'       => ['type'=>'DATETIME','null'=>true],
            'updated_at'       => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('cedula');
        $this->forge->addKey('proyecto_id');
        $this->forge->createTable('tbl_empleados', true, [
            'ENGINE'=>'InnoDB','CHARSET'=>'utf8mb4','COLLATE'=>'utf8mb4_general_ci'
        ]);

        $this->db->query('ALTER TABLE tbl_empleados
            ADD CONSTRAINT fk_emp_proy FOREIGN KEY (proyecto_id) REFERENCES tbl_proyectos(id)
            ON UPDATE CASCADE ON DELETE SET NULL');
    }

    public function down()
    {
        $this->forge->dropTable('tbl_empleados', true);
    }
}
