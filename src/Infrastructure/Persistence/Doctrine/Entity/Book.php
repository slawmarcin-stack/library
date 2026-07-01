<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use App\Infrastructure\Persistence\Doctrine\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Table(name: 'books')]
class Book
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\Column(length: 6, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d{6}$/', message: 'Serial number must be exactly 6 digits.')]
    private ?string $serialNumber = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $author = null;

    #[ORM\OneToMany(mappedBy: 'book', targetEntity: Loan::class, cascade: ['persist', 'remove'])]
    private Collection $loans;

    public function __construct()
    {
        $this->id = (string) Uuid::v4();
        $this->loans = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(string $serialNumber): void
    {
        $this->serialNumber = $serialNumber;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    public function getLoans(): Collection
    {
        return $this->loans;
    }

    public function getActiveLoan(): ?Loan
    {
        foreach ($this->loans as $loan) {
            if ($loan->isActive()) {
                return $loan;
            }
        }

        return null;
    }

    public function borrow(Reader $reader): Loan
    {
        if ($this->isBorrowed()) {
            throw new \DomainException('Book is already borrowed and cannot be borrowed again.');
        }

        $loan = new Loan($this, $reader);
        $this->loans->add($loan);

        return $loan;
    }

    public function returnCurrentLoan(): void
    {
        $loan = $this->getActiveLoan();
        if ($loan === null) {
            throw new \DomainException('Book is not borrowed and cannot be returned.');
        }

        $loan->returnBook();
    }

    public function isBorrowed(): bool
    {
        return $this->getActiveLoan() !== null;
    }
}
