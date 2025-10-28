<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProyectoAliasSeeder extends Seeder
{
    public function run()
    {
        helper('text');

        $norm = function (string $s): string {
            $s = mb_strtoupper(trim($s), 'UTF-8');
            $s = strtr($s, [
                'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
                'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U','ü'=>'U','ñ'=>'N'
            ]);
            // quita todo lo que no sea letra/numero/espacio
            $s = preg_replace('/[^A-Z0-9 ]+/', ' ', $s);
            // colapsa espacios
            $s = preg_replace('/\s+/', ' ', $s);
            return trim($s);
        };

        $db = \Config\Database::connect();
        $proyectos = $db->table('tbl_proyectos')->select('id, nombre')->get()->getResultArray();

        foreach ($proyectos as $p) {
            $alias = $p['nombre'];
            $aliasNorm = $norm($alias);

            // upsert simple
            $exists = $db->table('tbl_proyecto_alias')->where('alias_norm', $aliasNorm)->get()->getFirstRow('array');
            if ($exists) {
                // si ya existe, garantizamos que apunte al proyecto correcto
                if ((int)$exists['proyecto_id'] !== (int)$p['id']) {
                    $db->table('tbl_proyecto_alias')
                       ->where('id', $exists['id'])
                       ->update(['proyecto_id' => $p['id'], 'alias' => $alias, 'updated_at' => date('Y-m-d H:i:s')]);
                }
            } else {
                $db->table('tbl_proyecto_alias')->insert([
                    'proyecto_id' => $p['id'],
                    'alias'       => $alias,
                    'alias_norm'  => $aliasNorm,
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
