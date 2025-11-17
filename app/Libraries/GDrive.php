<?php namespace App\Libraries;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;

class GDrive
{
    protected Drive $service;
    protected string $driveId;           // ID de la Unidad Compartida
    protected ?string $shareDomain;      // dominio para permisos de solo lectura (opcional)

    public function __construct()
    {
        // 1) Resolver ruta del JSON (soporta relativa tipo "writable/keys/sa.json")
        $jsonPath = (string) env('GDRIVE_SA_JSON', '');
        $jsonPath = $this->resolvePath($jsonPath !== '' ? $jsonPath : WRITEPATH . 'keys/sa.json');

        if (!is_file($jsonPath)) {
            throw new \RuntimeException("No se encontró JSON de Service Account en {$jsonPath}");
        }

        // 2) Configurar cliente Google
        $client = new Client();
        $client->setAuthConfig($jsonPath);
        $client->setScopes([Drive::DRIVE]);
        $client->setAccessType('offline');

        // Impersonación (opcional)
        $imp = (string) env('GDRIVE_IMPERSONATE', '');
        if ($imp !== '') {
            $client->setSubject($imp);
        }

        $this->service = new Drive($client);

        // 3) Unidad Compartida (obligatoria para este flujo)
        $this->driveId = (string) env('GDRIVE_SHARED_DRIVE_ID', '');
        if ($this->driveId === '') {
            throw new \RuntimeException('Falta GDRIVE_SHARED_DRIVE_ID en .env');
        }

        // 4) Permisos de dominio (opcional)
        $domain = (string) env('GDRIVE_SHARE_DOMAIN', '');
        $this->shareDomain = $domain !== '' ? $domain : null;
    }

    /**
     * Crea/obtiene una carpeta anidada dentro de la Unidad Compartida.
     * Ej: "FURD/2025/123" -> retorna el ID de esa carpeta final.
     */
    public function ensurePath(string $path): string
    {
        $parts = array_values(array_filter(array_map('trim', explode('/', $path))));
        $parentId = $this->driveId; // en unidades compartidas, el root folder id == driveId

        foreach ($parts as $name) {
            $folderId = $this->findFolder($name, $parentId);
            if (!$folderId) {
                $folderId = $this->createFolder($name, $parentId);
            }
            $parentId = $folderId;
        }
        return $parentId;
    }

    /**
     * Sube un archivo a Drive y retorna:
     * ['id' => string, 'webViewLink' => ?string, 'webContentLink' => ?string]
     */
    public function upload(string $localPath, string $name, string $mime, string $parentId): array
    {
        $fileMeta = new DriveFile([
            'name'    => $name,
            'parents' => [$parentId],
        ]);

        $params = [
            'data'                   => file_get_contents($localPath),
            'mimeType'               => $mime ?: 'application/octet-stream',
            'uploadType'             => 'multipart',
            'supportsAllDrives'      => true,
            'fields'                 => 'id, webViewLink, webContentLink',
        ];

        $file = $this->service->files->create($fileMeta, $params);
        

        // Permisos de solo lectura para el dominio (si se configuró)
        if ($this->shareDomain) {
            try {
                $perm = new Permission([
                    'type'                => 'domain',
                    'role'                => 'reader',
                    'domain'              => $this->shareDomain,
                    'allowFileDiscovery'  => false,
                ]);

                $this->service->permissions->create(
                    $file->id,
                    $perm,
                    [
                        'supportsAllDrives'       => true,
                        'sendNotificationEmail'   => false,
                    ]
                );
            } catch (\Throwable $e) {
                log_message('error', 'GDrive set domain permission: ' . $e->getMessage());
            }
        }

        return [
            'id'              => $file->id,
            'webViewLink'     => $file->webViewLink ?? null,
            'webContentLink'  => $file->webContentLink ?? null,
        ];
    }

    /** Borra un archivo por su fileId */
    public function delete(string $fileId): void
    {
        $this->service->files->delete($fileId, ['supportsAllDrives' => true]);
    }

    // ==================== Helpers privados ====================

    /** Busca una carpeta por nombre dentro de un parent */
    protected function findFolder(string $name, string $parentId): ?string
    {
        $q = sprintf(
            "name = '%s' and mimeType = 'application/vnd.google-apps.folder' and '%s' in parents and trashed = false",
            $this->escape($name),
            $this->escape($parentId)
        );

        $res = $this->service->files->listFiles([
            'q'                         => $q,
            'supportsAllDrives'         => true,
            'includeItemsFromAllDrives' => true,
            'corpora'                   => 'drive',
            'driveId'                   => $this->driveId,
            'fields'                    => 'files(id,name)',
            'pageSize'                  => 1,
        ]);

        $files = $res->getFiles();
        return $files && isset($files[0]) ? $files[0]->id : null;
    }

    /** Crea una carpeta dentro de un parent de la Unidad Compartida */
    protected function createFolder(string $name, string $parentId): string
    {
        $fileMeta = new DriveFile([
            'name'     => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents'  => [$parentId],
            'driveId'  => $this->driveId,
        ]);

        $folder = $this->service->files->create($fileMeta, [
            'supportsAllDrives' => true,
            'fields'            => 'id',
        ]);

        return $folder->id;
    }

    /** Escapa comillas para queries simples a la API de Drive */
    protected function escape(string $s): string
    {
        return str_replace(["\\", "'"], ["\\\\", "\\'"], $s);
    }

    /**
     * Convierte rutas relativas en absolutas probando ROOTPATH y WRITEPATH.
     * Si ya es absoluta, la deja igual.
     */
    protected function resolvePath(string $p): string
    {
        if ($p === '') return $p;

        // Absoluta (Unix) o tipo C:\ en Windows
        if ($p[0] === '/' || preg_match('~^[A-Za-z]:\\\\~', $p)) {
            return $p;
        }

        // Si empieza con "writable/", resolver contra WRITEPATH
        if (strpos($p, 'writable/') === 0) {
            $candidate = WRITEPATH . substr($p, strlen('writable/'));
            if (is_file($candidate)) return $candidate;
        }

        // Candidatos comunes
        $candidates = [
            ROOTPATH . ltrim($p, '/\\'),
            WRITEPATH . ltrim($p, '/\\'),
        ];
        foreach ($candidates as $c) {
            if (is_file($c)) return $c;
        }

        // Devuelve lo recibido (fallará arriba si no existe)
        return $p;
    }
}
