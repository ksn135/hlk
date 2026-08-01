<?php

namespace App\Enum;

enum ReviewPackageFileStatus: string
{
    case Editing = 'editing';
    case Submitted = 'submitted';

    public function label(): string
    {
        return match ($this) {
            self::Editing => 'Редактирование',
            self::Submitted => 'Отправлен',
        };
    }
}
