<?php

namespace App\Models;

use CodeIgniter\Model;

class FurdAdjuntoModel extends Model
{
    protected $table      = 'tbl_adjuntos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';
    protected $dateFormat     = 'datetime';

    protected $allowedFields = [
        'origen','origen_id','fase',
        'nombre_original','ruta','mime','tamano_bytes',
        'sha1','storage_provider',
        'drive_file_id','drive_web_view_link','drive_web_content_link',
        'audit_created_by',
    ];

    public function listAllByFurd(int $furdId): array
    {
        return $this->where(['origen' => 'furd', 'origen_id' => $furdId])
                    ->orderBy('id','DESC')->findAll();
    }

    public function listByFase(int $furdId, string $fase): array
    {
        return $this->where(['origen' => 'furd', 'origen_id' => $furdId, 'fase' => $fase])
                    ->orderBy('id','DESC')->findAll();
    }

    public function deleteAndUnlink(int $adjuntoId): bool
    {
        $row = $this->find($adjuntoId);
        if (! $row) return false;

        if (($row['storage_provider'] ?? 'local') === 'gdrive') {
            try {
                $fileId = (string) ($row['drive_file_id'] ?? '');
                if ($fileId !== '') {
                    $g = new \App\Libraries\GDrive();
                    $g->delete($fileId);
                }
            } catch (\Throwable $e) {
                log_message('error', 'GDrive delete error: '.$e->getMessage());
            }
        } else {
            // local (compatibilidad)
            $abs = WRITEPATH . 'uploads/' . ltrim((string)($row['ruta'] ?? ''), '/\\');
            if (is_file($abs)) { @unlink($abs); }
        }

        return (bool) $this->delete($adjuntoId);
    }

    public function deleteByFurd(int $furdId): void
    {
        $rows = $this->where(['origen' => 'furd', 'origen_id' => $furdId])->findAll();
        foreach ($rows as $r) {
            $this->deleteAndUnlink((int)$r['id']);
        }
    }
}
