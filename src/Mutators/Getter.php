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
}
