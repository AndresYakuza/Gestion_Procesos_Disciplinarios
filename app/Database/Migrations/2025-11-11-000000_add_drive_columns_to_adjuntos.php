<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDriveColumnsToAdjuntos extends Migration
{
    /** Comprueba si existe la tabla (seguro en CI5) */
    private function tableExists(string $table): bool
    {
        $dbName = $this->db->database;
        $sql = "SELECT 1
                  FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = ?
                   AND TABLE_NAME   = ?
                 LIMIT 1";
        return (bool) $this->db->query($sql, [$dbName, $table])->getFirstRow();
    }

    /** Comprueba si existe una columna (seguro en CI5) */
    private function colExists(string $table, string $column): bool
    {
        $dbName = $this->db->database;
        $sql = "SELECT 1
                  FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = ?
                   AND TABLE_NAME   = ?
                   AND COLUMN_NAME  = ?
                 LIMIT 1";
        return (bool) $this->db->query($sql, [$dbName, $table, $column])->getFirstRow();
    }

    /** Comprueba si existe un índice (seguro en CI5) */
    private function indexExists(string $table, string $index): bool
    {
        $dbName = $this->db->database;
        $sql = "SELECT 1
                  FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = ?
                   AND TABLE_NAME   = ?
                   AND INDEX_NAME   = ?
                 LIMIT 1";
        return (bool) $this->db->query($sql, [$dbName, $table, $index])->getFirstRow();
    }

    public function up()
    {
        $table = 'tbl_adjuntos';
        if (!$this->tableExists($table)) {
            // Por si corren esta migración en una BD incompleta
            return;
        }

        $fields = [];

        if (!$this->colExists($table, 'updated_at')) {
            $fields['updated_at'] = ['type' => 'DATETIME', 'null' => true];
        }
        if (!$this->colExists($table, 'deleted_at')) {
            $fields['deleted_at'] = ['type' => 'DATETIME', 'null' => true];
        }
        if (!$this->colExists($table, 'sha1')) {
            $fields['sha1'] = ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true];
        }
        if (!$this->colExists($table, 'storage_provider')) {
            // 'local' | 'gdrive'
            $fields['storage_provider'] = ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'local'];
        }
        if (!$this->colExists($table, 'drive_file_id')) {
            $fields['drive_file_id'] = ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true];
        }
        if (!$this->colExists($table, 'drive_web_view_link')) {
            $fields['drive_web_view_link'] = ['type' => 'VARCHAR', 'constraint' => 512, 'null' => true];
        }
        if (!$this->colExists($table, 'drive_web_content_link')) {
            $fields['drive_web_content_link'] = ['type' => 'VARCHAR', 'constraint' => 512, 'null' => true];
        }

        if (!empty($fields)) {
            $this->forge->addColumn($table, $fields);
        }

        // Índices (sólo si no existen)
        if (!$this->indexExists($table, 'ix_adj_sha1')) {
            $this->db->query("CREATE INDEX ix_adj_sha1 ON {$table} (sha1)");
        }
        if (!$this->indexExists($table, 'ix_adj_drive_file')) {
            $this->db->query("CREATE INDEX ix_adj_drive_file ON {$table} (drive_file_id)");
        }
        if (!$this->indexExists($table, 'ix_adj_storage_provider')) {
            $this->db->query("CREATE INDEX ix_adj_storage_provider ON {$table} (storage_provider)");
        }

        // Normaliza valores antiguos
        $this->db->query("UPDATE {$table} SET storage_provider='local' WHERE storage_provider IS NULL OR storage_provider=''");
    }

    public function down()
    {
        $table = 'tbl_adjuntos';
        if (!$this->tableExists($table)) {
            return;
        }

        foreach ([
            'drive_web_content_link',
            'drive_web_view_link',
            'drive_file_id',
            'storage_provider',
            'sha1',
            'deleted_at',
            'updated_at',
        ] as $c) {
            if ($this->colExists($table, $c)) {
                $this->forge->dropColumn($table, $c);
            }
        }
    }
}
