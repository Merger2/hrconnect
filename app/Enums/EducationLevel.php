<?php

namespace App\Enums;

enum EducationLevel: string
{
    case SD = 'sd';
    case SMP = 'smp';
    case SMA = 'sma';
    case SMK = 'smk';
    case DIPLOMA = 'diploma';
    case BACHELOR = 'bachelor';
    case MASTER = 'master';
    case DOCTORATE = 'doctorate';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SD => 'SD / Sederajat',
            self::SMP => 'SMP / Sederajat',
            self::SMA => 'SMA / Sederajat',
            self::SMK => 'SMK / Sederajat',
            self::DIPLOMA => 'Diploma (D1-D4)',
            self::BACHELOR => 'Sarjana (S1)',
            self::MASTER => 'Magister (S2)',
            self::DOCTORATE => 'Doktor (S3)',
            self::OTHER => 'Lainnya',
        };
    }
}
