<?php

namespace Lmate\LaravelGoogleDrive\Mutators;

use Lmate\LaravelGoogleDrive\Adapters\GoogleDriveAdapter;

class DirectoryManager
{
    public function __construct(
        private readonly GoogleDriveAdapter $googleDrive
    ) {
    }

    public function mkdir($directoryName, string $folderId = null, $isPublic=false)
    {
        return $this->googleDrive->mkdir($directoryName, $folderId, $isPublic);
    }

    public function find($fileName, $parentId=null, $pageToken=null)
    {
        return $this->googleDrive->find($fileName, $parentId, $pageToken);
    }
    
    public function listFiles($parentId=null, $pageToken=null){
        return $this->googleDrive->listFiles($parentId, $pageToken);
    }
}