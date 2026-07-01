<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Model\BookId;
use App\Domain\Repository\BookRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

final class BookRepository extends ServiceEntityRepository implements BookRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function save(object $book): void
    {
        $this->getEntityManager()->persist($book);
        $this->getEntityManager()->flush();
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object
    {
        if ($id instanceof BookId) {
            $id = $id->toString();
        }

        return parent::find($id, $lockMode, $lockVersion);
    }

    public function remove(object $book): void
    {
        $this->getEntityManager()->remove($book);
        $this->getEntityManager()->flush();
    }

    public function findAllWithCurrentLoanStatus(): array
    {
        $rows = $this->createQueryBuilder('b')
            ->select(
                'b.id',
                'b.title',
                'b.author',
                'b.serialNumber as serial_number',
                'l.borrowedAt as borrowed_at',
                'r.id as reader_id',
                'r.firstName as reader_first_name',
                'r.lastName as reader_last_name',
                'r.libraryCardNumber as library_card_number'
            )
            ->leftJoin('b.loans', 'l', 'WITH', 'l.returnedAt IS NULL')
            ->leftJoin('l.reader', 'r')
            ->getQuery()
            ->getResult();

        return array_map(function (array $row) {
            $isBorrowed = $row['borrowed_at'] !== null;

            return [
                'id' => $row['id'],
                'serial_number' => $row['serial_number'],
                'title' => $row['title'],
                'author' => $row['author'],
                'is_borrowed' => $isBorrowed,
                'borrowed_by' => $isBorrowed ? $row['library_card_number'] : null,
                'borrowed_at' => $row['borrowed_at']?->format(\DateTimeInterface::ATOM),
                'current_loan' => $isBorrowed ? [
                    'reader_id' => $row['reader_id'],
                    'reader_name' => trim(sprintf('%s %s', $row['reader_first_name'], $row['reader_last_name'])),
                    'library_card_number' => $row['library_card_number'],
                    'borrowed_at' => $row['borrowed_at']?->format(\DateTimeInterface::ATOM),
                ] : null,
            ];
        }, $rows);
    }
}
