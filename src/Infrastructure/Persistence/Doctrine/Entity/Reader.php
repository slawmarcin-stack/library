<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'readers')]
class Reader
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 100)]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 6, unique: true)]
    #[Assert\Regex(pattern: '/^\d{6}$/', message: 'Library card number must be exactly 6 digits.')]
    private ?string $libraryCardNumber = null;

    #[ORM\OneToMany(mappedBy: 'reader', targetEntity: Loan::class)]
    private Collection $loans;

    public function __construct(string $firstName, string $lastName, string $email, string $libraryCardNumber)
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Podany adres email jest nieprawidłowy.');
        }

        if (empty($firstName) || empty($lastName)) {
            throw new \InvalidArgumentException('Imię i nazwisko nie mogą być puste.');
        }

        $this->id = (string) Uuid::v4();
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->libraryCardNumber = $libraryCardNumber;
        $this->loans = new ArrayCollection();
    }

    // Gettery (brak setterów – dane zmieniamy przez metody biznesowe, jeśli zajdzie potrzeba)
    public function getId(): string
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFullName(): string
    {
        return sprintf('%s %s', $this->firstName, $this->lastName);
    }

    public function getLibraryCardNumber(): ?string
    {
        return $this->libraryCardNumber;
    }

    public function getLoans(): Collection
    {
        return $this->loans;
    }
}
