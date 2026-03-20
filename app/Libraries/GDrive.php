<?php namespace App\Libraries;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;

class GDrive
{
    protected Drive $service;
    protected string $driveId;
    protected ?string $shareDomain;

    public function __construct()
    {
        $jsonPath = (string) env('GDRIVE_SA_JSON', '');
        $jsonPath = $this->resolvePath($jsonPath !== '' ? $jsonPath : WRITEPATH . 'keys/sa.json');

        if (!is_file($jsonPath)) {
            throw new \RuntimeException("No se encontro JSON de Service Account en {$jsonPath}");
        }

        $client = new Client();
        $client->setAuthConfig($jsonPath);
        $client->setScopes([
            Drive::DRIVE,
            \Google\Service\Sheets::SPREADSHEETS,
        ]);
        $client->setAccessType('offline');

        $imp = (string) env('GDRIVE_IMPERSONATE', '');
        if ($imp !== '') {
            $client->setSubject($imp);
        }

        $this->service = new Drive($client);

        $this->driveId = (string) env('GDRIVE_SHARED_DRIVE_ID', '');
        if ($this->driveId === '') {
            throw new \RuntimeException('Falta GDRIVE_SHARED_DRIVE_ID en .env');
        }

        $domain = (string) env('GDRIVE_SHARE_DOMAIN', '');
        $this->shareDomain = $domain !== '' ? $domain : null;
    }

    public function getClient(): Client
    {
        return $this->service->getClient();
    }

    public function ensurePath(string $path): string
    {
        $parts = array_values(array_filter(array_map('trim', explode('/', $path))));
        $parentId = $this->driveId;

        foreach ($parts as $name) {
            $folderId = $this->findFolder($name, $parentId);
            if (!$folderId) {
                $folderId = $this->createFolder($name, $parentId);
            }
            $parentId = $folderId;
        }

        return $parentId;
    }

    public function createFolderInParent(string $name, string $parentId): string
    {
        $exists = $this->findFolder($name, $parentId);
        if ($exists) {
            return $exists;
        }

        return $this->createFolder($name, $parentId);
    }

    public function upload(string $localPath, string $name, string $mime, string $parentId): array
    {
        $fileMeta = new DriveFile([
            'name'    => $name,
            'parents' => [$parentId],
        ]);

        $file = $this->service->files->create($fileMeta, [
            'data'              => file_get_contents($localPath),
            'mimeType'          => $mime ?: 'application/octet-stream',
            'uploadType'        => 'multipart',
            'supportsAllDrives' => true,
            'fields'            => 'id, name, webViewLink, webContentLink, mimeType',
        ]);

        $this->applyDomainPermissionIfNeeded($file->id);

        return [
            'id'             => $file->id,
            'name'           => $file->name,
            'mimeType'       => $file->mimeType,
            'webViewLink'    => $file->webViewLink ?? null,
            'webContentLink' => $file->webContentLink ?? null,
        ];
    }

    public function uploadContent(string $content, string $name, string $mime, string $parentId): array
    {
        $fileMeta = new DriveFile([
            'name'    => $name,
            'parents' => [$parentId],
        ]);

        $file = $this->service->files->create($fileMeta, [
            'data'              => $content,
            'mimeType'          => $mime ?: 'application/octet-stream',
            'uploadType'        => 'multipart',
            'supportsAllDrives' => true,
            'fields'            => 'id, name, webViewLink, webContentLink, mimeType',
        ]);

        $this->applyDomainPermissionIfNeeded($file->id);

        return [
            'id'             => $file->id,
            'name'           => $file->name,
            'mimeType'       => $file->mimeType,
            'webViewLink'    => $file->webViewLink ?? null,
            'webContentLink' => $file->webContentLink ?? null,
        ];
    }

    public function copyFile(string $fileId, string $newName, string $parentId): array
    {
        $copied = $this->service->files->copy(
            $fileId,
            new DriveFile([
                'name'    => $newName,
                'parents' => [$parentId],
            ]),
            [
                'supportsAllDrives' => true,
                'fields'            => 'id, name, mimeType, webViewLink',
            ]
        );

        $this->applyDomainPermissionIfNeeded($copied->id);

        return [
            'id'          => $copied->id,
            'name'        => $copied->name,
            'mimeType'    => $copied->mimeType,
            'webViewLink' => $copied->webViewLink ?? null,
        ];
    }

    public function exportGoogleFile(string $fileId, string $exportMime): string
    {
        $response = $this->service->files->export($fileId, $exportMime, [
            'alt' => 'media',
        ]);

        return $response->getBody()->getContents();
    }

    public function downloadFile(string $fileId): string
    {
        $response = $this->service->files->get($fileId, [
            'alt'               => 'media',
            'supportsAllDrives' => true,
        ]);

        return $response->getBody()->getContents();
    }

    public function getFileMeta(string $fileId): array
    {
        $file = $this->service->files->get($fileId, [
            'supportsAllDrives' => true,
            'fields'            => 'id, name, mimeType, webViewLink, webContentLink',
        ]);

        return [
            'id'             => $file->id,
            'name'           => $file->name,
            'mimeType'       => $file->mimeType,
            'webViewLink'    => $file->webViewLink ?? null,
            'webContentLink' => $file->webContentLink ?? null,
        ];
    }

    public function delete(string $fileId): void
    {
        $this->service->files->delete($fileId, ['supportsAllDrives' => true]);
    }

    protected function applyDomainPermissionIfNeeded(string $fileId): void
    {
        if (!$this->shareDomain) {
            return;
        }

        try {
            $perm = new Permission([
                'type'               => 'domain',
                'role'               => 'reader',
                'domain'             => $this->shareDomain,
                'allowFileDiscovery' => false,
            ]);

            $this->service->permissions->create($fileId, $perm, [
                'supportsAllDrives'     => true,
                'sendNotificationEmail' => false,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'GDrive set domain permission: ' . $e->getMessage());
        }
    }

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

    protected function createFolder(string $name, string $parentId): string
    {
        $folder = $this->service->files->create(
            new DriveFile([
                'name'     => $name,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents'  => [$parentId],
                'driveId'  => $this->driveId,
            ]),
            [
                'supportsAllDrives' => true,
                'fields'            => 'id',
            ]
        );

        return $folder->id;
    }

    protected function escape(string $s): string
    {
        return str_replace(["\\", "'"], ["\\\\", "\\'"], $s);
    }

    protected function resolvePath(string $p): string
    {
        if ($p === '') {
            return $p;
        }

        if ($p[0] === '/' || preg_match('~^[A-Za-z]:\\\\~', $p)) {
            return $p;
        }

        if (strpos($p, 'writable/') === 0) {
            $candidate = WRITEPATH . substr($p, strlen('writable/'));
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $candidates = [
            ROOTPATH . ltrim($p, '/\\'),
            WRITEPATH . ltrim($p, '/\\'),
        ];

        foreach ($candidates as $c) {
            if (is_file($c)) {
                return $c;
            }
        }

        return $p;
    }
}
