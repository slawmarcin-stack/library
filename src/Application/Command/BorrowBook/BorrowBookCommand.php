<?php

declare(strict_types=1);

namespace App\Application\Command\BorrowBook;

final class BorrowBookCommand
{
    public function __construct(
        private string $bookId,
        private string $libraryCardNumber,
    ) {
    }

    public function getBookId(): string
    {
        return $this->bookId;
    }

    public function getLibraryCardNumber(): string
    {
        return $this->libraryCardNumber;
    }
}
