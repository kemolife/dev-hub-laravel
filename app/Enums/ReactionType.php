<?php

declare(strict_types=1);

namespace App\Enums;

enum ReactionType: string
{
    case Like = 'like';
    case Insightful = 'insightful';
    case Fire = 'fire';
    case Heart = 'heart';
    case MindBlown = 'mind_blown';

    public function emoji(): string
    {
        return match ($this) {
            self::Like => '👍',
            self::Insightful => '💡',
            self::Fire => '🔥',
            self::Heart => '❤️',
            self::MindBlown => '🤯',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Like => 'Like',
            self::Insightful => 'Insightful',
            self::Fire => 'Fire',
            self::Heart => 'Heart',
            self::MindBlown => 'Mind Blown',
        };
    }
}
