<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Infrastructure\Persistence\Doctrine\Entity\Book;
use App\Infrastructure\Persistence\Doctrine\Entity\Reader;
use PHPUnit\Framework\TestCase;

final class BookLoanFlowTest extends TestCase
{
    public function testBorrowMarksBookAsBorrowed(): void
    {
        $book = $this->createBook();
        $reader = $this->createReader();

        $loan = $book->borrow($reader);

        self::assertTrue($book->isBorrowed());
        self::assertTrue($loan->isActive());
        self::assertSame($reader, $loan->getReader());
    }

    public function testBorrowingAlreadyBorrowedBookThrowsDomainException(): void
    {
        $book = $this->createBook();
        $reader = $this->createReader();

        $book->borrow($reader);

        $this->expectException(\DomainException::class);
        $book->borrow($reader);
    }

    public function testReturnCurrentLoanChangesBookState(): void
    {
        $book = $this->createBook();
        $reader = $this->createReader();
        $loan = $book->borrow($reader);

        $book->returnCurrentLoan();

        self::assertFalse($book->isBorrowed());
        self::assertFalse($loan->isActive());
    }

    public function testReturningBookWithoutActiveLoanThrowsDomainException(): void
    {
        $book = $this->createBook();

        $this->expectException(\DomainException::class);
        $book->returnCurrentLoan();
    }

    private function createBook(): Book
    {
        $book = new Book();
        $book->setSerialNumber('123456');
        $book->setTitle('Domain-Driven Design');
        $book->setAuthor('Eric Evans');

        return $book;
    }

    private function createReader(): Reader
    {
        return new Reader('Jan', 'Kowalski', 'jan.kowalski@example.com', '654321');
    }
}
