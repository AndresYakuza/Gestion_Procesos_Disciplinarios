<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTblAdjuntos extends Migration
{
    protected string $table = 'tbl_adjuntos';

    /** ¿Existe la columna? */
    private function columnExists(string $table, string $column): bool
    {
        $dbName = $this->db->getDatabase();
        $sql = "SELECT 1
                  FROM information_schema.columns
                 WHERE table_schema = ?
                   AND table_name   = ?
                   AND column_name  = ?
                 LIMIT 1";
        return (bool) $this->db->query($sql, [$dbName, $table, $column])->getRowArray();
    }

    /** ¿Existe el índice? */
    private function indexExists(string $table, string $indexName): bool
    {
        $dbName = $this->db->getDatabase();
        $sql = "SELECT 1
                  FROM information_schema.statistics
                 WHERE table_schema = ?
                   AND table_name   = ?
                   AND index_name   = ?
                 LIMIT 1";
        return (bool) $this->db->query($sql, [$dbName, $table, $indexName])->getRowArray();
    }

    public function up()
    {
        // Agregar updated_at / deleted_at para timestamps + soft deletes
        if (! $this->columnExists($this->table, 'updated_at')) {
            $this->forge->addColumn($this->table, [
                'updated_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'created_at'],
            ]);
        }
        if (! $this->columnExists($this->table, 'deleted_at')) {
            $this->forge->addColumn($this->table, [
                'deleted_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'updated_at'],
            ]);
        }

        // Índice útil para búsquedas por origen/fase/fecha
        // (MariaDB 10.4 no soporta CREATE INDEX IF NOT EXISTS)
        $ix = 'ix_adj_origen_fase2';
        if (! $this->indexExists($this->table, $ix)) {
            $this->db->query(
                "ALTER TABLE `{$this->table}`
                 ADD INDEX `{$ix}` (`origen`,`origen_id`,`fase`,`created_at`)"
            );
        }
    }

    public function down()
    {
        $ix = 'ix_adj_origen_fase2';
        if ($this->indexExists($this->table, $ix)) {
            $this->db->query("ALTER TABLE `{$this->table}` DROP INDEX `{$ix}`");
        }
        if ($this->columnExists($this->table, 'deleted_at')) {
            $this->forge->dropColumn($this->table, 'deleted_at');
        }
        if ($this->columnExists($this->table, 'updated_at')) {
            $this->forge->dropColumn($this->table, 'updated_at');
        }
    }
}
