<?php

namespace Lmate\LaravelGoogleDrive\Adapters;

use Google\Service\{Drive, Drive\DriveFile};
use Lmate\LaravelGoogleDrive\Models\GoogleDriveFile;

class GoogleDriveAdapter
{
    private $parentId;
    
    public function __construct(
        private readonly Drive $googleServiceDrive
        
    ) {
        $this->parentId = $this->parentId ?: config('credentials.folder_id');
    }

    public function upload(GoogleDriveFile $file, string $folderId, $isPublic=false)
    {
        $googleDriveFile = $this->makeDriveFile($file, $folderId, $isPublic);
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

    private function makeDriveFile(GoogleDriveFile $uploadedFile, string $folderId, $isPublic=false): DriveFile
    {
        $filemetaData = [
            'name' => $uploadedFile->getName(),
            'parents' => [$folderId],
        ];
        
        if($isPublic){
            $filemetaData['permissions'] = [
                [
                    'type' => 'anyone',
                    'role' => 'reader',
                ],
            ];
        }
        
        return new DriveFile($filemetaData);
    }

    private function save2GDrive(DriveFile $googleDriveFile, GoogleDriveFile $file): DriveFile
    {
        return $this->googleServiceDrive->files->create($googleDriveFile,[
            'data' => $file->getContent(),
            'uploadType' => 'multipart',
            'fields' => 'id,mimeType,name,webViewLink,permissions,size'
        ]);
    }


    public function mkdir($directoryName, $parentFolderId = null): GoogleDriveFile
    {
        $response = $this->googleServiceDrive->files->create(
            new DriveFile([
                'name' => $directoryName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$parentFolderId ?: $this->parentId]
            ]),
            [
                'fields' => 'id,name,webViewLink'
            ]
        );

        return $this->createGDriveFile(
            $response,
            null,
            null,
            ''
        );
    }

    public function find($fileName, $parentId=null, $pageToken=null)
    {
        // Set up parameters for file search
        $optParams = array(
            'spaces' => 'drive',
            'q' => "name = '$fileName' and '" . ($parentId ?: $this->parentId) . "' in parents",
            'orderBy' => 'createdTime',
            'pageToken'=> $pageToken,
            'fields' => 'nextPageToken,files(id,name)',
        );

        return $this->finalize($optParams);
    }

    public function listFiles($parentId=null, $pageToken=null)
    {
        
        // Set up parameters for file search
        $optParams = array(
            'q' => "'".($parentId ?: $this->parentId)."' in parents",
            'fields' => 'nextPageToken,files(id,name)',
            'pageToken'=> $pageToken
        );
        
        return $this->finalize($optParams);
    }

    private function finalize($options)
    {
        // Execute the request to search for the file
        $files = $this->googleServiceDrive->files->listFiles($options);

        $result = [];

        foreach ($files->getFiles() as $file) {
            $result[] =(object) [
                'name' => $file->getName(),
                "id" => $file->getId(),
            ];
        } 
        
        $count = count($result);
        
        return [
            'data' => (object) [
                'total' => $count,
                'entries' => $result,
                'nextPageToken' => $files->getNextPageToken()
            ]
        ];
    }
}