<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;


// ================ tbl_empleados (máster mínimo: cédula, expedida_en, nombre, proyecto ================.
class CreateTblEmpleados extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],

            // Identificación
            'tipo_documento'   => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'numero_documento' => ['type' => 'VARCHAR', 'constraint' => 32],
            'nombre_completo'  => ['type' => 'VARCHAR', 'constraint' => 255],

            // Laboral
            'contrato'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'cod_nomina'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'sueldo'           => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'cargo_sige'       => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'cargo'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'categoria'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'codigo'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],

            // Contacto / Ubicación
            'barrio_vive'      => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'estrato'          => ['type' => 'TINYINT', 'constraint' => 2, 'unsigned' => true, 'null' => true],
            'celular'          => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'ciudad_vive'      => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'dpto_vive'        => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'profesion'        => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'correo'           => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'telefono'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],

            // Relación y estado
            'proyecto_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'activo'           => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],

            // Auditoría
            'audit_created_by' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'audit_updated_by' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('numero_documento');
        $this->forge->addKey('proyecto_id');
        $this->forge->addKey('cod_nomina');
        $this->forge->addKey('codigo');

        $this->forge->createTable('tbl_empleados', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
            'COLLATE' => 'utf8mb4_general_ci',
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
