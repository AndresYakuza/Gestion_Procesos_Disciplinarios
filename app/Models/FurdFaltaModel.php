<?php namespace App\Models;

use CodeIgniter\Model;

class FurdFaltaModel extends Model
{
    protected $table         = 'tbl_furd_faltas';
    protected $primaryKey    = 'id';

    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    // ⬅️ importante: coincide con tu FK (falta_id)
    protected $allowedFields = ['furd_id', 'falta_id'];

    /**
     * Sincroniza faltas de un FURD (acepta IDs o códigos).
     *
     * @throws \InvalidArgumentException si $furdId <= 0
     */
    // app/Models/FurdFaltaModel.php
    // app/Models/FurdFaltaModel.php
    // app/Models/FurdFaltaModel.php (solo el método)
    public function syncFaltas(int $furdId, array $faltas): void
    {
        if ($furdId <= 0) {
            throw new \InvalidArgumentException('furd_id inválido para sincronizar faltas.');
        }

        // Normaliza IDs: enteros > 0 y únicos
        $ids = array_values(array_unique(
            array_filter(array_map('intval', $faltas), static fn($v) => $v > 0)
        ));

        // Limpia asociaciones previas
        $this->builder()->where('furd_id', $furdId)->delete();

        if (empty($ids)) return;

        // Solo inserta las faltas que EXISTEN en el catálogo
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "
            INSERT INTO {$this->table} (furd_id, falta_id, created_at, updated_at)
            SELECT ?, r.id, NOW(), NOW()
            FROM tbl_rit_faltas r
            WHERE r.id IN ($placeholders)
        ";
        $this->db->query($sql, array_merge([$furdId], $ids));
    }



    /** Lista registros pivot del FURD */
    public function listByFurd(int $furdId): array
    {
        return $this->where('furd_id', $furdId)->findAll();
    }

    /** Elimina todas las faltas asociadas al FURD */
    public function deleteByFurd(int $furdId): void
    {
        $this->where('furd_id', $furdId)->delete();
    }

    /** Agrega una falta (ID) si no existe ya */
    public function attach(int $furdId, int $faltaId): void
    {
        if ($furdId <= 0 || $faltaId <= 0) {
            throw new \InvalidArgumentException('IDs inválidos en attach().');
        }

        $exists = $this->where(['furd_id' => $furdId, 'falta_id' => $faltaId])->first();
        if (!$exists) {
            $this->insert(['furd_id' => $furdId, 'falta_id' => $faltaId]);
        }
    }

    /** Quita una falta específica del FURD */
    public function detach(int $furdId, int $faltaId): void
    {
        $this->where(['furd_id' => $furdId, 'falta_id' => $faltaId])->delete();
    }
}
