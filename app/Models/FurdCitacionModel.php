<?php namespace App\Models;

use CodeIgniter\Model;

class FurdCitacionModel extends Model
{
    protected $table         = 'tbl_furd_citacion';
    protected $primaryKey    = 'id';

    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    protected $allowedFields = [
        'furd_id',
        'numero',
        'fecha_evento',
        'hora',
        'medio',
        'motivo',
        'motivo_recitacion',
        'reprogramada_de_id',
    ];

    /**
     * Última citación registrada para un FURD
     */
    public function findByFurd(int $furdId): ?array
    {
        return $this->where('furd_id', $furdId)
            ->orderBy('numero', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * Historial completo de citaciones para un FURD
     */
    public function listByFurd(int $furdId): array
    {
        return $this->where('furd_id', $furdId)
            ->orderBy('numero', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Última citación encontrada por consecutivo del FURD
     */
    public function findByConsecutivo(string $consecutivo): ?array
    {
        return $this->select('tbl_furd_citacion.*')
            ->join('tbl_furd', 'tbl_furd.id = tbl_furd_citacion.furd_id')
            ->where('tbl_furd.consecutivo', $consecutivo)
            ->orderBy('tbl_furd_citacion.numero', 'DESC')
            ->orderBy('tbl_furd_citacion.id', 'DESC')
            ->first();
    }

    public function listByFurdWithNotificaciones(int $furdId): array
    {
        $rows = $this->where('furd_id', $furdId)
            ->orderBy('numero', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        if (empty($rows)) return [];

        $notifModel = new \App\Models\FurdCitacionNotificacionModel();

        foreach ($rows as &$r) {
            $hist = $notifModel->listByCitacion((int)$r['id']);
            $r['notificaciones'] = array_map(static function(array $n){
                return [
                    'id'           => (int)$n['id'],
                    'canal'        => $n['canal'] ?? 'email',
                    'destinatario' => $n['destinatario'] ?? '',
                    'estado'       => $n['estado'] ?? '',
                    'notificado_at'=> $n['notificado_at'] ?? null,
                    'mensaje_id'   => $n['mensaje_id'] ?? null,
                    'error'        => $n['error'] ?? null,
                ];
            }, $hist);
        }

        return $rows;
    }

}
