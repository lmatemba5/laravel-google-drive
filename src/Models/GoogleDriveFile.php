<?php

namespace Lmate\LaravelGoogleDrive\Models;

final class GoogleDriveFile
{
    public function __construct(
        private readonly string $name,
        private readonly string $content = '',
        private readonly string $mimeType = '',
        private readonly string $fileId = '',
        private readonly string $webViewLink = '',
        private readonly array $permissions = [],
        private readonly int $size = 0
    ) {
    }

    public function isPublic(): bool
    {
        foreach ($this->permissions as $permission) {
            if ($permission['role'] == 'reader' && $permission['type'] == 'anyone') {
                return true;
            }
        }

        return false;
    }

    public function getEmbededUrl(): string
    {
        $isFile = $this->mimeType != 'application/vnd.google-apps.folder';

        if ($isFile) {
            return 'data:' . $this->mimeType . ';base64,' . base64_encode($this->content);
        }

        return $this->webViewLink;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getWebViewLink(): string
    {
        return $this->webViewLink;
    }

    public function getFileId(): string
    {
        return $this->fileId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getExtension(): string
    {
        $fileName = preg_split('/\./', $this->name);
        $size = count($fileName ?: []) - 1;

        return $fileName[$size] ?? '';
    }
}