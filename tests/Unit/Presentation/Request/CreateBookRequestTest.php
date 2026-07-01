<?php

declare(strict_types=1);

namespace App\Tests\Unit\Presentation\Request;

use App\Presentation\Request\CreateBookRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class CreateBookRequestTest extends TestCase
{
    public function testValidRequestHasNoValidationErrors(): void
    {
        $request = new CreateBookRequest('123456', 'Test title', 'Test author');

        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($request);

        self::assertCount(0, $violations);
    }

    public function testBlankFieldsProduceValidationErrors(): void
    {
        $request = new CreateBookRequest('', '', '');

        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($request);

        self::assertGreaterThanOrEqual(3, $violations->count());
    }
}
