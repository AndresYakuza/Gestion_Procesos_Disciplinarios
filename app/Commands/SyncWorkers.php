<?php
namespace App\Commands;

use App\Libraries\SorttimeClient;
use App\Models\EmpleadoModel;
use App\Models\EmpleadoContratoModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SyncWorkers extends BaseCommand
{
    protected $group       = 'workers';
    protected $name        = 'workers:sync';
    protected $description = 'Sincroniza empleados desde Sorttime (Informe Maestro de Trabajadores).';
    protected $usage       = 'workers:sync [desde_dd/mm/yyyy] [hasta_dd/mm/yyyy]';
    protected $arguments   = ['desde_dd/mm/yyyy', 'hasta_dd/mm/yyyy]'];

    public function run(array $params)
    {
        $nit   = env('sorttime.nit') ?: '802015186-6';
        $desde = $params[0] ?? date('01/m/Y'); // DD/MM/YYYY
        $hasta = $params[1] ?? date('t/m/Y');

        $isDate = fn(string $d) => (bool) preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $d);
        if (!$isDate($desde) || !$isDate($hasta)) {
            CLI::error('Formato de fecha inválido. Use DD/MM/YYYY.');
            return;
        }

        $client = new SorttimeClient();

        try {
            $rows = $client->getMasterWorkers($nit, $desde, $hasta);
        } catch (\Throwable $e) {
            CLI::error($e->getMessage());
            return;
        }

        if (!is_array($rows) || count($rows) === 0) {
            CLI::write('Sin datos para el rango especificado.');
            return;
        }

        $empleadoModel  = new EmpleadoModel();
        $contratoModel  = new EmpleadoContratoModel();
        $db             = \Config\Database::connect();

        // Helper para obtener valor por clave sin importar mayúsculas
        $get = function (array $r, string $k) {
            if (array_key_exists($k, $r)) return $r[$k];
            $kl = strtolower($k);
            foreach ($r as $key => $val) {
                if (strtolower($key) === $kl) return $val;
            }
            return null;
        };

        // Parse de fechas DD/MM/YYYY o YYYY-MM-DD
        $toDate = function ($v) {
            if (!$v) return null;
            $v = trim((string) $v);
            if ($v === '') return null;
            $d = \DateTime::createFromFormat('d/m/Y', $v) ?: \DateTime::createFromFormat('Y-m-d', $v);
            return $d ? $d->format('Y-m-d') : null;
        };

        $db->transStart();

        $insertadosEmp = 0; $actualizadosEmp = 0;
        $insertadosCon = 0; $actualizadosCon = 0;
        $touchedEmps   = [];

        foreach ($rows as $r) {
            // ----------- EMPLEADO -----------
            $numDoc = trim((string) ($get($r, 'NUMERO_DE_DOCUMENTO') ?? ''));
            if ($numDoc === '') continue;

            $empleadoPayload = [
                'numero_documento' => $numDoc,
                'tipo_documento'   => $get($r, 'TIPO_DOCUMENTO'),
                'nombre_completo'  => $get($r, 'NOMBRE_COMPLETO'),
                'correo'           => $get($r, 'CORREO'),
                'telefono'         => $get($r, 'TELEFONO'),
                'celular'          => $get($r, 'CELULAR'),
                'barrio_vive'      => $get($r, 'BARRIO_VIVE'),
                'estrato'          => ($e = $get($r, 'ESTRATO')) !== null && $e !== '' ? (int) $e : null,
                'ciudad_vive'      => $get($r, 'CIUDAD_VIVE'),
                'dpto_vive'        => $get($r, 'DPTO_VIVE'),
                'profesion'        => $get($r, 'PROFESION'),
            ];

            $emp = $empleadoModel->where('numero_documento', $numDoc)->first();
            if ($emp) {
                $empleadoModel->update($emp['id'], $empleadoPayload);
                $empleadoId = (int) $emp['id'];
                $actualizadosEmp++;
            } else {
                $empleadoId = (int) $empleadoModel->insert($empleadoPayload, true);
                $insertadosEmp++;
            }
            $touchedEmps[$empleadoId] = true;

            // ----------- CONTRATO -----------
            // contrato puede venir en CONTRATO; fallback a COD_NOMINA o CODIGO
            $contrato = trim((string) ($get($r, 'CONTRATO') ?? ''));
            if ($contrato === '') $contrato = trim((string) ($get($r, 'COD_NOMINA') ?? ''));
            if ($contrato === '') $contrato = trim((string) ($get($r, 'CODIGO') ?? ''));

            if ($contrato === '') {
                // sin contrato identificable, no crear registro de contrato
                continue;
            }

            $sueldoRaw = (string) ($get($r, 'SUELDO') ?? '');
            $sueldo    = $sueldoRaw === ''
                ? null
                : (float) str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.-]/', '', $sueldoRaw));

            $fechaIngreso = $toDate($get($r, 'FECHA_INGRESO') ?? $get($r, 'INGRESO'));
            $fechaRetiro  = $toDate($get($r, 'FECHA_RETIRO') ?? $get($r, 'RETIRO'));

            $contratoPayload = [
                'empleado_id'   => $empleadoId,
                'contrato'      => $contrato,
                'cod_nomina'    => $get($r, 'COD_NOMINA'),
                'proyecto_id'   => null, // mapéalo si tu API trae un código de proyecto
                'sueldo'        => $sueldo,
                'cargo_sige'    => $get($r, 'CARGO_SIGE'),
                'cargo'         => $get($r, 'CARGO'),
                'categoria'     => $get($r, 'CATEGORIA'),
                'codigo'        => $get($r, 'CODIGO'),
                'fecha_ingreso' => $fechaIngreso,
                'fecha_retiro'  => $fechaRetiro,
                'activo'        => $fechaRetiro ? 0 : 1,
            ];

            $con = $contratoModel->where([
                'empleado_id' => $empleadoId,
                'contrato'    => $contrato,
            ])->first();

            if ($con) {
                $contratoModel->update($con['id'], $contratoPayload);
                $actualizadosCon++;
            } else {
                $contratoModel->insert($contratoPayload);
                $insertadosCon++;
            }
        }

        // ----------- Actualizar estado activo del empleado (si tiene contrato activo) -----------
        if (!empty($touchedEmps)) {
            $ids = array_map('intval', array_keys($touchedEmps));
            $idList = implode(',', $ids);

            // Para cada empleado, set activo = 1 si existe contrato activo
            $q = $db->query("
                SELECT empleado_id, MAX(CASE WHEN activo=1 THEN 1 ELSE 0 END) AS tiene_activo
                FROM tbl_empleado_contratos
                WHERE empleado_id IN ($idList)
                GROUP BY empleado_id
            ");
            foreach ($q->getResultArray() as $row) {
                $empleadoModel->update((int)$row['empleado_id'], ['activo' => (int)$row['tiene_activo']]);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            CLI::error('Transacción fallida. No se guardaron los cambios.');
            return;
        }

        CLI::write(
            "OK empleados: +{$insertadosEmp}/~{$actualizadosEmp} | contratos: +{$insertadosCon}/~{$actualizadosCon} ".
            "| Rango {$desde}..{$hasta}"
        );
    }
}
