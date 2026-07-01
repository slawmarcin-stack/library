<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Command\AddBook;

use App\Application\Command\AddBook\AddBookCommand;
use App\Application\Command\AddBook\AddBookHandler;
use App\Domain\Repository\BookRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\Book;
use Doctrine\DBAL\LockMode;
use PHPUnit\Framework\TestCase;

final class AddBookHandlerTest extends TestCase
{
    public function testItCreatesAndSavesBookFromCommand(): void
    {
        $repository = new class() implements BookRepositoryInterface {
            public ?object $savedBook = null;

            public function save(object $book): void
            {
                $this->savedBook = $book;
            }

            public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
            {
                return null;
            }

            public function remove(object $book): void
            {
            }
        };

        $handler = new AddBookHandler($repository);
        $handler(new AddBookCommand('123456', 'Clean Architecture', 'Robert C. Martin'));

        self::assertInstanceOf(Book::class, $repository->savedBook);
        self::assertSame('123456', $repository->savedBook?->getSerialNumber());
        self::assertSame('Clean Architecture', $repository->savedBook?->getTitle());
        self::assertSame('Robert C. Martin', $repository->savedBook?->getAuthor());
    }
}
