<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixChkContratoFechas extends Migration
{
    public function up()
    {
        // Quita si existe (en MariaDB 10.4 puedes usar DROP CONSTRAINT o DROP CHECK)
        try { $this->db->query("ALTER TABLE tbl_empleado_contratos DROP CONSTRAINT chk_contrato_fechas"); } catch (\Throwable $e) {}
        try { $this->db->query("ALTER TABLE tbl_empleado_contratos DROP CHECK chk_contrato_fechas"); } catch (\Throwable $e) {}

        // Re-crear: válido si retiro es nulo, o ingreso es nulo (contratos sin fecha aún), o retiro >= ingreso
        $this->db->query("
            ALTER TABLE tbl_empleado_contratos
            ADD CONSTRAINT chk_contrato_fechas
            CHECK (
                fecha_retiro IS NULL
                OR fecha_ingreso IS NULL
                OR fecha_retiro >= fecha_ingreso
            )
        ");
    }

    public function down()
    {
        try { $this->db->query("ALTER TABLE tbl_empleado_contratos DROP CONSTRAINT chk_contrato_fechas"); } catch (\Throwable $e) {}
        try { $this->db->query("ALTER TABLE tbl_empleado_contratos DROP CHECK chk_contrato_fechas"); } catch (\Throwable $e) {}
    }
}
