<?php

declare(strict_types=1);

namespace App\Domain\Model;

class Book
{
    private BookId $id;

    private SerialNumber $serialNumber;

    private string $title;

    private string $author;

    private ?LibraryCard $borrowedBy = null;

    private ?\DateTimeImmutable $borrowedAt = null;

    public function __construct(BookId $id, SerialNumber $serialNumber, string $title, string $author)
    {
        $this->id = $id;
        $this->serialNumber = $serialNumber;
        $this->title = $title;
        $this->author = $author;
    }

    public function borrow(LibraryCard $card): void
    {
        if ($this->isBorrowed()) {
            throw new \DomainException('Book is already borrowed and cannot be borrowed again.');
        }

        $this->borrowedBy = $card;
        $this->borrowedAt = new \DateTimeImmutable();

    }

    public function return(): void
    {
        if (! $this->isBorrowed()) {
            throw new \DomainException('Book is not borrowed and cannot be returned.');
        }

        $this->borrowedBy = null;
        $this->borrowedAt = null;
    }

    public function isBorrowed(): bool
    {
        return $this->borrowedBy !== null;
    }
}
