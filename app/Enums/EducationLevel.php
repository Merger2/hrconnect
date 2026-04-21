<?php

namespace App\Enums;

enum EducationLevel: string
{
    case SD = 'sd';
    case SMP = 'smp';
    case SMA = 'sma';
    case SMK = 'smk';
    case D1 = 'd1';
    case D2 = 'd2';
    case D3 = 'd3';
    case D4 = 'd4';
    case S1 = 's1';
    case S2 = 's2';
    case S3 = 's3';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::OTHER => 'Lainnya',
            default => strtoupper($this->value),
        };
    }
}
