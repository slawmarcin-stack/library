<?php

declare(strict_types=1);

namespace App\Presentation\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateBookRequest
{
    #[Assert\NotBlank]
    public string $serialNumber;

    #[Assert\NotBlank]
    public string $title;

    #[Assert\NotBlank]
    public string $author;

    public function __construct(
        string $serial_number,
        string $title,
        string $author
    ) {
        $this->serialNumber = $serial_number;
        $this->title = $title;
        $this->author = $author;
    }
}
