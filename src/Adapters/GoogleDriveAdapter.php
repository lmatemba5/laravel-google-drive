<?php

namespace Lmate\LaravelGoogleDrive\Adapters;

use Google\Service\{Drive, Drive\DriveFile};
use Lmate\LaravelGoogleDrive\Models\GoogleDriveFile;

class GoogleDriveAdapter
{
    public function __construct(
        private readonly Drive $googleServiceDrive
    ) {
    }

    public function upload(GoogleDriveFile $file, string $folderId)
    {
        $googleDriveFile = $this->makeDriveFile($file, $folderId);
        $response = $this->save2GDrive(
            $googleDriveFile,
            $file
        );

        return $this->createGDriveFile($response, null, null, $file->getContent());
    }

    public function get(string $fileId): GoogleDriveFile
    {
        $response = $this->googleServiceDrive->files->get($fileId, [
            'fields' => 'mimeType,name,size,id,webViewLink,permissions'
        ]);

        return $this->createGDriveFile(
            $response,
            null,
            null,
            $this->googleServiceDrive->files->get($fileId, ['alt' => 'media'])->getBody()->getContents()
        );
    }

    private function createGDriveFile($fromResponse, $fileId = null, $fileName = null, $content = '')
    {
        return  new GoogleDriveFile(
            name: $fileName != null ?: $fromResponse->getName(),
            content: $content,
            mimeType: $fromResponse->getMimeType(),
            fileId: $fileId != null ?: $fromResponse->getId(),
            webViewLink: $fromResponse->getWebViewLink(),
            permissions: $fromResponse->getPermissions(),
            size: $fromResponse->getSize() || 0
        );
    }

    public function delete(string $fileId): bool
    {
        $response = $this->googleServiceDrive->files->delete($fileId);

        return empty($response->getBody()->getContents());
    }

    private function makeDriveFile(GoogleDriveFile $uploadedFile, string $folderId): DriveFile
    {
        return new DriveFile([
            'name' => $uploadedFile->getName(),
            'parents' => [$folderId],
        ]);
    }

    private function save2GDrive(DriveFile $googleDriveFile, GoogleDriveFile $file): DriveFile
    {

        return $this->googleServiceDrive->files->create(
            $googleDriveFile,
            [
                'data' => $file->getContent(),
                'uploadType' => 'multipart',
                'fields' => 'id,mimeType,name,webViewLink,permissions,size',
            ]
        );
    }

    public function mkdir($directoryName, $parentFolderId = null): GoogleDriveFile
    {
        $parentFolderId = $parentFolderId ?: config('credentials.folder_id');

        $response = $this->googleServiceDrive->files->create(
            new DriveFile([
                'name' => $directoryName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$parentFolderId]
            ]),
            [
                'fields' => 'id,mimeType,name,webViewLink,permissions,size'
            ]
        );

        return $this->createGDriveFile(
            $response,
            null,
            null,
            ''
        );
    }
}
