<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropFurdConsecutivoTrigger extends Migration
{
    public function up()
    {
        // 1) Eliminar trigger que actualizaba tbl_furd desde tbl_furd (causa del error)
        $this->db->query("DROP TRIGGER IF EXISTS `trg_furd_ai_consecutivo`;");

        // 2) Rellenar consecutivos faltantes o vacíos con formato FURD-YYYY-000123
        //    Año tomado de created_at, si no existe usa fecha_evento y si tampoco, el año actual.
        $this->db->query("
            UPDATE `tbl_furd` t
            LEFT JOIN (
                SELECT
                    id,
                    COALESCE(YEAR(created_at), YEAR(fecha_evento), YEAR(CURDATE())) AS anio
                FROM `tbl_furd`
            ) x ON x.id = t.id
            SET t.consecutivo = CONCAT('FURD-', x.anio, '-', LPAD(t.id, 6, '0'))
            WHERE t.consecutivo IS NULL OR t.consecutivo = '';
        ");
    }

    public function down()
    {
        // No recreamos el trigger para evitar reintroducir el bug de recursión.
        // (Si se necesita, se recomienda mantener el consecutivo en app/código).
    }
}
