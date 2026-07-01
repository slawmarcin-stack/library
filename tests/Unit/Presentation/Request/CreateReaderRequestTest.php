<?php

declare(strict_types=1);

namespace App\Tests\Unit\Presentation\Request;

use App\Presentation\Request\CreateReaderRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateReaderRequestTest extends TestCase
{
    public function testValidRequestHasNoValidationErrors(): void
    {
        $request = new CreateReaderRequest('Jan', 'Kowalski', 'jan.kowalski@example.com', '123456');

        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($request);

        self::assertCount(0, $violations);
    }

    public function testBlankFieldsProduceValidationErrors(): void
    {
        $request = new CreateReaderRequest('', '', '', '');

        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($request);

        self::assertGreaterThanOrEqual(4, $violations->count());
    }
}