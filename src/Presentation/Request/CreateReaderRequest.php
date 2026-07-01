<?php

declare(strict_types=1);

namespace App\Presentation\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateReaderRequest
{
    #[Assert\NotBlank]
    public string $firstName;

    #[Assert\NotBlank]
    public string $lastName;

    #[Assert\NotBlank]
    #[Assert\Email(message: 'Email address is not valid.')]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^\d{6}$/',
        message: 'Library card number must be exactly 6 digits.'
    )]
    public string $libraryCardNumber;

    public function __construct(
        string $first_name,
        string $last_name,
        string $email,
        string $library_card_number
    ) {
        $this->firstName = $first_name;
        $this->lastName = $last_name;
        $this->email = $email;
        $this->libraryCardNumber = $library_card_number;
    }
}
