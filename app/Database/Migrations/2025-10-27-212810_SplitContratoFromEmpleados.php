<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SplitContratoFromEmpleados extends Migration
{
    public function up()
    {
        // 1) Tabla de contratos
        $this->forge->addField([
            'id'               => ['type'=>'INT','constraint'=>11,'unsigned'=>true,'auto_increment'=>true],
            'empleado_id'      => ['type'=>'INT','constraint'=>11,'unsigned'=>true],
            'contrato'         => ['type'=>'VARCHAR','constraint'=>30],
            'cod_nomina'       => ['type'=>'VARCHAR','constraint'=>50,'null'=>true],
            'proyecto_id'      => ['type'=>'INT','constraint'=>11,'unsigned'=>true,'null'=>true],
            'sueldo'           => ['type'=>'DECIMAL','constraint'=>'12,2','null'=>true],
            'cargo_sige'       => ['type'=>'VARCHAR','constraint'=>150,'null'=>true],
            'cargo'            => ['type'=>'VARCHAR','constraint'=>150,'null'=>true],
            'categoria'        => ['type'=>'VARCHAR','constraint'=>100,'null'=>true],
            'codigo'           => ['type'=>'VARCHAR','constraint'=>50,'null'=>true],
            'fecha_ingreso'    => ['type'=>'DATE','null'=>true],
            'fecha_retiro'     => ['type'=>'DATE','null'=>true],
            'activo'           => ['type'=>'TINYINT','constraint'=>1,'default'=>1],
            'audit_created_by' => ['type'=>'VARCHAR','constraint'=>100,'null'=>true],
            'audit_updated_by' => ['type'=>'VARCHAR','constraint'=>100,'null'=>true],
            'created_at'       => ['type'=>'DATETIME','null'=>true],
            'updated_at'       => ['type'=>'DATETIME','null'=>true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['empleado_id','contrato']);
        $this->forge->addKey('cod_nomina');
        $this->forge->addKey('proyecto_id');
        $this->forge->createTable('tbl_empleado_contratos', true, [
            'ENGINE'=>'InnoDB','CHARSET'=>'utf8mb4','COLLATE'=>'utf8mb4_general_ci'
        ]);

        $this->db->query('ALTER TABLE tbl_empleado_contratos
            ADD CONSTRAINT fk_contrato_empleado
              FOREIGN KEY (empleado_id) REFERENCES tbl_empleados(id)
              ON UPDATE CASCADE ON DELETE CASCADE');

        // 2) Migrar datos existentes desde tbl_empleados (si aún hay columnas antiguas)
        $colsAntiguas = ['contrato','cod_nomina','sueldo','cargo_sige','cargo','categoria','codigo'];

        $fieldRows = $this->db->query("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME   = 'tbl_empleados'
        ")->getResultArray();

        $fieldNames = array_map(
            static fn($r) => strtolower($r['COLUMN_NAME']),
            $fieldRows
        );

        $hayCols = count(array_intersect($colsAntiguas, $fieldNames)) > 0;

        if ($hayCols) {
            $this->db->query("
                INSERT INTO tbl_empleado_contratos
                    (empleado_id, contrato, cod_nomina, sueldo, cargo_sige, cargo, categoria, codigo, activo, created_at)
                SELECT e.id,
                    COALESCE(NULLIF(TRIM(e.contrato),''), CONCAT('LEG-', e.id)),
                    e.cod_nomina, e.sueldo, e.cargo_sige, e.cargo, e.categoria, e.codigo,
                    1, NOW()
                FROM tbl_empleados e
                WHERE (e.contrato IS NOT NULL AND e.contrato <> '')
                OR (e.cod_nomina IS NOT NULL AND e.cod_nomina <> '')
                OR (e.cargo IS NOT NULL AND e.cargo <> '')
            ");

            $drop = array_values(array_intersect($colsAntiguas, $fieldNames));
            if (!empty($drop)) {
                $this->forge->dropColumn('tbl_empleados', $drop);
            }
        }

    }

    public function down()
    {
        // devolver columnas a empleados
        $this->forge->addColumn('tbl_empleados', [
            'contrato'     => ['type'=>'VARCHAR','constraint'=>30,'null'=>true,'after'=>'nombre_completo'],
            'cod_nomina'   => ['type'=>'VARCHAR','constraint'=>50,'null'=>true,'after'=>'contrato'],
            'sueldo'       => ['type'=>'DECIMAL','constraint'=>'12,2','null'=>true,'after'=>'cod_nomina'],
            'cargo_sige'   => ['type'=>'VARCHAR','constraint'=>150,'null'=>true,'after'=>'sueldo'],
            'cargo'        => ['type'=>'VARCHAR','constraint'=>150,'null'=>true,'after'=>'cargo_sige'],
            'categoria'    => ['type'=>'VARCHAR','constraint'=>100,'null'=>true,'after'=>'cargo'],
            'codigo'       => ['type'=>'VARCHAR','constraint'=>50,'null'=>true,'after'=>'categoria'],
        ]);
        $this->forge->dropTable('tbl_empleado_contratos', true);
    }
}
