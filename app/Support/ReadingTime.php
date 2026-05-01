<?php

declare(strict_types=1);

namespace App\Support;

readonly class ReadingTime
{
    public function __construct(public int $seconds) {}

    public function minutes(): int
    {
        return (int) ceil($this->seconds / 60);
    }

    public function label(): string
    {
        return $this->minutes().' min read';
    }
}
