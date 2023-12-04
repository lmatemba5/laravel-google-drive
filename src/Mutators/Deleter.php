<?php

namespace Lmate\LaravelGoogleDrive\Mutators;

use Lmate\LaravelGoogleDrive\Adapters\GoogleDriveAdapter;

class Deleter
{
    public function __construct(private readonly GoogleDriveAdapter $googleDrive)
    {
    }

    public function delete(string $fileId): bool
    {
        return $this->googleDrive->delete($fileId);
    }
}
