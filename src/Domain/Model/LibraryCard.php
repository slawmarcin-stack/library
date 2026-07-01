<?php

declare(strict_types=1);

namespace App\Domain\Model;

final class LibraryCard
{
    private string $value;

    public function __construct(string $value)
    {
        if (! preg_match('/^\d{6}$/', $value)) {
            throw new \InvalidArgumentException('Library card number must be exactly 6 digits.');
        }

        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
