<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTblFurd extends Migration
{
    public function up()
    {
        // ✅ obtener conexión manualmente
        $db = db_connect();

        // obtener lista de columnas actuales
        $fields = $db->getFieldNames('tbl_furd');

        // 🔹 eliminar columna "turno"
        if (in_array('turno', $fields)) {
            $this->forge->dropColumn('tbl_furd', 'turno');
        }

        // 🔹 renombrar "colaborador_id" → "empleado_id"
        if (in_array('colaborador_id', $fields)) {
            $this->forge->modifyColumn('tbl_furd', [
                'colaborador_id' => [
                    'name'       => 'empleado_id',
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => true,
                ],
            ]);
        }

        // 🔹 renombrar "supervisor_id" → "superior"
        if (in_array('supervisor_id', $fields)) {
            $this->forge->modifyColumn('tbl_furd', [
                'supervisor_id' => [
                    'name'       => 'superior',
                    'type'       => 'VARCHAR',
                    'constraint' => 150,
                    'null'       => true,
                ],
            ]);
        }

        // 🔹 agregar columnas faltantes
        $newFields = [];

        if (!in_array('cedula', $fields)) {
            $newFields['cedula'] = ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true];
        }

        if (!in_array('expedida_en', $fields)) {
            $newFields['expedida_en'] = ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true];
        }

        if (!in_array('empresa_usuaria', $fields)) {
            $newFields['empresa_usuaria'] = ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true];
        }

        if (!in_array('nombre_completo', $fields)) {
            $newFields['nombre_completo'] = ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true];
        }

        if (!in_array('correo', $fields)) {
            $newFields['correo'] = ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true];
        }

        if (!in_array('proyecto_id', $fields)) {
            $newFields['proyecto_id'] = ['type' => 'INT', 'unsigned' => true, 'null' => true];
        }

        if (!empty($newFields)) {
            $this->forge->addColumn('tbl_furd', $newFields);
        }
    }

    public function down()
    {
        $db = db_connect();
        $fields = $db->getFieldNames('tbl_furd');

        // eliminar campos agregados
        $drop = ['cedula', 'expedida_en', 'empresa_usuaria', 'nombre_completo', 'correo', 'proyecto_id'];
        foreach ($drop as $col) {
            if (in_array($col, $fields)) {
                $this->forge->dropColumn('tbl_furd', $col);
            }
        }

        // revertir nombres
        if (in_array('empleado_id', $fields)) {
            $this->forge->modifyColumn('tbl_furd', [
                'empleado_id' => [
                    'name'       => 'colaborador_id',
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => true,
                ],
            ]);
        }

        if (in_array('superior', $fields)) {
            $this->forge->modifyColumn('tbl_furd', [
                'superior' => [
                    'name'       => 'supervisor_id',
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => true,
                ],
            ]);
        }

        // restaurar turno si fue eliminado
        if (!in_array('turno', $fields)) {
            $this->forge->addColumn('tbl_furd', [
                'turno' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            ]);
        }
    }
}
