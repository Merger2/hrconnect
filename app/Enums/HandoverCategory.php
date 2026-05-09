<?php

namespace App\Enums;

enum HandoverCategory: string
{
    case DOCUMENT = 'document';
    case ASSET = 'asset';
    case DATA = 'data';
    case ACCESS = 'access';
    case RESPONSIBILITY = 'responsibility';

    public function label(): string
    {
        return match ($this) {
            self::DOCUMENT => 'Dokumen & Berkas',
            self::ASSET => 'Aset Fisik',
            self::DATA => 'Data & File',
            self::ACCESS => 'Akses Akun & Server',
            self::RESPONSIBILITY => 'Tanggung Jawab / Tugas',
        };
    }
}
