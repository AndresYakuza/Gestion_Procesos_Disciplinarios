<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

// ================ Migración para check de fechas y vista del contrato vigente (útil para FURD) ================
class AddChecksAndViewContratoActivo extends Migration
{
    public function up()
    {
        // Índice útil para búsquedas por empleado y activos
        $this->db->query("ALTER TABLE tbl_empleado_contratos
            ADD INDEX ix_contratos_emp_activo (empleado_id, activo)");

        // Check de fechas (MySQL 8+ aplica)
        $this->db->query("ALTER TABLE tbl_empleado_contratos
            ADD CONSTRAINT chk_contrato_fechas
            CHECK (fecha_retiro IS NULL OR fecha_ingreso IS NULL OR fecha_retiro >= fecha_ingreso)");

        // Vista: último contrato activo por empleado (si hay varios, toma el de mayor fecha_ingreso)
        $this->db->query("
            CREATE OR REPLACE VIEW vw_empleado_contrato_activo AS
            SELECT ec.*
            FROM tbl_empleado_contratos ec
            JOIN (
                SELECT empleado_id, MAX(COALESCE(fecha_ingreso,'1000-01-01')) AS max_ingreso
                FROM tbl_empleado_contratos
                WHERE activo = 1 AND (fecha_retiro IS NULL)
                GROUP BY empleado_id
            ) t ON t.empleado_id = ec.empleado_id AND COALESCE(ec.fecha_ingreso,'1000-01-01') = t.max_ingreso
            WHERE ec.activo = 1 AND ec.fecha_retiro IS NULL
        ");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS vw_empleado_contrato_activo");
        $this->db->query("ALTER TABLE tbl_empleado_contratos DROP INDEX ix_contratos_emp_activo");
        $this->db->query("ALTER TABLE tbl_empleado_contratos DROP CHECK chk_contrato_fechas");
    }
}
