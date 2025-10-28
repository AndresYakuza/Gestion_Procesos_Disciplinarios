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
    protected $description = 'Sincroniza empleados y contratos (ingresos/retiros) desde Sorttime.';
    protected $usage       = 'workers:sync [desde_dd/mm/yyyy] [hasta_dd/mm/yyyy]';
    protected $arguments   = ['desde_dd/mm/yyyy', 'hasta_dd/mm/yyyy'];

    public function run(array $params)
    {
        $nit   = env('sorttime.nit') ?: '802015186';
        $desde = $params[0] ?? date('01/m/Y');
        $hasta = $params[1] ?? date('d/m/Y');

        $isDate = fn(string $d) => (bool) preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $d);
        if (!$isDate($desde) || !$isDate($hasta)) {
            CLI::error('Formato inválido. Use DD/MM/YYYY.');
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
            CLI::write('Sin datos para el rango.');
            return;
        }

        $empleadoModel = new EmpleadoModel();
        $contratoModel = new EmpleadoContratoModel();
        $db = \Config\Database::connect();

        // helpers
        $normK = fn($s) => preg_replace('/[^A-Z0-9]/', '', strtoupper($s));
        $get   = function (array $r, string $key) use ($normK) {
            $want = $normK($key);
            foreach ($r as $k => $v) if ($normK((string)$k) === $want) return $v;
            return null;
        };
        $toDate = function ($v) {
            if ($v === null || $v === '') return null;
            if (($t = strtotime($v)) !== false) return date('Y-m-d', $t);
            $d = \DateTime::createFromFormat('d/m/Y', (string)$v);
            return $d ? $d->format('Y-m-d') : null;
        };
        $toDateTime = function ($v) {
            if ($v === null || $v === '') return null;
            if (($t = strtotime($v)) !== false) return date('Y-m-d H:i:s', $t);
            $d = \DateTime::createFromFormat('d/m/Y', (string)$v);
            return $d ? ($d->format('Y-m-d').' 00:00:00') : null;
        };
        $toDecimal = function ($v) {
            if ($v === null || $v === '') return null;
            $s = preg_replace('/[^\d,.-]/', '', (string)$v);
            $s = str_replace(['.', ','], ['', '.'], $s);
            return is_numeric($s) ? $s : null;
        };

        $db->transStart();

        $insEmp=0; $updEmp=0; $insCon=0; $updCon=0;
        $touched = [];

        foreach ($rows as $r) {
            $doc = trim((string)($get($r,'NUMERO_DE_DOCUMENTO') ?? ''));
            if ($doc === '') continue;

            // ---------- EMPLEADO ----------
            $empData = [
                'tipo_documento'   => $get($r,'TIPO_DOCUMENTO'),
                'numero_documento' => $doc,
                'nombre_completo'  => $get($r,'NOMBRE_COMPLETO'),
                'nombre_1'         => $get($r,'NOMBRE_1'),
                'nombre_2'         => $get($r,'NOMBRE_2'),
                'apellido_1'       => $get($r,'APELLIDO_1'),
                'apellido_2'       => $get($r,'APELLIDO_2'),

                'ciudad_expide'    => $get($r,'CIUDAD_EXPIDE'),
                'fecha_expide_cc'  => $toDate($get($r,'FECHA_EXPIDE_C.C.')),

                'fecha_nacimiento' => $toDate($get($r,'F._NACIMIENTO')),
                'ciudad_nac'       => $get($r,'CIUDAD_NAC'),
                'dpto_nac'         => $get($r,'DPTO_NAC'),
                'sexo'             => $get($r,'SEXO'),
                'estado_civil'     => $get($r,'ESTADO_CIVIL'),
                'grupo_sanguineo'  => $get($r,'GRUPO_SANGUINEO'),
                'grupo_social'     => $get($r,'G._SOCIAL'),
                'mujer_cf'         => $get($r,'MUJER_C.F.'),

                'eps'              => $get($r,'EPS'),
                'afp'              => $get($r,'AFP'),
                'fondo_cesantias'  => $get($r,'F._CESANTIAS'),
                'caja_compensacion'=> $get($r,'CAJA'),
                'arl'              => $get($r,'ARP'),

                'direccion_vive'   => $get($r,'DIRECCION_VIVE'),
                'barrio_vive'      => $get($r,'BARRIO_VIVE'),
                'estrato'          => $get($r,'ESTRATO'),
                'ciudad_vive'      => $get($r,'CIUDAD_VIVE'),
                'dpto_vive'        => $get($r,'DPTO_VIVE'),
                'profesion'        => $get($r,'PROFESION'),
                'avecindad'        => $get($r,'AVECINDAD'),

                'libreta_militar'      => $get($r,'LIBRETA_MILITAR'),
                'certificado_judicial' => $get($r,'CERTIFICADO_JUDICIAL'),
                'dto_lmil'             => $get($r,'DTO_LMIL'),

                'talla_camisa'     => $get($r,'TALLA_CAMISA'),
                'talla_pantalon'   => $get($r,'TALLA_PANTALON'),
                'talla_zapatos'    => $get($r,'TALLA_ZAPATOS'),
                'peso'             => $get($r,'PESO'),
                'estatura'         => $get($r,'ESTATURA'),

                'correo'           => $get($r,'CORREO'),
                'telefono'         => $get($r,'TELEFONO'),
                'celular'          => $get($r,'CELULAR'),
            ];

            $emp = $empleadoModel->where('numero_documento', $doc)->first();
            if ($emp) {
                $empleadoModel->update($emp['id'], $empData);
                $empleadoId = (int)$emp['id'];
                $updEmp++;
            } else {
                $empleadoId = (int)$empleadoModel->insert($empData, true);
                $insEmp++;
            }
            $touched[$empleadoId] = true;

            // ---------- CONTRATO (ingreso / retiro) ----------
            $contratoIdStr = trim((string)($get($r,'CONTRATO') ?? ''));
            if ($contratoIdStr === '') $contratoIdStr = trim((string)($get($r,'COD_NOMINA') ?? ''));
            if ($contratoIdStr === '') $contratoIdStr = trim((string)($get($r,'CODIGO') ?? ''));
            if ($contratoIdStr === '') continue;

            $estado     = (string)($get($r,'ESTADO') ?? '');
            $ingreso    = $toDate($get($r,'INGRESO'));
            $retiro     = $toDate($get($r,'RETIRO')); // puede venir o no
            $activoFlag = strtoupper($estado) === 'ACTIVO' && $retiro === null ? 1 : 0;

            $payload = [
                'empleado_id'     => $empleadoId,
                'contrato'        => $contratoIdStr,
                'cod_nomina'      => $get($r,'COD_NOMINA'),
                'sueldo'          => $toDecimal($get($r,'SUELDO')),
                'cargo_sige'      => $get($r,'CARGO_SIGE'),
                'cargo'           => $get($r,'CARGO'),
                'categoria'       => $get($r,'CATEGORIA'),
                'codigo'          => $get($r,'CODIGO'),
                'fecha_ingreso'   => $ingreso,
                'fecha_retiro'    => $retiro,
                'activo'          => $activoFlag,

                'nomina'          => $get($r,'NOMINA'),
                'tipo_contrato'   => $get($r,'TIPO'),
                'duracion'        => $get($r,'DURACION'),
                'nivel'           => $get($r,'NIVEL'),
                'fecha_sige'      => $toDate($get($r,'FECHA_SIGE')),
                'centro_costo'    => $get($r,'C._COSTO'),
                'dpto'            => $get($r,'DPTO'),
                'division'        => $get($r,'DIVISION'),
                'centro_trabajo'  => $get($r,'CENTRO_TRABAJO'),
                'tipo_ingreso'    => $get($r,'TIPO_INGRESO'),
                'periodo_pago'    => $get($r,'PERIODO_DE_PAGO'),
                'tipo_cuenta'     => $get($r,'TIPO_DE_CUENTA'),
                'banco'           => $get($r,'BANCO'),
                'cuenta'          => $get($r,'CUENTA'),
                'porcentaje_arl'  => $get($r,'PORCENTAJE'),
                'primera_vez'     => $get($r,'PRIMERA_VEZ'),
                'usuario_contrato'=> $get($r,'USUARIO_CONTRATO'),
                'ultimo_cambio'   => $toDateTime($get($r,'ULTIMO_CAMBIO')),
                'estado_contrato' => $estado,
                'cno'             => $get($r,'CNO'),
                'nombre_cno'      => $get($r,'NOMBRE_CNO'),
            ];

            $con = $contratoModel
                ->where(['empleado_id'=>$empleadoId,'contrato'=>$contratoIdStr])
                ->first();

            if ($con) {
                // Si tenemos "ULTIMO_CAMBIO" y el existente es más nuevo, no sobre-escribir
                $newTS = $payload['ultimo_cambio'];
                if (!empty($con['ultimo_cambio']) && !empty($newTS) && $con['ultimo_cambio'] > $newTS) {
                    // omite update
                } else {
                    $contratoModel->update($con['id'], $payload);
                    $updCon++;
                }
            } else {
                $contratoModel->insert($payload);
                $insCon++;
            }
        }

        // Recalcular activo del empleado (1 si tiene contrato activo sin retiro)
        if (!empty($touched)) {
            $ids = implode(',', array_map('intval', array_keys($touched)));
            $q = $db->query("
                SELECT empleado_id, MAX(CASE WHEN activo=1 AND fecha_retiro IS NULL THEN 1 ELSE 0 END) AS t
                FROM tbl_empleado_contratos
                WHERE empleado_id IN ($ids)
                GROUP BY empleado_id
            ");
            foreach ($q->getResultArray() as $row) {
                (new EmpleadoModel())->update((int)$row['empleado_id'], ['activo'=>(int)$row['t']]);
            }
        }

        $db->transComplete();

        if (!$db->transStatus()) {
            CLI::error('Transacción fallida.');
            return;
        }

        CLI::write("Sync OK  Empleados +{$insEmp}/~{$updEmp} | Contratos +{$insCon}/~{$updCon} | {$desde}..{$hasta}");
    }
}
