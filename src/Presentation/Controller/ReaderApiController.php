<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Infrastructure\Persistence\Doctrine\Entity\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/readers', name: 'api_readers_')]
class ReaderApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        $reader = new Reader(
            $payload['first_name'] ?? '',
            $payload['last_name'] ?? '',
            $payload['email'] ?? '',
            $payload['library_card_number'] ?? ''
        );

        $errors = $this->validator->validate($reader);
        if (count($errors) > 0) {
            return $this->json([
                'errors' => (string) $errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($reader);
        $this->em->flush();

        return $this->json([
            'status' => 'Czytelnik dodany',
            'id' => $reader->getId(),
            'library_card_number' => $reader->getLibraryCardNumber(),
        ], Response::HTTP_CREATED);
    }
}
