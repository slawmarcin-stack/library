<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'loans')]
class Loan
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Book::class, inversedBy: 'loans')]
    #[ORM\JoinColumn(name: 'book_id', referencedColumnName: 'id', nullable: false)]
    private Book $book;

    #[ORM\ManyToOne(targetEntity: Reader::class, inversedBy: 'loans')]
    #[ORM\JoinColumn(name: 'reader_id', referencedColumnName: 'id', nullable: false)]
    private Reader $reader;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $borrowedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $returnedAt = null;

    public function __construct(Book $book, Reader $reader)
    {
        $this->id = (string) Uuid::v4();
        $this->book = $book;
        $this->reader = $reader;
        $this->borrowedAt = new \DateTimeImmutable();
    }

    /**
     * Metoda biznesowa realizująca zwrot książki
     */
    public function returnBook(): void
    {
        if ($this->returnedAt !== null) {
            throw new \LogicException('This book has already been returned.');
        }

        $this->returnedAt = new \DateTimeImmutable();
    }

    // Gettery
    public function getId(): string
    {
        return $this->id;
    }

    public function getBook(): Book
    {
        return $this->book;
    }

    public function getReader(): Reader
    {
        return $this->reader;
    }

    public function getBorrowedAt(): \DateTimeImmutable
    {
        return $this->borrowedAt;
    }

    public function getReturnedAt(): ?\DateTimeImmutable
    {
        return $this->returnedAt;
    }

    public function isActive(): bool
    {
        return $this->returnedAt === null;
    }
}
