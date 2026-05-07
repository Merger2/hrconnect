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
            self::DOCUMENT => 'Dokumen',
            self::ASSET => 'Aset',
            self::DATA => 'Data',
            self::ACCESS => 'Akses Sistem',
            self::RESPONSIBILITY => 'Tanggung Jawab',
        };
    }
}
