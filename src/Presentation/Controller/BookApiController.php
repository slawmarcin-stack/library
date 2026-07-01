<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\Command\AddBook\AddBookCommand;
use App\Application\Dto\BookDto;
use App\Infrastructure\Persistence\Doctrine\Entity\Reader;
use App\Infrastructure\Persistence\Doctrine\Repository\BookRepository;
use App\Presentation\Request\CreateBookRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

        $bookDtos = array_map(static function (array $book): BookDto {
            $borrowedAt = null;
            if ($book['borrowed_at'] instanceof \DateTimeInterface) {
                $borrowedAt = $book['borrowed_at']->format(\DateTimeInterface::ATOM);
            } elseif (is_string($book['borrowed_at'])) {
                $borrowedAt = $book['borrowed_at'];
            }

            $readerName = null;
            if (! empty($book['reader_first_name']) || ! empty($book['reader_last_name'])) {
                $readerName = trim(sprintf('%s %s', $book['reader_first_name'] ?? '', $book['reader_last_name'] ?? ''));
            }

            return new BookDto(
                $book['id'] ?? null,
                $book['serial_number'],
                $book['title'],
                $book['author'],
                $borrowedAt !== null,
                $borrowedAt,
                $book['library_card_number'] ?? null,
                $book['reader_id'] ?? null,
                $readerName
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
                'error' => 'Niepoprawny JSON.',
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

        $this->commandBus->dispatch(new AddBookCommand(
            $createBookRequest->serialNumber,
            $createBookRequest->title,
            $createBookRequest->author,
        ));

        return $this->json([
            'status' => 'Książka dodana',
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
                'error' => 'Książka nie została znaleziona.',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($book);
        $this->em->flush();

        return $this->json([
            'status' => 'Książka usunięta',
        ]);
    }

    #[Route('/{id}/borrow', name: 'borrow', methods: ['PATCH'])]
    public function borrowBook(string $id, Request $request): JsonResponse
    {
        $book = $this->bookRepository->find($id);
        if (! $book) {
            return $this->json([
                'error' => 'Książka nie została znaleziona.',
            ], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true);

        $cardNumber = $payload['card_number'] ?? null;
        if (! $cardNumber) {
            return $this->json([
                'error' => 'Brak identyfikatora czytelnika.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $reader = $this->em->getRepository(Reader::class)->findByLibraryCardNumber($cardNumber);
        if (! $reader) {
            return $this->json([
                'error' => 'Czytelnik nie został znaleziony.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($book->isBorrowed()) {
            return $this->json([
                'error' => 'Książka jest już wypożyczona.',
            ], Response::HTTP_CONFLICT);
        }

        $loan = $book->borrow($reader);
        $this->em->persist($loan);

        $this->em->flush();
        return $this->json([
            'status' => 'Książka została wypożyczona.',
        ]);
    }

    #[Route('/{id}/return', name: 'return', methods: ['PATCH'])]
    public function returnBook(string $id, Request $request): JsonResponse
    {
        $book = $this->bookRepository->find($id);
        if (! $book) {
            return $this->json([
                'error' => 'Książka nie została znaleziona.',
            ], Response::HTTP_NOT_FOUND);
        }

        $book->returnCurrentLoan();

        $this->em->flush();
        return $this->json([
            'status' => 'Książka została zwrócona.',
        ]);
    }
}
