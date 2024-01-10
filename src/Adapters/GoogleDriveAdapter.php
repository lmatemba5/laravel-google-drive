<?php

namespace Lmate\LaravelGoogleDrive\Adapters;

use Google\Service\{Drive, Drive\DriveFile, Drive\Permission};
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
        $googleDriveFile = $this->makeDriveFile($file, $folderId);
        $response = $this->save2GDrive(
            $googleDriveFile,
            $file,
            $isPublic
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
        $filemetaData = [
            'name' => $uploadedFile->getName(),
            'parents' => [$folderId],
        ];
        
        return new DriveFile($filemetaData);
    }

    private function save2GDrive(DriveFile $googleDriveFile, GoogleDriveFile $file, $isPublic = false): DriveFile
    {
        $driveFile =  $this->googleServiceDrive->files->create($googleDriveFile,[
            'data' => $file->getContent(),
            'uploadType' => 'multipart',
            'fields' => 'id,mimeType,name,webViewLink,permissions,size'
        ]);

        if($isPublic){
            $this->makeFilePublic($driveFile->id);
        }

        return $driveFile;
    }

    public function makeFilePublic($fileId){
        $permission = new Permission([
            'type' => 'anyone',
            'role' => 'reader',
        ]);
        
        try{
            $this->googleServiceDrive->permissions->create($fileId, $permission);
            return true;
        }catch(\Exception $e){}
        
        return false;
    }

    public function makeFilePrivate($fileId)
    {
        try{
            $reponse = $this->googleServiceDrive->permissions->delete($fileId, 'anyoneWithLink');
            return $reponse->getStatusCode() == 204;
        }catch(\Exception $e){}
        
        return false;
    }


    public function mkdir($directoryName, $parentFolderId = null, $isPublic=false): GoogleDriveFile
    {
        $response = $this->googleServiceDrive->files->create(
            new DriveFile([
                'name' => $directoryName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$parentFolderId ?: $this->parentId]
            ]),
            [
                'fields' => 'id,name,webViewLink,parents'
            ]
        );

        if($isPublic){
            $this->makeFilePublic($response->id);
        }

        return $this->createGDriveFile(
            $response,
            null,
            null,
            ''
        );
    }

    public function find($fileName, $parentId=null, $pageToken=null)
    {        $parentId = $parentId ?: $this->parentId;
        return $this->finalize("name = '$fileName'", $parentId, $pageToken);
    }
    
    public function rename($fileId, $newName)
    {
        $newFileAttr = new DriveFile();
        $newFileAttr->setName($newName);

        $updatedFile = $this->googleServiceDrive->files->update($fileId, $newFileAttr, ['fields' => 'id, name']);

        return (object)[
            'name' => $updatedFile->name,
            'id' => $updatedFile->id
        ]; 
    }

    public function listFiles($parentId=null, $pageToken=null)
    {
        // Set up parameters for file search
        $optParams = array(
            'q' => "'".($parentId ?: $this->parentId)."' in parents",
            'fields' => 'nextPageToken,files(id,name,webViewLink)',
            'pageToken'=> $pageToken
        );
        
        return $this->finalize($optParams);
    }

    private function finalize($q, $parentId, $pageToken=null)
    {
        $optParams = array(
            'spaces' => 'drive',
            'q' => $q,
            'pageToken'=> $pageToken,
            'fields' => 'nextPageToken,files(id,name,webViewLink,parents)',
        );
        // Execute the request to search for the file
        $files = $this->googleServiceDrive->files->listFiles($optParams);

        $result = collect();
       
        foreach ($files->getFiles() as $file) {
            $isMyChild = false;
            
            foreach($file->parents as $parent){
               if($parent == $parentId){
                    $isMyChild = true;
                    break;
               }
            }
            
            if($isMyChild){
                $result->push((object)[
                    'name' => $file->name,
                    "id" => $file->id,
                    'link' => $file->getWebViewLink()
                ]);
            }
            
        } 
        
        return [
            'data' => $result,
            'nextPageToken' => $files->getNextPageToken()
        ];
    }
}