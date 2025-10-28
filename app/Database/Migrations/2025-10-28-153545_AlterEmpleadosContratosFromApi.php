<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterEmpleadosContratosFromApi extends Migration
{
    public function up()
    {
        // ===== A) EMPLEADOS (datos personales / de afiliación / domicilio) =====
        // Cambiar tipo de estrato a VARCHAR(30) si existe como numérico
        $this->db->query("
            ALTER TABLE tbl_empleados
            MODIFY COLUMN estrato VARCHAR(30) NULL
        ");

        // Columnas a agregar (si no existen)
        $empCols = $this->db->query("
            SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_empleados'
        ")->getResultArray();
        $empHas = array_flip(array_map(fn($r)=> strtolower($r['COLUMN_NAME']), $empCols));

        $addEmp = [];

        // Nombres desagregados
        foreach ([
            'nombre_1'        => "VARCHAR(80) NULL AFTER nombre_completo",
            'nombre_2'        => "VARCHAR(80) NULL AFTER nombre_1",
            'apellido_1'      => "VARCHAR(80) NULL AFTER nombre_2",
            'apellido_2'      => "VARCHAR(80) NULL AFTER apellido_1",
        ] as $col => $ddl) if (!isset($empHas[$col])) $addEmp[] = "ADD COLUMN $col $ddl";

        // Documento / expedición
        foreach ([
            'ciudad_expide'     => "VARCHAR(120) NULL AFTER tipo_documento",
            'fecha_expide_cc'   => "DATE NULL AFTER ciudad_expide",
        ] as $col => $ddl) if (!isset($empHas[$col])) $addEmp[] = "ADD COLUMN $col $ddl";

        // Demográficos
        foreach ([
            'fecha_nacimiento'  => "DATE NULL AFTER apellido_2",
            'ciudad_nac'        => "VARCHAR(120) NULL",
            'dpto_nac'          => "VARCHAR(120) NULL",
            'sexo'              => "VARCHAR(20) NULL",
            'estado_civil'      => "VARCHAR(30) NULL",
            'grupo_sanguineo'   => "VARCHAR(20) NULL",
            'grupo_social'      => "VARCHAR(50) NULL",
            'mujer_cf'          => "VARCHAR(10) NULL",   // 'SI'|'NO'
            'estatura'          => "VARCHAR(30) NULL",
            'peso'              => "VARCHAR(30) NULL",
            'talla_camisa'      => "VARCHAR(30) NULL",
            'talla_pantalon'    => "VARCHAR(30) NULL",
            'talla_zapatos'     => "VARCHAR(30) NULL",
        ] as $col => $ddl) if (!isset($empHas[$col])) $addEmp[] = "ADD COLUMN $col $ddl";

        // Afiliaciones
        foreach ([
            'eps'               => "VARCHAR(120) NULL",
            'afp'               => "VARCHAR(120) NULL",
            'fondo_cesantias'   => "VARCHAR(120) NULL",
            'caja_compensacion' => "VARCHAR(120) NULL",
            'arl'               => "VARCHAR(120) NULL",
        ] as $col => $ddl) if (!isset($empHas[$col])) $addEmp[] = "ADD COLUMN $col $ddl";

        // Dirección y contacto (ya tienes varios; sólo agrego los faltantes)
        foreach ([
            'direccion_vive'    => "VARCHAR(200) NULL AFTER barrio_vive",
            'avecindad'         => "VARCHAR(120) NULL",
        ] as $col => $ddl) if (!isset($empHas[$col])) $addEmp[] = "ADD COLUMN $col $ddl";

        // Otros documentos
        foreach ([
            'libreta_militar'     => "VARCHAR(50) NULL",
            'certificado_judicial'=> "VARCHAR(50) NULL",
            'dto_lmil'            => "VARCHAR(50) NULL",
        ] as $col => $ddl) if (!isset($empHas[$col])) $addEmp[] = "ADD COLUMN $col $ddl";

        if (!empty($addEmp)) {
            $this->db->query("ALTER TABLE tbl_empleados ".implode(", ", $addEmp));
        }

        // ===== B) CONTRATOS (datos laborales / nómina / puestos) =====
        $conCols = $this->db->query("
            SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbl_empleado_contratos'
        ")->getResultArray();
        $conHas = array_flip(array_map(fn($r)=> strtolower($r['COLUMN_NAME']), $conCols));

        $addCon = [];
        foreach ([
            'nomina'           => "VARCHAR(200) NULL AFTER contrato",
            'tipo_contrato'    => "VARCHAR(120) NULL",     // API: TIPO
            'duracion'         => "VARCHAR(120) NULL",
            'nivel'            => "VARCHAR(120) NULL",
            'fecha_sige'       => "DATE NULL",
            'centro_costo'     => "VARCHAR(120) NULL",     // API: C._COSTO
            'dpto'             => "VARCHAR(120) NULL",
            'division'         => "VARCHAR(120) NULL",
            'centro_trabajo'   => "VARCHAR(180) NULL",
            'tipo_ingreso'     => "VARCHAR(100) NULL",
            'periodo_pago'     => "VARCHAR(50) NULL",
            'tipo_cuenta'      => "VARCHAR(50) NULL",
            'banco'            => "VARCHAR(120) NULL",
            'cuenta'           => "VARCHAR(60) NULL",
            'porcentaje_arl'   => "VARCHAR(20) NULL",      // API: PORCENTAJE
            'primera_vez'      => "VARCHAR(10) NULL",
            'usuario_contrato' => "VARCHAR(120) NULL",
            'ultimo_cambio'    => "DATETIME NULL",
            'estado_contrato'  => "VARCHAR(20) NULL",      // API: ESTADO
            'cno'              => "VARCHAR(20) NULL",
            'nombre_cno'       => "VARCHAR(150) NULL",
        ] as $col => $ddl) if (!isset($conHas[$col])) $addCon[] = "ADD COLUMN $col $ddl";

        if (!empty($addCon)) {
            $this->db->query("ALTER TABLE tbl_empleado_contratos ".implode(", ", $addCon));
        }
    }

    public function down()
    {
        // Revertir es opcional; aquí sólo devolvemos estrato a TINYINT si lo deseas:
        $this->db->query("
            ALTER TABLE tbl_empleados
            MODIFY COLUMN estrato TINYINT(2) UNSIGNED NULL
        ");
        // (no se eliminan las columnas nuevas en down para no perder datos)
    }
}
