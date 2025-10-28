<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateViewContratoActivo extends Migration
{
    public function up()
    {
        // Quita versión previa, si existe
        $this->db->query("DROP VIEW IF EXISTS vw_empleado_contrato_activo");

        // Contrato vigente por empleado (última fecha_ingreso, activo, sin retiro)
        $this->db->query("
            CREATE VIEW vw_empleado_contrato_activo AS
            SELECT
                ec.id,
                ec.empleado_id,
                ec.contrato,
                ec.cod_nomina,
                ec.nomina,
                ec.proyecto_id,
                ec.sueldo,
                ec.cargo_sige,
                ec.cargo,
                ec.categoria,
                ec.codigo,
                ec.fecha_ingreso,
                ec.fecha_retiro,
                ec.activo,
                ec.tipo_contrato,
                ec.duracion,
                ec.nivel,
                ec.fecha_sige,
                ec.centro_costo,
                ec.dpto,
                ec.division,
                ec.centro_trabajo,
                ec.tipo_ingreso,
                ec.periodo_pago,
                ec.tipo_cuenta,
                ec.banco,
                ec.cuenta,
                ec.porcentaje_arl,
                ec.primera_vez,
                ec.usuario_contrato,
                ec.ultimo_cambio,
                ec.estado_contrato,
                ec.cno,
                ec.nombre_cno,
                ec.audit_created_by,
                ec.audit_updated_by,
                ec.created_at,
                ec.updated_at
            FROM tbl_empleado_contratos ec
            JOIN (
                SELECT empleado_id,
                       MAX(COALESCE(fecha_ingreso,'1000-01-01')) AS max_ingreso
                FROM tbl_empleado_contratos
                WHERE activo = 1 AND fecha_retiro IS NULL
                GROUP BY empleado_id
            ) t  ON t.empleado_id = ec.empleado_id
               AND COALESCE(ec.fecha_ingreso,'1000-01-01') = t.max_ingreso
            WHERE ec.activo = 1
              AND ec.fecha_retiro IS NULL
        ");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS vw_empleado_contrato_activo");

        // Versión simple previa
        $this->db->query("
            CREATE VIEW vw_empleado_contrato_activo AS
            SELECT ec.*
            FROM tbl_empleado_contratos ec
            JOIN (
                SELECT empleado_id,
                       MAX(COALESCE(fecha_ingreso,'1000-01-01')) AS max_ingreso
                FROM tbl_empleado_contratos
                WHERE activo = 1 AND fecha_retiro IS NULL
                GROUP BY empleado_id
            ) t  ON t.empleado_id = ec.empleado_id
               AND COALESCE(ec.fecha_ingreso,'1000-01-01') = t.max_ingreso
            WHERE ec.activo = 1
              AND ec.fecha_retiro IS NULL
        ");
    }
}
