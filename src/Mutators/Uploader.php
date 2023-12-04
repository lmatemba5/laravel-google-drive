<?php

namespace Lmate\LaravelGoogleDrive\Mutators;

use Lmate\LaravelGoogleDrive\Adapters\GoogleDriveAdapter;
use Lmate\LaravelGoogleDrive\Models\GoogleDriveFile;

class Uploader
{
    public function __construct(
        private readonly GoogleDriveAdapter $googleDrive
    ) {
    }

    public function upload(GoogleDriveFile $file, string $folderId): GoogleDriveFile
    {
        $folderId = $this->getFolderId($folderId);

        return $this->googleDrive->upload($file, $folderId);
    }

    private function getFolderId(string $folderId): string
    {
        $folderId = $folderId ?: config(
            'credentials.folder_id',
            ''
        );

        if (empty($folderId)) {
            throw new \Exception(
                'The folder_id is empty. Please check GOOGLE_DRIVE_FOLDER_ID .env variable'
            );
        }

        return $folderId;
    }
}
