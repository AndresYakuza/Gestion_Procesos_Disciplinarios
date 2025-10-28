<?php
namespace App\Commands;

use App\Libraries\SorttimeClient;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;


// execute:  php spark workers:peek 01/10/2025 27/10/2025
// commands para traer los ultimos 10 registro de la bd mediantes la api con toda la informacion

class WorkersPeek extends BaseCommand
{
    protected $group       = 'workers';
    protected $name        = 'workers:peek';
    protected $description = 'Muestra campos y últimos 10 registros del informe masterWorkers.';
    protected $usage       = 'workers:peek [desde_dd/mm/yyyy] [hasta_dd/mm/yyyy]';
    protected $arguments   = ['desde_dd/mm/yyyy', 'hasta_dd/mm/yyyy'];

    public function run(array $params)
    {
        $nit   = env('sorttime.nit') ?: '802015186';
        $desde = $params[0] ?? date('01/m/Y'); // DD/MM/YYYY
        $hasta = $params[1] ?? date('d/m/Y');

        $isDate = fn(string $d) => (bool) preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $d);
        if (!$isDate($desde) || !$isDate($hasta)) {
            CLI::error('Formato de fecha inválido. Use DD/MM/YYYY. Ej: 01/10/2025 27/10/2025');
            return;
        }

        $client = new SorttimeClient();

        try {
            $rows = $client->getMasterWorkers($nit, $desde, $hasta);
        } catch (\Throwable $e) {
            CLI::error($e->getMessage());
            return;
        }

        if (!is_array($rows) || empty($rows)) {
            CLI::write('Sin datos para el rango especificado.');
            return;
        }

        CLI::write('Total filas: ' . count($rows), 'yellow');

        // Campos detectados (unión de todas las claves)
        $keys = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                foreach ($row as $k => $v) {
                    $keys[strtoupper((string) $k)] = true;
                }
            }
        }
        $fields = array_keys($keys);
        sort($fields);

        CLI::write('Campos detectados (' . count($fields) . '):', 'green');
        CLI::write(implode(', ', $fields));
        CLI::newLine();

        // Últimos 10 registros
        $last10 = array_slice($rows, -10);
        CLI::write('Últimos 10 registros:', 'green');
        $json = json_encode($last10, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        CLI::write($json);

        // Guardar también a archivo para revisión
        $out = WRITEPATH . 'logs/masterWorkers_peek_' . date('Ymd_His') . '.json';
        file_put_contents(
            $out,
            json_encode(
                ['desde' => $desde, 'hasta' => $hasta, 'total' => count($rows), 'fields' => $fields, 'last10' => $last10],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );
        CLI::write('Guardado en: ' . $out, 'blue');
    }
}
