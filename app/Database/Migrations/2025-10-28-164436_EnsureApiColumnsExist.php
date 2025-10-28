<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnsureApiColumnsExist extends Migration
{
    private function fields($table)
    {
        $arr = [];
        $query = $this->db->query("SHOW COLUMNS FROM {$table}");
        foreach ($query->getResult() as $f) {
            $arr[$f->Field] = true;
        }
        return $arr;
    }


    public function up()
    {
        // ===== tbl_empleados =====
        $t = 'tbl_empleados';
        $have = $this->fields($t);
        $add = [];

        $addIf = function(string $col, array $def) use (&$add, $have) {
            if (!isset($have[$col])) { $add[$col] = $def; }
        };

        // personales / demográficos
        $addIf('nombre_1',          ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('nombre_2',          ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('apellido_1',        ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('apellido_2',        ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('ciudad_expide',     ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('fecha_expide_cc',   ['type'=>'DATE','null'=>true]);
        $addIf('fecha_nacimiento',  ['type'=>'DATE','null'=>true]);
        $addIf('ciudad_nac',        ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('dpto_nac',          ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('sexo',              ['type'=>'VARCHAR','constraint'=>30,'null'=>true]);
        $addIf('estado_civil',      ['type'=>'VARCHAR','constraint'=>60,'null'=>true]);
        $addIf('grupo_sanguineo',   ['type'=>'VARCHAR','constraint'=>30,'null'=>true]);
        $addIf('grupo_social',      ['type'=>'VARCHAR','constraint'=>60,'null'=>true]);
        $addIf('mujer_cf',          ['type'=>'VARCHAR','constraint'=>10,'null'=>true]);

        // afiliaciones
        $addIf('eps',               ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('afp',               ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('fondo_cesantias',   ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('caja_compensacion', ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('arl',               ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);

        // domicilio y otros
        $addIf('direccion_vive',    ['type'=>'VARCHAR','constraint'=>255,'null'=>true]);
        $addIf('barrio_vive',       ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        // asegurar estrato como VARCHAR
        if (isset($have['estrato'])) {
            // si no es varchar, intenta cambiar
            try { $this->db->query("ALTER TABLE {$t} MODIFY COLUMN estrato VARCHAR(30) NULL"); } catch (\Throwable $e) {}
        } else {
            $addIf('estrato',       ['type'=>'VARCHAR','constraint'=>30,'null'=>true]);
        }
        $addIf('ciudad_vive',       ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('dpto_vive',         ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('profesion',         ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('avecindad',         ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('libreta_militar',   ['type'=>'VARCHAR','constraint'=>60,'null'=>true]);
        $addIf('certificado_judicial',['type'=>'VARCHAR','constraint'=>60,'null'=>true]);
        $addIf('dto_lmil',          ['type'=>'VARCHAR','constraint'=>60,'null'=>true]);

        // tallas
        $addIf('talla_camisa',      ['type'=>'VARCHAR','constraint'=>30,'null'=>true]);
        $addIf('talla_pantalon',    ['type'=>'VARCHAR','constraint'=>30,'null'=>true]);
        $addIf('talla_zapatos',     ['type'=>'VARCHAR','constraint'=>30,'null'=>true]);
        $addIf('peso',              ['type'=>'VARCHAR','constraint'=>30,'null'=>true]);
        $addIf('estatura',          ['type'=>'VARCHAR','constraint'=>30,'null'=>true]);

        if (!empty($add)) $this->forge->addColumn($t, $add);

        // ===== tbl_empleado_contratos =====
        $t = 'tbl_empleado_contratos';
        $have = $this->fields($t);
        $add = [];

        $addIf = function(string $col, array $def) use (&$add, $have) {
            if (!isset($have[$col])) { $add[$col] = $def; }
        };

        $addIf('nomina',           ['type'=>'VARCHAR','constraint'=>150,'null'=>true]);
        $addIf('tipo_contrato',    ['type'=>'VARCHAR','constraint'=>150,'null'=>true]);
        $addIf('duracion',         ['type'=>'VARCHAR','constraint'=>150,'null'=>true]);
        $addIf('nivel',            ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('fecha_sige',       ['type'=>'DATE','null'=>true]);
        $addIf('centro_costo',     ['type'=>'VARCHAR','constraint'=>150,'null'=>true]);
        $addIf('dpto',             ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('division',         ['type'=>'VARCHAR','constraint'=>150,'null'=>true]);
        $addIf('centro_trabajo',   ['type'=>'VARCHAR','constraint'=>200,'null'=>true]);
        $addIf('tipo_ingreso',     ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('periodo_pago',     ['type'=>'VARCHAR','constraint'=>60,'null'=>true]);
        $addIf('tipo_cuenta',      ['type'=>'VARCHAR','constraint'=>60,'null'=>true]);
        $addIf('banco',            ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('cuenta',           ['type'=>'VARCHAR','constraint'=>60,'null'=>true]);
        $addIf('porcentaje_arl',   ['type'=>'VARCHAR','constraint'=>20,'null'=>true]);
        $addIf('primera_vez',      ['type'=>'VARCHAR','constraint'=>10,'null'=>true]);
        $addIf('usuario_contrato', ['type'=>'VARCHAR','constraint'=>120,'null'=>true]);
        $addIf('ultimo_cambio',    ['type'=>'DATETIME','null'=>true]);
        $addIf('estado_contrato',  ['type'=>'VARCHAR','constraint'=>60,'null'=>true]);
        $addIf('cno',              ['type'=>'VARCHAR','constraint'=>60,'null'=>true]);
        $addIf('nombre_cno',       ['type'=>'VARCHAR','constraint'=>200,'null'=>true]);

        if (!empty($add)) $this->forge->addColumn($t, $add);
    }

    public function down()
    {
        // no-op (no quitamos columnas)
    }
}
