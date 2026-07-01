<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Command\BorrowBook;

use App\Application\Command\BorrowBook\BookNotFoundException;
use App\Application\Command\BorrowBook\BorrowBookCommand;
use App\Application\Command\BorrowBook\BorrowBookHandler;
use App\Application\Command\BorrowBook\ReaderNotFoundException;
use App\Infrastructure\Persistence\Doctrine\Entity\Book;
use App\Infrastructure\Persistence\Doctrine\Entity\Reader;
use App\Infrastructure\Persistence\Doctrine\Repository\BookRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class BorrowBookHandlerTest extends TestCase
{
    public function testItBorrowsBookAndPersistsLoan(): void
    {
        $book = $this->createBook();
        $reader = $this->createReader('123456');

        $entityManagerForBookRepository = $this->createMock(EntityManagerInterface::class);
        $entityManagerForBookRepository->expects(self::once())
            ->method('getClassMetadata')
            ->willReturnCallback(static fn (string $className) => new \Doctrine\ORM\Mapping\ClassMetadata($className));

        $entityManagerForBookRepository->expects(self::once())
            ->method('find')
            ->with(Book::class, $book->getId(), null, null)
            ->willReturn($book);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects(self::once())
            ->method('getManagerForClass')
            ->with(Book::class)
            ->willReturn($entityManagerForBookRepository);

        $bookRepository = new BookRepository($managerRegistry);

        $readerRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();
        $readerRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['libraryCardNumber' => '123456'])
            ->willReturn($reader);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with(Reader::class)
            ->willReturn($readerRepository);

        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(\App\Infrastructure\Persistence\Doctrine\Entity\Loan::class));

        $entityManager->expects(self::once())
            ->method('flush');

        $handler = new BorrowBookHandler($bookRepository, $entityManager);
        $handler(new BorrowBookCommand($book->getId(), '123456'));

        self::assertTrue($book->isBorrowed());
    }

    public function testItThrowsWhenBookDoesNotExist(): void
    {
        $entityManagerForBookRepository = $this->createMock(EntityManagerInterface::class);
        $entityManagerForBookRepository->expects(self::once())
            ->method('getClassMetadata')
            ->willReturnCallback(static fn (string $className) => new \Doctrine\ORM\Mapping\ClassMetadata($className));

        $entityManagerForBookRepository->expects(self::once())
            ->method('find')
            ->with(Book::class, 'missing-book-id', null, null)
            ->willReturn(null);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects(self::once())
            ->method('getManagerForClass')
            ->with(Book::class)
            ->willReturn($entityManagerForBookRepository);

        $bookRepository = new BookRepository($managerRegistry);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');

        $handler = new BorrowBookHandler($bookRepository, $entityManager);

        $this->expectException(BookNotFoundException::class);
        $handler(new BorrowBookCommand('missing-book-id', '123456'));
    }

    public function testItThrowsWhenReaderDoesNotExist(): void
    {
        $book = $this->createBook();

        $entityManagerForBookRepository = $this->createMock(EntityManagerInterface::class);
        $entityManagerForBookRepository->expects(self::once())
            ->method('getClassMetadata')
            ->willReturnCallback(static fn (string $className) => new \Doctrine\ORM\Mapping\ClassMetadata($className));

        $entityManagerForBookRepository->expects(self::once())
            ->method('find')
            ->with(Book::class, $book->getId(), null, null)
            ->willReturn($book);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects(self::once())
            ->method('getManagerForClass')
            ->with(Book::class)
            ->willReturn($entityManagerForBookRepository);

        $bookRepository = new BookRepository($managerRegistry);

        $readerRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();
        $readerRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['libraryCardNumber' => '999999'])
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with(Reader::class)
            ->willReturn($readerRepository);

        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');

        $handler = new BorrowBookHandler($bookRepository, $entityManager);

        $this->expectException(ReaderNotFoundException::class);
        $handler(new BorrowBookCommand($book->getId(), '999999'));
    }

    public function testItThrowsDomainExceptionWhenBookAlreadyBorrowed(): void
    {
        $book = $this->createBook();
        $reader = $this->createReader('123456');
        $book->borrow($reader);

        $entityManagerForBookRepository = $this->createMock(EntityManagerInterface::class);
        $entityManagerForBookRepository->expects(self::once())
            ->method('getClassMetadata')
            ->willReturnCallback(static fn (string $className) => new \Doctrine\ORM\Mapping\ClassMetadata($className));

        $entityManagerForBookRepository->expects(self::once())
            ->method('find')
            ->with(Book::class, $book->getId(), null, null)
            ->willReturn($book);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects(self::once())
            ->method('getManagerForClass')
            ->with(Book::class)
            ->willReturn($entityManagerForBookRepository);

        $bookRepository = new BookRepository($managerRegistry);

        $readerRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();
        $readerRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['libraryCardNumber' => '123456'])
            ->willReturn($reader);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('getRepository')
            ->with(Reader::class)
            ->willReturn($readerRepository);

        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');

        $handler = new BorrowBookHandler($bookRepository, $entityManager);

        $this->expectException(\DomainException::class);
        $handler(new BorrowBookCommand($book->getId(), '123456'));
    }

    private function createBook(): Book
    {
        $book = new Book();
        $book->setSerialNumber('654321');
        $book->setTitle('Test title');
        $book->setAuthor('Test author');

        return $book;
    }

    private function createReader(string $libraryCardNumber): Reader
    {
        return new Reader('Jan', 'Kowalski', 'jan.kowalski@example.com', $libraryCardNumber);
    }
}
