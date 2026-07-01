<?php

declare(strict_types=1);

namespace App\Domain\Model;

final class SerialNumber
{
    private string $value;

    public function __construct(string $value)
    {
        if (! preg_match('/^\d{6}$/', $value)) {
            throw new \InvalidArgumentException('Numer seryjny musi składać się z dokładnie 6 cyfr.');
        }
        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
