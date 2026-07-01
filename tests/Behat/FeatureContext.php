<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Infrastructure\Persistence\Doctrine\Entity\Book;
use App\Infrastructure\Persistence\Doctrine\Entity\Reader;
use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class FeatureContext implements Context
{
    private Book $book;

    private Reader $reader;

    private ?\Throwable $caughtException = null;

    private ?int $validationErrors = null;

    private ValidatorInterface $validator;

    public function __construct()
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    #[Given('a fresh book and a reader')]
    public function aFreshBookAndAReader(): void
    {
        $this->book = new Book();
        $this->book->setSerialNumber('123456');
        $this->book->setTitle('DDD in PHP');
        $this->book->setAuthor('Author Name');

        $this->reader = new Reader('Jan', 'Kowalski', 'jan.kowalski@example.com', '654321');
        $this->caughtException = null;
        $this->validationErrors = null;
    }

    #[When('I borrow the book')]
    public function iBorrowTheBook(): void
    {
        $this->book->borrow($this->reader);
    }

    #[Then('the book should be marked as borrowed')]
    public function theBookShouldBeMarkedAsBorrowed(): void
    {
        if (! $this->book->isBorrowed()) {
            throw new \RuntimeException('Expected book to be borrowed.');
        }
    }

    #[When('I return the book')]
    public function iReturnTheBook(): void
    {
        $this->book->returnCurrentLoan();
    }

    #[Then('the book should not be borrowed anymore')]
    public function theBookShouldNotBeBorrowedAnymore(): void
    {
        if ($this->book->isBorrowed()) {
            throw new \RuntimeException('Expected book to be returned.');
        }
    }

    #[Given('the book is already borrowed')]
    public function theBookIsAlreadyBorrowed(): void
    {
        $this->book->borrow($this->reader);
    }

    #[When('I try to borrow the same book again')]
    public function iTryToBorrowTheSameBookAgain(): void
    {
        try {
            $this->book->borrow($this->reader);
            $this->caughtException = null;
        } catch (\Throwable $throwable) {
            $this->caughtException = $throwable;
        }
    }

    #[Then('borrowing should fail')]
    public function borrowingShouldFail(): void
    {
        if ($this->caughtException === null) {
            throw new \RuntimeException('Expected borrowing to fail.');
        }
    }

    #[Given('a reader with library card number :card')]
    public function aReaderWithLibraryCardNumber(string $card): void
    {
        $this->reader = new Reader('Jan', 'Kowalski', 'jan.kowalski@example.com', $card);
        $this->validationErrors = null;
    }

    #[When('I validate the reader')]
    public function iValidateTheReader(): void
    {
        $violations = $this->validator->validate($this->reader);
        $this->validationErrors = $violations->count();
    }

    #[Then('reader validation should fail')]
    public function readerValidationShouldFail(): void
    {
        if (($this->validationErrors ?? 0) === 0) {
            throw new \RuntimeException('Expected reader validation to fail for invalid card number.');
        }
    }
}
