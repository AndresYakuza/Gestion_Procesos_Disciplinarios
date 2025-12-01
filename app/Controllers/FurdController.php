<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\HandlesAdjuntos;
use App\Models\FurdModel;
use App\Models\FurdFaltaModel;
use App\Models\FurdAdjuntoModel;
use App\Requests\FurdRegistroRequest;

class FurdController extends BaseController
{
    use HandlesAdjuntos;

    /** Formulario de registro (fase 1) */
    public function index()
    {
        // $faltas debe venir del catálogo (tbl_rit_faltas). Si ya lo cargas en otro lado, ajusta aquí.
        $faltas = model('RitFaltaModel')->orderBy('codigo')->findAll();
        return view('furd/create', compact('faltas'));
    }

    /** (opcional) detalle de un FURD por consecutivo */
    public function show(string $consecutivo)
    {
        $fm = new FurdModel();
        $furd = $fm->findByConsecutivo($consecutivo);
        if (!$furd) {
            return redirect()->to(site_url('/'))->with('errors', ['FURD no encontrado']);
        }

        $faltas = (new FurdFaltaModel())->listByFurd((int)$furd['id']);
        $adj    = (new FurdAdjuntoModel())->listAllByFurd((int)$furd['id']);
        return view('furd/show', compact('furd', 'faltas', 'adj'));
    }

    /** Crea FURD, genera consecutivo, guarda faltas (M:N) y adjuntos (fase=registro) */
    public function store()
    {
        helper(['filesystem']);

// 1) Subida temporal
$files = $this->request->getFiles()['evidencias'] ?? [];
$tmpDir = WRITEPATH . 'uploads/tmp';
if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);

$temp = []; // [{tmp, original, mime, size}]
if (!empty($files)) {
    foreach ($files as $file) {
        if ($file->isValid() && !$file->hasMoved()) {
            $newName  = $file->getRandomName();
            $original = $file->getClientName();
            $mime     = $file->getClientMimeType();
            $size     = (int) $file->getSize();

            $file->move($tmpDir, $newName);
            $temp[] = [
                'tmp'      => $newName,
                'original' => $original,
                'mime'     => $mime,
                'size'     => $size,
            ];
        }
    }

    // 👉 Solo si hay archivos nuevos, actualizamos la sesión
    if (!empty($temp)) {
        session()->set('temp_evidencias', array_column($temp, 'original')); // solo nombres para mostrar
        session()->set('temp_evidencias_meta', $temp);                      // metadata para mover a Drive
    }
}

// 2) Normalizar fecha + validar
$rawFecha = trim((string)$this->request->getPost('fecha_evento'));
if ($rawFecha === '') {
    $errors = ['fecha_evento' => 'La fecha del evento es obligatoria.'];

    if ($this->request->isAJAX()) {
        return $this->response
            ->setStatusCode(422)
            ->setJSON(['ok' => false, 'errors' => $errors]);
    }

    return redirect()->back()->with('errors', $errors)->withInput();
}

$fechaTexto = mb_strtolower($rawFecha, 'UTF-8');
$fechaTexto = strtr($fechaTexto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', ',' => '']);
$meses = [
    'enero' => 'january', 'febrero' => 'february', 'marzo' => 'march',
    'abril' => 'april',   'mayo'    => 'may',      'junio' => 'june',
    'julio' => 'july',    'agosto'  => 'august',   'septiembre' => 'september',
    'setiembre' => 'september', 'octubre' => 'october',
    'noviembre' => 'november',  'diciembre' => 'december'
];

if (preg_match('~^\s*(\d{1,2})/(\d{1,2})/(\d{4})\s*$~', $fechaTexto, $m)) {
    $timestamp = strtotime(sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]));
} else {
    $timestamp = strtotime(str_ireplace(array_keys($meses), array_values($meses), $fechaTexto));
}

if ($timestamp === false) {
    $errors = ['fecha_evento' => 'La fecha ingresada no es válida.'];

    if ($this->request->isAJAX()) {
        return $this->response
            ->setStatusCode(422)
            ->setJSON(['ok' => false, 'errors' => $errors]);
    }

    return redirect()->back()->with('errors', $errors)->withInput();
}

$fechaConvertida = date('Y-m-d', $timestamp);

$postData = $this->request->getPost();
$postData['fecha_evento'] = $fechaConvertida;

$validation = \Config\Services::validation();
$validation->setRules(\App\Requests\FurdRegistroRequest::rules(), \App\Requests\FurdRegistroRequest::messages());

if (!$validation->run($postData)) {
    $errors = $validation->getErrors();

    if ($this->request->isAJAX()) {
        // ❗ Aquí ahora respondemos JSON, sin redirect
        return $this->response
            ->setStatusCode(422)
            ->setJSON([
                'ok'     => false,
                'errors' => $errors,
            ]);
    }

    // Fallback clásico (sin JS)
    return redirect()->back()->with('errors', $errors)->withInput();
}


        // 3) Persistencia
        $db = db_connect();
        $db->transStart();
        try {
            $fm = new \App\Models\FurdModel();

            // Resolver empleado/proyecto
            $empleadoId = (int)($postData['empleado_id'] ?? 0);
            $proyectoId = (int)($postData['proyecto_id'] ?? 0);
            $cedulaNorm = preg_replace('/\D+/', '', (string)($postData['cedula'] ?? ''));

            if ($empleadoId <= 0 && $cedulaNorm !== '') {
                $rowEmp = $db->table('tbl_empleados')->select('id')->where('numero_documento', $cedulaNorm)->get()->getRowArray();
                if ($rowEmp) $empleadoId = (int)$rowEmp['id'];

                if ($empleadoId > 0) {
                    $rowCtr = $db->table('vw_empleado_contrato_activo')
                        ->select('proyecto_id, empresa_usuaria')
                        ->where('empleado_id', $empleadoId)->get()->getRowArray();
                    if ($rowCtr) {
                        $proyectoId = (int)($rowCtr['proyecto_id'] ?? 0);
                        if (empty($postData['empresa_usuaria']) && !empty($rowCtr['empresa_usuaria'])) {
                            $postData['empresa_usuaria'] = (string)$rowCtr['empresa_usuaria'];
                        }
                    }
                }
            }

            $payload = [
                'empleado_id'     => $empleadoId ?: null,
                'proyecto_id'     => $proyectoId ?: null,
                'cedula'          => (string)($postData['cedula'] ?? ''),
                'expedida_en'     => (string)($postData['expedida_en'] ?? ''),
                'empresa_usuaria' => (string)($postData['empresa_usuaria'] ?? ''),
                'nombre_completo' => (string)($postData['nombre_completo'] ?? ''),
                'correo'          => (string)($postData['correo'] ?? ''),
                'correo_cliente'  => (string)($postData['correo_cliente'] ?? ''), 
                'fecha_evento'    => $fechaConvertida,
                'hora_evento'     => (string)($postData['hora'] ?? ''),
                'superior'        => (string)($postData['superior'] ?? ''),
                'hecho'           => (string)($postData['hecho'] ?? ''),
                'estado'          => 'registro',
            ];

            $id = (int)$fm->insert($payload, true);
            if ($id <= 0) {
                throw new \RuntimeException('No se pudo crear el FURD (ID=0).');
            }

            $consecutivo = sprintf('PD-%06d', $id);
            $fm->update($id, ['consecutivo' => $consecutivo]);

            // Faltas
            $faltas = (array)($postData['faltas'] ?? []);
            if ($id > 0 && !empty($faltas)) {
                (new \App\Models\FurdFaltaModel())->syncFaltas($id, $faltas);
            }

            // 4) Adjuntos -> Google Drive
            $meta = session('temp_evidencias_meta') ?? [];
            if ($meta) {
                $root = (string) env('GDRIVE_ROOT', 'FURD');
                $g    = new \App\Libraries\GDrive();

                // Carpeta: FURD/AAAA/{id}
                $folderPath = rtrim($root, '/') . '/' . date('Y') . '/' . $id;
                $parentId   = $g->ensurePath($folderPath);

                $am = new \App\Models\FurdAdjuntoModel();

                foreach ($meta as $m) {
                    $src  = $tmpDir . '/' . $m['tmp'];
                    if (!is_file($src)) continue;

                    // hash para dedupe opcional
                    $sha1 = @sha1_file($src) ?: null;

                    $up = $g->upload($src, $m['original'], $m['mime'], $parentId);

                    // limpia tmp
                    @unlink($src);

                    $am->insert([
                        'origen'                => 'furd',
                        'origen_id'             => $id,
                        'fase'                  => 'registro',
                        'nombre_original'       => $m['original'],
                        'ruta'                  => $folderPath . '/' . $m['original'], // ruta "lógica"
                        'mime'                  => $m['mime'],
                        'tamano_bytes'          => (int)$m['size'],
                        'sha1'                  => $sha1,
                        'storage_provider'      => 'gdrive',
                        'drive_file_id'         => $up['id'],
                        'drive_web_view_link'   => $up['webViewLink'] ?? null,
                        'drive_web_content_link' => $up['webContentLink'] ?? null,
                        'created_at'            => date('Y-m-d H:i:s'),
                    ]);
                }

                session()->remove('temp_evidencias');
                session()->remove('temp_evidencias_meta');
            }

            $db->transComplete();

            $mensajeOk = "Registro creado con consecutivo {$consecutivo}. Continúa con la Citación.";

            // 👉 Si la petición viene por AJAX (tu XHR del formulario)
            if ($this->request->isAJAX()) {
                // Guardamos flashdata para el PRÓXIMO request real (GET /seguimiento)
                session()->setFlashdata('ok', $mensajeOk);
                session()->setFlashdata('consecutivo', $consecutivo);

                return $this->response->setJSON([
                    'ok'         => true,
                    'redirectTo' => site_url('seguimiento'),
                ]);
            }

            // 👉 Fallback por si alguien envía el formulario sin JS
            return redirect()
                ->to(site_url('seguimiento'))
                ->with('ok', $mensajeOk)
                ->with('consecutivo', $consecutivo);
        } catch (\Throwable $e) {
            log_message('error', 'Error al guardar FURD: ' . $e->getMessage());
            $db->transRollback();
            return redirect()->back()->with('errors', [$e->getMessage()])->withInput();
        }
    }





    /** (opcional) elimina todo el proceso */
    public function destroy(int $id)
    {
        $fm = new FurdModel();
        $row = $fm->find($id);
        if (!$row) return redirect()->back()->with('errors', ['Proceso no existe']);

        $db = db_connect();
        $db->transStart();
        try {
            // Elimina pivote de faltas y adjuntos
            (new FurdFaltaModel())->deleteByFurd($id);
            (new FurdAdjuntoModel())->deleteByFurd($id);
            // Elimina fases si las manejas en tablas separadas
            db_connect()->table('tbl_furd_citacion')->where('furd_id', $id)->delete();
            db_connect()->table('tbl_furd_descargos')->where('furd_id', $id)->delete();
            db_connect()->table('tbl_furd_soporte')->where('furd_id', $id)->delete();
            db_connect()->table('tbl_furd_decision')->where('furd_id', $id)->delete();
            // Elimina el proceso
            $fm->delete($id);

            $db->transComplete();
            return redirect()->to(site_url('seguimiento'))->with('ok', 'Proceso eliminado');
        } catch (\Throwable $e) {
            $db->transRollback();
            return redirect()->back()->with('errors', [$e->getMessage()]);
        }
    }

    /** (AJAX) Adjuntar/Desasociar faltas si decides hacerlo en la vista vía XHR */
    public function attachFalta(int $furdId)
    {
        $faltaId = (int)$this->request->getPost('falta_id');
        if (!$faltaId) return $this->response->setJSON(['ok' => false, 'msg' => 'Falta inválida']);
        (new FurdFaltaModel())->attach($furdId, $faltaId);
        return $this->response->setJSON(['ok' => true]);
    }

    public function detachFalta(int $furdId, int $faltaId)
    {
        (new FurdFaltaModel())->detach($furdId, $faltaId);
        return $this->response->setJSON(['ok' => true]);
    }

    public function adjuntos()
    {
        $consec = (string) $this->request->getGet('consecutivo');

        // Si no mandan consecutivo, devolvemos array vacío
        if ($consec === '') {
            return $this->response->setJSON([]);
        }

        $furd = (new FurdModel())->findByConsecutivo($consec);
        if (!$furd) {
            // El JS ya está preparado para recibir [] en caso de error
            return $this->response->setJSON([]);
        }

        // Por defecto, trae adjuntos de la fase citación, que es lo que quieres mostrar en descargos.
        $fase = (string) $this->request->getGet('fase') ?: 'citacion';

        $rows = (new FurdAdjuntoModel())->listByFase((int) $furd['id'], $fase);

        // Normalizamos a [{ nombre, mime, tamano, url }]
        $out = array_map(static function (array $row) {
            return [
                'nombre' => $row['nombre']    ?? $row['filename']    ?? 'archivo',
                'mime'   => $row['mime']      ?? $row['mimetype']    ?? '',
                'tamano' => $row['tamano']    ?? $row['size']        ?? null,
                'url'    => base_url('adjuntos/' . $row['id'] . '/open'),
                // si tu tabla tiene otros campos, los puedes mapear aquí
            ];
        }, $rows ?? []);

        return $this->response->setJSON($out);
    }
}
