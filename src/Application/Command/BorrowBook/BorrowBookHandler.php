<?php

declare(strict_types=1);

namespace App\Application\Command\BorrowBook;

use App\Domain\Model\BookId;
use App\Domain\Model\LibraryCard;
use App\Domain\Repository\BookRepositoryInterface;

final class BorrowBookHandler
{
    public function __construct(
        private BookRepositoryInterface $repository
    ) {
    }

    public function __invoke(BorrowBookCommand $command): void
    {
        $bookId = BookId::fromString($command->getBookId());
        $book = $this->repository->find($bookId);

        if (! $book) {
            throw new BookNotFoundException();
        }

        $libraryCard = new LibraryCard($command->getLibraryCardNumber());
        $book->borrow($libraryCard);

        $this->repository->save($book);
    }
}
