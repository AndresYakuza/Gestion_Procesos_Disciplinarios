<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateVwFurdSeguimiento extends Migration
{
    public function up()
    {
        $db = db_connect();

        // 🔹 Eliminar la vista anterior si existe
        $db->query('DROP VIEW IF EXISTS vw_furd_seguimiento');

        // 🔹 Crear la vista corregida
        $db->query("
            CREATE ALGORITHM=UNDEFINED 
            VIEW vw_furd_seguimiento AS
            SELECT 
                f.id AS id,
                f.consecutivo AS consecutivo,
                f.empleado_id AS empleado_id,       -- ✅ corregido
                f.fecha_evento AS fecha_evento,
                f.hora_evento AS hora_evento,
                f.estado AS estado,
                f.created_at AS furd_creado,
                f.updated_at AS furd_actualizado,
                c.created_at AS citacion_creada,
                d.created_at AS descargos_creados,
                s.created_at AS soporte_creado,
                de.created_at AS decision_creada
            FROM 
                proyecto_gpd.tbl_furd AS f
                LEFT JOIN proyecto_gpd.tbl_furd_citacion AS c ON c.furd_id = f.id
                LEFT JOIN proyecto_gpd.tbl_furd_descargos AS d ON d.furd_id = f.id
                LEFT JOIN proyecto_gpd.tbl_furd_soporte AS s ON s.furd_id = f.id
                LEFT JOIN proyecto_gpd.tbl_furd_decision AS de ON de.furd_id = f.id
        ");
    }

    public function down()
    {
        $db = db_connect();

        // 🔹 Eliminar la vista corregida
        $db->query('DROP VIEW IF EXISTS vw_furd_seguimiento');

        // 🔹 Recrear la versión anterior (por rollback)
        $db->query("
            CREATE ALGORITHM=UNDEFINED 
            VIEW vw_furd_seguimiento AS
            SELECT 
                f.id AS id,
                f.consecutivo AS consecutivo,
                f.colaborador_id AS colaborador_id,   -- ❌ versión antigua
                f.fecha_evento AS fecha_evento,
                f.hora_evento AS hora_evento,
                f.estado AS estado,
                f.created_at AS furd_creado,
                f.updated_at AS furd_actualizado,
                c.created_at AS citacion_creada,
                d.created_at AS descargos_creados,
                s.created_at AS soporte_creado,
                de.created_at AS decision_creada
            FROM 
                proyecto_gpd.tbl_furd AS f
                LEFT JOIN proyecto_gpd.tbl_furd_citacion AS c ON c.furd_id = f.id
                LEFT JOIN proyecto_gpd.tbl_furd_descargos AS d ON d.furd_id = f.id
                LEFT JOIN proyecto_gpd.tbl_furd_soporte AS s ON s.furd_id = f.id
                LEFT JOIN proyecto_gpd.tbl_furd_decision AS de ON de.furd_id = f.id
        ");
    }
}
