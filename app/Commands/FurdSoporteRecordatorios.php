<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\FurdSoporteModel;
use App\Models\FurdModel;
use App\Services\FurdMailService;

class FurdSoporteRecordatorios extends BaseCommand
{
    protected $group       = 'FURD';
    protected $name        = 'furd:recordatorios-soporte';
    protected $description = 'Envía recordatorios al cliente y archiva procesos vencidos de soporte.';

    public function run(array $params)
    {
        $now     = new \DateTimeImmutable('now');
        $fiveAgo = $now->modify('-5 days')->format('Y-m-d H:i:s');
        $tenAgo  = $now->modify('-10 days')->format('Y-m-d H:i:s');

        $soporteModel = new FurdSoporteModel();
        $furdModel    = new FurdModel();
        $mailService  = new FurdMailService(service('email'));

        // ---------- 1) Recordatorios a los 5 días ----------
        $recordatorios = $soporteModel
            ->select([
                'tbl_furd_soporte.*',
                'f.id               AS furd_id',
                'f.consecutivo      AS furd_consecutivo',
                'f.nombre_completo  AS furd_nombre_completo',
                'f.cedula           AS furd_cedula',
                'f.correo           AS furd_correo',
                'f.correo_cliente   AS furd_correo_cliente',
                'f.proyecto_id      AS furd_proyecto_id',
                'f.empresa_usuaria  AS furd_empresa_usuaria',
            ])
            ->join('tbl_furd f', 'f.id = tbl_furd_soporte.furd_id')
            ->where('tbl_furd_soporte.cliente_estado', 'pendiente')
            ->where('tbl_furd_soporte.cliente_respondido_at IS NULL', null, false)
            ->where('f.estado', 'soporte')
            ->where('tbl_furd_soporte.notificado_cliente_at <=', $fiveAgo)
            ->where('tbl_furd_soporte.recordatorio_cliente_at IS NULL', null, false)
            ->where('tbl_furd_soporte.auto_archivado_at IS NULL', null, false)
            ->findAll();

        foreach ($recordatorios as $row) {
            // separar “vista FURD”
            $furd = [
                'id'             => (int) $row['furd_id'],
                'consecutivo'    => $row['furd_consecutivo'],
                'nombre_completo'=> $row['furd_nombre_completo'],
                'cedula'         => $row['furd_cedula'],
                'correo'         => $row['furd_correo'],
                'correo_cliente' => $row['furd_correo_cliente'],
                'proyecto_id'    => $row['furd_proyecto_id'],
                'empresa_usuaria'=> $row['furd_empresa_usuaria'],
            ];

            // y los datos del soporte tal cual
            $soporte = $row;

            $mailService->notifySoporteRecordatorio($furd, $soporte);
            CLI::write("Recordatorio enviado FURD #{$furd['id']}", 'green');
        }

        // ---------- 2) Auto-archivo a los 10 días ----------
        $porArchivar = $soporteModel
            ->select([
                'tbl_furd_soporte.*',
                'f.id               AS furd_id',
                'f.consecutivo      AS furd_consecutivo',
                'f.nombre_completo  AS furd_nombre_completo',
                'f.cedula           AS furd_cedula',
                'f.correo           AS furd_correo',
                'f.correo_cliente   AS furd_correo_cliente',
                'f.proyecto_id      AS furd_proyecto_id',
                'f.empresa_usuaria  AS furd_empresa_usuaria',
            ])
            ->join('tbl_furd f', 'f.id = tbl_furd_soporte.furd_id')
            ->where('tbl_furd_soporte.cliente_estado', 'pendiente')
            ->where('tbl_furd_soporte.cliente_respondido_at IS NULL', null, false)
            ->where('f.estado', 'soporte')
            ->where('tbl_furd_soporte.notificado_cliente_at <=', $tenAgo)
            ->where('tbl_furd_soporte.auto_archivado_at IS NULL', null, false)
            ->findAll();

        foreach ($porArchivar as $row) {
            $furd = [
                'id'             => (int) $row['furd_id'],
                'consecutivo'    => $row['furd_consecutivo'],
                'nombre_completo'=> $row['furd_nombre_completo'],
                'cedula'         => $row['furd_cedula'],
                'correo'         => $row['furd_correo'],
                'correo_cliente' => $row['furd_correo_cliente'],
                'proyecto_id'    => $row['furd_proyecto_id'],
                'empresa_usuaria'=> $row['furd_empresa_usuaria'],
            ];

            $soporte = $row;

            $mailService->notifySoporteAutoArchivado($furd, $soporte);
            CLI::write("Proceso archivado FURD #{$furd['id']}", 'yellow');
        }
    }
}
