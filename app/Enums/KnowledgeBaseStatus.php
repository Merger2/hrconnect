<?php

namespace App\Enums;

enum KnowledgeBaseStatus: string
{
    case PROCESSING = 'processing';
    case READY = 'ready';
    case ERROR = 'error';

    public function label(): string
    {
        return match ($this) {
            self::PROCESSING => 'Sedang Diproses AI',
            self::READY => 'Siap Digunakan',
            self::ERROR => 'Gagal Diproses',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PROCESSING => 'yellow',
            self::READY => 'green',
            self::ERROR => 'red',
        };
    }
}
