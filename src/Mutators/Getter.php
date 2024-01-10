<?php

namespace Lmate\LaravelGoogleDrive\Mutators;

use Lmate\LaravelGoogleDrive\Adapters\GoogleDriveAdapter;
use Lmate\LaravelGoogleDrive\Models\GoogleDriveFile;

class Getter
{
    public function __construct(
        private readonly GoogleDriveAdapter $googleDrive,
    ) {
    }

    public function get(string $fileId): GoogleDriveFile
    {
        return $this->googleDrive->get($fileId);
    }

    public function makeFilePublic(string $fileId)
    {
        return $this->googleDrive->makeFilePublic($fileId);
    }

    public function makeFilePrivate(string $fileId)
    {
        return $this->googleDrive->makeFilePrivate($fileId);
    }

    public function rename($fileId, $newName){
        return $this->googleDrive->rename($fileId, $newName);
    }
}