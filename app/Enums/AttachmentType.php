<?php

namespace App\Enums;

enum AttachmentType: string
{
    case Document = 'document';
    case Image = 'image';
    case Link = 'link';

    public function label(): string
    {
        return match ($this) {
            self::Document => 'Archivo',
            self::Image => 'Imagen',
            self::Link => 'Link',
        };
    }
}
