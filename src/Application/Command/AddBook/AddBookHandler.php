<?php

declare(strict_types=1);

namespace App\Application\Command\AddBook;

use App\Domain\Repository\BookRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\Book;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: AddBookCommand::class)]
final class AddBookHandler
{
    public function __construct(
        private BookRepositoryInterface $repository
    ) {
    }

    public function __invoke(AddBookCommand $command): void
    {
        $book = new Book();
        $book->setSerialNumber($command->getSerialNumber());
        $book->setTitle($command->getTitle());
        $book->setAuthor($command->getAuthor());

        $this->repository->save($book);
    }
}
