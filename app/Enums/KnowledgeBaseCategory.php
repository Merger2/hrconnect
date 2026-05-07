<?php

namespace App\Enums;

enum KnowledgeBaseCategory: string
{
    case HR_POLICY = 'hr_policy';
    case IT_GUIDE = 'it_guide';
    case GENERAL = 'general';
    case FINANCE = 'finance';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::HR_POLICY => 'Kebijakan HR',
            self::IT_GUIDE => 'Panduan IT',
            self::GENERAL => 'Umum',
            self::FINANCE => 'Keuangan',
            self::OTHER => 'Lainnya',
        };
    }
}
