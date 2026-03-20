<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\HandlesAdjuntos;
use App\Models\FurdModel;
use App\Models\FurdFaltaModel;
use App\Models\FurdAdjuntoModel;
use App\Requests\FurdRegistroRequest;
use CodeIgniter\Exceptions\PageNotFoundException;


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
            'enero' => 'january',
            'febrero' => 'february',
            'marzo' => 'march',
            'abril' => 'april',
            'mayo'    => 'may',
            'junio' => 'june',
            'julio' => 'july',
            'agosto'  => 'august',
            'septiembre' => 'september',
            'setiembre' => 'september',
            'octubre' => 'october',
            'noviembre' => 'november',
            'diciembre' => 'december'
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
        $validation->setRules(
            \App\Requests\FurdRegistroRequest::rules(),
            \App\Requests\FurdRegistroRequest::messages()
        );

        if (!$validation->withRequest($this->request)->run($postData)) {

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
                $root = trim((string) env('GDRIVE_ROOT', 'FURD'), '/');
                $g    = new \App\Libraries\GDrive();

                $basePath      = $root . '/' . date('Y') . '/' . $consecutivo;
                $furdPath      = $basePath . '/FURD';
                $adjuntosPath  = $furdPath . '/Adjuntos';

                $parentId = $g->ensurePath($adjuntosPath);

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
                        'ruta'                  => $adjuntosPath . '/' . $m['original'],
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

            $faltasRows = $this->getFaltasByFurdId($id);
            $adjuntosRows = (new \App\Models\FurdAdjuntoModel())->listByFase($id, 'registro');

            $formatoMeta = null;
            try {
                $googleFormat = new \App\Services\FurdGoogleFormatService();
                $formatoMeta = $googleFormat->generar(
                    array_merge($payload, [
                        'id' => $id,
                        'consecutivo' => $consecutivo,
                    ]),
                    $faltasRows,
                    $adjuntosRows
                );

                // opcional: guardar metadata del PDF en tbl_furd_adjuntos
                (new \App\Models\FurdAdjuntoModel())->insert([
                    'origen'                 => 'furd',
                    'origen_id'              => $id,
                    'fase'                   => 'formato',
                    'nombre_original'        => $formatoMeta['pdf_name'],
                    'ruta'                   => ($formatoMeta['consecutivo_folder_path'] ?? '') . '/FURD/Formato del reporte disciplinario/' . $formatoMeta['pdf_name'],
                    'mime'                   => 'application/pdf',
                    'tamano_bytes'           => null,
                    'sha1'                   => null,
                    'storage_provider'       => 'gdrive',
                    'drive_file_id'          => $formatoMeta['pdf_file_id'],
                    'drive_web_view_link'    => $formatoMeta['pdf_web_view_link'] ?? null,
                    'drive_web_content_link' => $formatoMeta['pdf_web_content_link'] ?? null,
                    'created_at'             => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
                log_message('error', 'Error generando formato FURD en Google: ' . $e->getMessage());
            }

            $db->transComplete();

            // 🔔 Enviar correos de notificación (trabajador + procesos disciplinarios)
            try {
                $this->enviarCorreosRegistroFurd($id, $formatoMeta);
            } catch (\Throwable $mailEx) {
                // No romper el flujo si el correo falla; solo se deja log
                log_message('error', 'Error enviando correos de FURD creado (ID: ' . $id . '): ' . $mailEx->getMessage());
            }

            $mensajeOk = "Registro creado con consecutivo {$consecutivo}. Continúa con la Citación.";


            // 👉 Si la petición viene por AJAX (tu XHR del formulario)
            if ($this->request->isAJAX()) {
                // Guardamos flashdata para el PRÓXIMO request real (GET /seguimiento)
                session()->setFlashdata('ok', $mensajeOk);
                session()->setFlashdata('consecutivo', $consecutivo);

                return $this->response->setJSON([
                    'ok'            => true,
                    'redirectTo'    => site_url('seguimiento'),
                    'drivePdfUrl'   => $formatoMeta['pdf_web_view_link'] ?? null,
                    'consecutivo'   => $consecutivo,
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

    /**
     * Envía correos cuando se crea un FURD:
     *  - Al trabajador (si hay correo)
     *  - Al área de procesos disciplinarios
     */
    private function enviarCorreosRegistroFurd(int $furdId, ?array $formatoMeta = null): void
    {
        $db = db_connect();

        // Datos del FURD + empleado + proyecto
        $furd = $db->table('tbl_furd f')
            ->select("
            f.*,
            e.numero_documento AS cedula_trabajador,
            e.nombre_completo  AS nombre_trabajador,
            e.correo           AS correo_trabajador,
            f.empresa_usuaria  AS empresa,
            p.nombre           AS proyecto
        ")
            ->join('tbl_empleados e', 'e.id = f.empleado_id', 'left')
            ->join('tbl_proyectos p', 'p.id = f.proyecto_id', 'left')
            ->where('f.id', $furdId)
            ->get()
            ->getRowArray();

        if (!$furd) {
            return;
        }

        // Faltas asociadas
        $faltas = $this->getFaltasByFurdId($furdId);

        // Adjuntos de la fase de registro
        $adjuntos = (new \App\Models\FurdAdjuntoModel())->listByFase($furdId, 'registro');

        $consecutivo = $furd['consecutivo'] ?? sprintf('PD-%06d', $furdId);

        $correoTrabajador = trim((string)($furd['correo'] ?? $furd['correo_trabajador'] ?? ''));
        $correoCliente    = trim((string)($furd['correo_cliente'] ?? ''));
        $correoProcesos   = trim((string)env('email.fromEmail', ''));

        // Si no hay destinatarios útiles, no hacemos nada
        if ($correoTrabajador === '' && $correoProcesos === '') {
            return;
        }

        // Preparar PDF adjunto del formato recién generado
        $tmpPdfPath = null;

        if (!empty($formatoMeta['pdf_file_id'])) {
            try {
                $g = new \App\Libraries\GDrive();
                $binary = $g->downloadFile((string)$formatoMeta['pdf_file_id']);

                $tmpDir = WRITEPATH . 'tmp';
                if (!is_dir($tmpDir)) {
                    @mkdir($tmpDir, 0775, true);
                }

                $safeName = trim((string)($formatoMeta['pdf_name'] ?? ''));
                if ($safeName === '') {
                    $safeName = 'RH-FO23_' . preg_replace('/[^\w\-]+/', '_', $consecutivo) . '.pdf';
                }

                $tmpPdfPath = $tmpDir . DIRECTORY_SEPARATOR . $safeName;

                if (file_put_contents($tmpPdfPath, $binary) === false) {
                    throw new \RuntimeException('No se pudo escribir el PDF temporal en disco.');
                }

                log_message('debug', 'PDF temporal preparado para correo FURD {id}: {path}', [
                    'id'   => $furdId,
                    'path' => $tmpPdfPath,
                ]);
            } catch (\Throwable $e) {
                log_message(
                    'error',
                    'No se pudo preparar PDF adjunto para correo FURD ID ' . $furdId . ': ' . $e->getMessage()
                );
                $tmpPdfPath = null;
            }
        } else {
            log_message('error', 'FURD ID {id} sin pdf_file_id en formatoMeta; el correo saldrá sin adjunto.', [
                'id' => $furdId,
            ]);
        }

        $email = \Config\Services::email();

        // Cuerpo HTML común
        $html = view('emails/furd/furd_registro_resumen', [
            'furd'        => $furd,
            'faltas'      => $faltas,
            'adjuntos'    => $adjuntos,
            'consecutivo' => $consecutivo,
        ]);

        try {
            // 1) Correo al trabajador
            if ($correoTrabajador !== '') {
                $email->clear(true);
                $email->setTo($correoTrabajador);
                $email->setSubject("Registro de proceso disciplinario {$consecutivo}");
                $email->setMessage($html);
                $email->setMailType('html');

                if ($tmpPdfPath && is_file($tmpPdfPath)) {
                    log_message('debug', 'Adjuntando PDF a correo trabajador FURD {id}: {path}', [
                        'id'   => $furdId,
                        'path' => $tmpPdfPath,
                    ]);
                    $email->attach($tmpPdfPath);
                } else {
                    log_message('error', 'No se adjunta PDF en correo FURD {id} porque no existe archivo temporal.', [
                        'id' => $furdId,
                    ]);
                }

                if (!$email->send()) {
                    log_message(
                        'error',
                        'Error enviando correo al trabajador para FURD ' . $consecutivo . '. Debug: ' .
                            $email->printDebugger(['headers', 'subject'])
                    );
                }
            }

            // 2) Correo a procesos disciplinarios
            if ($correoProcesos !== '') {
                $email->clear(true);
                $email->setTo($correoProcesos);

                if ($correoCliente !== '') {
                    $email->setCC($correoCliente);
                }

                $email->setSubject("Nuevo FURD registrado {$consecutivo}");
                $email->setMessage($html);
                $email->setMailType('html');

                if ($tmpPdfPath && is_file($tmpPdfPath)) {
                    log_message('debug', 'Adjuntando PDF a correo procesos FURD {id}: {path}', [
                        'id'   => $furdId,
                        'path' => $tmpPdfPath,
                    ]);
                    $email->attach($tmpPdfPath);
                } else {
                    log_message('error', 'No se adjunta PDF en correo FURD {id} porque no existe archivo temporal.', [
                        'id' => $furdId,
                    ]);
                }

                if (!$email->send()) {
                    log_message(
                        'error',
                        'Error enviando correo a procesos disciplinarios para FURD ' . $consecutivo . '. Debug: ' .
                            $email->printDebugger(['headers', 'subject'])
                    );
                }
            }
        } catch (\Throwable $e) {
            log_message(
                'error',
                'Error general enviando correos del FURD ' . $consecutivo . ': ' . $e->getMessage()
            );
        } finally {
            // Limpiar archivo temporal del servidor
            if ($tmpPdfPath && is_file($tmpPdfPath)) {
                @unlink($tmpPdfPath);
            }
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
    // Helper para obtener las faltas (reutilizable)
    private function getFaltasByFurdId(int $furdId): array
    {
        return db_connect()->table('tbl_furd_faltas ff')
            ->select('rf.codigo, rf.gravedad, rf.descripcion')
            ->join(
                'tbl_rit_faltas rf',
                'rf.id = COALESCE(ff.falta_id, ff.rit_falta_id)',
                'left'
            )
            ->where('ff.furd_id', $furdId)
            ->orderBy('rf.codigo', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function descargarFormato(int $id)
    {
        $row = (new \App\Models\FurdAdjuntoModel())
            ->where('origen', 'furd')
            ->where('origen_id', $id)
            ->where('fase', 'formato')
            ->orderBy('id', 'DESC')
            ->first();

        if (!$row || empty($row['drive_file_id'])) {
            throw PageNotFoundException::forPageNotFound('No se encontró el formato en Drive.');
        }

        $g = new \App\Libraries\GDrive();
        $binary = $g->downloadFile($row['drive_file_id']);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . ($row['nombre_original'] ?? 'furd.pdf') . '"')
            ->setBody($binary);
    }
}
