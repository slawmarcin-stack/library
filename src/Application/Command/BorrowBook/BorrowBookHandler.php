<?php

declare(strict_types=1);

namespace App\Application\Command\BorrowBook;

use App\Infrastructure\Persistence\Doctrine\Entity\Reader;
use App\Infrastructure\Persistence\Doctrine\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: BorrowBookCommand::class)]
final class BorrowBookHandler
{
    public function __construct(
        private BookRepository $bookRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(BorrowBookCommand $command): void
    {
        $book = $this->bookRepository->find($command->getBookId())
            ?? throw new BookNotFoundException();

        $reader = $this->entityManager->getRepository(Reader::class)->findOneBy([
            'libraryCardNumber' => $command->getLibraryCardNumber(),
        ]) ?? throw new ReaderNotFoundException();

        if (! $reader instanceof Reader) {
            throw new BookNotFoundException();
        }

        $loan = $book->borrow($reader);
        $this->entityManager->persist($loan);
        $this->entityManager->flush();
    }
}
