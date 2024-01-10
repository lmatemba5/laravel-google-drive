<?php

namespace Lmate\LaravelGoogleDrive\Adapters;

use Lmate\LaravelGoogleDrive\Mutators\Uploader;
use Lmate\LaravelGoogleDrive\Mutators\Deleter;
use Lmate\LaravelGoogleDrive\Mutators\DirectoryManager;
use Lmate\LaravelGoogleDrive\Mutators\Getter;
use Lmate\LaravelGoogleDrive\Models\GoogleDriveFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class GoogleDriveHandler
{
    public function __construct(
        private readonly Uploader $uploader,
        private readonly Getter $getter,
        private readonly Deleter $deleter,
        private readonly DirectoryManager $dmanager
    ) {
    }

    public function upload(UploadedFile $uploadedFile, string $folderId = '',$isPublic=false): GoogleDriveFile
    {
        $file = new GoogleDriveFile(
            name: $uploadedFile->getClientOriginalName(),
            content: $uploadedFile->getContent(),
            mimeType: $uploadedFile->getMimeType() ?? 'application/octet-stream'
        );

        return $this->uploader->upload($file, $folderId, $isPublic);
    }

    public function mkdir($directoryName, string $folderId = null, $isPublic=false)
    {
        return $this->dmanager->mkdir($directoryName, $folderId, $isPublic);
    }

    public function find($fileName, $parentId=null, $perPage=null, $pageToken=null)
    {
        return $this->dmanager->find($fileName, $parentId, $perPage, $pageToken);
    }

    public function makeFilePublic(string $fileId)
    {
        return $this->getter->makeFilePublic($fileId);
    }

    public function makeFilePrivate(string $fileId)
    {
        return $this->getter->makeFilePrivate($fileId);
    }
    
    public function listFiles($parentId=null, $perPage= null, $pageToken=null){
        return $this->dmanager->listFiles($parentId, $perPage, $pageToken);
    }

    public function rename($fileId, $newName)
    {
        return $this->getter->rename($fileId, $newName);
    }

    /**
     * @param UploadedFile[] $uploadedFiles
     */
    public function uploadMany(array $uploadedFiles, string $folderId = ''): array
    {
        $result = [];

        foreach ($uploadedFiles as $uploadedFile) {
            if (!($uploadedFile instanceof UploadedFile)) {
                throw new \Exception(
                    'Invalid UploadedFile.'
                );
            }

            $result[] = $this->upload($uploadedFile, $folderId);
        }

        return $result;
    }

    public function get(string $fileId): GoogleDriveFile
    {
        return $this->getter->get($fileId);
    }

    public function delete(string $fileId): bool
    {
        return $this->deleter->delete($fileId);
    }
}