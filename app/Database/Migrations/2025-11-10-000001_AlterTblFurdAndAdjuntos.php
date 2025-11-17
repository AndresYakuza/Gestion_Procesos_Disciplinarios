<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterTblFurdAndAdjuntos extends Migration
{
    public function up()
    {
        // 1) FURD: consecutivo único y enum de estado normalizado
        // consecutivo
        $this->forge->addColumn('tbl_furd', [
            'consecutivo' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'unique'     => true,
                'after'      => 'id',
            ],
        ]);

        // estado -> ENUM('registrado','citacion','descargos','soporte','decision')
        // Si tu motor no acepta ENUM por forge, puedes usar raw query.
        $this->db->query("
            ALTER TABLE tbl_furd
            MODIFY COLUMN estado
            ENUM('registrado','citacion','descargos','soporte','decision')
            NOT NULL DEFAULT 'registrado'
        ");

        // Trigger: autogenerar consecutivo después del insert (una sola sentencia)
        $this->db->query("DROP TRIGGER IF EXISTS trg_furd_ai_consecutivo");
        $this->db->query("
            CREATE TRIGGER trg_furd_ai_consecutivo
            AFTER INSERT ON tbl_furd
            FOR EACH ROW
            UPDATE tbl_furd
               SET consecutivo = CONCAT('FURD-', DATE_FORMAT(CURDATE(), '%Y'), '-', LPAD(NEW.id, 6, '0'))
             WHERE id = NEW.id AND consecutivo IS NULL
        ");

        // 2) ADJUNTOS: agregar 'fase' y un índice compuesto
        $this->forge->addColumn('tbl_adjuntos', [
            'fase' => [
                'type'       => 'ENUM',
                'constraint' => ['registro','citacion','descargos','soporte','decision'],
                'default'    => 'registro',
                'null'       => false,
                'after'      => 'origen_id',
            ],
        ]);

        $this->db->query("ALTER TABLE tbl_adjuntos ADD KEY ix_adj_origen_fase (origen, origen_id, fase)");
    }

    public function down()
    {
        // Quitar trigger
        $this->db->query("DROP TRIGGER IF EXISTS trg_furd_ai_consecutivo");

        // Quitar índice y columna 'fase' en adjuntos
        $this->db->query("ALTER TABLE tbl_adjuntos DROP INDEX ix_adj_origen_fase");
        $this->forge->dropColumn('tbl_adjuntos', 'fase');

        // Quitar 'consecutivo' en FURD
        $this->forge->dropColumn('tbl_furd', 'consecutivo');

        // (Opcional) Revertir estado (si conoces el enum anterior). Dejamos el enum actual.
        // $this->db->query("ALTER TABLE tbl_furd MODIFY COLUMN estado ENUM('registrado') NOT NULL DEFAULT 'registrado'");
    }
}
