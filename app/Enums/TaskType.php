<?php

namespace App\Enums;

enum TaskType: string
{
    case Bug = 'bug';
    case Feature = 'feature';
    case Test = 'test';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Bug => 'Bug',
            self::Feature => 'Feature',
            self::Test => 'Test',
            self::Other => 'Otro',
        };
    }
}
