<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUpdatedDeletedAdjuntos extends Migration
{
    private function tableExists(string $table): bool
    {
        return (bool) $this->db->query(
            "SELECT 1
               FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
              LIMIT 1",
            [$table]
        )->getRow();
    }

    private function columnExists(string $table, string $column): bool
    {
        return (bool) $this->db->query(
            "SELECT 1
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = ?
              LIMIT 1",
            [$table, $column]
        )->getRow();
    }

    public function up()
    {
        if (! $this->tableExists('tbl_adjuntos')) {
            throw new \RuntimeException('Tabla tbl_adjuntos no existe.');
        }

        if (! $this->columnExists('tbl_adjuntos', 'updated_at')) {
            $this->db->query("ALTER TABLE `tbl_adjuntos` ADD COLUMN `updated_at` DATETIME NULL AFTER `created_at`");
        }

        if (! $this->columnExists('tbl_adjuntos', 'deleted_at')) {
            $this->db->query("ALTER TABLE `tbl_adjuntos` ADD COLUMN `deleted_at` DATETIME NULL AFTER `updated_at`");
        }
    }

    public function down()
    {
        if ($this->columnExists('tbl_adjuntos', 'deleted_at')) {
            $this->db->query("ALTER TABLE `tbl_adjuntos` DROP COLUMN `deleted_at`");
        }

        if ($this->columnExists('tbl_adjuntos', 'updated_at')) {
            $this->db->query("ALTER TABLE `tbl_adjuntos` DROP COLUMN `updated_at`");
        }
    }
}
