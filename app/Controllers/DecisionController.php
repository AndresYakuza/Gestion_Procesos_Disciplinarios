<?php

namespace App\Controllers;

use CodeIgniter\HTTP\Files\UploadedFile;

class DecisionController extends BaseController
{
    /**
     * Catálogo de decisiones (puedes moverlo a una tabla más adelante).
     */
    private function decisionCatalog(): array
    {
        return [
            'Llamado de atención',
            'Suspensión disciplinaria',
            'Terminación de contrato',
        ];
    }

    /**
     * GET /decision
     */
    public function create()
    {
        return view('decision/index', [
            'decisiones' => $this->decisionCatalog(),
        ]);
    }

    /**
     * POST /decision
     * - Valida datos
     * - Maneja soporte opcional
     * - (TODO) Persistir en BD
     */
    public function store()
    {
        helper(['form']);

        // Para in_list[] construimos el catálogo
        $catalogo = $this->decisionCatalog();
        $catalogoRegla = implode(',', $catalogo); // no contiene comas internas

        $rules = [
            'consecutivo' => 'required|is_natural_no_zero',
            'decision'    => 'required|in_list[' . $catalogoRegla . ']',
            // El archivo es opcional; validamos solo si viene válido más abajo
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $consecutivo = (int) $this->request->getPost('consecutivo');
        $decision    = (string) $this->request->getPost('decision');

        // --- Soporte opcional ---
        $fileInfo = null;
        /** @var UploadedFile|null $file */
        $file = $this->request->getFile('soporte');

        if ($file && $file->isValid() && ! $file->hasMoved()) {

            // Reglas de archivo (solo si hay upload)
            $fileRules = [
                'soporte' => [
                    'rules'  => 'max_size[soporte,10240]|ext_in[soporte,pdf,jpg,jpeg,png,heic,doc,docx,xls,xlsx]|'
                              . 'mime_in[soporte,application/pdf,image/jpg,image/jpeg,image/png,image/heic,'
                              . 'application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,'
                              . 'application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet]',
                    'label'  => 'Soporte',
                    'errors' => [
                        'max_size' => 'El soporte no debe superar los 10 MB.',
                        'ext_in'   => 'Tipo de archivo no permitido.',
                        'mime_in'  => 'MIME no permitido.',
                    ],
                ],
            ];

            if (! $this->validate($fileRules)) {
                return redirect()->back()->withInput()
                    ->with('errors', $this->validator->getErrors());
            }

            // Directorio destino
            $subdir = date('Y/m');
            $dest   = WRITEPATH . 'uploads/decision/' . $subdir;

            if (! is_dir($dest)) {
                @mkdir($dest, 0775, true);
            }

            $newName = time() . '_' . $file->getRandomName();
            $file->move($dest, $newName);

            $fileInfo = [
                'nombre_original' => $file->getClientName(),
                'mime'            => $file->getClientMimeType(),
                'tamano_bytes'    => $file->getSize(),
                'ruta'            => 'writable/uploads/decision/' . $subdir . '/' . $newName, // relativa al proyecto
                // Sugerencia: servir vía endpoint de descarga seguro en vez de exponer WRITEPATH
            ];
        }

        // --------------------------------------------------
        // TODO Persistir en BD:
        //  - Tabla: tbl_decision (o la que definas)
        //  - Campos sugeridos: furd_id o consecutivo, decision, soporte_ruta, soporte_mime,
        //    soporte_nombre, soporte_tamano, audit_created_by, created_at, etc.
        //
        //  Ejemplo de estructura de payload a guardar:
        //  $payload = [
        //      'consecutivo'      => $consecutivo,
        //      'decision'         => $decision,
        //      'soporte_ruta'     => $fileInfo['ruta'] ?? null,
        //      'soporte_mime'     => $fileInfo['mime'] ?? null,
        //      'soporte_nombre'   => $fileInfo['nombre_original'] ?? null,
        //      'soporte_tamano'   => $fileInfo['tamano_bytes'] ?? null,
        //      'created_at'       => date('Y-m-d H:i:s'),
        //      'audit_created_by' => user()->id ?? null, // si usas auth
        //  ];
        //  (new DecisionModel())->insert($payload);
        // --------------------------------------------------

        $msg = 'Decisión registrada correctamente para el consecutivo #' . $consecutivo . '.';
        if ($fileInfo) {
            $msg .= ' Se adjuntó soporte: ' . $fileInfo['nombre_original'] . '.';
        }

        return redirect()->to(site_url('decision'))
            ->with('msg', $msg);
    }
}
