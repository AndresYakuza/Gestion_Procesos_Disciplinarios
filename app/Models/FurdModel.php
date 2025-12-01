<?php

namespace App\Models;

use CodeIgniter\Model;

class FurdModel extends Model
{
    protected $table      = 'tbl_furd';
    protected $primaryKey = 'id';

    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    // ✅ Campos permitidos según la estructura real
    protected $allowedFields = [
        'consecutivo',
        'estado',
        'empleado_id',       // ← renombrado correctamente
        'cedula',
        'expedida_en',
        'empresa_usuaria',
        'nombre_completo',
        'correo',
        'correo_cliente',
        'fecha_evento',
        'hora_evento',
        'superior',
        'hecho',
        'proyecto_id'
    ];

    /**
     * Buscar un FURD por consecutivo
     */
    public function findByConsecutivo(string $consecutivo): ?array
    {
        return $this->where('consecutivo', $consecutivo)->first();
    }

    /**
     * Generar consecutivo único: PD-000001, PD-000002, ...
     */
    public function nextConsecutivo(): string
    {
        $prefix = 'PD-';

        // Busca el último FURD con consecutivo que empiece por PD-
        $last = $this->select('consecutivo')
                    ->like('consecutivo', $prefix, 'after')
                    ->orderBy('id', 'DESC')
                    ->first();

        $num = 1;
        if ($last && !empty($last['consecutivo'])) {
            // Espera formato PD-000123
            $num = (int)substr($last['consecutivo'], strlen($prefix)) + 1;
        }

        return $prefix . str_pad((string)$num, 6, '0', STR_PAD_LEFT);
    }
}
