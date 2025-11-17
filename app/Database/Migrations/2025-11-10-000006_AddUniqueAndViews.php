<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueAndViews extends Migration
{
    public function up()
    {
        // Evitar duplicados en la relación FURD-Faltas
        // (Ignora error si ya existe)
        try {
            $this->db->query("ALTER TABLE tbl_furd_faltas ADD UNIQUE KEY uq_furd_falta (furd_id, falta_id)");
        } catch (\Throwable $e) {
            // noop
        }

        // Vista de seguimiento
        $this->db->query("DROP VIEW IF EXISTS vw_furd_seguimiento");
        $this->db->query("
            CREATE VIEW vw_furd_seguimiento AS
            SELECT
              f.id,
              f.consecutivo,
              f.colaborador_id,
              f.fecha_evento,
              f.hora_evento,
              f.estado,
              f.created_at      AS furd_creado,
              f.updated_at      AS furd_actualizado,
              c.created_at      AS citacion_creada,
              d.created_at      AS descargos_creados,
              s.created_at      AS soporte_creado,
              de.created_at     AS decision_creada
            FROM tbl_furd f
            LEFT JOIN tbl_furd_citacion c ON c.furd_id = f.id
            LEFT JOIN tbl_furd_descargos d ON d.furd_id = f.id
            LEFT JOIN tbl_furd_soporte   s ON s.furd_id = f.id
            LEFT JOIN tbl_furd_decision  de ON de.furd_id = f.id
        ");

        // Vista de timeline
        $this->db->query("DROP VIEW IF EXISTS vw_furd_timeline");
        $this->db->query("
            CREATE VIEW vw_furd_timeline AS
            SELECT f.id AS furd_id, f.consecutivo, 'registro' AS etapa, f.created_at AS fecha, NULL AS detalle
              FROM tbl_furd f
            UNION ALL
            SELECT c.furd_id, f.consecutivo, 'citacion', c.created_at, CONCAT('Medio: ', c.medio)
              FROM tbl_furd_citacion c JOIN tbl_furd f ON f.id=c.furd_id
            UNION ALL
            SELECT d.furd_id, f.consecutivo, 'descargos', d.created_at, CONCAT('Medio: ', d.medio)
              FROM tbl_furd_descargos d JOIN tbl_furd f ON f.id=d.furd_id
            UNION ALL
            SELECT s.furd_id, f.consecutivo, 'soporte', s.created_at, CONCAT('Resp: ', s.responsable)
              FROM tbl_furd_soporte s JOIN tbl_furd f ON f.id=s.furd_id
            UNION ALL
            SELECT de.furd_id, f.consecutivo, 'decision', de.created_at, LEFT(de.decision_text,120)
              FROM tbl_furd_decision de JOIN tbl_furd f ON f.id=de.furd_id
        ");
    }

    public function down()
    {
        // Vistas
        $this->db->query("DROP VIEW IF EXISTS vw_furd_timeline");
        $this->db->query("DROP VIEW IF EXISTS vw_furd_seguimiento");

        // Unique
        try {
            $this->db->query("ALTER TABLE tbl_furd_faltas DROP INDEX uq_furd_falta");
        } catch (\Throwable $e) {
            // noop
        }
    }
}
