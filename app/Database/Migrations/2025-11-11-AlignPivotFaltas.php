<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlignPivotFaltas extends Migration
{
    // -------- helpers --------
    private function columnExists(string $table, string $column): bool
    {
        $sql = "SELECT 1
                  FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME   = ?
                   AND COLUMN_NAME  = ?
                 LIMIT 1";
        return (bool) $this->db->query($sql, [$table, $column])->getRow();
    }

    private function fkExists(string $table, string $constraint): bool
    {
        $sql = "SELECT 1
                  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME   = ?
                   AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                   AND CONSTRAINT_NAME = ?
                 LIMIT 1";
        return (bool) $this->db->query($sql, [$table, $constraint])->getRow();
    }

    private function indexExists(string $table, string $index): bool
    {
        $sql = "SHOW INDEX FROM `{$table}` WHERE Key_name = ?";
        return (bool) $this->db->query($sql, [$index])->getRow();
    }

    public function up()
    {
        $table = 'tbl_furd_faltas';

        // 1) Drop FKs si existen (MySQL no soporta IF EXISTS para FKs en todas las versiones)
        if ($this->fkExists($table, 'fk_ff_falta')) {
            $this->db->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `fk_ff_falta`");
        }
        if ($this->fkExists($table, 'fk_ff_furd')) {
            $this->db->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `fk_ff_furd`");
        }

        // 2) Eliminar columna legacy si existe
        if ($this->columnExists($table, 'rit_falta_id')) {
            $this->db->query("ALTER TABLE `{$table}` DROP COLUMN `rit_falta_id`");
        }

        // 3) Asegurar tipos/nullable correctos
        $this->db->query("
            ALTER TABLE `{$table}`
              MODIFY `furd_id`  INT UNSIGNED NOT NULL,
              MODIFY `falta_id` INT UNSIGNED NOT NULL
        ");

        // 4) Índice único compuesto (evita duplicados)
        if (! $this->indexExists($table, 'ux_furd_falta')) {
            $this->db->query("
                ALTER TABLE `{$table}`
                ADD UNIQUE KEY `ux_furd_falta` (`furd_id`,`falta_id`)
            ");
        }

        // 5) Recrear FKs correctos
        $this->db->query("
            ALTER TABLE `{$table}`
              ADD CONSTRAINT `fk_ff_furd`
                FOREIGN KEY (`furd_id`) REFERENCES `tbl_furd`(`id`)
                ON DELETE CASCADE ON UPDATE CASCADE,
              ADD CONSTRAINT `fk_ff_falta`
                FOREIGN KEY (`falta_id`) REFERENCES `tbl_rit_faltas`(`id`)
                ON DELETE RESTRICT ON UPDATE CASCADE
        ");
    }

    public function down()
    {
        $table = 'tbl_furd_faltas';

        if ($this->fkExists($table, 'fk_ff_falta')) {
            $this->db->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `fk_ff_falta`");
        }
        if ($this->fkExists($table, 'fk_ff_furd')) {
            $this->db->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `fk_ff_furd`");
        }

        if ($this->indexExists($table, 'ux_furd_falta')) {
            $this->db->query("ALTER TABLE `{$table}` DROP INDEX `ux_furd_falta`");
        }

        // No recreamos la columna legacy.
    }
}
