<?php

namespace App\Support;

class AttachmentPathValidator
{
    public function isSafeRelativePath(string $path): bool
    {
        if ($path !== trim($path)) {
            return false;
        }

        $path = trim($path);

        if ($path === '' || strlen($path) > 1024) {
            return false;
        }

        if (collect(explode('/', $path))->contains(fn (string $segment): bool => $segment === '' || $segment === '.')) {
            return false;
        }

        return ! str_starts_with($path, '/')
            && ! str_contains($path, '..')
            && ! str_contains($path, '://')
            && ! str_contains($path, '\\')
            && ! str_contains($path, "\0")
            && preg_match('/[\x00-\x1F\x7F]/', $path) !== 1
            && preg_match('/^[a-zA-Z]:[\\\\\\/]/', $path) !== 1;
    }

    public function safeDownloadName(?string $name, string $fallback = 'download'): string
    {
        $name = basename((string) $name);
        $name = trim(preg_replace('/[\x00-\x1F\x7F\\\\\\/]+/', '_', $name) ?? '');
        $name = trim(preg_replace('/[^A-Za-z0-9._ -]+/', '_', $name) ?? '', " ._\t\n\r\0\x0B");

        if ($name === '' || $name === '.' || $name === '..') {
            $name = $fallback;
        }

        return substr($name, 0, 160);
    }
}
