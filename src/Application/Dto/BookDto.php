<?php

declare(strict_types=1);

namespace App\Application\Dto;

class BookDto
{
    public function __construct(
        public ?string $id,
        public string $serialNumber,
        public string $title,
        public string $author,
        public bool $isBorrowed = false,
        public ?string $borrowedAt = null,
        public ?string $borrowedBy = null,
        public ?string $readerId = null,
        public ?string $readerName = null
    ) {
    }
}
