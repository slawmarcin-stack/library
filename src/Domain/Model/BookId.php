<?php

declare(strict_types=1);

namespace App\Domain\Model;

final class BookId
{
    private string $value;

    public function __construct(string $value)
    {
        if (! preg_match('/^\d+$/', $value)) {
            throw new \InvalidArgumentException('Book id must be numeric.');
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
