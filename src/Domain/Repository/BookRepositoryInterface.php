<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use Doctrine\DBAL\LockMode;

interface BookRepositoryInterface
{
    public function save(object $book): void;

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object;

    public function remove(object $book): void;
}
