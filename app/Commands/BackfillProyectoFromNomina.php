<?php
namespace App\Commands;

use App\Models\ProyectoAliasModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class BackfillProyectoFromNomina extends BaseCommand
{
    protected $group       = 'projects';
    protected $name        = 'projects:backfill';
    protected $description = 'Resuelve proyecto_id en contratos usando el alias de nomina.';
    protected $usage       = 'projects:backfill';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $aliasModel = new ProyectoAliasModel();

        $norm = function (?string $s): ?string {
            if ($s === null) return null;
            $s = mb_strtoupper(trim($s), 'UTF-8');
            $s = strtr($s, [
                'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
                'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U','ü'=>'U','ñ'=>'N'
            ]);
            $s = preg_replace('/[^A-Z0-9 ]+/', ' ', $s);
            $s = preg_replace('/\s+/', ' ', $s);
            $s = trim($s);
            return $s === '' ? null : $s;
        };

        $rows = $db->table('tbl_empleado_contratos')
            ->select('id, nomina, proyecto_id')
            ->get()->getResultArray();

        $updated = 0; $skipped = 0;

        foreach ($rows as $r) {
            if (!empty($r['proyecto_id'])) { $skipped++; continue; }
            $n = $norm($r['nomina'] ?? null);
            if ($n === null) { $skipped++; continue; }

            $alias = $aliasModel->where('alias_norm', $n)->first();
            if ($alias) {
                $db->table('tbl_empleado_contratos')
                   ->where('id', $r['id'])
                   ->update(['proyecto_id' => $alias['proyecto_id']]);
                $updated++;
            } else {
                // crea un alias faltante rápido apuntando a un proyecto "desconocido" si existiera
                // o bien saltar y reportar
            }
        }

        CLI::write("Backfill terminado. Actualizados: {$updated} | Omitidos: {$skipped}");
    }
}
