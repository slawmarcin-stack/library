<?php

declare(strict_types=1);

namespace App\Application\Command\AddBook;

final class AddBookCommand
{
    public function __construct(
        private string $serialNumber,
        private string $title,
        private string $author,
    ) {
    }

    public function getSerialNumber(): string
    {
        return $this->serialNumber;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }
}
