<?php

namespace App\Enum;

enum ReviewPackageStatus: string
{
    case Active = 'active';
    case Submitted = 'submitted';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Активный',
            self::Submitted => 'Отправлен',
            self::Revoked => 'Отозван',
        };
    }

    public function isEditable(): bool
    {
        return self::Active === $this;
    }
}
