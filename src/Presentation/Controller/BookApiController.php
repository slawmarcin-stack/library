<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Command\AddBook\AddBookCommand;
use App\Application\Command\BorrowBook\BookNotFoundException;
use App\Application\Command\BorrowBook\BorrowBookCommand;
use App\Application\Command\BorrowBook\ReaderNotFoundException;
use App\Application\Dto\BookDto;
use App\Infrastructure\Persistence\Doctrine\Repository\BookRepository;
use App\Presentation\Request\CreateBookRequest;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/books', name: 'api_books_')]
class BookApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private BookRepository $bookRepository,
        private ValidatorInterface $validator,
        private MessageBusInterface $commandBus
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $books = $this->bookRepository->findAllWithCurrentLoanStatus();

        $bookDtos = array_map(function (array $book): BookDto {
            $currentLoan = is_array($book['current_loan'] ?? null) ? $book['current_loan'] : null;

            return new BookDto(
                $book['id'] ?? null,
                $book['serial_number'],
                $book['title'],
                $book['author'],
                $this->resolveBorrowedAt($book, $currentLoan) !== null,
                $this->resolveBorrowedAt($book, $currentLoan),
                $this->resolveBorrowedBy($book, $currentLoan),
                $this->resolveReaderId($book, $currentLoan),
                $this->resolveReaderName($book, $currentLoan)
            );
        }, $books);

        return $this->json($bookDtos);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (! is_array($payload)) {
            return $this->json([
                'error' => 'Invalid JSON.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $createBookRequest = new CreateBookRequest(
            $payload['serial_number'] ?? '',
            $payload['title'] ?? '',
            $payload['author'] ?? ''
        );

        $errors = $this->validator->validate($createBookRequest);
        if (count($errors) > 0) {
            return $this->json([
                'errors' => (string) $errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->commandBus->dispatch(new AddBookCommand(
                $createBookRequest->serialNumber,
                $createBookRequest->title,
                $createBookRequest->author,
            ));
        } catch (HandlerFailedException $exception) {
            $previous = $exception->getPrevious();
            if ($previous instanceof UniqueConstraintViolationException) {
                return $this->json([
                    'error' => 'A book with the given serial number already exists.',
                ], Response::HTTP_CONFLICT);
            }

            throw $exception;
        } catch (UniqueConstraintViolationException) {
            return $this->json([
                'error' => 'A book with the given serial number already exists.',
            ], Response::HTTP_CONFLICT);
        }

        return $this->json([
            'status' => 'Book created',
            'book' => new BookDto(
                null,
                $createBookRequest->serialNumber,
                $createBookRequest->title,
                $createBookRequest->author,
                false,
                null,
                null,
                null,
                null
            ),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $book = $this->bookRepository->find($id);
        if (! $book) {
            return $this->json([
                'error' => 'Book not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($book);
        $this->em->flush();

        return $this->json([
            'status' => 'Book deleted',
        ]);
    }

    #[Route('/{id}/borrow', name: 'borrow', methods: ['PATCH'])]
    public function borrowBook(string $id, Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (! is_array($payload)) {
            return $this->json([
                'error' => 'Invalid JSON.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $cardNumber = $payload['card_number'] ?? null;
        if (! $cardNumber) {
            return $this->json([
                'error' => 'Missing reader identifier.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->commandBus->dispatch(new BorrowBookCommand($id, (string) $cardNumber));
        } catch (HandlerFailedException $exception) {
            $previous = $exception->getPrevious();

            if ($previous instanceof BookNotFoundException) {
                return $this->json([
                    'error' => 'Book not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            if ($previous instanceof ReaderNotFoundException) {
                return $this->json([
                    'error' => 'Reader not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            if ($previous instanceof \DomainException) {
                return $this->json([
                    'error' => 'The book is already borrowed.',
                ], Response::HTTP_CONFLICT);
            }

            throw $exception;
        }

        return $this->json([
            'status' => 'Book borrowed',
        ]);
    }

    #[Route('/{id}/return', name: 'return', methods: ['PATCH'])]
    public function returnBook(string $id, Request $request): JsonResponse
    {
        $book = $this->bookRepository->find($id);
        if (! $book) {
            return $this->json([
                'error' => 'Book not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $book->returnCurrentLoan();

        $this->em->flush();
        return $this->json([
            'status' => 'Book returned',
        ]);
    }

    private function resolveBorrowedAt(array $book, ?array $currentLoan): ?string
    {
        $borrowedAtSource = $book['borrowed_at'] ?? ($currentLoan['borrowed_at'] ?? null);

        if ($borrowedAtSource instanceof \DateTimeInterface) {
            return $borrowedAtSource->format(\DateTimeInterface::ATOM);
        }

        if (is_string($borrowedAtSource)) {
            return $borrowedAtSource;
        }

        return null;
    }

    private function resolveBorrowedBy(array $book, ?array $currentLoan): ?string
    {
        return $book['borrowed_by'] ?? $book['library_card_number'] ?? ($currentLoan['library_card_number'] ?? null);
    }

    private function resolveReaderId(array $book, ?array $currentLoan): ?string
    {
        return $book['reader_id'] ?? ($currentLoan['reader_id'] ?? null);
    }

    private function resolveReaderName(array $book, ?array $currentLoan): ?string
    {
        return $currentLoan['reader_name']
            ?? trim(sprintf('%s %s', $book['reader_first_name'] ?? '', $book['reader_last_name'] ?? ''));
    }
}
