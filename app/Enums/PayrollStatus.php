<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case VERIFIED = 'verified';
    case APPROVED = 'approved';
    case PAID = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Diajukan',
            self::VERIFIED => 'Diverifikasi',
            self::APPROVED => 'Disetujui',
            self::PAID => 'Ditransfer',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'zinc',
            self::SUBMITTED => 'warning',
            self::VERIFIED => 'info',
            self::APPROVED => 'success',
            self::PAID => 'emerald',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::SUBMITTED],
            self::SUBMITTED => [self::VERIFIED, self::DRAFT],
            self::VERIFIED => [self::APPROVED, self::DRAFT],
            self::APPROVED => [self::PAID],
            self::PAID => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
