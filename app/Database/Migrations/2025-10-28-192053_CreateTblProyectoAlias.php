<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTblProyectoAlias extends Migration
{
            public function up()
            {
                // Tabla de alias: un nombre normalizado de "nomina" apunta a un proyecto
                $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'proyecto_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'alias' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
            ],
            'alias_norm' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
                'comment'    => 'Alias normalizado (UPPER, sin tildes/extra). Debe ser único global.',
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);             // primary key
        $this->forge->addUniqueKey('alias_norm');     // unique constraint
        $this->forge->addKey(['proyecto_id']);
        $this->forge->addForeignKey('proyecto_id', 'tbl_proyectos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('tbl_proyecto_alias', true);
        
        // FK en contratos si hiciera falta (idempotente)
        // Nota: si ya existe, MariaDB la ignora al repetirla
        $this->db->query("
            ALTER TABLE tbl_empleado_contratos
            ADD CONSTRAINT fk_contrato_proyecto
            FOREIGN KEY (proyecto_id) REFERENCES tbl_proyectos(id)
            ON UPDATE CASCADE ON DELETE SET NULL
        ");
    }

    public function down()
    {
        // Quita FK opcionalmente si existe
        @ $this->db->query("ALTER TABLE tbl_empleado_contratos DROP FOREIGN KEY fk_contrato_proyecto");
        $this->forge->dropTable('tbl_proyecto_alias', true);
    }
}
